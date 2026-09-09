<?php

namespace App\Notifications;

use App\Models\BargainingRequest;
use App\Models\BargainingStoreOffer;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Fcm\FcmChannel;
use NotificationChannels\Fcm\FcmMessage;
use NotificationChannels\Fcm\Resources\Notification as FcmNotification;

class BargainingOfferAwardedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $bargainingRequest;
    protected $offer;

    /**
     * Create a new notification instance.
     *
     * @param  \App\Models\BargainingRequest  $bargainingRequest
     * @param  \App\Models\BargainingStoreOffer  $offer
     * @return void
     */
    public function __construct(BargainingRequest $bargainingRequest, BargainingStoreOffer $offer)
    {
        $this->bargainingRequest = $bargainingRequest;
        $this->offer = $offer;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        $channels = ['database'];

        // Add FCM channel if enabled for vendor
        if (config('bargaining.notifications.vendor.offer_awarded', true)) {
            $channels[] = FcmChannel::class;
        }

        return $channels;
    }

    /**
     * Get the FCM representation of the notification (for Firebase push).
     *
     * @param  mixed  $notifiable
     * @return \NotificationChannels\Fcm\FcmMessage
     */
    public function toFcm($notifiable)
    {
        $title = trans('messages.bargaining_offer_awarded');
        $body = trans('messages.your_offer_won', [
            'amount' => $this->offer->total_amount,
            'items' => $this->offer->items_available
        ]);

        return FcmMessage::create()
            ->setNotification(
                FcmNotification::create()
                    ->setTitle($title)
                    ->setBody($body)
                    ->setImage($this->offer->store->logo_full_url ?? null)
            )
            ->setData([
                'type' => 'bargaining_offer_awarded',
                'request_code' => $this->bargainingRequest->request_code,
                'offer_id' => $this->offer->id,
                'total_amount' => $this->offer->total_amount,
                'items_count' => $this->offer->items_available,
            ]);
    }

    /**
     * Get the array representation of the notification (for database).
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'type' => 'bargaining_offer_awarded',
            'title' => trans('messages.bargaining_offer_awarded'),
            'message' => trans('messages.your_offer_won', [
                'amount' => $this->offer->total_amount,
                'items' => $this->offer->items_available
            ]),
            'request_code' => $this->bargainingRequest->request_code,
            'offer_id' => $this->offer->id,
            'offer_type' => $this->offer->offer_type,
            'total_amount' => $this->offer->total_amount,
            'items_count' => $this->offer->items_available,
            'store_id' => $this->offer->store_id,
            'timestamp' => now()->toIso8601String(),
        ];
    }
}
