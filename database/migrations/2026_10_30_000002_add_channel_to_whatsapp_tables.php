<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. WhatsApp Conversations - add channel and TFC inquiry/grievance linkages
        if (Schema::hasTable('whatsapp_conversations')) {
            Schema::table('whatsapp_conversations', function (Blueprint $table) {
                if (! Schema::hasColumn('whatsapp_conversations', 'channel')) {
                    $table->string('channel', 30)->default('vanriti')->after('status')->index();
                }
                if (! Schema::hasColumn('whatsapp_conversations', 'tfc_inquiry_id')) {
                    $table->foreignId('tfc_inquiry_id')->nullable()->after('user_id')->constrained('tfc_inquiries')->nullOnDelete();
                }
                if (! Schema::hasColumn('whatsapp_conversations', 'tfc_grievance_id')) {
                    $table->foreignId('tfc_grievance_id')->nullable()->after('tfc_inquiry_id')->constrained('tfc_grievance_tickets')->nullOnDelete();
                }
            });
        }

        // 2. WhatsApp Campaigns - add channel
        if (Schema::hasTable('whatsapp_campaigns')) {
            Schema::table('whatsapp_campaigns', function (Blueprint $table) {
                if (! Schema::hasColumn('whatsapp_campaigns', 'channel')) {
                    $table->string('channel', 30)->default('vanriti')->after('status')->index();
                }
            });
        }

        // 3. WhatsApp Bot Rules - add channel
        if (Schema::hasTable('whatsapp_bot_rules')) {
            Schema::table('whatsapp_bot_rules', function (Blueprint $table) {
                if (! Schema::hasColumn('whatsapp_bot_rules', 'channel')) {
                    $table->string('channel', 30)->default('vanriti')->after('action_type')->index();
                }
            });
        }

        // 4. WhatsApp Quick Replies - add channel
        if (Schema::hasTable('whatsapp_quick_replies')) {
            Schema::table('whatsapp_quick_replies', function (Blueprint $table) {
                if (! Schema::hasColumn('whatsapp_quick_replies', 'channel')) {
                    $table->string('channel', 30)->default('vanriti')->after('category')->index();
                }
            });
        }

        // 5. WhatsApp Templates - add channel
        if (Schema::hasTable('whatsapp_templates')) {
            Schema::table('whatsapp_templates', function (Blueprint $table) {
                if (! Schema::hasColumn('whatsapp_templates', 'channel')) {
                    $table->string('channel', 30)->nullable()->after('category')->index();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('whatsapp_conversations')) {
            Schema::table('whatsapp_conversations', function (Blueprint $table) {
                if (Schema::hasColumn('whatsapp_conversations', 'tfc_grievance_id')) {
                    $table->dropForeign(['tfc_grievance_id']);
                    $table->dropColumn('tfc_grievance_id');
                }
                if (Schema::hasColumn('whatsapp_conversations', 'tfc_inquiry_id')) {
                    $table->dropForeign(['tfc_inquiry_id']);
                    $table->dropColumn('tfc_inquiry_id');
                }
                if (Schema::hasColumn('whatsapp_conversations', 'channel')) {
                    $table->dropColumn('channel');
                }
            });
        }

        if (Schema::hasTable('whatsapp_campaigns')) {
            Schema::table('whatsapp_campaigns', function (Blueprint $table) {
                if (Schema::hasColumn('whatsapp_campaigns', 'channel')) {
                    $table->dropColumn('channel');
                }
            });
        }

        if (Schema::hasTable('whatsapp_bot_rules')) {
            Schema::table('whatsapp_bot_rules', function (Blueprint $table) {
                if (Schema::hasColumn('whatsapp_bot_rules', 'channel')) {
                    $table->dropColumn('channel');
                }
            });
        }

        if (Schema::hasTable('whatsapp_quick_replies')) {
            Schema::table('whatsapp_quick_replies', function (Blueprint $table) {
                if (Schema::hasColumn('whatsapp_quick_replies', 'channel')) {
                    $table->dropColumn('channel');
                }
            });
        }

        if (Schema::hasTable('whatsapp_templates')) {
            Schema::table('whatsapp_templates', function (Blueprint $table) {
                if (Schema::hasColumn('whatsapp_templates', 'channel')) {
                    $table->dropColumn('channel');
                }
            });
        }
    }
};
