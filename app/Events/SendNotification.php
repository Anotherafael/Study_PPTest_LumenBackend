<?php

namespace App\Events;

use Illuminate\Support\Facades\Event;
use App\Models\Transactions\Transaction;

class SendNotification extends Event
{
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Transaction $transaction)
    {
        $this->transaction = $transaction;
    }
}
