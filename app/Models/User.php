<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'username', 'firstname', 'lastname', 'employee_number',
        'email', 'phone', 'role', 'designation', 'is_blocked',
        'google2fa_secret', 'mfa_enabled', 'password',
        'must_change_password', 'created_by',
    ];

    protected $hidden = ['password', 'remember_token', 'google2fa_secret'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_blocked' => 'boolean',
            'mfa_enabled' => 'boolean',
            'must_change_password' => 'boolean',
        ];
    }

    public function getFullNameAttribute(): string
    {
        return $this->firstname . ' ' . $this->lastname;
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isHod(): bool
    {
        return $this->role === 'hod';
    }

    public function isTechnician(): bool
    {
        return $this->role === 'technician';
    }

    public function isAdminOrHod(): bool
    {
        return in_array($this->role, ['super_admin', 'hod']);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function workshopTasks()
    {
        return $this->hasMany(WorkshopEquipmentRegister::class, 'technician_assigned');
    }

    public function comments()
    {
        return $this->hasMany(TaskComment::class, 'commented_by');
    }

    public function otpTokens()
    {
        return $this->hasMany(OtpToken::class);
    }
}
