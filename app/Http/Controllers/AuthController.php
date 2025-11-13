<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    // Show login form
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('login');
    }

    // Handle login
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        // Check if user exists and is active (including soft-deleted users)
        $user = User::withTrashed()->where('email', $validated['email'])->first();
        
        if ($user) {
            // Check if account is deactivated or soft-deleted
            if ($user->trashed() || !$user->is_active) {
                return back()->withErrors(['loginError' => 'This account has been deactivated. Please contact an administrator.']);
            }
        }

        if (Auth::attempt(['email' => $validated['email'], 'password' => $validated['password'], 'is_active' => true], true)) {
            $request->session()->regenerate();
            
            // Log login activity
            $user = Auth::user();
            if ($user) {
                ActivityLogger::logLogin($user->id, $user->email, $request);
            }
            
            // Check if user came from QR code scan
            if ($request->session()->has('qr_scan') && isset($request->session()->get('qr_scan')['document_id'])) {
                $documentId = $request->session()->get('qr_scan')['document_id'];
                $request->session()->forget('qr_scan');
                return redirect()->route('documents.view', $documentId);
            }
            
            return redirect()->route('dashboard');
        }

        return back()->withErrors(['loginError' => 'Invalid username or password']);
    }

    // Logout
    public function logout(Request $request)
    {
        // Log logout activity before logging out
        $user = Auth::user();
        if ($user) {
            ActivityLogger::logLogout($user->id, $user->email, $request);
        }
        
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
