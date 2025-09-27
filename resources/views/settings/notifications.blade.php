@extends('layouts.app')

@section('content')
    <h2 class="text-3xl font-bold text-gray-800 dark:text-white mb-8">Pengingat</h2>

    @if (session('success'))
        <div class="mb-4 p-4 bg-green-100 text-green-800 rounded">
            {{ session('success') }}
        </div>
    @endif

    <form action="{{ route('settings.notifications.update') }}" method="POST" class="space-y-6 max-w-md">
        @csrf
        <div>
            <label class="inline-flex items-center">
                <input type="checkbox" name="notify_transaction_approved" value="1" @checked(old('notify_transaction_approved', $notify_transaction_approved)) class="form-checkbox">
                <span class="ml-2">Transaksi disetujui</span>
            </label>
        </div>
        <div>
            <label class="inline-flex items-center">
                <input type="checkbox" name="notify_transaction_deleted" value="1" @checked(old('notify_transaction_deleted', $notify_transaction_deleted)) class="form-checkbox">
                <span class="ml-2">Transaksi dihapus</span>
            </label>
        </div>
        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded">Simpan</button>
    </form>
@endsection
