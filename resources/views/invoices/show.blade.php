@extends('layouts.app')

@section('content')
    <h1>Invoice Details</h1>
    <p>Invoice Number: {{ $invoice->number }}</p>
    <p>Client Name: {{ $invoice->client_name }}</p>
    <p>Client Email: {{ $invoice->client_email }}</p>
    <p>Client Address: {{ $invoice->client_address }}</p>
    <p>Issue Date: {{ $invoice->issue_date->format('Y-m-d') }}</p>
    <p>Due Date: {{ $invoice->due_date->format('Y-m-d') }}</p>
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
@endsection
