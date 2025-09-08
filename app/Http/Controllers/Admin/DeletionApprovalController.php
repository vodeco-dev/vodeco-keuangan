<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TransactionDeletionRequest;
use App\Models\Setting;
use App\Notifications\TransactionApproved;
use App\Notifications\TransactionDeleted;
use App\Notifications\TransactionRejected;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DeletionApprovalController extends Controller
{
    public function __construct(private TransactionService $transactionService)
    {
    }

    public function index(): View
    {
        $requests = TransactionDeletionRequest::with(['transaction', 'requester'])
            ->where('status', 'pending')
            ->get();

        return view('admin.deletion_requests.index', compact('requests'));
    }

    public function approve(TransactionDeletionRequest $deletionRequest): RedirectResponse
    {
        $transaction = $deletionRequest->transaction;
        $transaction->delete();
        $this->transactionService->clearSummaryCacheForUser($transaction->user);

        $deletionRequest->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        if (Setting::get('notify_transaction_approved')) {
            $deletionRequest->requester->notify(new TransactionApproved($deletionRequest->transaction));
        }

        return redirect()->route('admin.deletion-requests.index')
            ->with('success', 'Permintaan penghapusan disetujui.');
    }

    public function reject(Request $request, TransactionDeletionRequest $deletionRequest): RedirectResponse
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $deletionRequest->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'reason' => $request->input('reason'),
        ]);

        // Kirim notifikasi ke requester
        $deletionRequest->requester->notify(new TransactionRejected($deletionRequest));

        return redirect()->route('admin.deletion-requests.index')
            ->with('success', 'Permintaan penghapusan ditolak.');
    }
}
