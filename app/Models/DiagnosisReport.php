<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiagnosisReport extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'user_id', 'crop', 'crop_label', 'disease_name', 'disease_key',
        'probability', 'disease_probability', 'level', 'pathogen',
        'signs_in_photo', 'symptoms', 'treatment', 'prevention',
        'image_path', 'latitude', 'longitude', 'status',
        'verified_at', 'verified_by', 'rejection_reason',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'probability' => 'integer',
        'disease_probability' => 'integer',
        'verified_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isVerified(): bool
    {
        return $this->status === self::STATUS_VERIFIED;
    }

    public function imageUrl(): string
    {
        return asset('storage/'.$this->image_path);
    }
}
