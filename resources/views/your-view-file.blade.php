// Add a search input for the driver dropdown
<div>
    <label class="form-label">Driver</label>
    <input type="text" id="driver_search" class="form-input" placeholder="Search driver by name">
    <select name="driver_id" id="driver_id" class="form-input">
        <option value="">Select Driver</option>
        @foreach($drivers as $driver)
            <option value="{{ $driver->user->id ?? $driver->id }}">
                {{ $driver->user->name ?? $driver->name }}
            </option>
        @endforeach
    </select>
</div>

<script>
    document.getElementById('driver_search').addEventListener('input', function() {
        const searchValue = this.value.toLowerCase();
        const driverOptions = document.querySelectorAll('#driver_id option');

        driverOptions.forEach(option => {
            const driverName = option.textContent.toLowerCase();
            if (driverName.includes(searchValue) || option.value === '') {
                option.style.display = '';
            } else {
                option.style.display = 'none';
            }
        });
    });
</script>

// Add autocomplete functionality for vehicle registration number
<div>
    <label class="form-label">Vehicle (Number Plate) *</label>
    <input type="text" id="vehicle_plate" class="form-input" placeholder="Type number plate (e.g. GWL-001)" required autocomplete="off">
    <input type="hidden" name="vehicle_id" id="vehicle_id">
    <div class="text-xs text-gray-500 mt-1" id="vehicle_plate_help">The vehicle will be auto-selected when the plate matches.</div>
</div>

<script>
    document.getElementById('vehicle_plate').addEventListener('input', function() {
        const query = this.value;
        if (query.length > 2) {
            fetch(`/api/vehicles?registration_number=${query}`)
                .then(response => response.json())
                .then(data => {
                    if (data.length > 0) {
                        const vehicle = data[0]; // Assuming the first match is the best match
                        document.getElementById('vehicle_id').value = vehicle.id;
                        document.getElementById('vehicle_plate_help').textContent = `Matched: ${vehicle.registration_number}`;
                    } else {
                        document.getElementById('vehicle_id').value = '';
                        document.getElementById('vehicle_plate_help').textContent = 'No match found.';
                    }
                });
        }
    });
</script>