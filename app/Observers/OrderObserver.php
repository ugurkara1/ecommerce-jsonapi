<?php

namespace App\Observers;

use App\Models\Order;

class OrderObserver
{
    /**
     * Handle the Order "created" event.
     */
    public function created(Order $order)
    {
        $order->customer->assignSpendingSegments();
    }

    public function updated(Order $order)
    {
        $order->customer->assignSpendingSegments();
    }

    public function deleted(Order $order)
    {
        $order->customer->assignSpendingSegments();
    }

    /**
     * Handle the Order "restored" event.
     */
    public function restored(Order $order): void
    {
        //
    }

    /**
     * Handle the Order "force deleted" event.
     */
    public function forceDeleted(Order $order): void
    {
        //
    }
}
