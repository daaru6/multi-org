<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

trait BelongsToOrganization
{
    /**
     * Boot the trait.
     */
    protected static function bootBelongsToOrganization(): void
    {
        // Auto-set organization_id on create
        static::creating(function (Model $model) {
            if (empty($model->organization_id) && session('current_organization_id')) {
                $model->organization_id = session('current_organization_id');
            }
        });
        
        // Apply global scope to filter by current organization
        static::addGlobalScope('organization', function (Builder $builder) {
            if (session('current_organization_id')) {
                $builder->where('organization_id', session('current_organization_id'));
            }
        });
    }
    
    /**
     * Get the organization that owns the model.
     */
    public function organization()
    {
        return $this->belongsTo(\App\Models\Organization::class);
    }
    
    /**
     * Scope a query to only include models for a specific organization.
     */
    public function scopeForOrganization(Builder $query, $organizationId)
    {
        return $query->where('organization_id', $organizationId);
    }
    
    /**
     * Remove the global organization scope temporarily.
     */
    public static function withoutOrganizationScope()
    {
        return static::withoutGlobalScope('organization');
    }
}