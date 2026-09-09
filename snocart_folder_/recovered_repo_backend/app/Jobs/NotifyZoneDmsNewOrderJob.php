<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\DmZoneNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class NotifyZoneDmsNewOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $orderId;

    public function __construct(int $orderId)
    {
        $this->orderId = $orderId;
    }

    public function handle()
    {
        $order = Order::find($this->orderId);
        if (!$order || $order->delivery_man_id) return;

        $service = new DmZoneNotificationService();
        $service->notifyZoneDmsAboutNewOrder($order);
    }
}
