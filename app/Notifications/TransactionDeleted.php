<?php

namespace App\Notifications;

use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TransactionDeleted extends Notification
{
    use Queueable;

    public function __construct(private Transaction $transaction)
    {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Transaksi Dihapus')
            ->line('Transaksi "' . $this->transaction->description . '" telah dihapus.');
    }
}
