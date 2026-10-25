<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name', 'phone', 'email', 'password', 'role_id', 'active',
        'account_type', 'approval_status', 'approved_at', 'approved_by', 'must_change_password',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'approved_at' => 'datetime',
            'password' => 'hashed',
            'active' => 'boolean',
            'is_super_root' => 'boolean',
            'must_change_password' => 'boolean',
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function isStaff(): bool
    {
        return $this->account_type === 'staff';
    }

    public function isApproved(): bool
    {
        return ! $this->trashed() && $this->active && $this->approval_status === 'approved';
    }

    public function allows(string $permission): bool
    {
        if (! $this->isApproved() || ! $this->isStaff()) {
            return false;
        }

        return $this->is_super_root || $this->role?->allows($permission) === true;
    }

    public function homeRoute(): string
    {
        if ($this->allows('panel.view')) {
            return 'dashboard';
        }

        if ($this->allows('users.manage') || $this->allows('roles.manage')) {
            return 'admin.users.index';
        }

        return 'dashboard';
    }
}
