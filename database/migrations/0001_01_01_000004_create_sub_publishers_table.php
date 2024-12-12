<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('sub_publishers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('publisher_id')->constrained()->onDelete('cascade');
            $table->string('display_name');         // Nome visualizzato nell'interfaccia
            $table->string('invoice_group');        // Gruppo di fatturazione (legal_name del publisher)
            $table->string('ax_name'); 
            $table->string('channel_detail')->nullable();
            $table->text('notes')->nullable();      // Note interne per admin/operativi
            $table->boolean('is_active')->default(true);
            $table->boolean('is_primary')->default(false); // Flag per identificare il sub-publisher principale
            $table->timestamps();
            $table->softDeletes();

            // Indici
            $table->index(['publisher_id', 'is_active']);
            $table->index('is_primary');
        });
    }

    public function down()
    {
        Schema::dropIfExists('sub_publishers');
    }
};