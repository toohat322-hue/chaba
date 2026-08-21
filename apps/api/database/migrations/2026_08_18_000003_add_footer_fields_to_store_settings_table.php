<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Footer CMS: the About blurb and the WhatsApp floating-button config are
// both store-wide singleton settings — they belong on the same
// store_settings row built for Admin Settings, not a separate table.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->string('about_title_ar')->nullable();
            $table->string('about_title_fr')->nullable();
            $table->string('about_title_en')->nullable();
            $table->text('about_description_ar')->nullable();
            $table->text('about_description_fr')->nullable();
            $table->text('about_description_en')->nullable();
            $table->string('whatsapp_number')->nullable();
            $table->string('whatsapp_message_ar')->nullable();
            $table->string('whatsapp_message_fr')->nullable();
            $table->string('whatsapp_message_en')->nullable();
            $table->boolean('whatsapp_active')->default(true);
        });
    }

    public function down(): void
    {
        Schema::table('store_settings', function (Blueprint $table) {
            $table->dropColumn([
                'about_title_ar', 'about_title_fr', 'about_title_en',
                'about_description_ar', 'about_description_fr', 'about_description_en',
                'whatsapp_number', 'whatsapp_message_ar', 'whatsapp_message_fr', 'whatsapp_message_en',
                'whatsapp_active',
            ]);
        });
    }
};
