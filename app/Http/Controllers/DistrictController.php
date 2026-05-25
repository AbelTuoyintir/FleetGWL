<?php

namespace App\Http\Controllers;
use App\Models\District;
use App\Models\Region;

use Illuminate\Http\Request;

class DistrictController extends Controller
{
    //
    public function store(Request $request){
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'region_id' => 'nullable|exists:regions,id',
            'digital_address' => 'nullable|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:20',
            'status' => 'nullable|in:active,inactive',
        ]);

        $district = District::create(array_merge($validated, ['created_by' => auth()->id()]));

        return redirect()->back()->with('success', 'District created successfully.');
    }

    public function update(Request $request, District $district){
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:districts,name,'.$district->id,
            'digital_address' => 'nullable|string|max:255',
            'region_id' => 'nullable|exists:regions,id',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:20',
            'status' => 'nullable|in:active,inactive',
        ]);

        $district->update(array_merge($validated, ['updated_by' => auth()->id()]));

        return redirect()->back()->with('success', 'District updated successfully.');
    }


    public function show(District $district)
    {
        // Load any relationships if needed
        $district->load(['region']);
        
        return view('districts.show', compact('district'));
    }

    public function edit(District $district)
    {
        // Load region for the edit form if needed
        $regions = Region::all(); // Assuming you need regions for dropdown
        
        return view('districts.edit', compact('district', 'regions'));
    }

    public function destroy(District $district){
        $district->softDelete();
        return redirect()->back()->with('success', 'District deleted successfully.');
    }


}
