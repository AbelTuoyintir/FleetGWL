@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Maintenance Details</h1>
        <p><strong>Vehicle:</strong> {{ $maintenance->vehicle->registration_number ?? 'N/A' }}</p>
        <p><strong>Driver:</strong> {{ $maintenance->driver->name ?? 'N/A' }}</p>
        <p><strong>Maintenance Type:</strong> {{ $maintenance->maintenance_type }}</p>
        <p><strong>Date:</strong> {{ $maintenance->maintenance_date }}</p>
        <p><strong>Description:</strong> {{ $maintenance->description }}</p>
        <p><strong>Status:</strong> {{ ucfirst($maintenance->status) }}</p>
    </div>
@endsection