<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\Rule;
use App\Enums\Role;
use App\Services\ActivityLogger;

class UserController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(User::class, 'user');
    }
    /**
     * Menampilkan daftar semua user.
     */
    public function index()
    {
        $users = User::latest()->paginate(10);
        return view('users.index', compact('users'));
    }

    /**
     * Menampilkan form untuk membuat user baru.
     */
    public function create()
    {
        return view('users.create');
    }

    /**
     * Menyimpan user baru ke database.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', Rule::enum(Role::class)],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);

        ActivityLogger::log($request->user(), 'create_user', 'Membuat user ' . $user->email);

        return redirect()->route('users.index')->with('success', 'User berhasil dibuat.');
    }

    /**
     * Memperbarui role user.
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'role' => ['required', Rule::enum(Role::class)],
        ]);

        $user->update([
            'role' => $request->role,
        ]);

        ActivityLogger::log($request->user(), 'update_user_role', 'Memperbarui role user ' . $user->email);

        return redirect()->route('users.index')->with('success', 'Role user berhasil diperbarui.');
    }
}
