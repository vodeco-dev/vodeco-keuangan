<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TransactionDeletionRequest;
use App\Models\Setting;
use App\Notifications\TransactionApproved;
use App\Notifications\TransactionDeleted;
use App\Services\TransactionService;
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
            $deletionRequest->requester->notify(new TransactionApproved($transaction));
        }

        if (Setting::get('notify_transaction_deleted')) {
            $deletionRequest->requester->notify(new TransactionDeleted($transaction));
        }

        return redirect()->route('admin.deletion-requests.index')
            ->with('success', 'Permintaan penghapusan disetujui.');
    }

    public function reject(TransactionDeletionRequest $deletionRequest): RedirectResponse
    {
        $deletionRequest->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return redirect()->route('admin.deletion-requests.index')
            ->with('success', 'Permintaan penghapusan ditolak.');
    }
}
