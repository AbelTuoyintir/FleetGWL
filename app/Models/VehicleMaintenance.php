<?php

namespace App\Models;

/**
 * Backward-compatible alias used by controllers that still type-hint
 * VehicleMaintenance while the canonical model is Maintenance.
 */
class VehicleMaintenance extends Maintenance
{
}
