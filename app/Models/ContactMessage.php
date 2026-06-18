<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    protected $fillable = [
        'name',
        'company',
        'email',
        'phone',
        'message',
        'locale',
        'ip_address',
    ];
}
