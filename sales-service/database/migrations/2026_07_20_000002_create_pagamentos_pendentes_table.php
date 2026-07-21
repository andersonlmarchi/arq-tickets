<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagamentos_pendentes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('evento_id');
            $table->unsignedInteger('quantidade');
            $table->string('chave_pagamento', 4);
            $table->unsignedTinyInteger('tentativas_chave_errada')->default(0);
            $table->string('status', 32);
            $table->uuid('correlation_id')->nullable();
            $table->timestamp('expires_at');
            $table->foreignId('venda_id')->nullable()->constrained('vendas');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagamentos_pendentes');
    }
};
