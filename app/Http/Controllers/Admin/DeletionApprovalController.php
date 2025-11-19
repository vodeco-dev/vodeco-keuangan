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

    public function index(Request $request): View
    {
        // Sorting: default terbaru ke terlama berdasarkan created_at
        $sortBy = $request->input('sort_by', 'created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        
        // Validasi sort_by untuk keamanan
        $allowedSortColumns = ['created_at', 'updated_at'];
        if (!in_array($sortBy, $allowedSortColumns)) {
            $sortBy = 'created_at';
        }
        
        // Validasi sort_order
        $sortOrder = strtolower($sortOrder) === 'asc' ? 'asc' : 'desc';
        
        $requests = TransactionDeletionRequest::with(['transaction', 'requester'])
            ->where('status', 'pending')
            ->orderBy($sortBy, $sortOrder)
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
