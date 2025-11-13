<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            'Administration',
            'Finance',
            'Procurement',
            'HR',
            'IT',
            'Education',
        ];

        $created = [];
        foreach ($departments as $name) {
            $created[$name] = Department::firstOrCreate(['name' => $name]);
        }

        // Assign existing users by role to departments deterministically
        if ($admin = User::where('role', 'admin')->first()) {
            $admin->department_id = $created['Administration']->id;
            $admin->save();
        }
        if ($owner = User::where('role', 'owner')->first()) {
            $owner->department_id = $created['Procurement']->id;
            $owner->save();
        }
        if ($handler = User::where('role', 'handler')->first()) {
            $handler->department_id = $created['Procurement']->id;
            $handler->save();
        }
        if ($auditor = User::where('role', 'auditor')->first()) {
            $auditor->department_id = $created['Administration']->id;
            $auditor->save();
        }

        // Assign specific IT and Education handlers/owners if present
        $itDept = $created['IT'];
        $eduDept = $created['Education'];

        $itHandler = User::where('email', 'it.handler@example.com')->first();
        if ($itHandler) { $itHandler->department_id = $itDept->id; $itHandler->save(); }

        $eduHandler = User::where('email', 'educ.handler@example.com')->first();
        if ($eduHandler) { $eduHandler->department_id = $eduDept->id; $eduHandler->save(); }

        $itOwner = User::where('email', 'it.owner@example.com')->first();
        if ($itOwner) { $itOwner->department_id = $itDept->id; $itOwner->save(); }

        $eduOwner = User::where('email', 'educ.owner@example.com')->first();
        if ($eduOwner) { $eduOwner->department_id = $eduDept->id; $eduOwner->save(); }
    }
}


