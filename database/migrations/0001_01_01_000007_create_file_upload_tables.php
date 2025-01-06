<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('file_uploads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('original_filename');
            $table->string('stored_filename');
            $table->string('ax_export_path')->nullable();
            $table->string('ax_export_status')->nullable();
            $table->string('sftp_status')->nullable();
            $table->string('sftp_error_message')->nullable();
            $table->timestamp('sftp_uploaded_at')->nullable();
            $table->string('mime_type');
            $table->integer('file_size');
            $table->integer('total_records')->nullable();
            $table->integer('processed_records')->default(0);
            $table->decimal('progress_percentage', 5, 2)->default(0);
            $table->json('processing_stats')->nullable();
            $table->enum('status', ['pending', 'processing', 'completed', 'error', 'published'])->default('pending');
            $table->text('error_message')->nullable();
            $table->date('process_date');
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('notification_sent_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

       
    }

    public function down()
    {
        Schema::dropIfExists('file_uploads');
    }
};
