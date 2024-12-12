<?php 
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
        public function up()
    {
        Schema::create('ax_data', function (Blueprint $table) {
            $table->id();
            $table->foreignId('publisher_id')->constrained('publishers')->onDelete('cascade');
            $table->string('ax_vend_account')->nullable();
            $table->string('ax_vend_id')->nullable();
            $table->string('country_id')->nullable();
            $table->string('vend_group')->nullable();
            $table->string('party_type')->nullable();
            $table->string('tax_withhold_calculate')->nullable();
            $table->string('item_id')->nullable();
            $table->string('email')->nullable();
            $table->string('cost_profit_center')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ax_data');
    }
};

