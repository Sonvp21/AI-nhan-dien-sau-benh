<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DiagnosisReport extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_VERIFIED = 'verified';

    public const STATUS_REJECTED = 'rejected';

    protected $fillable = [
        'user_id', 'sender_name', 'crop', 'crop_label', 'disease_name', 'disease_key',
        'probability', 'disease_probability', 'level', 'pathogen',
        'signs_in_photo', 'symptoms', 'treatment', 'prevention',
        'image_path', 'latitude', 'longitude', 'status',
        'verified_at', 'verified_by', 'rejection_reason', 'resolved_at',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'probability' => 'integer',
        'disease_probability' => 'integer',
        'verified_at' => 'datetime',
        'resolved_at' => 'datetime',
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

    public function isResolved(): bool
    {
        return $this->resolved_at !== null;
    }

    /**
     * Report đang "hoạt động" trên bản đồ dịch bệnh (cả public và dashboard
     * vùng dịch admin): đã verified VÀ chưa được đánh dấu xử lý.
     */
    public function scopeActiveOnMap($query)
    {
        return $query->where('status', self::STATUS_VERIFIED)->whereNull('resolved_at');
    }

    public function imageUrl(): string
    {
        return asset('storage/'.$this->image_path);
    }

    /**
     * Tên hiển thị của người gửi: ưu tiên tài khoản nếu tình cờ có đăng nhập
     * (dữ liệu cũ từ trước khi bỏ yêu cầu đăng nhập), không thì lấy tên tự
     * nhập trong form (sender_name), cuối cùng mới rơi về "Ẩn danh".
     */
    public function senderDisplayName(): string
    {
        return $this->user->name ?? $this->sender_name ?? 'Ẩn danh';
    }
}
