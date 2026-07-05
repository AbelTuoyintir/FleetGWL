FleetGWL - Fleet Management Application
🚀 Overview
FleetGWL is a comprehensive fleet management solution built with Laravel, designed for live vehicle tracking and complete fleet operations management. The application provides real-time visibility into your fleet while streamlining maintenance, fuel management, driver coordination, and administrative tasks.

https://via.placeholder.com/1200x600/1a202c/ffffff?text=FleetGWL+Dashboard

✨ Key Features
🗺️ Live Vehicle Tracking
Real-time GPS tracking with Leaflet.js map integration

Custom vehicle markers with status indicators (moving/idle/offline)

Follow mode with automatic map centering

24-hour route history playback with dashed polyline visualization

Live telemetry display: speed, ignition, fuel level, battery status

Multiple map themes: Light, Dark, Satellite

🚗 Vehicle Management
Complete CRUD operations with photo upload support

Advanced filtering: status, region, vehicle type, search

Bulk import/export with CSV/Excel support and validation

Vehicle statistics dashboard: status distribution, maintenance due, insurance expiry

Duplicate handling with update existing option during import

Soft delete functionality

🔧 Maintenance & Job Orders
Dispatch/release vehicles to/from maintenance

Job order creation with checklist items grouped by category

Service priority levels and scheduling

Maintenance alerts with acknowledgment workflow

Service provider tracking and estimated costs

Maintenance history with status tracking

⛽ Fuel Management
Fuel log CRUD with automatic calculations

Distance tracking: computes distance traveled between fuel-ups

Fuel efficiency: calculates km/liter and cost per kilometer

Real-time efficiency updates when logs are modified

Analytics dashboard: monthly trends, cost analysis, efficiency metrics

Cost breakdown per vehicle with expense tracking

Fuel forecasting based on historical usage patterns

Export capabilities: CSV, Excel, PDF support

📊 Mileage Logs
Track vehicle mileage with odometer readings

Calculate distance traveled between logs

Analytics dashboard with trend visualization

Export functionality for reporting

👤 Driver Management
Complete driver profiles with contact information

Vehicle assignment/unassignment workflow

Driver search API for quick lookup

Statistics dashboard for driver performance

📍 Location Management
Hierarchical structure: Regions → Districts → Stations

Complete CRUD operations for all levels

Integrated with vehicle and driver management

📄 Document Management
Upload, preview, and download vehicle documents

Expiring soon tracking with visual badges

Acknowledge workflow for critical documents

Trash management with restore and force delete

Bulk actions for document management

🤖 AI Support Chat
Built-in AI assistant for fleet-related queries

Chat history persistence

Contextual help for operational questions

📈 Reports & Analytics
Utilization reports redirect to vehicle status overview

Cost analysis integrated with fuel management

Fuel efficiency reports with performance metrics

Real-time statistics across all modules

🛠️ Technology Stack
Backend
Framework: Laravel 10.x

PHP: 8.1+

Database: MySQL/PostgreSQL with Eloquent ORM

Authentication: Laravel Sanctum with role-based access

Excel Processing: maatwebsite/excel

Validation: Laravel validation with custom rules

Frontend
JavaScript Framework: Vanilla JS with AJAX

Mapping: Leaflet.js with custom markers

CSS: Tailwind CSS / Bootstrap mix

Charts: Chart.js for analytics visualization

Templating: Laravel Blade

Development Tools
Testing: PHPUnit with performance tests

Version Control: Git

Dependency Management: Composer, NPM

📋 Prerequisites
PHP 8.1 or higher

Composer

Node.js & NPM (for frontend assets)

MySQL 5.7+ or PostgreSQL 10+

Git

🔧 Installation
1. Clone the Repository
bash
git clone https://github.com/yourusername/fleetgwl.git
cd fleetgwl
2. Install PHP Dependencies
bash
composer install
3. Install Frontend Dependencies
bash
npm install
npm run build
4. Environment Configuration
bash
cp .env.example .env
php artisan key:generate
Update .env with your database credentials:

env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=fleetgwl
DB_USERNAME=your_username
DB_PASSWORD=your_password
5. Run Database Migrations & Seeders
bash
php artisan migrate --seed
6. Create Storage Link
bash
php artisan storage:link
7. Start Development Server
bash
php artisan serve
Access the application at: http://localhost:8000

🚦 User Roles
Admin
Full access to all modules

Vehicle management with import/export

Maintenance and job order management

Fuel and mileage tracking

Driver and location management

Document management

Report generation

Driver
Vehicle tracking view

Online status management

Job order creation (limited)

