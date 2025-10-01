@php
    $formatCurrency = fn ($value) => 'Rp' . number_format($value, 0, ',', '.');

    $summaryItems = [
        ['label' => 'Total Pemasukan', 'value' => $formatCurrency($totals['pemasukan'] ?? 0)],
        ['label' => 'Total Pengeluaran', 'value' => $formatCurrency($totals['pengeluaran'] ?? 0)],
        ['label' => 'Saldo Bersih', 'value' => $formatCurrency($totals['selisih'] ?? 0)],
        ['label' => 'Total Hutang', 'value' => $formatCurrency($totals['hutang'] ?? 0)],
        ['label' => 'Pembayaran Hutang', 'value' => $formatCurrency($totals['pembayaranHutang'] ?? 0)],
        ['label' => 'Sisa Hutang', 'value' => $formatCurrency($totals['sisaHutang'] ?? 0)],
    ];
@endphp

<table class="summary-table">
    <tbody>
        @foreach(array_chunk($summaryItems, 3) as $row)
            <tr>
                @foreach($row as $item)
                    <td>
                        <div class="summary-label">{{ $item['label'] }}</div>
                        <div class="summary-value">{{ $item['value'] }}</div>
                    </td>
                @endforeach
                @for($i = count($row); $i < 3; $i++)
                    <td class="summary-placeholder">&nbsp;</td>
                @endfor
            </tr>
        @endforeach
    </tbody>
</table>

<div class="table-section">
    <span class="section-title">Pemasukan &amp; Pengeluaran</span>
    <table class="dual-table">
        <tbody>
            <tr>
                <td>
                    <table>
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Kategori</th>
                                <th>Deskripsi</th>
                                <th class="text-right">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($incomeTransactions as $transaction)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($transaction->date)->isoFormat('D MMMM YYYY') }}</td>
                                    <td>{{ $transaction->category?->name }}</td>
                                    <td>{{ $transaction->description }}</td>
                                    <td class="text-right">{{ $formatCurrency($transaction->amount) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="empty-state">Tidak ada pemasukan pada periode ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3">Total Pemasukan</td>
                                <td class="text-right">{{ $formatCurrency($totals['pemasukan'] ?? 0) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </td>
                <td>
                    <table>
                        <thead>
                            <tr>
                                <th>Tanggal</th>
                                <th>Kategori</th>
                                <th>Deskripsi</th>
                                <th class="text-right">Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($expenseTransactions as $transaction)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($transaction->date)->isoFormat('D MMMM YYYY') }}</td>
                                    <td>{{ $transaction->category?->name }}</td>
                                    <td>{{ $transaction->description }}</td>
                                    <td class="text-right">{{ $formatCurrency($transaction->amount) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="empty-state">Tidak ada pengeluaran pada periode ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3">Total Pengeluaran</td>
                                <td class="text-right">{{ $formatCurrency($totals['pengeluaran'] ?? 0) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </td>
            </tr>
        </tbody>
    </table>
</div>

<div class="table-section">
    <span class="section-title">Laporan Hutang</span>
    <table>
        <thead>
            <tr>
                <th>Pihak Terkait</th>
                <th>Deskripsi</th>
                <th class="text-right">Jumlah</th>
                <th class="text-right">Terbayar</th>
                <th class="text-right">Sisa</th>
                <th>Jatuh Tempo</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($debts as $debt)
                <tr>
                    <td>{{ $debt->related_party }}</td>
                    <td>{{ $debt->description }}</td>
                    <td class="text-right">{{ $formatCurrency($debt->amount) }}</td>
                    <td class="text-right">{{ $formatCurrency($debt->paid_amount) }}</td>
                    <td class="text-right">{{ $formatCurrency($debt->remaining_amount) }}</td>
                    <td>{{ \Carbon\Carbon::parse($debt->due_date)->isoFormat('D MMMM YYYY') }}</td>
                    <td>
                        <span class="status-badge {{ $debt->status === 'lunas' ? 'status-paid' : 'status-unpaid' }}">
                            {{ ucfirst($debt->status) }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="empty-state">Tidak ada data hutang pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="2">Total</td>
                <td class="text-right">{{ $formatCurrency($totals['hutang'] ?? 0) }}</td>
                <td class="text-right">{{ $formatCurrency($totals['pembayaranHutang'] ?? 0) }}</td>
                <td class="text-right">{{ $formatCurrency($totals['sisaHutang'] ?? 0) }}</td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>
</div>
