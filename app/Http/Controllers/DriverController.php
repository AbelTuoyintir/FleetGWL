<?php

namespace App\Http\Controllers;

use App\Models\Driver;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class DriverController extends Controller
{
    /**
     * Generate a unique staff ID for newly created driver users.
     */
    private function generateDriverStaffId(): string
    {
        do {
            $staffId = 'DRV'.now()->format('Ymd').strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
        } while (User::where('staffID', $staffId)->exists());

        return $staffId;
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Eager load user and vehicle relationships
        $drivers = Driver::with(['user', 'vehicle'])
            ->where('status', '!=', 'deleted')
            ->withCount(['mileageLogs as mileage_logs_count', 'fuelLogs as fuel_logs_count', 'maintenances as maintenances_count'])
            ->latest()
            ->paginate(10);

        // Get available vehicles
        $availableVehicles = Vehicle::whereNull('assigned_driver_id')->get();

        // Get statistics for the dashboard
        $stats = [
            'total_drivers' => Driver::where('status', '!=', 'deleted')->count(),
            'assigned_drivers' => Driver::whereHas('vehicle')->where('status', '!=', 'deleted')->count(),
            'active_drivers' => Driver::where('status', 'active')->count(),
            'inactive_drivers' => Driver::where('status', 'inactive')->count(),
            'available_vehicles' => Vehicle::whereNull('assigned_driver_id')->count(),
        ];

        return view('admin.drivers.index', compact('drivers', 'availableVehicles', 'stats'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $users = User::doesntHave('driver')->get(); // Users who are not already drivers
        $vehicles = Vehicle::whereNull('assigned_driver_id')->get();

        return view('admin.drivers.create', compact('users', 'vehicles'));
    }

    public function store(Request $request)
    {
        try {
            // Debug: Log incoming request
            \Log::info('Driver store request received', $request->except(['password', 'password_confirmation']));

            // Validate the request
            $validated = $request->validate([
                'registration_mode' => 'required|in:existing,new',

                // For existing user
                'user_id' => [
                    'nullable',
                    'exists:users,id',
                    Rule::unique('drivers', 'user_id')->whereNull('deleted_at'),
                ],

                // For new user
                'name' => 'required_if:registration_mode,new|string|max:255',
                'email' => 'required_if:registration_mode,new|email|unique:users,email',
                'phone' => 'nullable|string|max:20',
                'password' => 'required_if:registration_mode,new|string|min:8|confirmed',

                // Driver specific fields
                'license_number' => 'required|string|max:50|unique:drivers,license_number',
                'license_expiry_date' => 'nullable|date',
                'license_class' => 'nullable|in:A,B,C,D,E',
                'address' => 'nullable|string|max:255',
                'emergency_contact_name' => 'nullable|string|max:255',
                'emergency_contact_phone' => 'nullable|string|max:20',
                'assigned_vehicle_id' => 'nullable|exists:vehicles,id',
                'license_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'notes' => 'nullable|string',
            ]);

            \Log::info('Validation passed', Arr::except($validated, ['password', 'password_confirmation']));

            DB::beginTransaction();

            $userId = null;

            // Handle user creation if in "new user" mode
            if ($validated['registration_mode'] === 'new') {
                // Check if email already exists
                if (User::where('email', $validated['email'])->exists()) {
                    throw new \Exception('Email already exists in the system.');
                }

                // Create new user - Using only 'name' field
                $user = User::create([
                    'name' => $validated['name'],  // Just the name field
                    'email' => $validated['email'],
                    'phone' => $validated['phone'] ?? null,
                    'password' => Hash::make($validated['password']),
                    'role' => 'driver',
                    'staffID' => $this->generateDriverStaffId(),
                    'status' => 'active',
                ]);

                $userId = $user->id;
                \Log::info('New user created', ['user_id' => $userId, 'name' => $validated['name']]);
            } else {
                // Use existing user
                $userId = $validated['user_id'];
                \Log::info('Using existing user', ['user_id' => $userId]);
            }

            // Prepare driver data
            $driverData = [
                'user_id' => $userId,
                'license_number' => $validated['license_number'],
                'license_expiry_date' => $validated['license_expiry_date'] ?? null,
                'license_class' => $validated['license_class'] ?? null,
                'address' => $validated['address'] ?? null,
                'emergency_contact_name' => $validated['emergency_contact_name'] ?? null,
                'emergency_contact_phone' => $validated['emergency_contact_phone'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'status' => 'active',
                'created_by' => auth()->id(),
            ];

            // Handle license photo upload
            if ($request->hasFile('license_photo') && $request->file('license_photo')->isValid()) {
                $driverData['license_photo'] = $request->file('license_photo')->store('driver-license-photos', 'public');
                \Log::info('License photo uploaded', ['path' => $driverData['license_photo']]);
            }

            // Create driver
            $driver = Driver::create($driverData);
            \Log::info('Driver created', ['driver_id' => $driver->id]);

            // Update vehicle's assigned driver if provided
            if ($request->filled('assigned_vehicle_id')) {
                $vehicle = Vehicle::find($request->assigned_vehicle_id);
                if ($vehicle) {
                    if (is_null($vehicle->assigned_driver_id)) {
                        $vehicle->assigned_driver_id = $driver->id;
                        $vehicle->save();
                        \Log::info('Vehicle assigned to driver', ['vehicle_id' => $vehicle->id, 'driver_id' => $driver->id]);
                    } else {
                        throw new \Exception('This vehicle is already assigned to another driver.');
                    }
                }
            }

            DB::commit();

            return redirect()->route('drivers.index')
                ->with('success', 'Driver created successfully.');

        } catch (ValidationException $e) {
            DB::rollBack();
            \Log::error('Validation failed', ['errors' => $e->errors()]);

            return back()->withErrors($e->errors())->withInput();
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Driver creation failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Failed to create driver: '.$e->getMessage())->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        // Bolt: Consolidate stats and relationship loading into a single query using withCount, withSum, and withAvg.
        // This fixes the 500 error caused by the missing 'distance_traveled' column and significantly reduces database roundtrips.
        $driver = Driver::where('status', '!=', 'deleted')
            ->with(['user', 'vehicle', 'mileageLogs' => function ($query) {
                $query->latest()->limit(10);
            }])
            ->withCount('maintenances')
            ->withSum('fuelLogs as total_fuel', 'fuel_quantity')
            ->withAvg('fuelLogs as avg_efficiency', 'fuel_efficiency')
            ->findOrFail($id);

        // Calculate mileage by summing the difference between end and start readings
        $totalMileage = $driver->mileageLogs()
            ->selectRaw('SUM(COALESCE(end_mileage, 0) - COALESCE(start_mileage, 0)) as total')
            ->value('total') ?? 0;

        $stats = [
            'total_mileage' => (int) $totalMileage,
            'total_fuel' => (float) ($driver->total_fuel ?? 0),
            'total_maintenances' => (int) ($driver->maintenances_count ?? 0),
            'avg_efficiency' => (float) ($driver->avg_efficiency ?? 0),
        ];

        return view('admin.drivers.show', compact('driver', 'stats'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Driver $driver)
    {
        $driver->load('user');
        $vehicles = Vehicle::whereNull('assigned_driver_id')
            ->orWhere('assigned_driver_id', $driver->id)
            ->get();

        return view('admin.drivers.edit', compact('driver', 'vehicles'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Driver $driver)
    {
        $validated = $request->validate([
            'license_number' => 'required|string|max:50|unique:drivers,license_number,'.$driver->id,
            'license_expiry_date' => 'nullable|date',
            'license_class' => 'nullable|in:A,B,C,D,E',
            'address' => 'nullable|string|max:255',
            'emergency_contact_name' => 'nullable|string|max:255',
            'emergency_contact_phone' => 'nullable|string|max:20',
            'assigned_vehicle_id' => 'nullable|exists:vehicles,id',
            'license_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'nullable|in:active,inactive',
        ]);

        DB::beginTransaction();

        try {
            // Handle license photo upload
            if ($request->hasFile('license_photo')) {
                // Delete old license photo if exists
                if ($driver->license_photo && Storage::disk('public')->exists($driver->license_photo)) {
                    Storage::disk('public')->delete($driver->license_photo);
                }
                $validated['license_photo'] = $request->file('license_photo')->store('driver-license-photos', 'public');
            }

            // Manage vehicle assignment
            $currentVehicle = Vehicle::where('assigned_driver_id', $driver->id)->first();
            $oldVehicleId = $currentVehicle ? $currentVehicle->id : null;
            $newVehicleId = $request->input('assigned_vehicle_id');

            // Clear old vehicle assignment if changed
            if ($oldVehicleId && $oldVehicleId != $newVehicleId) {
                $oldVehicle = Vehicle::find($oldVehicleId);
                if ($oldVehicle) {
                    $oldVehicle->assigned_driver_id = null;
                    $oldVehicle->save();
                }
            }

            // Assign to new vehicle if provided and different
            if ($newVehicleId && $oldVehicleId != $newVehicleId) {
                $newVehicle = Vehicle::find($newVehicleId);
                if ($newVehicle) {
                    // If assigned to someone else, prevent reassignment
                    if ($newVehicle->assigned_driver_id && $newVehicle->assigned_driver_id != $driver->id) {
                        return back()->with('error', 'Vehicle is already assigned to another driver.');
                    }

                    $newVehicle->assigned_driver_id = $driver->id;
                    $newVehicle->save();
                }
            }

            // Remove assigned_vehicle_id from validated since drivers table doesn't have this column
            unset($validated['assigned_vehicle_id']);

            // Update driver
            $driver->update($validated);

            // Update user status if provided
            if ($request->has('status') && $driver->user) {
                $driver->user->update(['status' => $validated['status']]);
            }

            DB::commit();

            return redirect()->route('drivers.index')
                ->with('success', 'Driver updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Failed to update driver: '.$e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Driver $driver)
    {
        try {
            // Unassign driver from vehicle if assigned
            $driver->vehicle()->update(['assigned_driver_id' => null]);

            // Custom soft delete
            $driver->update(['status' => 'deleted']);

            // Also update user status if exists
            if ($driver->user) {
                $driver->user->update(['status' => 'inactive']);
            }

            return redirect()->route('drivers.index')
                ->with('success', 'Driver deleted successfully.');

        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete driver: '.$e->getMessage());
        }
    }

    /**
     * Assign vehicle to driver
     */
    public function assignVehicle(Request $request, Driver $driver)
    {
        $request->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
        ]);

        $vehicle = Vehicle::findOrFail($request->vehicle_id);

        // Check if vehicle is already assigned
        if ($vehicle->assigned_driver_id) {
            return back()->with('error', 'This vehicle is already assigned to another driver.');
        }

        // Assign vehicle
        $vehicle->update(['assigned_driver_id' => $driver->id]);

        return redirect()->route('drivers.index')->with('success', 'Vehicle assigned successfully!');
    }

    /**
     * Unassign vehicle from driver
     */
    public function unassignVehicle(Driver $driver)
    {
        if (! $driver->vehicle) {
            return back()->with('error', 'No vehicle assigned to this driver.');
        }

        $driver->vehicle->update(['assigned_driver_id' => null]);

        return redirect()->route('drivers.index')->with('success', 'Vehicle unassigned successfully!');
    }

    /**
     * Get driver statistics
     */
    public function statistics()
    {
        $totalDrivers = Driver::where('status', '!=', 'deleted')->count();

        // Count distinct drivers that are assigned to at least one vehicle
        $assignedDrivers = Vehicle::whereNotNull('assigned_driver_id')
            ->distinct('assigned_driver_id')
            ->count('assigned_driver_id');

        $unassignedDrivers = $totalDrivers - $assignedDrivers;

        // Recent drivers
        $recentDrivers = Driver::with('user')->where('status', '!=', 'deleted')->latest()->take(5)->get();

        // Vehicles needing drivers
        $vehiclesWithoutDrivers = Vehicle::whereNull('assigned_driver_id')->count();

        return view('admin.drivers.statistics', compact(
            'totalDrivers',
            'assignedDrivers',
            'unassignedDrivers',
            'vehiclesWithoutDrivers',
            'recentDrivers'
        ));
    }

    /**
     * Search drivers by name (for AJAX)
     */
    public function searchDrivers(Request $request)
    {
        try {
            $search = $request->query('search');

            if (! $search || strlen($search) < 2) {
                return response()->json([
                    'success' => false,
                    'drivers' => [],
                ]);
            }

            $drivers = Driver::where('status', 'active')
                ->whereHas('user', function ($query) use ($search) {
                    $query->where('first_name', 'LIKE', "%{$search}%")
                        ->orWhere('last_name', 'LIKE', "%{$search}%")
                        ->orWhere('email', 'LIKE', "%{$search}%");
                })
                ->with('user:id,first_name,last_name,email')
                ->limit(10)
                ->get()
                ->map(function ($driver) {
                    return [
                        'id' => $driver->id,
                        'name' => $driver->user ? $driver->user->first_name.' '.$driver->user->last_name : 'Unknown',
                        'email' => $driver->user ? $driver->user->email : null,
                    ];
                });

            return response()->json([
                'success' => true,
                'drivers' => $drivers,
            ]);

        } catch (\Exception $e) {
            \Log::error('Driver search error: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'drivers' => [],
            ], 500);
        }
    }

    /**
     * Show the profile form for the authenticated driver
     */
    public function profile()
    {
        $user = auth()->user();
        $driver = Driver::where('user_id', $user->id)->first();

        if (! $driver) {
            return redirect()->route('dashboard')->with('error', 'Driver profile not found.');
        }

        return view('driver.profile', compact('driver', 'user'));
    }

    /**
     * Update the profile for the authenticated driver
     */
    public function updateProfile(Request $request)
    {
        $user = auth()->user();
        $driver = Driver::where('user_id', $user->id)->first();

        if (! $driver) {
            return redirect()->route('dashboard')->with('error', 'Driver profile not found.');
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,'.$user->id,
            'phone' => 'nullable|string|max:20',
            'license_number' => 'required|string|max:50|unique:drivers,license_number,'.$driver->id,
            'license_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        try {
            DB::beginTransaction();

            // Update user information
            $user->update([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? $user->phone,
            ]);

            // Handle license photo upload
            $driverData = [
                'license_number' => $validated['license_number'],
            ];

            if ($request->hasFile('license_photo')) {
                // Delete old license photo if exists
                if ($driver->license_photo && Storage::disk('public')->exists($driver->license_photo)) {
                    Storage::disk('public')->delete($driver->license_photo);
                }
                $driverData['license_photo'] = $request->file('license_photo')->store('driver-license-photos', 'public');
            }

            $driver->update($driverData);

            DB::commit();

            return redirect()->route('driver.profile')->with('success', 'Profile updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->with('error', 'Failed to update profile: '.$e->getMessage());
        }
    }
}
