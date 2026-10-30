<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_tree_nodes', function (Blueprint $table) {
            $table->foreignId('ancestor_id')->constrained('direct_selling_members')->cascadeOnDelete();
            $table->foreignId('descendant_id')->constrained('direct_selling_members')->cascadeOnDelete();
            $table->unsignedInteger('depth');
            $table->enum('group_leg', ['orange', 'blue', 'self'])->index();
            $table->timestamps();

            $table->primary(['ancestor_id', 'descendant_id'], 'pk_member_tree_nodes');
            $table->index(['ancestor_id', 'group_leg', 'depth'], 'idx_ancestor_leg_depth');
            $table->index(['descendant_id', 'depth'], 'idx_descendant_depth');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_tree_nodes');
    }
};