Vehicle assignment visibility

📁 Project Structure
text
fleetgwl/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/
│   │   │   │   └── VehicleTrackingController.php
│   │   │   ├── AiSupportController.php
│   │   │   ├── DocumentController.php
│   │   │   ├── DriverController.php
│   │   │   ├── FuelManagementController.php
│   │   │   ├── LocationController.php
│   │   │   ├── MaintenanceController.php
│   │   │   ├── MileageLogController.php
│   │   │   └── VehicleController.php
│   │   └── Middleware/
│   │       ├── Authenticate.php
│   │       └── RoleMiddleware.php
│   └── Models/
│       ├── Vehicle.php
│       ├── Driver.php
│       ├── FuelLog.php
│       ├── Maintenance.php
│       ├── MileageLog.php
│       └── Document.php
├── resources/
│   └── views/
│       └── admin/
│           ├── vehicles/
│           │   ├── tracking.blade.php
│           │   ├── show.blade.php
│           │   ├── vehicles.blade.php
│           │   └── create-job-orders.blade.php
│           ├── fuel-management/
│           ├── maintenance/
│           ├── drivers/
│           └── documents/
├── routes/
│   ├── web.php
│   ├── admin.php
│   └── driver.php
├── database/
│   ├── migrations/
│   └── seeders/
├── public/
│   └── storage/
└── tests/
    ├── Feature/
    │   ├── VehicleControllerPerformanceTest.php
    │   ├── VehicleMileagePerformanceTest.php
    │   └── VehicleTrackingTest.php
    └── Performance/
        └── DashboardPerformanceTest.php
🗺️ Routing Structure
Admin Routes (Prefix: /admin)
php
// Vehicle Management
/vehicles                    → Vehicle listing & management
/vehicles/tracking           → Live tracking dashboard
/vehicles/tracking/data      → AJAX data endpoint
/vehicles/{id}/history       → Route history API
/vehicles/import             → Bulk import
/vehicles/export             → CSV export

// Fuel Management
/fuel-management             → Fuel log management
/fuel-management/analytics   → Fuel analytics
/fuel-management/cost-analysis → Cost analysis
/fuel-management/forecast    → Fuel forecasting

// Maintenance
/maintenance                 → Maintenance management
/maintenance/vehicles-needing → Vehicles requiring maintenance
/maintenance/schedule/{id}   → Schedule maintenance

// Documents
/documents                   → Document management
/documents/expiring          → Expiring documents
/documents/trashed           → Trash management

// Drivers
/drivers                     → Driver management
/drivers/{id}/assign-vehicle → Vehicle assignment

// Locations
/locations                   → Location management
Public Routes
php
/ai-support/chat             → AI chat endpoint
/ai-support/history          → Chat history
/api/drivers/search          → Driver search API
/api/vehicles/search         → Vehicle search API
🎯 Core Modules
1. Live Tracking System
The tracking dashboard (/vehicles/tracking) provides real-time fleet visibility:

javascript
// Auto-refresh every 5 seconds
setInterval(fetchData, 5000);

// Vehicle status logic
const isOffline = (now - lastSeen) >= 300000; // 5 minutes
const isIdle = speed === 0 && !isOffline;
const isMoving = speed > 0;
Key UI Components:

Interactive Leaflet map

Vehicle list with search and filters

Live activity ticker

History playback controls

Map theme switcher

2. Vehicle Import/Export
Import Features:

CSV/Excel file support

Automatic header mapping

Row validation with error reporting

Duplicate handling (skip or update)

Driver matching by email

Failed rows download

Export Features:

CSV format with comprehensive fields

Filtered data export

Status and region filtering

3. Fuel Management System
Automatic Calculations:

php
distance_traveled = current_odometer - previous_odometer
fuel_efficiency = distance_traveled / fuel_quantity
cost_per_distance = fuel_cost / distance_traveled
Analytics Features:

Monthly fuel usage trends

Cost per kilometer analysis

Vehicle-specific breakdowns

Fuel type distribution

Efficiency comparisons

4. Maintenance Workflow
Job Order Creation:

Select vehicle

Choose maintenance type

Select services from checklist

Set priority and schedule

Assign technician

Record estimated cost

Status Flow:

text
Created → Dispatched → In Progress → Completed
  ↓
Waiting (driver-initiated)
5. Document Management
Supported Operations:

Upload with type classification

Preview (image/PDF)

Download

Acknowledge (for expiring documents)

Move to trash

Restore from trash

Permanent deletion

Expiration Tracking:

Visual indicators (expired/expiring soon)

Acknowledgment workflow

