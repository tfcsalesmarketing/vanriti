<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_kyc', function (Blueprint $table) {
            $table->id();
            $table->foreignId('direct_selling_member_id')->index()->constrained('direct_selling_members')->cascadeOnDelete();
            $table->enum('document_type', ['pan', 'aadhaar', 'voter_id', 'passport'])->default('pan');
            $table->text('document_number'); // Encrypted at rest
            $table->string('document_number_last4', 4)->nullable()->index(); // Masked display: XXXXXXXX1234
            $table->string('front_image_path', 500); // Stored in private disk
            $table->string('back_image_path', 500)->nullable(); // Stored in private disk
            $table->text('pan_number')->nullable(); // Encrypted at rest
            $table->string('pan_number_last4', 4)->nullable();
            $table->string('pan_image_path', 500)->nullable();
            
            // Banking columns (nullable, reserved for future approved payout process)
            $table->string('bank_name', 150)->nullable();
            $table->text('account_number')->nullable();
            $table->string('account_number_last4', 4)->nullable();
            $table->string('ifsc_code', 20)->nullable();
            $table->string('account_holder_name', 150)->nullable();

            $table->enum('status', ['submitted', 'under_review', 'approved', 'rejected', 'resubmission_required'])->default('submitted')->index();
            $table->text('rejection_reason')->nullable();
            $table->text('admin_remarks')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('submitted_at')->useCurrent();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_kyc');
    }
};
