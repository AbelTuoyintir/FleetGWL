<?php

namespace App\Http\Controllers;

use App\Models\Region;
use App\Models\District;
use App\Models\Station;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class LocationController extends Controller
{
    public function index()
    {
        return view('admin.locations.index');
    }

    // ==================== STATS ====================
    public function getStats()
    {
        return response()->json([
            'success' => true,
            'total_regions' => Region::count(),
            'total_districts' => District::count(),
            'total_stations' => Station::count(),
        ]);
    }

    // ==================== REGIONS ====================
    public function getRegions(Request $request)
    {
        $regions = Region::orderBy('created_at', 'desc')->paginate(15);
        return response()->json(['success' => true, 'data' => $regions->items(), 'pagination' => $this->getPaginationData($regions)]);
    }

    public function getRegionsList()
    {
        return response()->json(['success' => true, 'regions' => Region::active()->get(['id', 'name', 'code'])]);
    }

    public function getRegion($id)
    {
        $region = Region::findOrFail($id);
        return response()->json(['success' => true, 'data' => $region]);
    }

    public function getDistrictsByRegion($id)
    {
        $districts = District::where('region_id', $id)->get(['id', 'name']);
        return response()->json(['success' => true, 'districts' => $districts]);
    }

    public function storeRegion(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:regions,name',
            'code' => 'required|string|max:50|unique:regions,code',
            'status' => 'in:active,inactive'
        ]);
        
        $validated['created_by'] = auth()->id();
        Region::create($validated);
        
        return response()->json(['success' => true, 'message' => 'Region created successfully']);
    }

    public function updateRegion(Request $request, $id)
    {
        $region = Region::findOrFail($id);
        
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('regions')->ignore($id)],
            'code' => ['required', 'string', 'max:50', Rule::unique('regions')->ignore($id)],
            'status' => 'in:active,inactive'
        ]);
        
        $validated['updated_by'] = auth()->id();
        $region->update($validated);
        
        return response()->json(['success' => true, 'message' => 'Region updated successfully']);
    }

    public function deleteRegion($id)
    {
        $region = Region::findOrFail($id);
        $region->delete();
        return response()->json(['success' => true, 'message' => 'Region deleted successfully']);
    }

    // ==================== DISTRICTS ====================
    public function getDistricts(Request $request)
    {
        $districts = District::with('region')->orderBy('created_at', 'desc')->paginate(15);
        $data = $districts->getCollection()->map(function($district) {
            return [
                'id' => $district->id,
                'name' => $district->name,
                'code' => $district->code,
                'region_name' => $district->region ? $district->region->name : 'N/A',
                'status' => $district->status,
                'created_at' => $district->created_at
            ];
        });
        return response()->json(['success' => true, 'data' => $data, 'pagination' => $this->getPaginationData($districts)]);
    }

    public function getDistrict($id)
    {
        $district = District::findOrFail($id);
        return response()->json(['success' => true, 'data' => $district]);
    }

    public function storeDistrict(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:districts,code',
            'region_id' => 'required|exists:regions,id',
            'status' => 'in:active,inactive'
        ]);
        
        $validated['created_by'] = auth()->id();
        District::create($validated);
        
        return response()->json(['success' => true, 'message' => 'District created successfully']);
    }

    public function updateDistrict(Request $request, $id)
    {
        $district = District::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => ['required', 'string', 'max:50', Rule::unique('districts')->ignore($id)],
            'region_id' => 'required|exists:regions,id',
            'status' => 'in:active,inactive'
        ]);
        
        $validated['updated_by'] = auth()->id();
        $district->update($validated);
        
        return response()->json(['success' => true, 'message' => 'District updated successfully']);
    }

    public function deleteDistrict($id)
    {
        $district = District::findOrFail($id);
        $district->delete();
        return response()->json(['success' => true, 'message' => 'District deleted successfully']);
    }

    // ==================== STATIONS ====================
    public function getStations(Request $request)
    {
        $stations = Station::with(['region', 'district'])->orderBy('created_at', 'desc')->paginate(15);
        $data = $stations->getCollection()->map(function($station) {
            return [
                'id' => $station->id,
                'name' => $station->name,
                'code' => $station->code,
                'type' => $station->type,
                'region_name' => $station->region ? $station->region->name : 'N/A',
                'district_name' => $station->district ? $station->district->name : 'N/A',
                'status' => $station->status,
                'created_at' => $station->created_at
            ];
        });
        return response()->json(['success' => true, 'data' => $data, 'pagination' => $this->getPaginationData($stations)]);
    }

    public function getStation($id)
    {
        $station = Station::findOrFail($id);
        return response()->json(['success' => true, 'data' => $station]);
    }

    public function storeStation(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:stations,code',
            'type' => 'required|in:station,treatment_plant,pumping_station,reservoir,workshop,distribution',
            'region_id' => 'required|exists:regions,id',
            'district_id' => 'nullable|exists:districts,id',
            'status' => 'in:active,inactive'
        ]);
        
        $validated['created_by'] = auth()->id();
        Station::create($validated);
        
        return response()->json(['success' => true, 'message' => 'Station created successfully']);
    }

    public function updateStation(Request $request, $id)
    {
        $station = Station::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => ['required', 'string', 'max:50', Rule::unique('stations')->ignore($id)],
            'type' => 'required|in:station,treatment_plant,pumping_station,reservoir,workshop',
            'region_id' => 'required|exists:regions,id',
            'district_id' => 'nullable|exists:districts,id',
            'status' => 'in:active,inactive'
        ]);
        
        $validated['updated_by'] = auth()->id();
        $station->update($validated);
        
        return response()->json(['success' => true, 'message' => 'Station updated successfully']);
    }

    public function deleteStation($id)
    {
        $station = Station::findOrFail($id);
        $station->delete();
        return response()->json(['success' => true, 'message' => 'Station deleted successfully']);
    }

    private function getPaginationData($paginator)
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];
    }
}
