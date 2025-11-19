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
        
        $deletionRequests = TransactionDeletionRequest::with('transaction')
            ->where('requested_by', $request->user()->id)
            ->orderBy($sortBy, $sortOrder)
            ->paginate(15)
            ->appends($request->query());

        return view('user_deletion_requests.index', [
            'requests' => $deletionRequests,
        ]);
    }
}
