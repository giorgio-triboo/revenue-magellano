<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('file_uploads', function (Blueprint $table) {
            // Indice su status per velocizzare i filtri per stato
            $table->index('status', 'file_uploads_status_index');
            
            // Indice su created_at per velocizzare l'ordinamento latest()
            $table->index('created_at', 'file_uploads_created_at_index');
            
            // Indice composito su user_id e created_at per le query dei publisher
            // che filtrano per user_id e ordinano per created_at
            $table->index(['user_id', 'created_at'], 'file_uploads_user_created_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('file_uploads', function (Blueprint $table) {
            $table->dropIndex('file_uploads_status_index');
            $table->dropIndex('file_uploads_created_at_index');
            $table->dropIndex('file_uploads_user_created_index');
        });
    }
};
