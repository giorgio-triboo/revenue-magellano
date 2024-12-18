<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('publishers', function (Blueprint $table) {
            $table->id();
            $table->string('vat_number', 30)->unique();  // Aumentata lunghezza per supportare formato CC + numero
            $table->string('company_name');              // Nome azienda
            $table->string('legal_name');                // Ragione sociale
            $table->string('state')->nullable();                 
            $table->string('state_id')->nullable();
            $table->string('county')->nullable();          
            $table->string('county_id')->nullable();                  
            $table->string('city')->nullable();                    
            $table->string('postal_code', 5)->nullable();         
            $table->string('address', 5)->nullable();          
            $table->string('iban', 27)->nullable();      // IBAN
            $table->string('swift', 11)->nullable();     // SWIFT/BIC code
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Indici per ottimizzare le ricerche comuni
            $table->index('vat_number');
            $table->index('company_name');
        });
    }

    public function down()
    {
        Schema::dropIfExists('publishers');
    }
};

