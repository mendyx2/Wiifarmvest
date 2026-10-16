<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdminActionLog extends Model
{
    protected $fillable = [
        'admin_id',
        'admin_name',
        'admin_username',
        'admin_role',
        'method',
        'route_name',
        'uri',
        'action',
        'subject_type',
        'subject_id',
        'description',
        'meta',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'meta' => 'object',
    ];

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }
}
