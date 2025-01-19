<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;  // Mengimpor JWTAuth
use Tymon\JWTAuth\Exceptions\JWTException;

class UserController extends Controller
{
    // Registrasi pengguna baru
    public function register(Request $request)
{
    // Validasi input
    $request->validate([
        'name' => 'required|string|max:255',
        'email' => 'required|string|email|max:255|unique:users',
        'address' => 'required|string|max:255',
        'phone' => 'required|string|max:15',
        'license_number' => 'required|string|max:20|unique:users',
        'password' => 'required|string|min:8|confirmed',
    ]);

    try {
        // Proses pembuatan pengguna baru
        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->address = $request->address;
        $user->phone = $request->phone;
        $user->license_number = $request->license_number;
        $user->password = Hash::make($request->password);
        $user->save();

        // Response sukses
        return response()->json(['message' => 'User registered successfully!'], 201);
    } catch (\Exception $e) {
        // Jika terjadi error saat menyimpan pengguna
        return response()->json([
            'error' => 'Registration failed',
            'message' => $e->getMessage()
        ], 500);
    }
}

    // Melihat daftar semua pengguna
    public function index()
    {
        $users = User::all();
        return response()->json($users, 200);
    }

    // Melihat detail pengguna berdasarkan ID
    public function show($id)
    {
        $user = User::findOrFail($id);
        return response()->json($user, 200);
    }

    // Login pengguna
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $credentials = $request->only('email', 'password');

        try {
            if (!$token = JWTAuth::attempt($credentials)) {
                return response()->json(['error' => 'Invalid credentials'], 401);
            }
        } catch (JWTException $e) {
            return response()->json(['error' => 'Could not create token'], 500);
        }

        return response()->json(compact('token'));
    }
}