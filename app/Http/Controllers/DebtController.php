<?php

namespace App\Http\Controllers;

use App\Models\Debt;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // Diambil dari branch 'main'

class DebtController extends Controller
{
    /**
     * Terapkan authorization policy ke semua method resource controller.
     * Diambil dari branch 'codex/...'
     */
    public function __construct()
    {
        $this->authorizeResource(Debt::class, 'debt');
    }

    /**
     * Menampilkan daftar hutang & piutang milik pengguna yang sedang login.
     * Menggabungkan logika query dari 'codex/...'
     */
    public function index(Request $request)
    {
        $query = Debt::with('payments')
            ->where('user_id', $request->user()->id) // Keamanan: Filter data milik user
            ->latest();

        // Logika filter
        if ($request->filled('type_filter')) {
            $query->where('type', $request->type_filter);
        }
        if ($request->filled('status_filter')) {
            $query->where('status', $request->status_filter);
        }
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('description', 'like', '%' . $request->search . '%')
                  ->orWhere('related_party', 'like', '%' . $request->search . '%');
            });
        }

        $debts = $query->get();

        // Kalkulasi ringkasan yang aman untuk multi-user
        $totalPiutang = Debt::where('user_id', $request->user()->id)->where('type', 'piutang')->sum('amount');
        $totalHutang = Debt::where('user_id', $request->user()->id)->where('type', 'hutang')->sum('amount');
        $totalBelumLunas = $debts->where('status', 'belum lunas')->sum('remaining_amount');
        $totalLunas = Debt::where('user_id', $request->user()->id)->where('status', 'lunas')->sum('amount');

        return view('debts.index', [
            'title' => 'Hutang & Piutang',
            'debts' => $debts,
            'totalPiutang' => $totalPiutang,
            'totalHutang' => $totalHutang,
            'totalBelumLunas' => $totalBelumLunas,
            'totalLunas' => $totalLunas,
        ]);
    }

    /**
     * Menyimpan catatan hutang/piutang baru.
     */
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
            'user_id' => $request->user()->id, // Keamanan: Pastikan data baru memiliki pemilik
        ]);

        return redirect()->route('debts.index')->with('success', 'Catatan berhasil ditambahkan.');
    }

    /**
     * Menyimpan pembayaran/cicilan baru.
     * Menggabungkan authorize, DB::transaction, dan try-catch dari kedua branch.
     */
    public function storePayment(Request $request, Debt $debt)
    {
        // Keamanan: Pastikan user boleh mengupdate data ini
        $this->authorize('update', $debt);

        $request->validate([
            'amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'notes' => 'nullable|string',
        ]);

        try {
            // Keandalan: Pastikan semua operasi database berhasil atau tidak sama sekali
            DB::transaction(function () use ($request, $debt) {
                $debt->payments()->create($request->all());

                // Reload relasi untuk mendapatkan paid_amount yang ter-update
                $debt->load('payments');

                // Cek jika sudah lunas
                if ($debt->paid_amount >= $debt->amount) {
                    $debt->update(['status' => 'lunas']);

                    $categoryType = $debt->type == 'piutang' ? 'pemasukan' : 'pengeluaran';
                    $category = \App\Models\Category::firstOrCreate(
                        ['name' => 'Pelunasan ' . ucfirst($debt->type), 'user_id' => $request->user()->id],
                        ['type' => $categoryType]
                    );

                    Transaction::create([
                        'category_id' => $category->id,
                        'date' => now(),
                        'amount' => $debt->amount,
                        'description' => 'Pelunasan: ' . $debt->description,
                        'user_id' => $request->user()->id, // Keamanan: Pastikan transaksi memiliki pemilik
                    ]);
                }
            });

            return back()->with('success', 'Pembayaran berhasil dicatat.');
        } catch (\Exception $e) {
            // Jika terjadi error, tampilkan pesan kesalahan
            return back()->withErrors('Terjadi kesalahan saat menyimpan pembayaran.');
        }
    }

    /**
     * Menghapus catatan hutang/piutang.
     */
    public function destroy(Debt $debt)
    {
        // Keamanan: Otorisasi sudah ditangani oleh __construct()
        $debt->delete();
        return redirect()->route('debts.index')->with('success', 'Catatan berhasil dihapus.');
    }
}