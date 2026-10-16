<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationLog extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function notifyingAdmin()
    {
        return $this->belongsTo(Admin::class, 'sent_by_admin_id');
    }
}
