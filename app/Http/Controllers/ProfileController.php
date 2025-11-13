<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function update(Request $request)
    {
        $user = Auth::user();

        // Explicitly reject email updates - only admins can change emails
        if ($request->has('email')) {
            return response()->json([
                'message' => 'Email cannot be changed. Please contact an administrator to change your email address.',
                'errors' => ['email' => ['Email changes must be made by an administrator']]
            ], 422);
        }

        $validationRules = [
            'name' => 'nullable|string|max:255',
            'current_password' => 'nullable|string',
            'password' => 'nullable|string|min:6|confirmed',
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ];

        $data = $request->validate($validationRules);

        // Verify current password if changing password
        if (!empty($data['password'])) {
            if (empty($data['current_password']) || !Hash::check($data['current_password'], $user->password)) {
                return response()->json([
                    'message' => 'Current password is incorrect',
                    'errors' => ['current_password' => ['The current password is incorrect']]
                ], 422);
            }
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        unset($data['current_password'], $data['password_confirmation']);

        // Handle profile picture upload
        if ($request->hasFile('profile_picture')) {
            // Delete old profile picture if exists
            if ($user->profile_picture) {
                $oldPath = $user->profile_picture;
                // Ensure path is correct
                if (!str_starts_with($oldPath, 'profile-pictures/')) {
                    $oldPath = 'profile-pictures/' . basename($oldPath);
                }
                if (Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
            }

            // Store new profile picture
            $path = $request->file('profile_picture')->store('profile-pictures', 'public');
            $data['profile_picture'] = $path;
        }

        // Only update name if provided, otherwise keep existing name
        if (isset($data['name'])) {
            $data['name'] = trim($data['name']);
            if (empty($data['name'])) {
                unset($data['name']); // Don't update if empty after trimming
            }
        }

        // If no data to update, return early
        if (empty($data)) {
            return response()->json([
                'message' => 'No changes to update',
            ], 200);
        }

        // Track what changed for activity log
        $changes = [];
        if (isset($data['name'])) {
            $changes[] = 'name';
        }
        if (isset($data['password'])) {
            $changes[] = 'password';
            ActivityLogger::logPasswordChange($user->id, $user->email, $request);
        }
        if (isset($data['profile_picture'])) {
            $changes[] = 'profile picture';
        }
        
        $user->update($data);
        $user->refresh(); // Refresh to get updated attributes

        // Log profile update activity (excluding password change which is logged separately)
        if (!isset($data['password']) && !empty($changes)) {
            ActivityLogger::logProfileUpdate($user->id, $user->email, $changes, $request);
        }

        // Always return JSON for API-based application
        $profilePictureUrl = $user->profile_picture_url;
        
        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'department_id' => $user->department_id,
                'profile_picture' => $profilePictureUrl,
            ],
        ]);
    }

    public function show(Request $request)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json(['message' => 'Unauthenticated'], 401);
                }
                return redirect()->route('login');
            }

            // Safely get profile picture URL
            $profilePictureUrl = null;
            try {
                $profilePictureUrl = $user->profile_picture_url;
            } catch (\Exception $e) {
                // If there's an error getting the URL, just set it to null
                \Log::warning('Error getting profile picture URL: ' . $e->getMessage());
                $profilePictureUrl = null;
            }

            // Always return JSON for API-based application
            return response()->json([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'department_id' => $user->department_id,
                    'profile_picture' => $profilePictureUrl,
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('ProfileController show error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'message' => 'An error occurred while loading profile',
                    'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
                ], 500);
            }
            
            return redirect()->back()->with('error', 'An error occurred while loading profile');
        }
    }

    /**
     * Remove profile picture and revert to default (no picture)
     */
    public function removeProfilePicture(Request $request)
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }

            // Check if user has a profile picture
            if (!$user->profile_picture) {
                return response()->json([
                    'message' => 'No profile picture to remove',
                ], 200);
            }

            // Delete the file from storage
            $path = $user->profile_picture;
            // Ensure the path is correct
            if (!str_starts_with($path, 'profile-pictures/')) {
                $path = 'profile-pictures/' . basename($path);
            }

            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }

            // Set profile_picture to null in database
            $user->update(['profile_picture' => null]);
            $user->refresh();

            return response()->json([
                'message' => 'Profile picture removed successfully',
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'department_id' => $user->department_id,
                    'profile_picture' => null, // Now null (default)
                ],
            ]);
        } catch (\Exception $e) {
            \Log::error('ProfileController removeProfilePicture error: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'An error occurred while removing profile picture',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Serve profile picture file directly
     */
    public function serveProfilePicture($filename)
    {
        // Normalize the path - ensure it's in profile-pictures directory
        $filename = basename($filename); // Security: prevent directory traversal
        $path = 'profile-pictures/' . $filename;
        
        // Check if file exists
        if (!Storage::disk('public')->exists($path)) {
            // Try to find the file by checking all files in profile-pictures directory
            // This handles cases where the path might be stored differently
            $files = Storage::disk('public')->files('profile-pictures');
            $found = false;
            foreach ($files as $file) {
                if (basename($file) === $filename) {
                    $path = $file;
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                abort(404, 'Profile picture not found');
            }
        }

        // Get the file
        $file = Storage::disk('public')->get($path);
        $mimeType = Storage::disk('public')->mimeType($path);

        return response($file, 200)
            ->header('Content-Type', $mimeType)
            ->header('Cache-Control', 'public, max-age=31536000'); // Cache for 1 year
    }


    /**
     * Helper method to get profile picture URL
     */
    private function getProfilePictureUrl($profilePicturePath)
    {
        if (!$profilePicturePath) {
            return null;
        }

        // Ensure the path is correct
        $path = $profilePicturePath;
        // If path doesn't start with 'profile-pictures/', add it
        if (!str_starts_with($path, 'profile-pictures/')) {
            $path = 'profile-pictures/' . basename($path);
        }

        // Check if file exists
        if (Storage::disk('public')->exists($path)) {
            // Try storage URL first
            $storageUrl = Storage::disk('public')->url($path);
            
            // If symlink exists, use it. Otherwise, use route to serve file
            // Check if public/storage symlink exists by trying to access it
            $publicPath = public_path('storage/' . $path);
            if (file_exists($publicPath) || is_link(public_path('storage'))) {
                return $storageUrl;
            }
            
            // Fallback: use route to serve file directly
            return route('profile.picture', ['filename' => basename($path)]);
        }

        return null;
    }
}
