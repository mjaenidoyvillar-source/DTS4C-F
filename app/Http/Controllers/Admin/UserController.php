<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::withTrashed()->with('department');
        
        // Filter by role
        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }
        
        // Filter by department
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->input('department_id'));
        }
        
        $users = $query->orderBy('name')->paginate(20)->withQueryString();
        $departments = Department::orderBy('name')->get();

        // Return JSON for API requests
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'data' => $users->map(function($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name ?? $user->email,
                        'email' => $user->email,
                        'role' => $user->role,
                        'department' => $user->department->name ?? null,
                        'department_id' => $user->department_id,
                        'is_active' => $user->is_active ?? true,
                        'deleted_at' => $user->deleted_at ? $user->deleted_at->format('Y-m-d H:i:s') : null,
                        'created_at' => $user->created_at->format('Y-m-d H:i:s'),
                    ];
                }),
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'departments' => $departments->map(fn($d) => ['id' => $d->id, 'name' => $d->name]),
            ]);
        }

        return view('admin.users.index', compact('users', 'departments'));
    }

    public function store(Request $request)
    {
        $validationRules = [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|in:admin,owner,handler,auditor',
            'department_id' => [
                'nullable',
                'exists:departments,id',
                Rule::requiredIf(fn () => in_array($request->input('role'), ['owner','handler','auditor'])),
            ],
        ];
        
        $data = $request->validate($validationRules);

        $data['password'] = Hash::make($data['password']);
        
        // Trim and ensure name is not empty
        $data['name'] = trim($data['name']);
        if (empty($data['name'])) {
            return redirect()->back()->withErrors(['name' => 'Name field cannot be empty.'])->withInput();
        }
        
        $user = User::create($data);

        // Return JSON for API requests
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => 'User created successfully',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name ?? $user->email,
                    'email' => $user->email,
                    'role' => $user->role,
                    'department_id' => $user->department_id,
                ],
            ], 201);
        }

        return redirect()->route('admin.users.index')->with('status', 'User created');
    }

    public function update(Request $request, User $user)
    {
        // Allow updating email and department
        $data = $request->validate([
            'email' => [
                'sometimes',
                'email',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'department_id' => [
                'nullable',
                'exists:departments,id',
            ],
        ]);

        $user->update($data);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => 'User updated successfully',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'department_id' => $user->department_id,
                ],
            ]);
        }

        return redirect()->route('admin.users.index')->with('status', 'User updated successfully');
    }

    public function deactivate(Request $request, $userId)
    {
        // Get user (route model binding might not work if user is soft-deleted, so use find directly)
        $user = User::withTrashed()->findOrFail($userId);
        
        // Prevent deactivation of administrator accounts
        if ($user->role === 'admin') {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'Administrator accounts cannot be deactivated.',
                    'error' => 'Administrator accounts are protected from deactivation.'
                ], 403);
            }

            return redirect()->route('admin.users.index')
                ->with('error', 'Administrator accounts cannot be deactivated.');
        }
        
        // Set inactive and soft delete
        $user->update(['is_active' => false]);
        if (!$user->trashed()) {
            $user->delete(); // Soft delete
        }

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => 'User deactivated successfully',
            ]);
        }

        return redirect()->route('admin.users.index')->with('status', 'User deactivated successfully');
    }

    public function activate(Request $request, $userId)
    {
        // Get user including trashed (route model binding doesn't work with soft deletes)
        $user = User::withTrashed()->findOrFail($userId);
        
        // Restore if soft deleted and activate
        if ($user->trashed()) {
            $user->restore();
        }
        $user->update(['is_active' => true]);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => 'User activated successfully',
            ]);
        }

        return redirect()->route('admin.users.index')->with('status', 'User activated successfully');
    }

    public function destroy(Request $request, User $user)
    {
        // Permanently delete the user
        $user->forceDelete();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'message' => 'User permanently deleted',
            ]);
        }

        return redirect()->route('admin.users.index')->with('status', 'User permanently deleted');
    }
}

