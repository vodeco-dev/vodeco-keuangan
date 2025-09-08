<?php

namespace App\Notifications;

use App\Models\TransactionDeletionRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TransactionRejected extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public TransactionDeletionRequest $deletionRequest)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $transaction = $this->deletionRequest->transaction;
        $reason = $this->deletionRequest->reason;

        return (new MailMessage)
                    ->subject('Permintaan Penghapusan Transaksi Ditolak')
                    ->line('Permintaan Anda untuk menghapus transaksi berikut telah ditolak:')
                    ->line('Deskripsi: ' . $transaction->description)
                    ->line('Jumlah: Rp' . number_format($transaction->amount, 2, ',', '.'))
                    ->line('Tanggal: ' . $transaction->date->format('d F Y'))
                    ->line('Alasan Penolakan: ' . $reason)
                    ->action('Lihat Transaksi', route('transactions.index'))
                    ->line('Silakan hubungi admin jika Anda memiliki pertanyaan.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'transaction_id' => $this->deletionRequest->transaction->id,
            'transaction_description' => $this->deletionRequest->transaction->description,
            'message' => 'Permintaan penghapusan transaksi Anda ditolak.',
            'reason' => $this->deletionRequest->reason,
        ];
    }
}
