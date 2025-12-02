@props(['invoice'])

<div class="items-section">
    <table class="items-table">
        <thead class="table-header">
            <tr>
                <th>Deskripsi Item</th>
                <th style="text-align: center;">Harga Satuan</th>
                <th style="text-align: center;">Jml</th>
                <th style="text-align: right;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
            <tr class="item-row">
                <td class="item-description">{{ $item->description }}</td>
                <td class="item-price">Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                <td class="item-quantity">{{ $item->quantity }}</td>
                <td class="item-total">Rp {{ number_format($item->price * $item->quantity, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>

