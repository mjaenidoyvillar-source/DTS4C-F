<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DepartmentController extends Controller
{
    public function index(Request $request)
    {
        $departments = Department::withCount('users')->orderBy('name')->paginate(20);

        // Return JSON for API requests
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'data' => $departments->map(function($department) {
                    return [
                        'id' => $department->id,
                        'name' => $department->name,
                        'users_count' => $department->users_count,
                        'created_at' => $department->created_at->format('Y-m-d H:i:s'),
                        'updated_at' => $department->updated_at->format('Y-m-d H:i:s'),
                    ];
                }),
                'current_page' => $departments->currentPage(),
                'last_page' => $departments->lastPage(),
                'per_page' => $departments->perPage(),
                'total' => $departments->total(),
            ]);
        }

        return view('admin.departments.index', compact('departments'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255|unique:departments,name',
        ]);

        $department = Department::create($data);

        // Return JSON for API requests
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => 'Department created successfully',
                'department' => [
                    'id' => $department->id,
                    'name' => $department->name,
                ],
            ], 201);
        }

        return redirect()->route('admin.departments.index')->with('status', 'Department created successfully');
    }

    public function update(Request $request, Department $department)
    {
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('departments', 'name')->ignore($department->id),
            ],
        ]);

        $department->update($data);

        // Return JSON for API requests
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => 'Department updated successfully',
                'department' => [
                    'id' => $department->id,
                    'name' => $department->name,
                ],
            ]);
        }

        return redirect()->route('admin.departments.index')->with('status', 'Department updated successfully');
    }

    public function destroy(Request $request, Department $department)
    {
        // Check if department has users
        if ($department->users()->count() > 0) {
            $message = 'Cannot delete department. It has ' . $department->users()->count() . ' user(s) assigned.';
            
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => $message,
                ], 422);
            }

            return redirect()->route('admin.departments.index')
                ->with('error', $message);
        }

        $department->delete();

        // Return JSON for API requests
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => 'Department deleted successfully',
            ]);
        }

        return redirect()->route('admin.departments.index')->with('status', 'Department deleted successfully');
    }
}

