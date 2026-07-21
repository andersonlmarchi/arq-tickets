<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagamentoPendente extends Model
{
    public const STATUS_AGUARDANDO = 'aguardando';

    public const STATUS_CONFIRMADO = 'confirmado';

    public const STATUS_EXPIRADO = 'expirado';

    public const STATUS_CANCELADO = 'cancelado';

    public const STATUS_CHAVE_ESGOTADA = 'chave_esgotada';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'evento_id',
        'quantidade',
        'chave_pagamento',
        'tentativas_chave_errada',
        'status',
        'correlation_id',
        'expires_at',
        'venda_id',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    public function venda(): BelongsTo
    {
        return $this->belongsTo(Venda::class);
    }
}
