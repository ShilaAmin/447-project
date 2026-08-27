<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\UserController;
use App\Http\Controllers\ItemController;
use App\Http\Controllers\ExchangeRequestController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\CommunityController;



// Smart home: if logged in → /dashboard, else → /login
Route::get('/', function () {
    if (session()->has('user_id')) {
        return redirect('/dashboard');
    }
    return redirect('/login');
})->name('home');


// Auth
Route::get('/signup', [UserController::class, 'showSignupForm']);
Route::post('/signup', [UserController::class, 'signup']);
Route::get('/login', [UserController::class, 'showLoginForm']);
Route::post('/login', [UserController::class, 'login']);
Route::get('/dashboard', [UserController::class, 'dashboard']);
Route::get('/logout', [UserController::class, 'logout']);

// Profile Settings
Route::get('/profile', [UserController::class, 'editProfile'])->name('profile.edit');
Route::put('/profile', [UserController::class, 'updateProfile'])->name('profile.update');

// Items
Route::get('/items/create', [ItemController::class, 'create']);
Route::post('/items', [ItemController::class, 'store']);
Route::get('/items/search', [ItemController::class, 'searchForm'])->name('items.search.form');
Route::get('/items/search/results', [ItemController::class, 'searchResults'])->name('items.search.results');
Route::post('/items/{id}/request', [ExchangeRequestController::class, 'store']);

// My Items + Edit/Update/Delete
Route::get('/my-items', [ItemController::class, 'myItems'])->name('items.mine');
Route::get('/items/{id}/edit', [ItemController::class, 'edit'])->name('items.edit');
Route::put('/items/{id}', [ItemController::class, 'update'])->name('items.update');
Route::delete('/items/{id}', [ItemController::class, 'destroy'])->name('items.destroy');

// Requests (incoming for owners)
Route::get('/requests', [ExchangeRequestController::class, 'index']);
Route::post('/requests/{id}/accept', [ExchangeRequestController::class, 'accept']);
Route::post('/requests/{id}/decline', [ExchangeRequestController::class, 'decline']);
Route::post('/requests/{id}/complete', [ExchangeRequestController::class, 'complete']);

// My Requests (outgoing)
Route::get('/my-requests', [ExchangeRequestController::class, 'myRequests'])->name('requests.mine');

// Notifications
Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
Route::get('/notifications/{id}/open', [NotificationController::class, 'open'])->name('notifications.open');
Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
Route::post('/notifications/mark-all', [NotificationController::class, 'markAll'])->name('notifications.markAll');

// ADMIN (email-checked inside controller)
Route::get('/admin/users', [AdminController::class, 'usersIndex'])->name('admin.users');
Route::post('/admin/users/{id}/delete', [AdminController::class, 'deleteUser'])->name('admin.users.delete');
Route::post('/admin/users/{id}/warn',   [AdminController::class, 'warnUser'])->name('admin.users.warn');
Route::get('/admin/items', [AdminController::class, 'itemsIndex'])->name('admin.items');
Route::get('/admin/stats', [AdminController::class, 'stats'])->name('admin.stats');

// Community
Route::get('/community', [CommunityController::class, 'index'])->name('community.index');
Route::post('/community', [CommunityController::class, 'store'])->name('community.store');
Route::get('/community/{id}/edit', [CommunityController::class, 'edit'])->name('community.edit');
Route::put('/community/{id}', [CommunityController::class, 'update'])->name('community.update');
Route::delete('/community/{id}', [CommunityController::class, 'destroy'])->name('community.destroy');

// Comments
Route::post('/community/{postId}/comments', [CommunityController::class, 'storeComment'])->name('community.comments.store');

// Negotiation (Offers & Counter-Offers)
Route::get('/requests/{id}/negotiate', [ExchangeRequestController::class, 'negotiate'])->name('requests.negotiate');
Route::post('/requests/{id}/offers', [ExchangeRequestController::class, 'sendOffer'])->name('requests.offers.send');
Route::post('/offers/{offerId}/accept', [ExchangeRequestController::class, 'acceptOffer'])->name('offers.accept');
Route::post('/offers/{offerId}/decline', [ExchangeRequestController::class, 'declineOffer'])->name('offers.decline');
