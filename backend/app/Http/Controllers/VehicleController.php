<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class VehicleController extends Controller
{
    public function search(Request $request)
    {
        $user = $request->user();
        $query = Vehicle::query();

        $accessibleProperties = $user->getAccessiblePropertyNames();
        $query->filterByProperties($accessibleProperties);

        if ($request->has('q')) {
            $query->search($request->q);
        }

        $filters = ['property', 'tag_number', 'plate_number', 'state', 'make', 'model', 
                    'color', 'year', 'apt_number', 'owner_name', 'owner_phone', 
                    'owner_email', 'reserved_space'];

        foreach ($filters as $filter) {
            if ($request->has($filter) && $request->$filter !== '') {
                if ($filter === 'property' || $filter === 'year') {
                    $query->where($filter, $request->$filter);
                } else {
                    $query->where($filter, 'LIKE', '%' . $request->$filter . '%');
                }
            }
        }

        $vehicles = $query->orderBy('created_at', 'desc')->get();

        return response()->json($vehicles);
    }

    public function show(Request $request, string $id)
    {
        $user = $request->user();
        $vehicle = Vehicle::findOrFail($id);

        if (!$user->canAccessProperty($vehicle->property)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        return response()->json($vehicle);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'property' => 'required|string|exists:properties,name',
            'tag_number' => 'nullable|string|max:100',
            'plate_number' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:50',
            'make' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:50',
            'year' => 'nullable|string|max:10',
            'apt_number' => 'nullable|string|max:50',
            'owner_name' => 'nullable|string|max:255',
            'owner_phone' => 'nullable|string|max:50',
            'owner_email' => 'nullable|email|max:255',
            'reserved_space' => 'nullable|string|max:100',
        ]);

        if (!$user->canAccessProperty($validated['property'])) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $vehicle = Vehicle::create($validated);

        AuditLog::log($user, 'create', 'vehicle', $vehicle->id, $validated);

        return response()->json($vehicle, 201);
    }

    public function update(Request $request, string $id)
    {
        $user = $request->user();
        $vehicle = Vehicle::findOrFail($id);

        if (!$user->canAccessProperty($vehicle->property)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $validated = $request->validate([
            'property' => 'sometimes|required|string|exists:properties,name',
            'tag_number' => 'nullable|string|max:100',
            'plate_number' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:50',
            'make' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:50',
            'year' => 'nullable|string|max:10',
            'apt_number' => 'nullable|string|max:50',
            'owner_name' => 'nullable|string|max:255',
            'owner_phone' => 'nullable|string|max:50',
            'owner_email' => 'nullable|email|max:255',
            'reserved_space' => 'nullable|string|max:100',
        ]);

        if (isset($validated['property']) && !$user->canAccessProperty($validated['property'])) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $oldData = $vehicle->toArray();
        $vehicle->update($validated);

        AuditLog::log($user, 'update', 'vehicle', $vehicle->id, [
            'old' => $oldData,
            'new' => $vehicle->toArray()
        ]);

        return response()->json($vehicle);
    }

    public function destroy(Request $request, string $id)
    {
        $user = $request->user();
        $vehicle = Vehicle::findOrFail($id);

        if (!$user->canAccessProperty($vehicle->property)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        $vehicleData = $vehicle->toArray();
        $vehicle->delete();

        AuditLog::log($user, 'delete', 'vehicle', $id, $vehicleData);

        return response()->json(['message' => 'Vehicle deleted successfully']);
    }

    public function export(Request $request)
    {
        $user = $request->user();
        $query = Vehicle::query();

        $accessibleProperties = $user->getAccessiblePropertyNames();
        $query->filterByProperties($accessibleProperties);

        if ($request->has('property')) {
            $query->where('property', $request->property);
        }

        $vehicles = $query->get();

        $filename = 'vehicles_export_' . date('Ymd_His') . '.csv';
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($vehicles) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['property', 'tagNumber', 'plateNumber', 'state', 'make', 'model', 
                           'color', 'year', 'aptNumber', 'ownerName', 'ownerPhone', 
                           'ownerEmail', 'reservedSpace']);

            foreach ($vehicles as $vehicle) {
                fputcsv($file, [
                    $vehicle->property,
                    $vehicle->tag_number,
                    $vehicle->plate_number,
                    $vehicle->state,
                    $vehicle->make,
                    $vehicle->model,
                    $vehicle->color,
                    $vehicle->year,
                    $vehicle->apt_number,
                    $vehicle->owner_name,
                    $vehicle->owner_phone,
                    $vehicle->owner_email,
                    $vehicle->reserved_space,
                ]);
            }

            fclose($file);
        };

        AuditLog::log($user, 'csv_export', 'vehicle', null, ['count' => $vehicles->count()]);

        return response()->stream($callback, 200, $headers);
    }
}
