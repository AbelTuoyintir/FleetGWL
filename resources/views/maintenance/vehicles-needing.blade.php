@extends('layouts.maintenance')

@section('title', 'Vehicles Needing Maintenance')

@section('content')
<div class="max-w-7xl mx-auto p-6">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Vehicles Needing Maintenance</h1>
            <p class="text-gray-600 text-sm mt-1">Review and acknowledge maintenance alerts.</p>
        </div>
        <button onclick="window.location.reload()" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">
            <i class="fas fa-sync mr-2"></i>Refresh
        </button>
    </div>

    <div class="bg-white rounded-xl shadow-sm border">
        <div class="px-4 py-3 border-b flex items-center justify-between">
            <div class="text-sm text-gray-600">Total: <span id="totalCount" class="font-semibold text-gray-900">0</span></div>
            <div class="text-sm text-gray-600">Last updated: <span id="updatedAt" class="font-semibold text-gray-900">-</span></div>
        </div>

        <div class="p-4">
            <div id="loading" class="text-gray-600">Loading...</div>

            <div class="overflow-x-auto" id="tableWrap" style="display:none;">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="text-left px-4 py-3 text-gray-600 font-medium">Vehicle</th>
                            <th class="text-left px-4 py-3 text-gray-600 font-medium">Driver</th>
                            <th class="text-right px-4 py-3 text-gray-600 font-medium">Current Mileage</th>
                            <th class="text-right px-4 py-3 text-gray-600 font-medium">Mileage Since Maintenance</th>
                            <th class="text-right px-4 py-3 text-gray-600 font-medium">Excess</th>
                            <th class="text-center px-4 py-3 text-gray-600 font-medium">Progress</th>
                            <th class="text-center px-4 py-3 text-gray-600 font-medium">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="rows"></tbody>
                </table>
            </div>

            <div id="emptyState" class="hidden py-10 text-center text-gray-500">
                <i class="fas fa-clipboard-check text-3xl mb-3 text-green-600"></i>
                <div class="font-medium">No pending maintenance alerts.</div>
                <div class="text-sm">You're all good.</div>
            </div>
        </div>
    </div>
</div>

<script>
    const API_URL = '/maintenance/vehicles-needing/data';

    function progressBar(percent) {
        const p = Math.max(0, Math.min(100, Number(percent) || 0));
        return `
            <div class="w-28 mx-auto">
                <div class="h-2 bg-gray-200 rounded-full overflow-hidden">
                    <div class="h-2 ${p >= 80 ? 'bg-red-500' : (p >= 50 ? 'bg-yellow-500' : 'bg-blue-500')}" style="width:${p}%"></div>
                </div>
                <div class="text-xs text-gray-600 mt-1">${p}%</div>
            </div>
        `;
    }

    function renderRows(items) {
        const rowsEl = document.getElementById('rows');
        rowsEl.innerHTML = '';

        if (!items || items.length === 0) {
            document.getElementById('tableWrap').style.display = 'none';
            document.getElementById('emptyState').classList.remove('hidden');
            return;
        }

        document.getElementById('tableWrap').style.display = '';
        document.getElementById('emptyState').classList.add('hidden');

        for (const item of items) {
            const progressHtml = progressBar(item.progress_percentage);

            rowsEl.insertAdjacentHTML('beforeend', `
                <tr class="border-b hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium text-gray-900">${item.registration_number ?? ''} (${item.make ?? ''} ${item.model ?? ''})</td>
                    <td class="px-4 py-3">${item.driver_name ?? 'Unassigned'}</td>
                    <td class="px-4 py-3 text-right">${item.current_mileage ?? 0}</td>
                    <td class="px-4 py-3 text-right">${item.mileage_since_maintenance ?? 0}</td>
                    <td class="px-4 py-3 text-right">${item.excess_mileage ?? 0}</td>
                    <td class="px-4 py-3 text-center">${progressHtml}</td>
                    <td class="px-4 py-3 text-center">
                        <div class="flex justify-center gap-2">
                            <button class="px-3 py-2 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 rounded-lg text-xs font-medium transition shadow-sm" onclick="acknowledgeAlert(${item.id})" title="Acknowledge Alert">
                                <i class="fas fa-check text-green-500 mr-1"></i>Acknowledge
                            </button>
                            <button class="px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-medium transition shadow-sm" onclick="dispatchVehicle(${item.id})" title="Dispatch for Maintenance">
                                <i class="fas fa-truck-ramp-box mr-1"></i>Dispatch
                            </button>
                        </div>
                    </td>
                </tr>
            `);
        }
    }

    async function acknowledgeAlert(vehicleId) {
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            const res = await fetch(`/maintenance/vehicle/${vehicleId}/acknowledge`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            });

            const data = await res.json();
            if (!data.success) {
                Swal.fire({ icon: 'error', title: 'Failed', text: data.message || 'Could not acknowledge alert.' });
                return;
            }

            Swal.fire({ icon: 'success', title: 'Acknowledged', text: 'Alert acknowledged successfully.' });
            load();
        } catch (e) {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Something went wrong.' });
        }
    }

    async function dispatchVehicle(vehicleId) {
        const { value: notes } = await Swal.fire({
            title: 'Dispatch Vehicle',
            input: 'textarea',
            inputLabel: 'Maintenance Notes',
            inputPlaceholder: 'Enter any specific maintenance requirements or issues...',
            showCancelButton: true,
            confirmButtonText: 'Dispatch to Workshop',
            confirmButtonColor: '#2563eb',
            inputAttributes: {
                'aria-label': 'Maintenance Notes'
            }
        });

        if (notes !== undefined) {
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                const formData = new FormData();
                formData.append('maintenance_notes', notes);

                const res = await fetch(`/vehicles/${vehicleId}/maintenance`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                if (res.ok) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Dispatched',
                        text: 'Vehicle has been dispatched for maintenance.',
                        timer: 2000
                    });
                    // Also acknowledge the alert if it exists
                    await acknowledgeAlert(vehicleId);
                    load();
                } else {
                    const data = await res.json();
                    Swal.fire('Error', data.message || 'Failed to dispatch vehicle.', 'error');
                }
            } catch (e) {
                console.error(e);
                Swal.fire('Error', 'An unexpected error occurred.', 'error');
            }
        }
    }

    async function load() {
        document.getElementById('loading').style.display = '';
        document.getElementById('tableWrap').style.display = 'none';
        document.getElementById('emptyState').classList.add('hidden');

        const res = await fetch(API_URL, { headers: { 'Accept': 'application/json' } });
        const json = await res.json();

        document.getElementById('totalCount').textContent = json.count ?? 0;
        document.getElementById('updatedAt').textContent = new Date().toLocaleString();
        document.getElementById('loading').style.display = 'none';

        renderRows(json.data);
    }

    load();
</script>
@endsection
