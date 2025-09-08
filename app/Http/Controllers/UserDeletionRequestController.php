<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Models\TransactionDeletionRequest;

class UserDeletionRequestController extends Controller
{
    /**
     * Display a listing of the user's deletion requests.
     */
    public function index(Request $request): View
    {
        $deletionRequests = TransactionDeletionRequest::with('transaction')
            ->where('requested_by', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('user_deletion_requests.index', [
            'requests' => $deletionRequests,
        ]);
    }
}
