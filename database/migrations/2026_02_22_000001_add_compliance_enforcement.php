<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add date_of_birth + consent fields to users
        Schema::table('tbl_users', function (Blueprint $table) {
            $table->date('date_of_birth')->nullable();
            $table->boolean('is_minor')->default(false);
            $table->timestamp('terms_accepted_at')->nullable();
            $table->string('terms_version', 20)->nullable();
            $table->timestamp('privacy_accepted_at')->nullable();
            $table->string('privacy_version', 20)->nullable();
        });

        // 2. Consent audit trail
        Schema::create('tbl_consent_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('consent_type', 50); // terms, privacy, data_processing, marketing, cookies
            $table->string('version', 20);
            $table->string('action', 20); // accepted, withdrawn
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->timestampsTz();

            $table->foreign('user_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->index(['user_id', 'consent_type']);
            $table->index('created_at');
        });

        // 3. Grievance system (IT Rules 2021 - mandatory)
        Schema::create('tbl_grievances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('ticket_number', 20)->unique();
            $table->string('category', 50); // content_removal, account_issue, privacy, harassment, other
            $table->string('subject');
            $table->text('description');
            $table->string('attachment')->nullable();
            $table->smallInteger('status')->default(0); // 0=received, 1=acknowledged, 2=in_progress, 3=resolved, 4=closed
            $table->smallInteger('priority')->default(1); // 1=low, 2=medium, 3=high, 4=urgent
            $table->text('resolution_notes')->nullable();
            $table->unsignedBigInteger('assigned_to')->nullable();
            $table->timestamp('acknowledged_at')->nullable(); // Must be within 24 hours
            $table->timestamp('resolved_at')->nullable(); // Must be within 15 days (30 for complex)
            $table->timestamp('deadline_at')->nullable(); // Auto-set: 15 days from creation
            $table->timestampsTz();

            $table->foreign('user_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->index('user_id');
            $table->index('status');
            $table->index('ticket_number');
            $table->index('deadline_at');
        });

        // 4. Grievance responses (communication thread)
        Schema::create('tbl_grievance_responses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('grievance_id');
            $table->unsignedBigInteger('responder_id'); // admin/GRO user_id or user_id
            $table->boolean('is_admin')->default(false);
            $table->text('message');
            $table->string('attachment')->nullable();
            $table->timestampsTz();

            $table->foreign('grievance_id')->references('id')->on('tbl_grievances')->onDelete('cascade');
            $table->index('grievance_id');
        });

        // 5. Appeal mechanism for moderation decisions
        Schema::create('tbl_appeals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('appeal_type', 30); // post_removal, account_ban, account_freeze, violation
            $table->unsignedBigInteger('reference_id')->nullable(); // post_id, violation_id, etc.
            $table->text('reason');
            $table->text('additional_context')->nullable();
            $table->smallInteger('status')->default(0); // 0=pending, 1=under_review, 2=upheld, 3=overturned, 4=partially_overturned
            $table->text('decision_notes')->nullable();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestampsTz();

            $table->foreign('user_id')->references('id')->on('tbl_users')->onDelete('cascade');
            $table->index('user_id');
            $table->index('status');
            $table->index(['appeal_type', 'reference_id']);
        });

        // 6. GRO settings in tbl_settings
        Schema::table('tbl_settings', function (Blueprint $table) {
            $table->string('gro_name', 100)->nullable(); // Grievance Redressal Officer name
            $table->string('gro_email', 100)->nullable();
            $table->string('gro_phone', 20)->nullable();
            $table->string('gro_address')->nullable();
            $table->string('terms_version', 20)->default('1.0');
            $table->string('privacy_version', 20)->default('1.0');
            $table->integer('minimum_age')->default(13);
            $table->integer('grievance_deadline_days')->default(15);
        });

        // 7. Content takedown notices (IT Rules 2021 - 72 hour response)
        Schema::create('tbl_takedown_notices', function (Blueprint $table) {
            $table->id();
            $table->string('notice_number', 20)->unique();
            $table->string('source', 30); // government, court_order, user_report, auto_moderation
            $table->unsignedBigInteger('target_post_id')->nullable();
            $table->unsignedBigInteger('target_user_id')->nullable();
            $table->text('reason');
            $table->text('legal_reference')->nullable();
            $table->smallInteger('status')->default(0); // 0=received, 1=reviewed, 2=actioned, 3=rejected
            $table->timestamp('action_deadline')->nullable(); // 72 hours for government orders
            $table->text('action_taken')->nullable();
            $table->unsignedBigInteger('actioned_by')->nullable();
            $table->timestamp('actioned_at')->nullable();
            $table->timestampsTz();

            $table->index('status');
            $table->index('action_deadline');
        });

        // 8. Data retention policy tracking
        Schema::create('tbl_data_retention_logs', function (Blueprint $table) {
            $table->id();
            $table->string('data_type', 50); // notifications, login_sessions, reports, deleted_accounts, chat_messages
            $table->integer('records_purged')->default(0);
            $table->integer('retention_days');
            $table->timestampsTz();

            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_data_retention_logs');
        Schema::dropIfExists('tbl_takedown_notices');
        Schema::dropIfExists('tbl_appeals');
        Schema::dropIfExists('tbl_grievance_responses');
        Schema::dropIfExists('tbl_grievances');
        Schema::dropIfExists('tbl_consent_logs');

        Schema::table('tbl_users', function (Blueprint $table) {
            $table->dropColumn(['date_of_birth', 'is_minor', 'terms_accepted_at', 'terms_version', 'privacy_accepted_at', 'privacy_version']);
        });

        Schema::table('tbl_settings', function (Blueprint $table) {
            $table->dropColumn(['gro_name', 'gro_email', 'gro_phone', 'gro_address', 'terms_version', 'privacy_version', 'minimum_age', 'grievance_deadline_days']);
        });
    }
};
