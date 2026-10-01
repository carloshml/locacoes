<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'avatar',
        'phone',
        'position',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'email_verified_at',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'is_active' => 'boolean',
        'password' => 'hashed',
    ];

    public function profile()
    {
        return $this->hasOne(UserProfile::class);
    }

    public function activityLogs()
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByRole($query, $role)
    {
        return $query->where('role', $role);
    }

    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isManager()
    {
        return in_array($this->role, ['admin', 'manager']);
    }

    public function hasPermission($permission)
    {
        $permissions = [
            'admin' => ['*'],
            'manager' => ['users.view', 'users.create', 'users.edit', 'users.delete', 'reports.view'],
            'user' => ['profile.edit', 'pessoas.view', 'pessoas.create', 'pessoas.edit'],
        ];

        $userPermissions = $permissions[$this->role] ?? $permissions['user'];

        if (in_array('*', $userPermissions)) {
            return true;
        }

        return in_array($permission, $userPermissions);
    }

    public function getRoleBadgeAttribute()
    {
        $badges = [
            'admin' => '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Administrador</span>',
            'manager' => '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">Gerente</span>',
            'user' => '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Usuário</span>',
        ];

        return $badges[$this->role] ?? $badges['user'];
    }

    public function getStatusBadgeAttribute()
    {
        if ($this->is_active) {
            return '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Ativo</span>';
        }
        return '<span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Inativo</span>';
    }
}