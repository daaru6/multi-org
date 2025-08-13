<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\ContactNoteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\OrganizationController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::get('/', function () {
    return view('welcome');
});

// Health check endpoint
Route::get('/healthz', [HealthController::class, 'check'])->name('health.check');

// Test logout functionality
Route::get('/test-logout', function () {
    if (!auth()->check()) {
        return 'Please login first';
    }
    
    $token = csrf_token();
    return '
    <form method="POST" action="' . route('logout') . '">
        <input type="hidden" name="_token" value="' . $token . '">
        <button type="submit">Test Logout</button>
    </form>
    <p>Current user: ' . auth()->user()->name . '</p>
    <p>CSRF Token: ' . $token . '</p>
    ';
})->middleware('auth');

// Authentication routes will be added when Breeze is installed
require __DIR__.'/auth.php';

// Protected routes
Route::middleware(['auth', 'verified'])->group(function () {
    
    // Organization management routes (not scoped)
    Route::resource('organizations', OrganizationController::class);
    Route::post('organizations/{organization}/switch', [OrganizationController::class, 'switch'])
        ->name('organizations.switch');
    
    // Organization-scoped routes
    Route::middleware('set.current.organization')->group(function () {
        
        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
            
        // Organization-specific routes with slug
        Route::prefix('{organization}')->group(function () {
            // Contacts management
            Route::resource('contacts', ContactController::class);
            Route::get('contacts/{contact}/duplicate', [ContactController::class, 'duplicate'])
                ->name('contacts.duplicate');
            
            // Contact notes
            Route::post('contacts/{contact}/notes', [ContactNoteController::class, 'store'])
                ->name('contacts.notes.store');
            Route::delete('contacts/{contact}/notes/{note}', [ContactNoteController::class, 'destroy'])
                ->name('contacts.notes.destroy');
        });
    });
});

// Profile routes
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
