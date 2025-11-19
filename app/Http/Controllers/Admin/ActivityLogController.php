<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
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
        
        $logs = ActivityLog::with('user')
            ->orderBy($sortBy, $sortOrder)
            ->paginate(50)
            ->appends($request->query());

        return view('admin.activity_logs.index', compact('logs'));
    }
}

