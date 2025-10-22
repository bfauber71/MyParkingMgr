<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Property;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index()
    {
        $users = User::with('assignedProperties')->get();
        
        return response()->json($users->map(function($user) {
            return [
                'id' => $user->id,
                'username' => $user->username,
                'role' => $user->role,
                'assignedProperties' => $user->assignedProperties->pluck('name'),
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ];
        }));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'username' => 'required|string|max:255|unique:users,username',
            'password' => 'required|string|min:8',
            'role' => 'required|in:admin,user,operator',
            'assignedProperties' => 'nullable|array',
            'assignedProperties.*' => 'exists:properties,name',
        ]);

        if ($validated['role'] === 'user' && empty($validated['assignedProperties'])) {
            return response()->json([
                'error' => 'Validation failed',
                'message' => 'Users must be assigned to at least one property'
            ], 422);
        }

        DB::beginTransaction();
        try {
            $user = User::create([
                'username' => $validated['username'],
                'password' => Hash::make($validated['password']),
                'role' => $validated['role'],
            ]);

            if (!empty($validated['assignedProperties'])) {
                $propertyIds = Property::whereIn('name', $validated['assignedProperties'])->pluck('id');
                $user->assignedProperties()->attach($propertyIds);
            }

            DB::commit();

            AuditLog::log($request->user(), 'create', 'user', $user->id, [
                'username' => $validated['username'],
                'role' => $validated['role'],
                'assignedProperties' => $validated['assignedProperties'] ?? []
            ]);

            return response()->json([
                'id' => $user->id,
                'username' => $user->username,
                'role' => $user->role,
                'assignedProperties' => $user->assignedProperties->pluck('name'),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to create user'], 500);
        }
    }

    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'password' => 'nullable|string|min:8',
            'role' => 'sometimes|required|in:admin,user,operator',
            'assignedProperties' => 'nullable|array',
            'assignedProperties.*' => 'exists:properties,name',
        ]);

        if (isset($validated['role']) && $validated['role'] === 'user' && empty($validated['assignedProperties'])) {
            return response()->json([
                'error' => 'Validation failed',
                'message' => 'Users must be assigned to at least one property'
            ], 422);
        }

        DB::beginTransaction();
        try {
            if (isset($validated['password'])) {
                $user->password = Hash::make($validated['password']);
            }

            if (isset($validated['role'])) {
                $user->role = $validated['role'];
            }

            $user->save();

            if (isset($validated['assignedProperties'])) {
                $propertyIds = Property::whereIn('name', $validated['assignedProperties'])->pluck('id');
                $user->assignedProperties()->sync($propertyIds);
            }

            DB::commit();

            AuditLog::log($request->user(), 'update', 'user', $user->id, $validated);

            return response()->json([
                'id' => $user->id,
                'username' => $user->username,
                'role' => $user->role,
                'assignedProperties' => $user->assignedProperties->pluck('name'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to update user'], 500);
        }
    }

    public function destroy(Request $request, string $id)
    {
        $currentUser = $request->user();
        $userToDelete = User::findOrFail($id);

        if ($currentUser->id === $userToDelete->id) {
            return response()->json(['error' => 'Cannot delete yourself'], 400);
        }

        $userData = $userToDelete->toArray();
        $userToDelete->delete();

        AuditLog::log($currentUser, 'delete', 'user', $id, $userData);

        return response()->json(['message' => 'User deleted successfully']);
    }
}
