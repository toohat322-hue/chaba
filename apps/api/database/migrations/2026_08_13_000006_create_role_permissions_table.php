<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// PRD §12: role_permissions — role_id (FK), permission_id (FK). Composite PK.
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->foreignUuid('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignUuid('permission_id')->constrained('permissions')->cascadeOnDelete();

            $table->primary(['role_id', 'permission_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
    }
};
