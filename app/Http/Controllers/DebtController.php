<?php

namespace App\Http\Controllers;

use App\Models\Debt;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DebtController extends Controller
{
    public function index(Request $request)
    {
        $query = Debt::with('payments')->latest();

        // Filter berdasarkan Tipe
        if ($request->filled('type_filter')) {
            $query->where('type', $request->type_filter);
        }

        // Filter berdasarkan Status
        if ($request->filled('status_filter')) {
            $query->where('status', $request->status_filter);
        }

        // Pencarian
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('description', 'like', '%' . $request->search . '%')
                  ->orWhere('related_party', 'like', '%' . $request->search . '%');
            });
        }

        $debts = $query->get();

        // Kalkulasi untuk kartu ringkasan
        $totalPiutang = Debt::where('type', 'piutang')->sum('amount');
        $totalHutang = Debt::where('type', 'hutang')->sum('amount');
        $totalBelumLunas = $debts->where('status', 'belum lunas')->sum('remaining_amount');
        $totalLunas = Debt::where('status', 'lunas')->sum('amount');


        return view('debts.index', [
            'title' => 'Hutang & Piutang',
            'debts' => $debts,
            'totalPiutang' => $totalPiutang,
            'totalHutang' => $totalHutang,
            'totalBelumLunas' => $totalBelumLunas,
            'totalLunas' => $totalLunas,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'description' => 'required|string|max:255',
            'related_party' => 'required|string|max:255',
            'type' => 'required|in:hutang,piutang',
            'amount' => 'required|numeric|min:0',
            'due_date' => 'nullable|date',
        ]);

        Debt::create([
            'description' => $request->description,
            'related_party' => $request->related_party,
            'type' => $request->type,
            'amount' => $request->amount,
            'due_date' => $request->due_date,
            'status' => 'belum lunas',
        ]);

        return redirect()->route('debts.index')->with('success', 'Catatan berhasil ditambahkan.');
    }

    public function storePayment(Request $request, Debt $debt)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::transaction(function () use ($request, $debt) {
                $debt->payments()->create($request->all());

                // Cek jika sudah lunas
                if ($debt->paid_amount >= $debt->amount) {
                    $debt->update(['status' => 'lunas']);

                    // Buat transaksi baru
                    $categoryType = $debt->type == 'piutang' ? 'pemasukan' : 'pengeluaran';

                    // Cari kategori default atau buat jika tidak ada
                    $category = \App\Models\Category::firstOrCreate(
                        ['name' => 'Pelunasan ' . ucfirst($debt->type)],
                        ['type' => $categoryType]
                    );

                    Transaction::create([
                        'category_id' => $category->id,
                        'date' => now(),
                        'amount' => $debt->amount,
                        'description' => 'Pelunasan: ' . $debt->description,
                    ]);
                }
            });

            return back()->with('success', 'Pembayaran berhasil dicatat.');
        } catch (\Exception $e) {
            return back()->withErrors('Terjadi kesalahan saat menyimpan pembayaran.');
        }
    }

    public function destroy(Debt $debt)
    {
        $debt->delete();
        return redirect()->route('debts.index')->with('success', 'Catatan berhasil dihapus.');
    }
}