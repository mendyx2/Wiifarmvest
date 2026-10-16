<?php

namespace App\Models;
 

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;


class Admin extends Authenticatable
{
    
    use HasFactory, Notifiable, HasRoles;


    protected $guard_name = 'admin';

    protected $fillable = [
        'name',
        'username', // Changed from email to username
        'password',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Find admin by username for authentication
     */
    public function findForPassport($username)
    {
        return $this->where('username', $username)->first();
    }

    public function actionLogs()
    {
        return $this->hasMany(AdminActionLog::class);
    }
}
