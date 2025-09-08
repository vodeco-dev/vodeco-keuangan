<?php

namespace App\Notifications;

use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TransactionApproved extends Notification
{
    use Queueable;

    public function __construct(private Transaction $transaction)
    {
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Permintaan Penghapusan Transaksi Disetujui')
            ->line('Kabar baik! Permintaan Anda untuk menghapus transaksi berikut telah disetujui dan transaksi telah dihapus dari sistem.')
            ->line('Deskripsi: ' . $this->transaction->description)
            ->line('Jumlah: Rp' . number_format($this->transaction->amount, 2, ',', '.'))
            ->line('Tanggal: ' . $this->transaction->date->format('d F Y'))
            ->action('Lihat Transaksi Anda', route('transactions.index'));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'transaction_id' => $this->transaction->id,
            'transaction_description' => $this->transaction->description,
            'message' => 'Permintaan penghapusan transaksi Anda telah disetujui.',
        ];
    }
}
