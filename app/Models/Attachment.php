<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    protected $table = 'attachments';

    protected $fillable = [
        'agency_id',
        'attachable_type',
        'attachable_id',
        'original_name',
        'stored_name',
        'mime_type',
        'size_bytes',
        'storage_disk',
        'storage_path',
        'created_by',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
    ];

    public function attachable()
    {
        return $this->morphTo();
    }

    public function isImage(): bool
    {
        $m = strtolower((string) $this->mime_type);
        return str_starts_with($m, 'image/');
    }

    public function isPdf(): bool
    {
        return strtolower((string) $this->mime_type) === 'application/pdf';
    }

    public function safeDisplayName(int $limit = 28): string
    {
        $name = (string) $this->original_name;
        if (mb_strlen($name) <= $limit) return $name;
        return mb_substr($name, 0, $limit - 1) . '…';
    }
}
