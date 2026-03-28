<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    protected $fillable = ['name', 'original_filename', 'content', 'embedding', 'total_chunks'];

    protected function casts(): array
    {
        return [
            'embedding' => 'array',
        ];
    }
}
