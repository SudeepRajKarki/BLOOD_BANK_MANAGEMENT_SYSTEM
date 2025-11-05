<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BloodInventory;
class BloodInventoryController extends Controller
{
    public function index(Request $request)
    {
        $query = BloodInventory::query();
        if ($request->has('blood_group')) {
            $query->where('blood_type', $request->blood_group);
        }
        if ($request->has('location')) {
            $query->where('location', $request->location);
        }
        return response()->json($query->get());
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            'blood_type' => 'required|string|max:3',
            'quantity_ml' => 'required|integer|min:1',
            'location' => 'required|string',
        ]);
        $inventory = BloodInventory::create($validated);
        return response()->json(['message' => 'Created', 'data' => $inventory], 201);
    }
    public function update(Request $request, $id)
    {
        $inventory = BloodInventory::findOrFail($id);
        $inventory->update($request->all());
        return response()->json(['message' => 'Updated', 'data' => $inventory]);
    }
    public function show($id)
    {
        $inventory = BloodInventory::findOrFail($id);
        return response()->json($inventory);
    }
    public function destroy($id)
    {
        BloodInventory::destroy($id);
        return response()->json(['message' => 'Deleted']);
    }
}
