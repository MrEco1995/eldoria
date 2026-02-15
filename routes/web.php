<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PartyController;
use App\Http\Controllers\PartyInviteController;
use App\Http\Controllers\PartyMemberController;
use App\Http\Controllers\PartyCharacterController;
use App\Http\Controllers\PartyRollController;
use App\Http\Controllers\PublicMediaController;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::redirect('/login-blade', '/login')->name('login.blade');
Route::redirect('/register-blade', '/register')->name('register.blade');

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
})->name('welcome');

Route::get('/eldoria/geschichte', function () {
    return Inertia::render('Lore/History');
})->name('lore.history');

Route::get('/media/public/{path}', [PublicMediaController::class, 'show'])
    ->where('path', '.*')
    ->name('media.public');

Route::get('/lobby', function (Request $request) {
    $user = $request->user();

    return Inertia::render('Lobby', [
        'ownedParties' => $user->ownedParties()->select('id', 'name')->get(),
        'memberParties' => $user->parties()
            ->where('parties.owner_id', '!=', $user->id)
            ->select('parties.id', 'parties.name')
            ->get(),
        'inStartedParty' => $user->parties()->whereNotNull('parties.started_at')->exists(),
    ]);
})->middleware(['auth', 'verified'])->name('lobby');

Route::middleware(['auth', 'verified'])->get('/dashboard', function () {
    return redirect()->route('lobby');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/parties/create', [PartyController::class, 'create'])->name('parties.create');
    Route::post('/parties', [PartyController::class, 'store'])->name('parties.store');
    Route::get('/parties/{party}', [PartyController::class, 'show'])->name('parties.show');
    Route::get('/parties/{party}/started', [PartyController::class, 'started'])->name('parties.started');
    Route::post('/parties/{party}/start', [PartyController::class, 'start'])->name('parties.start');
    Route::post('/parties/{party}/end', [PartyController::class, 'end'])->name('parties.end');
    Route::post('/parties/{party}/close', [PartyController::class, 'close'])->name('parties.close');
    Route::post('/parties/{party}/ready', [PartyMemberController::class, 'toggleReady'])
        ->name('parties.ready.toggle');
    Route::post('/parties/{party}/leave', [PartyMemberController::class, 'leave'])
        ->name('parties.leave');
    Route::post('/parties/{party}/members/{userId}/remove', [PartyMemberController::class, 'remove'])
        ->name('parties.members.remove');
    Route::post('/parties/{party}/characters', [PartyCharacterController::class, 'store'])
        ->name('parties.characters.store');
    Route::post('/parties/{party}/rolls', [PartyRollController::class, 'store'])
        ->name('parties.rolls.store');
    Route::post('/parties/{party}/invites', [PartyInviteController::class, 'regenerate'])
        ->name('parties.invites.regenerate');
    Route::get('/invites/{token}', [PartyInviteController::class, 'join'])
        ->name('parties.invites.join');
});

require __DIR__.'/auth.php';
