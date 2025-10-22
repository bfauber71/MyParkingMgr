<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('username', $request->username)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            AuditLog::create([
                'user_id' => $user?->id,
                'username' => $request->username,
                'action' => 'failed_login',
                'entity_type' => 'user',
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        Auth::login($user);
        $request->session()->regenerate();

        AuditLog::log($user, 'login', 'user');

        return response()->json([
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'role' => $user->role,
                'assignedProperties' => $user->assignedProperties()->pluck('name'),
            ]
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        
        if ($user) {
            AuditLog::log($user, 'logout', 'user');
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'Logged out successfully']);
    }

    public function user(Request $request)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        return response()->json([
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'role' => $user->role,
                'assignedProperties' => $user->assignedProperties()->pluck('name'),
            ]
        ]);
    }
}
