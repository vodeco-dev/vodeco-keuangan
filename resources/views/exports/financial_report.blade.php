<table>
    <thead>
        <tr>
            <th>Tanggal</th>
            <th>Kategori</th>
            <th>Tipe</th>
            <th>Deskripsi</th>
            <th>Jumlah</th>
        </tr>
    </thead>
    <tbody>
    @foreach($transactions as $transaction)
        <tr>
            <td>{{ $transaction->date }}</td>
            <td>{{ $transaction->category->name }}</td>
            <td>{{ ucfirst($transaction->category->type) }}</td>
            <td>{{ $transaction->description }}</td>
            <td>{{ $transaction->amount }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<table>
    <thead>
        <tr>
            <th>Pihak Terkait</th>
            <th>Deskripsi</th>
            <th>Jumlah</th>
            <th>Terbayar</th>
            <th>Sisa</th>
            <th>Jatuh Tempo</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
    @foreach($debts as $debt)
        <tr>
            <td>{{ $debt->related_party }}</td>
            <td>{{ $debt->description }}</td>
            <td>{{ $debt->amount }}</td>
            <td>{{ $debt->paid_amount }}</td>
            <td>{{ $debt->remaining_amount }}</td>
            <td>{{ $debt->due_date }}</td>
            <td>{{ ucfirst($debt->status) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>
