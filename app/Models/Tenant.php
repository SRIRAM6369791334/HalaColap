<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tenant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'domain',
        'company_name',
        'email',
        'phone',
        'address',
        'gst_number',
        'logo_path',
        'currency',
        'timezone',
        'date_format',
        'is_active',
        'reseller_id',
        'settings',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'settings' => 'array',
        ];
    }

    /**
     * Reseller relationship.
     */
    public function reseller(): BelongsTo
    {
        return $this->belongsTo(Reseller::class);
    }

    /**
     * Users associated with this tenant.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tenant_user')
            ->withPivot('username', 'role', 'is_active')
            ->withTimestamps();
    }

    /**
     * Creator of this tenant.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Dealers belonging to this tenant.
     */
    public function dealers(): HasMany
    {
        return $this->hasMany(Dealer::class);
    }

    /**
     * Customers belonging to this tenant.
     */
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    /**
     * Vendors belonging to this tenant.
     */
    public function vendors(): HasMany
    {
        return $this->hasMany(Vendor::class);
    }

    /**
     * Active tenants scope.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
