<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Ensure default departments exist
        $adminDeptId = DB::table('departments')->where('name', 'Administration')->value('id');
        if (!$adminDeptId) {
            $adminDeptId = DB::table('departments')->insertGetId(['name' => 'Administration', 'created_at' => now(), 'updated_at' => now()]);
        }
        $procDeptId = DB::table('departments')->where('name', 'Procurement')->value('id');
        if (!$procDeptId) {
            $procDeptId = DB::table('departments')->insertGetId(['name' => 'Procurement', 'created_at' => now(), 'updated_at' => now()]);
        }

        // Owners and handlers -> Procurement (if null)
        DB::table('users')
            ->whereNull('department_id')
            ->whereIn('role', ['owner','handler'])
            ->update(['department_id' => $procDeptId]);

        // Auditors -> Administration (if null)
        DB::table('users')
            ->whereNull('department_id')
            ->where('role', 'auditor')
            ->update(['department_id' => $adminDeptId]);
        // Admins remain null
    }

    public function down(): void
    {
        // No-op (do not unset departments during rollback)
    }
};


