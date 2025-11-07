@extends('layouts.app')

@section('content')
    @if (session('status'))
        <div class="mb-4 rounded border border-green-300 bg-green-50 p-4 text-green-800">
            {{ session('status') }}
        </div>
    @endif

    <h1>Invoice Details</h1>
    <p>Invoice Number: {{ $invoice->number }}</p>
    <p>Client Name: {{ $invoice->client_name }}</p>
    <p>Nomor WhatsApp Klien: {{ $invoice->client_whatsapp }}</p>
    <p>Client Address: {{ $invoice->client_address }}</p>
    <p>Customer Service: {{ $invoice->customer_service_name ?? $invoice->customerService?->name ?? '-' }}</p>
    <p>Issue Date: {{ $invoice->issue_date?->format('Y-m-d') ?? '-' }}</p>
    <p>Due Date: {{ $invoice->due_date?->format('Y-m-d') ?? '-' }}</p>
    <p>Total: {{ $invoice->total }}</p>
    <h2>Items</h2>
    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th>Quantity</th>
                <th>Price</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ $item->price }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <section class="mt-6">
        <h2>Pelunasan Publik</h2>
        <p class="text-sm text-gray-600">Berikan tautan di bawah ini kepada pihak eksternal untuk mengonfirmasi pelunasan tanpa perlu login.</p>

        @if ($invoice->settlement_token)
            <div class="mt-3 rounded border border-gray-300 p-4">
                <p><strong>Token:</strong> <code>{{ $invoice->settlement_token }}</code></p>
                <p><strong>Tautan Konfirmasi:</strong>
                    <a href="{{ route('invoices.settlement.show', ['token' => $invoice->settlement_token]) }}" target="_blank" rel="noopener">
                        {{ route('invoices.settlement.show', ['token' => $invoice->settlement_token]) }}
                    </a>
                </p>
                <p><strong>Berlaku hingga:</strong>
                    {{ optional($invoice->settlement_token_expires_at)->format('d F Y H:i') ?? 'Tidak ditentukan' }}
                </p>
            </div>

            <form method="POST" action="{{ route('invoices.settlement-token.refresh', $invoice) }}" class="mt-3">
                @csrf
                <label for="expires_at" class="block text-sm font-medium">Perpanjang / putar token hingga</label>
                <input type="datetime-local" name="expires_at" id="expires_at" class="mt-1 w-full max-w-sm"
                    value="{{ optional($invoice->settlement_token_expires_at)->format('Y-m-d\TH:i') }}">
                @error('expires_at')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <button type="submit" class="mt-2 inline-flex items-center rounded bg-indigo-600 px-4 py-2 text-white">
                    Putar Token
                </button>
            </form>

            <form method="POST" action="{{ route('invoices.settlement-token.revoke', $invoice) }}" class="mt-3">
                @csrf
                @method('DELETE')
                <button type="submit" class="inline-flex items-center rounded bg-red-600 px-4 py-2 text-white">
                    Cabut Token
                </button>
            </form>
        @else
            <div class="mt-3 rounded border border-yellow-300 bg-yellow-50 p-4 text-yellow-800">
                Token pelunasan belum dibuat. Klik tombol di bawah untuk menghasilkan token baru.
            </div>

            <form method="POST" action="{{ route('invoices.settlement-token.refresh', $invoice) }}" class="mt-3">
                @csrf
                <button type="submit" class="inline-flex items-center rounded bg-indigo-600 px-4 py-2 text-white">
                    Buat Token Pelunasan
                </button>
            </form>
        @endif
    </section>
@endsection