Expiry notifications

🔐 Security
Authentication
Laravel Sanctum for API authentication

Session-based web authentication

Role-based authorization

Authorization Middleware
php
// Admin routes protection
Route::middleware(['auth', 'role:admin'])->group(function () {
    // All admin routes
});

// Driver routes protection
Route::middleware(['auth', 'role:driver'])->group(function () {
    // Driver routes
});
Data Protection
CSRF protection on all forms

Input validation with Laravel rules

SQL injection prevention via Eloquent

XSS protection via Blade escaping

File upload validation (type, size)

🚀 Performance Optimizations
Database Optimization
Eager Loading: Reduces N+1 queries

Aggregation Queries: SQL selectRaw for statistics

Conditional Aggregation: Batched data processing

Pagination: 25 items per page for listing

Indexes: Optimized for query performance

Caching
Browser caching for static assets

Session-based caching for import results

Query caching for frequently accessed data

Frontend Performance
Lazy loading for map tiles

Debounced search inputs

Optimized DOM updates

Minimal re-renders

🧪 Testing
Performance Tests
bash
php artisan test --filter VehicleControllerPerformanceTest
php artisan test --filter VehicleMileagePerformanceTest
php artisan test --filter DashboardPerformanceTest
Feature Tests
bash
php artisan test --filter VehicleTrackingTest
📊 Database Schema Highlights
Vehicles Table
sql
- id (primary)
- registration_number (unique)
- make, model, year
- vehicle_type
- color
- chassis_number
- engine_number
- status (active/maintenance/inactive)
- current_odometer
- fuel_type
- photo_path
- region_id (foreign)
- driver_id (foreign)
- created_at, updated_at, deleted_at
Fuel Logs Table
sql
- id (primary)
- vehicle_id (foreign)
- driver_id (foreign)
- date
- odometer
- fuel_quantity
- fuel_cost
- fuel_type
- distance_traveled
- fuel_efficiency
- cost_per_distance
- previous_odometer
- status (active/deleted)
- created_by (foreign)
Maintenance Table
sql
- id (primary)
- vehicle_id (foreign)
- maintenance_type
- maintenance_date
- description
- checklist (JSON)
- priority
- service_provider
- estimated_cost
- actual_cost
- status (waiting/dispatched/in_progress/completed/cancelled)
- driver_id (foreign)
- technician_notes
- created_by (foreign)
🚨 Common Issues & Solutions
Import Issues
Empty headers: File must have headers matching expected fields

Invalid email: Driver email must exist in users table

Duplicate vehicle: Use update_existing flag to handle duplicates

Fuel Calculations
Odometer must increase: System validates odometer progression

Missing previous log: First fuel log sets base odometer

Zero distance: Cannot calculate efficiency for zero distance

Tracking Issues
No vehicles visible: Ensure vehicles have active status

Offline vehicles: Check last_seen_at timestamp

Map not loading: Verify Leaflet CDN accessibility

🤝 Contributing
We welcome contributions! Please follow these steps:

Fork the repository

Create a feature branch (git checkout -b feature/AmazingFeature)

Commit your changes (git commit -m 'Add AmazingFeature')

Push to branch (git push origin feature/AmazingFeature)

Open a Pull Request

Coding Standards
Follow PSR-12 coding standards

Write tests for new features

Update documentation accordingly

Use meaningful commit messages

📝 License
This project is proprietary and confidential. Unauthorized copying, distribution, or use is strictly prohibited.

📞 Support
For support, email support@fleetgwl.com or open an issue in the repository.

🎯 Quick Feature Checklist
Live vehicle tracking dashboard (Leaflet)

Follow mode & route history playback

Vehicle CRUD + photo upload

Bulk import/export with validation

Vehicle statistics dashboard

Maintenance dispatch & release

Job order creation with checklists

Maintenance alerts & acknowledgment

Mileage log CRUD + analytics

Fuel log CRUD with auto-calculations

Fuel analytics & cost analysis

Fuel forecasting

Driver management

Location management (Regions/Districts/Stations)

Document management

AI Support chat

Reports & analytics

🏗️ Future Roadmap
Mobile application (React Native)

Real-time notifications (WebSockets)

Advanced predictive maintenance

GPS hardware integration

Multi-tenant support

API documentation (Swagger/OpenAPI)

Automated reporting via email

Fuel price tracking integration

Driver mobile app

📚 Additional Resources
Documentation: /docs directory

API Reference: /api/documentation (coming soon)

Demo: https://demo.fleetgwl.com

Changelog: /CHANGELOG.md

Built with ❤️ using Laravel
