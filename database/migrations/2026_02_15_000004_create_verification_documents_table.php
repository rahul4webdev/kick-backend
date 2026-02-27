<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verification_documents', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('user_id');
            $table->string('document_type', 50); // 'aadhaar', 'pan_card', 'passport', 'business_license'
            $table->string('document_url', 999);
            $table->smallInteger('status')->default(0); // 0=pending, 1=verified, 2=rejected
            $table->text('rejection_reason')->nullable();
            $table->timestampTz('verified_at')->nullable();
            $table->timestampsTz();

            $table->foreign('user_id')->references('id')->on('tbl_users')->onDelete('cascade');
        });

        DB::statement('CREATE INDEX idx_verification_docs_user ON verification_documents (user_id, status)');
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_documents');
    }
};
