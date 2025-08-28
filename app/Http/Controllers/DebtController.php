<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DebtController extends Controller
{
    public function index() {
        return view('debts.index');
    }
}
