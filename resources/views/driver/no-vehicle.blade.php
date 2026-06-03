@extends('layouts.driver')

@section('title', 'No Vehicle Assigned')

@section('content')
<div class="min-h-screen bg-gray-50 py-10">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white shadow rounded-3xl p-8 text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-yellow-100 text-yellow-600 mb-5">
                <i class="fas fa-exclamation-triangle text-2xl"></i>
            </div>
            <h1 class="text-3xl font-semibold text-gray-900 mb-3">No vehicle assigned yet</h1>
            <p class="text-gray-600 mb-6">We couldn't find a vehicle linked to your driver profile. Please contact your fleet administrator to assign a vehicle before using the Fuel & Mileage dashboard.</p>
            <a href="{{ route('driver.dashboard') ?? url('/') }}" class="inline-flex items-center justify-center px-6 py-3 bg-blue-600 text-white rounded-full text-sm font-semibold hover:bg-blue-700 transition">
                <i class="fas fa-arrow-left mr-2"></i>Back to Driver Home
            </a>
        </div>
    </div>
</div>
@endsection
