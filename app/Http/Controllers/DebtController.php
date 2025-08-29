<?php

namespace App\Http\Controllers;

use App\Models\Debt;
use App\Models\Transaction;
use App\Services\DebtService;
use Illuminate\Http\Request;

class DebtController extends Controller
{
    public function __construct(private DebtService $debtService)
    {
    }

    public function index(Request $request)
    {
        $debts = $this->debtService->getDebts($request);
        $summary = $this->debtService->getSummary($debts);

        return view(
            'debts.index',
            array_merge(
                [
                    'title' => 'Hutang & Piutang',
                    'debts' => $debts,
                ],
                $summary
            )
        );
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

        return back()->with('success', 'Pembayaran berhasil dicatat.');
    }

    public function destroy(Debt $debt)
    {
        $debt->delete();
        return redirect()->route('debts.index')->with('success', 'Catatan berhasil dihapus.');
    }
}