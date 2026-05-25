<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index()
    {
        $users = User::where('status', '!=', 'deleted')->paginate(10);
        return view('users.index', compact('users'));
    }

    public function create()
    {
        return view('users.create');
    }

    public function store(Request $request)
    {

        try{
            $validated = $request->validate([
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
                'email' => 'required|string|email|max:255|unique:users',
                'phone' => 'nullable|string|max:20',
                'role' => 'required|in:admin,driver,technician',
                'password' => 'required|string|min:8|confirmed',
                'staffID'=>'required|string|max:255',
            ]);

            User::create($validated);

            return redirect()->route('users.index')->with('success', 'User created successfully.');
        }catch(\Illuminate\Validation\ValidationException $e){
            \Log::error('Error during course enrollment.', [
            'error' => $e->getMessage()],);
            return redirect()->back()->withErrors($e->errors())->withInput();
        }
    }

    public function show(User $user)
    {
        $drivers = Driver::where('user_id', $user);
        // dd($drivers);
        return view('users.show', compact('user'));
    }

    public function edit(User $user)
    {
        return view('users.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => 'nullable|string|max:20',
            'role' => 'required|in:admin,driver,technician',
            'password' => 'nullable|string|min:8|confirmed',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'dob' => 'nullable|date|before:today',
        ]);

        $user->update($validated);

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        $user->softDelete();
        return redirect()->route('users.index')->with('success', 'User deleted successfully.');
    }
}
