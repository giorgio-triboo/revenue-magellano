<?php
// 2024_11_09_172559_create_statements_tables.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('statements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('file_upload_id')->nullable()->constrained();
            $table->foreignId('publisher_id')->constrained();
            $table->foreignId('sub_publisher_id')->constrained();
            $table->string('campaign_name');
            
            // Date di consuntivo
            $table->year('statement_year');
            $table->unsignedTinyInteger('statement_month');
            
            // Date di competenza
            $table->year('competence_year');
            $table->unsignedTinyInteger('competence_month');
            
            // Dati di revenue
            $table->enum('revenue_type', ['cpl', 'cpc', 'cpm', 'tmk', 'crg', 'cpa', 'sms']);
            $table->integer('validated_quantity');
            $table->decimal('pay_per_unit', 10, 2);
            $table->decimal('total_amount', 12, 2);
            
            // Nuovi campi
            $table->string('notes')->nullable();
            $table->string('sending_date')->nullable();
            
            // Stati e metadati
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->json('raw_data')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            // Indici ottimizzati
            $table->index(['statement_year', 'statement_month', 'publisher_id']);
            $table->index(['competence_year', 'competence_month']);
            $table->index('campaign_name');
            $table->index('sub_publisher_id');
            $table->index('revenue_type');
            $table->index('is_published');
        });
    }

    public function down()
    {
        Schema::dropIfExists('statements');
    }
};