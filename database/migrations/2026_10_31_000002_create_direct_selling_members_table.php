<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('direct_selling_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('member_id', 32)->unique()->index();
            $table->foreignId('sponsor_id')->nullable()->index()->constrained('direct_selling_members')->nullOnDelete();
            $table->foreignId('placement_parent_id')->nullable()->index()->constrained('direct_selling_members')->nullOnDelete();
            $table->enum('placement_position', ['orange', 'blue'])->nullable()->index();
            $table->date('registration_date')->index();
            $table->enum('membership_status', ['pending', 'active', 'suspended', 'terminated'])->default('active')->index();
            $table->enum('activation_status', ['inactive', 'active'])->default('inactive')->index();
            $table->enum('kyc_status', ['not_submitted', 'submitted', 'under_review', 'approved', 'rejected', 'resubmission_required'])->default('not_submitted')->index();
            $table->enum('mobile_verification_status', ['unverified', 'verified'])->default('unverified')->index();
            $table->string('registered_ip', 45)->nullable();
            $table->timestamp('first_login_at')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();

            // Crucial database constraint: strictly 1 member per placement position per placement parent!
            $table->unique(['placement_parent_id', 'placement_position'], 'uq_tfc_placement_slot');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('direct_selling_members');
    }
};
