<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use Notifiable;

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
            'password'          => 'hashed',
        ];
    }

    public function companies()
    {
        return $this->belongsToMany(Company::class)->withTimestamps();
    }

    public function preference()
    {
        return $this->hasOne(UserPreference::class);
    }
    public function roles()
    {
        return $this->belongsToMany(Role::class)->withTimestamps();
    }
    public function assignRole($role, $companyId = null)
    {
        if (is_string($role)) {
            $role = Role::where('name', $role)->firstOrFail();
        }

        $companyId = $companyId ?? auth()->user()->preference->company_id;

        // Use attach with pivot data to preserve existing assignments
        $this->roles()->attach($role->id, ['company_id' => $companyId]);
    }
    public function hasRoleInCompany(string $roleName, int $companyId): bool
    {
        return $this->roles()
            ->where('name', $roleName)
            ->wherePivot('company_id', $companyId)
            ->exists();
    }
    public function employee()
    {
        return $this->hasOne(Employee::class);
    }
    public function hasPermission(string $permissionName): bool
    {
        $companyId = $this->preference?->company_id;

        if (!$companyId) {
            return false;
        }

        return $this->roles()
            ->wherePivot('company_id', $companyId)
            ->with('permissions')
            ->get()
            ->flatMap->permissions
            ->where('company_id', $companyId)
            ->pluck('name')
            ->contains($permissionName);
    }
    public function rolesForCompany($companyId)
    {
        return $this->roles()->wherePivot('company_id', $companyId)->get();
    }
    /**
     * Check if the user has a role by name, optionally scoped to a company.
     *
     * @param string $roleName
     * @param int|null $companyId
     * @return bool
     */
    public function hasRole(string $roleName, int $companyId = null): bool
    {
        $query = $this->roles()->where('name', $roleName);

        if ($companyId) {
            $query->wherePivot('company_id', $companyId);
        }

        return $query->exists();
    }
    public function hasAnyRole($roles, $companyId = null)
    {
        $roles = is_array($roles) ? $roles : [$roles];
        $companyId = $companyId ?? $this->preference->company_id ?? null;

        return $this->roles()
            ->wherePivot('company_id', $companyId)
            ->whereIn('name', $roles)
            ->exists();
    }
    public function hasAnyPermission(array $permissions): bool
    {
        $companyId = $this->preference?->company_id;

        if (!$companyId) {
            return false;
        }

        return $this->roles()
            ->wherePivot('company_id', $companyId)
            ->with('permissions')
            ->get()
            ->flatMap->permissions
            ->where('company_id', $companyId)
            ->pluck('name')
            ->intersect($permissions)
            ->isNotEmpty();
    }
}
