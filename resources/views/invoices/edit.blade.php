@extends('layouts.app')

@section('content')
    <h1>Edit Invoice</h1>
    <form action="{{ route('invoices.update', $invoice) }}" method="POST">
        @csrf
        @method('PUT')
        <div>
            <label for="customer_service_id">Customer Service</label>
            <select name="customer_service_id" id="customer_service_id">
                <option value="">Pilih customer service</option>
                @foreach($customerServices as $customerService)
                    <option value="{{ $customerService->id }}" @selected(old('customer_service_id', $invoice->customer_service_id) == $customerService->id)>
                        {{ $customerService->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="client_name">Client Name</label>
            <input type="text" name="client_name" id="client_name" value="{{ $invoice->client_name }}">
        </div>
        <div>
            <label for="client_email">Client Email</label>
            <input type="email" name="client_email" id="client_email" value="{{ $invoice->client_email }}">
        </div>
        <div>
            <label for="client_address">Client Address</label>
            <textarea name="client_address" id="client_address">{{ $invoice->client_address }}</textarea>
        </div>
        <div>
            <label for="issue_date">Issue Date</label>
            <input type="date" name="issue_date" id="issue_date" value="{{ $invoice->issue_date->format('Y-m-d') }}">
        </div>
        <div>
            <label for="due_date">Due Date</label>
            <input type="date" name="due_date" id="due_date" value="{{ $invoice->due_date->format('Y-m-d') }}">
        </div>
        <h2>Items</h2>
        <div id="items">
            @foreach($invoice->items as $item)
                <div>
                    <input type="text" name="items[{{ $loop->index }}][description]" value="{{ $item->description }}">
                    <input type="number" name="items[{{ $loop->index }}][quantity]" value="{{ $item->quantity }}">
                    <input type="number" name="items[{{ $loop->index }}][price]" value="{{ $item->price }}">
                </div>
            @endforeach
        </div>
        <button type="submit">Update</button>
    </form>
@endsection
