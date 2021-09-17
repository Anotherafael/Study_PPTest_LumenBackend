<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use App\Exceptions\EmptyWalletException;
use App\Exceptions\TransactionDeniedException;
use App\Exceptions\IdleServiceException;
use PHPUnit\Framework\InvalidDataProviderException;
use App\Repositories\Transactions\TransactionRepository;

class TransactionController extends Controller
{

    private $repository;

    public function __construct(TransactionRepository $repository)
    {
        $this->repository = $repository;
    }

    public function postTransaction(Request $request) 
    {
        $this->validate($request, [
            'provider' => 'required|in:users,retailers',
            'payee_id' => 'required',
            'amount' => 'required|numeric'
        ]);

        $fields = $request->only('provider', 'payee_id', 'amount');
        
        try {
            $result = $this->repository->handle($fields);
            return response()->json($result);
        } catch (InvalidDataProviderException | EmptyWalletException $exception) {
            return response()->json(['errors' => ['main' => $exception->getMessage()]], $exception->getCode());
        } catch (TransactionDeniedException | IdleServiceException $exception) {
            return response()->json(['errors' => ['main' => $exception->getMessage()]], 401);
        } catch (\Exception $exception) {
            Log::critical('[Transaction Gone So Wrong]', [
                'message' => $exception->getMessage()
            ]);
        }
        
    }
}
