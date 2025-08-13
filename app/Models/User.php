<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function organizations(): BelongsToMany
    {
        return $this->belongsToMany(Organization::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function ownedOrganizations(): HasMany
    {
        return $this->hasMany(Organization::class, 'owner_user_id');
    }

    public function getCurrentOrganization()
    {
        $currentOrgId = session('current_organization_id');
        if ($currentOrgId) {
            return $this->organizations()->where('organizations.id', $currentOrgId)->first();
        }
        
        // Fallback to first organization
        return $this->organizations()->first();
    }

    /**
     * Check if user has a specific role in the given organization (legacy pivot table method)
     */
    public function hasRoleInOrganization($role, $organizationId)
    {
        return $this->organizations()
            ->where('organization_id', $organizationId)
            ->wherePivot('role', $role)
            ->exists();
    }
    
    /**
     * Check if user is admin in current organization using Spatie permissions
     */
    public function isAdminInCurrentOrganization()
    {
        return $this->hasRole('Admin');
    }
    
    /**
     * Check if user is member in current organization using Spatie permissions
     */
    public function isMemberInCurrentOrganization()
    {
        return $this->hasRole('Member');
    }
    
    /**
     * Check if user can manage the current organization
     */
    public function canManageCurrentOrganization()
    {
        return $this->hasPermissionTo('manage organization');
    }
    
    /**
     * Check if user can perform contact operations
     */
    public function canViewContacts()
    {
        return $this->hasPermissionTo('view contacts');
    }
    
    public function canCreateContacts()
    {
        return $this->hasPermissionTo('create contacts');
    }
    
    public function canEditContacts()
    {
        return $this->hasPermissionTo('edit contacts');
    }
    
    public function canDeleteContacts()
    {
        return $this->hasPermissionTo('delete contacts');
    }
}
