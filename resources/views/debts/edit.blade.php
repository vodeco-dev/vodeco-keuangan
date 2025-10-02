@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4">
    <h2 class="text-2xl font-bold mb-4">Edit Catatan Hutang</h2>

    <div class="bg-white rounded-lg shadow-sm p-6">
        <form action="{{ route('debts.update', $debt) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="mb-4">
                <label for="description" class="block text-sm font-medium text-gray-700">Deskripsi</label>
                <input type="text" name="description" id="description" value="{{ old('description', $debt->description) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
            </div>

            <div class="mb-4">
                <label for="related_party" class="block text-sm font-medium text-gray-700">Pihak Terkait</label>
                <input type="text" name="related_party" id="related_party" value="{{ old('related_party', $debt->related_party) }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" required>
            </div>

            <div class="mb-4">
                <label for="due_date" class="block text-sm font-medium text-gray-700">Jatuh Tempo</label>
                <input type="date" name="due_date" id="due_date" value="{{ old('due_date', $debt->due_date ? \Carbon\Carbon::parse($debt->due_date)->format('Y-m-d') : '') }}" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
            </div>

            <div class="flex justify-end">
                <a href="{{ route('debts.index') }}" class="px-4 py-2 bg-gray-200 rounded-lg mr-2">Batal</a>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>
@endsection
