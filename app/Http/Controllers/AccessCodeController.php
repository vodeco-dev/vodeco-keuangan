<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\AccessCode;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AccessCodeController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['nullable', 'exists:users,id'],
            'role' => ['required', 'string', 'in:' . implode(',', array_map(fn (Role $role) => $role->value, Role::cases()))],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        $role = Role::from($validated['role']);

        $user = null;
        if (! empty($validated['user_id'])) {
            $user = User::find($validated['user_id']);
        }

        $rawCode = strtoupper(Str::random(10));
        $publicId = (string) Str::uuid();

        $accessCode = AccessCode::create([
            'public_id' => $publicId,
            'user_id' => $user?->id,
            'role' => $role,
            'code_hash' => Hash::make($rawCode),
            'expires_at' => $validated['expires_at'] ?? null,
        ]);

        $displayCode = $publicId . ':' . $rawCode;

        return back()->with('access_code_generated', [
            'code' => $displayCode,
            'role' => $accessCode->role->label(),
            'user' => $user?->name,
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $user = $request->user();
        [$publicId, $rawCode] = array_pad(explode(':', $request->input('code'), 2), 2, null);

        if (! $publicId || ! $rawCode) {
            return back()->withErrors([
                'code' => 'Format kode akses tidak valid.',
            ]);
        }

        $accessCode = AccessCode::where('public_id', $publicId)->first();

        if (! $accessCode || ! Hash::check($rawCode, $accessCode->code_hash)) {
            return back()->withErrors([
                'code' => 'Kode akses tidak ditemukan atau sudah tidak berlaku.',
            ]);
        }

        if ($accessCode->used_at) {
            return back()->withErrors([
                'code' => 'Kode akses sudah digunakan.',
            ]);
        }

        if ($accessCode->isExpired()) {
            return back()->withErrors([
                'code' => 'Kode akses sudah kedaluwarsa.',
            ]);
        }

        if ($accessCode->role !== $user->role) {
            return back()->withErrors([
                'code' => 'Kode akses tidak sesuai dengan peran Anda.',
            ]);
        }

        if ($accessCode->user_id && $accessCode->user_id !== $user->id) {
            return back()->withErrors([
                'code' => 'Kode akses tidak terhubung dengan akun Anda.',
            ]);
        }

        $accessCode->forceFill([
            'used_at' => now(),
            'used_by' => $user->id,
        ])->save();

        $verifiedRoles = collect(session('verified_access_roles', []))
            ->push($user->role->value)
            ->unique()
            ->values()
            ->all();

        session(['verified_access_roles' => $verifiedRoles]);

        return redirect()
            ->route('invoices.index')
            ->with('access_code_status', 'Kode akses berhasil diverifikasi untuk ' . $user->role->label() . '.');
    }
}
