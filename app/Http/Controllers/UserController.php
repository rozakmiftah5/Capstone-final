<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $users = User::query()
            ->orderByRaw("CASE WHEN role = 'Admin' THEN 0 ELSE 1 END")
            ->orderBy('username')
            ->get();

        return view('users.index', compact('users'));
    }

    public function create()
    {
        // Gunakan Auth::user()
        if (Auth::user()->role !== 'Admin') {
            return redirect()->route('users.index')->with('error', 'Akses ditolak. Hanya Admin yang dapat menambah user.');
        }

        return view('users.create');
    }

    public function store(Request $request)
    {
        if (Auth::user()->role !== 'Admin') {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            'username' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role' => 'required|in:Admin,User,Warehouse Staff',
        ]);

        User::create([
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
        ]);

        return redirect()->route('users.index')->with('success', 'User berhasil ditambahkan.');
    }

    public function edit(int $id)
    {
        if (Auth::user()->role !== 'Admin') {
            return redirect()->route('users.index')->with('error', 'Akses ditolak. Hanya Admin yang dapat mengedit user.');
        }

        $user = User::findOrFail($id);

        return view('users.edit', compact('user'));
    }

    public function update(Request $request, int $id)
    {
        if (Auth::user()->role !== 'Admin') {
            abort(403, 'Unauthorized action.');
        }
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'username' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$id.',user_id',
            'password' => 'nullable|min:6',
            'role' => 'required|in:Admin,User,Warehouse Staff',
        ]);

        $data = [
            'username' => $validated['username'],
            'email' => $validated['email'],
            'role' => $validated['role'],
        ];

        if (! empty($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        $user->update($data);

        return redirect()->route('users.index')->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(int $id)
    {
        if (Auth::user()->role !== 'Admin') {
            return redirect()->route('users.index')->with('error', 'Akses ditolak. Hanya Admin yang dapat menghapus user.');
        }

        $user = User::findOrFail($id);
        $user->delete();

        return redirect()->route('users.index')->with('success', 'User berhasil dihapus.');
    }
}
