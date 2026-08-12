<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteSession extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'session_id', 'first_seen_at', 'last_seen_at',
    ];

    protected $casts = [
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    /**
     * Xem đang "online" là còn hoạt động trong 5 phút gần nhất.
     */
    public const ONLINE_WINDOW_MINUTES = 5;
}
