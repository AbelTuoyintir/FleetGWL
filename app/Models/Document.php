<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use App\Traits\BelongsToTenant;
use Illuminate\Support\Str;

class Document extends Model
{
    use Auditable;

    protected $fillable = [
        'title',
        'slug',
        'description',
        'file_path',
        'file_name',
        'file_type',
        'extension',
        'file_size',
        'document_type',
        'reference_number',
        'vehicle_id',
        'issue_date',
        'expiry_date',
        'is_expired',
        'reminder_date',
        'status',
        'is_public',
        'requires_acknowledgement',
        'acknowledged_at',
        'acknowledged_by',
        'metadata',
        'tags',
        'version',
        'previous_version_id',
        'deleted_by',
    ];

    protected $casts = [
        'issue_date' => 'date',
        'expiry_date' => 'date',
        'reminder_date' => 'date',
        'is_expired' => 'boolean',
        'is_public' => 'boolean',
        'requires_acknowledgement' => 'boolean',
        'acknowledged_at' => 'datetime',
        'metadata' => 'array',
        'file_size' => 'integer',
    ];

    public static function getDocumentTypes(): array
    {
        return [
            'insurance',
            'registration',
            'invoice',
            'receipt',
            'contract',
            'certificate',
            'license',
            'permit',
            'road_worthy',
            'manual',
            'report',
            'other',
        ];
    }

    public static function getDocumentTypeDefinitions(): array
    {
        return [
            'insurance' => ['name' => 'Insurance', 'icon' => 'fa-file-invoice', 'color' => 'blue', 'bg' => 'bg-blue-100', 'text' => 'text-blue-700'],
            'registration' => ['name' => 'Registration', 'icon' => 'fa-id-card', 'color' => 'green', 'bg' => 'bg-green-100', 'text' => 'text-green-700'],
            'invoice' => ['name' => 'Invoice', 'icon' => 'fa-file-invoice-dollar', 'color' => 'purple', 'bg' => 'bg-purple-100', 'text' => 'text-purple-700'],
            'receipt' => ['name' => 'Receipt', 'icon' => 'fa-receipt', 'color' => 'teal', 'bg' => 'bg-teal-100', 'text' => 'text-teal-700'],
            'contract' => ['name' => 'Contract', 'icon' => 'fa-file-signature', 'color' => 'indigo', 'bg' => 'bg-indigo-100', 'text' => 'text-indigo-700'],
            'certificate' => ['name' => 'Certificate', 'icon' => 'fa-certificate', 'color' => 'yellow', 'bg' => 'bg-yellow-100', 'text' => 'text-yellow-700'],
            'license' => ['name' => 'License', 'icon' => 'fa-id-card', 'color' => 'cyan', 'bg' => 'bg-cyan-100', 'text' => 'text-cyan-700'],
            'permit' => ['name' => 'Permit', 'icon' => 'fa-passport', 'color' => 'orange', 'bg' => 'bg-orange-100', 'text' => 'text-orange-700'],
            'road_worthy' => ['name' => 'Road Worthy', 'icon' => 'fa-car', 'color' => 'red', 'bg' => 'bg-red-100', 'text' => 'text-red-700'],
            'manual' => ['name' => 'Manual', 'icon' => 'fa-book', 'color' => 'gray', 'bg' => 'bg-gray-100', 'text' => 'text-gray-700'],
            'report' => ['name' => 'Report', 'icon' => 'fa-chart-line', 'color' => 'blue', 'bg' => 'bg-blue-100', 'text' => 'text-blue-700'],
            'other' => ['name' => 'Other', 'icon' => 'fa-file-alt', 'color' => 'gray', 'bg' => 'bg-gray-100', 'text' => 'text-gray-700'],
        ];
    }

    public static function getStatuses(): array
    {
        return [
            'active' => ['label' => 'Active', 'color' => 'green'],
            'expired' => ['label' => 'Expired', 'color' => 'red'],
            'archived' => ['label' => 'Archived', 'color' => 'gray'],
            'draft' => ['label' => 'Draft', 'color' => 'yellow'],
        ];
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function acknowledgedBy()
    {
        return $this->belongsTo(User::class, 'acknowledged_by');
    }

    public function previousVersion()
    {
        return $this->belongsTo(Document::class, 'previous_version_id');
    }

    public function nextVersions()
    {
        return $this->hasMany(Document::class, 'previous_version_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeExpiringSoon($query, $days = 30)
    {
        return $query->where('expiry_date', '>=', now())
            ->where('expiry_date', '<=', now()->addDays($days))
            ->where('status', 'active');
    }

    public function scopeExpired($query)
    {
        return $query->where(function ($q) {
            $q->where('expiry_date', '<', now())
                ->orWhere('is_expired', true);
        })->where('status', 'active');
    }

    public function scopeForVehicle($query, $vehicleId)
    {
        return $query->where('vehicle_id', $vehicleId);
    }

    public function scopeOfType($query, $type)
    {
        return $query->where('document_type', $type);
    }

    public function getIsExpiredAttribute()
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function getDaysUntilExpiryAttribute()
    {
        if (!$this->expiry_date) {
            return null;
        }

        return now()->diffInDays($this->expiry_date, false);
    }

    public function getExpiryStatusAttribute()
    {
        if (!$this->expiry_date) {
            return 'no_expiry';
        }

        if ($this->expiry_date->isPast()) {
            return 'expired';
        }

        if ($this->expiry_date->diffInDays(now()) <= 30) {
            return 'expiring_soon';
        }

        return 'valid';
    }

    public function getFormattedFileSizeAttribute()
    {
        if (!$this->file_size) {
            return 'Unknown';
        }

        if ($this->file_size >= 1048576) {
            return round($this->file_size / 1048576, 2) . ' MB';
        }

        if ($this->file_size >= 1024) {
            return round($this->file_size / 1024, 2) . ' KB';
        }

        return $this->file_size . ' B';
    }

    public function getDocumentTypeInfoAttribute()
    {
        $definitions = self::getDocumentTypeDefinitions();
        return $definitions[$this->document_type] ?? $definitions['other'];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($document) {
            if (empty($document->slug)) {
                $slug = Str::slug($document->title);
                $count = static::where('slug', 'LIKE', "{$slug}%")->count();
                $document->slug = $count ? "{$slug}-{$count}" : $slug;
            }

            if ($document->expiry_date && $document->expiry_date->isPast()) {
                $document->is_expired = true;
            }
        });

        static::updating(function ($document) {
            if ($document->expiry_date && $document->expiry_date->isPast()) {
                $document->is_expired = true;
            } else {
                $document->is_expired = false;
            }
        });
    }
}

