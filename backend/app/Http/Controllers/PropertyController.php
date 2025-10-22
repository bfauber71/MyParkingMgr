<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\PropertyContact;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PropertyController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->isAdmin() || $user->isOperator()) {
            $properties = Property::with('contacts')->get();
        } else {
            $properties = $user->assignedProperties()->with('contacts')->get();
        }

        return response()->json($properties);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:properties,name',
            'address' => 'nullable|string',
            'contacts' => 'nullable|array|max:3',
            'contacts.*.name' => 'required|string|max:255',
            'contacts.*.phone' => 'nullable|string|max:50',
            'contacts.*.email' => 'nullable|email|max:255',
        ]);

        DB::beginTransaction();
        try {
            $property = Property::create([
                'name' => $validated['name'],
                'address' => $validated['address'] ?? null,
            ]);

            if (isset($validated['contacts'])) {
                foreach ($validated['contacts'] as $index => $contact) {
                    PropertyContact::create([
                        'property_id' => $property->id,
                        'name' => $contact['name'],
                        'phone' => $contact['phone'] ?? null,
                        'email' => $contact['email'] ?? null,
                        'position' => $index,
                    ]);
                }
            }

            DB::commit();

            AuditLog::log($request->user(), 'create', 'property', $property->id, $validated);

            return response()->json($property->load('contacts'), 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to create property'], 500);
        }
    }

    public function update(Request $request, string $id)
    {
        $property = Property::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255|unique:properties,name,' . $id,
            'address' => 'nullable|string',
            'contacts' => 'nullable|array|max:3',
            'contacts.*.name' => 'required|string|max:255',
            'contacts.*.phone' => 'nullable|string|max:50',
            'contacts.*.email' => 'nullable|email|max:255',
        ]);

        DB::beginTransaction();
        try {
            $oldData = $property->toArray();
            
            $property->update([
                'name' => $validated['name'] ?? $property->name,
                'address' => $validated['address'] ?? $property->address,
            ]);

            if (isset($validated['contacts'])) {
                $property->contacts()->delete();
                foreach ($validated['contacts'] as $index => $contact) {
                    PropertyContact::create([
                        'property_id' => $property->id,
                        'name' => $contact['name'],
                        'phone' => $contact['phone'] ?? null,
                        'email' => $contact['email'] ?? null,
                        'position' => $index,
                    ]);
                }
            }

            DB::commit();

            AuditLog::log($request->user(), 'update', 'property', $property->id, [
                'old' => $oldData,
                'new' => $validated
            ]);

            return response()->json($property->load('contacts'));
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Failed to update property'], 500);
        }
    }

    public function destroy(Request $request, string $id)
    {
        $property = Property::findOrFail($id);

        $vehicleCount = $property->vehicles()->count();
        if ($vehicleCount > 0) {
            return response()->json([
                'error' => 'Cannot delete property with vehicles',
                'message' => "This property has {$vehicleCount} vehicles. Delete them first."
            ], 400);
        }

        $propertyData = $property->toArray();
        $property->delete();

        AuditLog::log($request->user(), 'delete', 'property', $id, $propertyData);

        return response()->json(['message' => 'Property deleted successfully']);
    }
}
