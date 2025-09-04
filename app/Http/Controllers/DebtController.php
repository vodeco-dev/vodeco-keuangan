<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDebtRequest;
use App\Http\Requests\StoreDebtPaymentRequest;
use App\Models\Debt;
use App\Models\Transaction;
use App\Services\DebtService;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB; // Diambil dari branch 'main'

class DebtController extends Controller
{
    protected DebtService $debtService;
    protected TransactionService $transactionService;

    /**
     * Terapkan authorization policy ke semua method resource controller.
     * Diambil dari branch 'codex/...'
     */
    public function __construct(DebtService $debtService, TransactionService $transactionService)
    {
        $this->debtService = $debtService;
        $this->transactionService = $transactionService;
        $this->authorizeResource(Debt::class, 'debt');
    }

    /**
     * Menampilkan daftar hutang & piutang milik pengguna yang sedang login.
     * Menggabungkan logika query dari 'codex/...'
     */
    public function index(Request $request)
    {
        $debts = $this->debtService->getDebts($request, $request->user());

        $summary = $this->debtService->getSummary($request->user());

        return view('debts.index', array_merge([
            'title' => 'Hutang & Piutang',
            'debts' => $debts,
        ], $summary));
    }

    /**
     * Menyimpan catatan hutang/piutang baru.
     */
    public function store(StoreDebtRequest $request)
    {
        $validated = $request->validated();

        Debt::create(array_merge($validated, [
            'status' => 'belum lunas',
            'user_id' => $request->user()->id, // Keamanan: Pastikan data baru memiliki pemilik
        ]));

        return redirect()->route('debts.index')->with('success', 'Catatan berhasil ditambahkan.');
    }

    /**
     * Menyimpan pembayaran/cicilan baru.
     * Menggabungkan authorize, DB::transaction, dan try-catch dari kedua branch.
     */
    public function storePayment(StoreDebtPaymentRequest $request, Debt $debt)
    {
        // Keamanan: Pastikan user boleh mengupdate data ini
        $this->authorize('update', $debt);

        $validated = $request->validated();

        try {
            // Keandalan: Pastikan semua operasi database berhasil atau tidak sama sekali
            DB::transaction(function () use ($validated, $request, $debt) {
                $debt->payments()->create([
                    'amount' => $validated['payment_amount'],
                    'payment_date' => $validated['payment_date'] ?? now(),
                    'notes' => $validated['notes'] ?? null,
                ]);

                // Reload relasi untuk mendapatkan paid_amount yang ter-update
                $debt->load('payments');

                // Cek jika sudah lunas
                if ($debt->paid_amount >= $debt->amount) {
                    $debt->update(['status' => 'lunas']);

                    $categoryType = $debt->type == Debt::TYPE_DOWN_PAYMENT ? 'pemasukan' : 'pengeluaran';
                    $category = \App\Models\Category::firstOrCreate(
                        ['name' => 'Pelunasan ' . ucwords(str_replace('_', ' ', $debt->type))],
                        ['type' => $categoryType]
                    );

                    Transaction::create([
                        'category_id' => $category->id,
                        'date' => now(),
                        'amount' => $debt->amount,
                        'description' => 'Pelunasan: ' . $debt->description,
                        'user_id' => $request->user()->id, // Keamanan: Pastikan transaksi memiliki pemilik
                    ]);

                    $this->transactionService->clearSummaryCacheForUser($request->user());
                }
            });

            return redirect()->route('debts.index')->with('success', 'Pembayaran berhasil dicatat.');
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
