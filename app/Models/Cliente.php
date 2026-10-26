<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cliente extends Model
{
    protected $table = 'clientes';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'address' => 'array',
            'raw_data' => 'array',
            'synced_at' => 'datetime',
        ];
    }
}
