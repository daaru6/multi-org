<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;

class Contact extends Model
{
    use BelongsToOrganization;
    protected $fillable = [
        'organization_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'avatar_path',
        'created_by',
        'updated_by',
    ];

    protected static function boot()
    {
        parent::boot();
        
        // Auto-set user tracking
        static::creating(function ($contact) {
            if (empty($contact->created_by) && auth()->id()) {
                $contact->created_by = auth()->id();
            }
            if (empty($contact->updated_by) && auth()->id()) {
                $contact->updated_by = auth()->id();
            }
        });
        
        static::updating(function ($contact) {
            if (auth()->id()) {
                $contact->updated_by = auth()->id();
            }
        });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(ContactNote::class);
    }

    public function meta(): HasMany
    {
        return $this->hasMany(ContactMeta::class);
    }

    public function getFullNameAttribute(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('first_name', 'like', "%{$search}%")
              ->orWhere('last_name', 'like', "%{$search}%")
              ->orWhere('email', 'like', "%{$search}%");
        });
    }
}
