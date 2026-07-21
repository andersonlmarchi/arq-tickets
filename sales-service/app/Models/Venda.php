<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Venda extends Model
{
    public const UPDATED_AT = null;

    protected $fillable = [
        'evento_id',
        'quantidade',
        'status',
        'correlation_id',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function pagamentoPendente(): HasOne
    {
        return $this->hasOne(PagamentoPendente::class);
    }
}
