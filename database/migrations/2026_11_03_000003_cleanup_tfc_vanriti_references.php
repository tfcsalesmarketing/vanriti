<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Update WhatsApp widget default message in tfc_settings
        DB::table('tfc_settings')
            ->where('key', 'whatsapp_widget_default_msg')
            ->update([
                'value' => 'Hello TFC Sales & Marketing, I am interested in learning more about TFC products and the direct selling business opportunity.',
                'updated_at' => now(),
            ]);

        // 2. Remove any TFC menu items linking to Vanriti
        DB::table('tfc_menus')
            ->where('title', 'like', '%vanriti%')
            ->orWhere('url', 'like', '%vanriti%')
            ->delete();

        // 3. Remove any TFC pages mentioning Vanriti
        DB::table('tfc_pages')
            ->where('slug', 'like', '%vanriti%')
            ->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op
    }
};

