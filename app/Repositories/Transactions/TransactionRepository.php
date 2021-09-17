<?php

namespace App\Repositories\Transactions;

use App\Models\User;
use Ramsey\Uuid\Uuid;
use App\Models\Retailer;
use App\Services\MockyService;
use App\Events\SendNotification;
use Illuminate\Support\Facades\DB;
use App\Models\Transactions\Wallet;
use Illuminate\Support\Facades\Auth;
use App\Exceptions\EmptyWalletException;
use App\Exceptions\IdleServiceException;
use App\Models\Transactions\Transaction;
use App\Exceptions\TransactionDeniedException;
use PHPUnit\Framework\InvalidDataProviderException;

class TransactionRepository
{
    
    public function handle(array $data) : Transaction
    {   
        if (!$this->guardCanTransfer()) {
            throw new TransactionDeniedException('Retailer is not authorized to make transactions', 401);
        }

        if (!$payee = $this->retrievePayee($data)) {
            throw new InvalidDataProviderException('User Not Found', 404);
        }
        
        $myWallet = Auth::guard($data['provider'])->user()->wallet;

        if (!$this->checkUserBalance($myWallet, $data['amount'])) {
            throw new EmptyWalletException('You dont have this amount to transfer.', 422);
        }

        if (!$this->isServiceAbleToMakeTransaction()) {
            throw new IdleServiceException('Service is not responding. Try again later.');
        }

        return $this->makeTransaction($payee, $data);
    }

    public function guardCanTransfer(): bool
    {
        if (Auth::guard('users')->check()) {
            return true;
        } else if (Auth::guard('retailers')->check()) {
            return false;
        } else {
            throw new InvalidDataProviderException('Provider Not found', 422);
        }
    }

    public function getProvider(string $provider)
    {
        if ($provider == "users") {
            return new User();
        } else if ($provider == "retailers") {
            return new Retailer();
        } else {
            throw new InvalidDataProviderException('Provider Not Found');
        }
    }

    private function checkUserBalance(Wallet $wallet, $money)
    {
        return $wallet->balance >= $money;
    }

    /**
     * Function to know if the user exists on provider
     * both functions should trigger an exception
     * when something is wrong
     *
     * @param array $data
     */
    private function retrievePayee(array $data)
    {
        try {
            $model = $this->getProvider($data['provider']);
            return $model->find($data['payee_id']);
        } catch (InvalidDataProviderException | \Exception $e) {
            return false;
        }

    }

    private function isServiceAbleToMakeTransaction(): bool
    {
        $service = app(MockyService::class)->authorizeTransaction();
        return $service['message'] == 'Autorizado';
    }

    private function makeTransaction($payee, array $data)
    {
        $payload = [
            'id' => Uuid::uuid4()->toString(),
            'payer_wallet_id' => Auth::guard($data['provider'])->user()->wallet->id,
            'payee_wallet_id' => $payee->wallet->id,
            'amount' => $data['amount']
        ];

        return DB::transaction(function () use ($payload) {
            $transaction = Transaction::create($payload);

            $transaction->walletPayer->withdraw($payload['amount']);
            $transaction->walletPayee->deposit($payload['amount']);

            event(new SendNotification($transaction));
            
            return $transaction;
        });
    }
    
}