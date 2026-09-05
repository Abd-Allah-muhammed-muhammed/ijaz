<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $token
 * @property string $field
 * @property string $path
 * @property string $original_name
 * @property string $mime_type
 * @property int $size
 * @property Carbon|null $created_at
 */
class ProviderRegistrationUpload extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'token',
        'field',
        'path',
        'original_name',
        'mime_type',
        'size',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'created_at' => 'datetime',
        ];
    }
}
