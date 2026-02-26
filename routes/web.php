<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PartyController;
use App\Http\Controllers\PartyInviteController;
use App\Http\Controllers\PartyMemberController;
use App\Http\Controllers\PartyCharacterController;
use App\Http\Controllers\PartyInventoryItemController;
use App\Http\Controllers\PartyRollController;
use App\Http\Controllers\PartyNpcTradeOfferController;
use App\Http\Controllers\PartyTalentRequestController;
use App\Http\Controllers\PartyTradeSessionController;
use App\Http\Controllers\PartyWalletTransactionController;
use App\Http\Controllers\PartyCharacterHpController;
use App\Http\Controllers\Admin\Auth\AuthenticatedSessionController as AdminAuthenticatedSessionController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\RaceController as AdminRaceController;
use App\Http\Controllers\Admin\CharacterClassController as AdminCharacterClassController;
use App\Http\Controllers\Admin\CharacterController as AdminCharacterController;
use App\Http\Controllers\Admin\MapPointController as AdminMapPointController;
use App\Http\Controllers\Admin\QuestController as AdminQuestController;
use App\Http\Controllers\Admin\TalentController as AdminTalentController;
use App\Http\Controllers\FriendshipController;
use App\Http\Controllers\PublicMediaController;
use App\Models\Friendship;
use App\Models\User;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

$adminPrefix = trim((string) env('ADMIN_PATH_PREFIX', 'xpnp-2709'), '/');
$adminLoginPath = trim((string) env('ADMIN_LOGIN_PATH', 'login'), '/');

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
    $userSearch = trim((string) $request->query('user_search', ''));

    $usersQuery = User::query()
        ->select('id', 'name', 'email')
        ->where('id', '!=', $user->id)
        ->orderBy('name');

    if ($userSearch !== '') {
        $usersQuery->where(function ($query) use ($userSearch) {
            $query->where('name', 'like', "%{$userSearch}%")
                ->orWhere('email', 'like', "%{$userSearch}%");
        });
    }

    $users = $usersQuery->limit(40)->get();
    $userIds = $users->pluck('id');
    $friendshipsByOtherId = Friendship::query()
        ->where(function ($query) use ($user, $userIds) {
            $query
                ->where('requester_id', $user->id)
                ->whereIn('recipient_id', $userIds)
                ->orWhere(function ($nested) use ($user, $userIds) {
                    $nested
                        ->where('recipient_id', $user->id)
                        ->whereIn('requester_id', $userIds);
                });
        })
        ->whereIn('status', [Friendship::STATUS_PENDING, Friendship::STATUS_ACCEPTED])
        ->orderByDesc('id')
        ->get()
        ->mapWithKeys(function (Friendship $entry) use ($user) {
            $otherId = $entry->requester_id === $user->id ? (int) $entry->recipient_id : (int) $entry->requester_id;
            return [$otherId => $entry];
        });

    $pendingFriendRequests = Friendship::query()
        ->with('requester:id,name,email')
        ->where('recipient_id', $user->id)
        ->where('status', Friendship::STATUS_PENDING)
        ->orderByDesc('id')
        ->get();

    $friends = Friendship::query()
        ->with(['requester:id,name,email', 'recipient:id,name,email'])
        ->where('status', Friendship::STATUS_ACCEPTED)
        ->where(function ($query) use ($user) {
            $query->where('requester_id', $user->id)
                ->orWhere('recipient_id', $user->id);
        })
        ->orderByDesc('id')
        ->get()
        ->map(function (Friendship $entry) use ($user) {
            $friend = $entry->requester_id === $user->id ? $entry->recipient : $entry->requester;
            return [
                'id' => $friend?->id,
                'name' => $friend?->name,
                'email' => $friend?->email,
            ];
        })
        ->filter(fn (array $entry) => !empty($entry['id']))
        ->values();

    return Inertia::render('Lobby', [
        'ownedParties' => $user->ownedParties()->select('id', 'name')->get(),
        'memberParties' => $user->parties()
            ->where('parties.owner_id', '!=', $user->id)
            ->select('parties.id', 'parties.name')
            ->get(),
        'inStartedParty' => $user->parties()->whereNotNull('parties.started_at')->exists(),
        'userSearch' => $userSearch,
        'users' => $users->map(function (User $entry) use ($user, $friendshipsByOtherId) {
            $relationship = $friendshipsByOtherId->get((int) $entry->id);
            $relationshipStatus = null;
            if ($relationship instanceof Friendship) {
                if ($relationship->status === Friendship::STATUS_ACCEPTED) {
                    $relationshipStatus = 'accepted';
                } elseif ($relationship->status === Friendship::STATUS_PENDING) {
                    $relationshipStatus = (int) $relationship->requester_id === (int) $user->id
                        ? 'outgoing_pending'
                        : 'incoming_pending';
                }
            }

            return [
                'id' => (int) $entry->id,
                'name' => $entry->name,
                'email' => $entry->email,
                'relationshipStatus' => $relationshipStatus,
            ];
        })->values(),
        'pendingFriendRequests' => $pendingFriendRequests->map(fn (Friendship $entry) => [
            'id' => (int) $entry->id,
            'requester' => [
                'id' => (int) $entry->requester?->id,
                'name' => $entry->requester?->name,
                'email' => $entry->requester?->email,
            ],
            'createdAt' => optional($entry->created_at)?->toISOString(),
        ])->values(),
        'friends' => $friends,
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
    Route::post('/parties/{party}/characters/{partyCharacter}/hp', [PartyCharacterHpController::class, 'update'])
        ->name('parties.characters.hp.update');
    Route::post('/parties/{party}/inventory-items', [PartyInventoryItemController::class, 'store'])
        ->name('parties.inventory-items.store');
    Route::patch('/parties/{party}/inventory-items/{inventoryItem}', [PartyInventoryItemController::class, 'update'])
        ->name('parties.inventory-items.update');
    Route::post('/parties/{party}/inventory-items/{inventoryItem}/use', [PartyInventoryItemController::class, 'use'])
        ->name('parties.inventory-items.use');
    Route::post('/parties/{party}/trades', [PartyTradeSessionController::class, 'store'])
        ->name('parties.trades.store');
    Route::post('/parties/{party}/trades/{tradeSession}/accept', [PartyTradeSessionController::class, 'accept'])
        ->name('parties.trades.accept');
    Route::post('/parties/{party}/npc-trade-offer', [PartyNpcTradeOfferController::class, 'upsert'])
        ->name('parties.npc-trade-offer.upsert');
    Route::post('/parties/{party}/npc-trade-offer/open', [PartyNpcTradeOfferController::class, 'open'])
        ->name('parties.npc-trade-offer.open');
    Route::post('/parties/{party}/npc-trade-offer/close', [PartyNpcTradeOfferController::class, 'close'])
        ->name('parties.npc-trade-offer.close');
    Route::post('/parties/{party}/npc-trade-offer/claim', [PartyNpcTradeOfferController::class, 'claim'])
        ->name('parties.npc-trade-offer.claim');
    Route::post('/parties/{party}/npc-trade-offer/release', [PartyNpcTradeOfferController::class, 'release'])
        ->name('parties.npc-trade-offer.release');
    Route::post('/parties/{party}/npc-trade-offer/buy', [PartyNpcTradeOfferController::class, 'buy'])
        ->name('parties.npc-trade-offer.buy');
    Route::post('/parties/{party}/npc-trade-offer/sell-offers', [PartyNpcTradeOfferController::class, 'storeSellOffer'])
        ->name('parties.npc-trade-offer.sell-offers.store');
    Route::post('/parties/{party}/npc-trade-offer/sell-offers/{sellOffer}/resolve', [PartyNpcTradeOfferController::class, 'resolveSellOffer'])
        ->name('parties.npc-trade-offer.sell-offers.resolve');
    Route::post('/parties/{party}/wallet-transactions', [PartyWalletTransactionController::class, 'store'])
        ->name('parties.wallet-transactions.store');
    Route::delete('/parties/{party}/inventory-items/{inventoryItem}', [PartyInventoryItemController::class, 'destroy'])
        ->name('parties.inventory-items.destroy');
    Route::post('/parties/{party}/rolls', [PartyRollController::class, 'store'])
        ->name('parties.rolls.store');
    Route::post('/parties/{party}/talent-requests', [PartyTalentRequestController::class, 'store'])
        ->name('parties.talent-requests.store');
    Route::post('/parties/{party}/talent-requests/{talentRequest}/confirm', [PartyTalentRequestController::class, 'confirm'])
        ->name('parties.talent-requests.confirm');
    Route::post('/parties/{party}/invites', [PartyInviteController::class, 'regenerate'])
        ->name('parties.invites.regenerate');
    Route::get('/invites/{token}', [PartyInviteController::class, 'join'])
        ->name('parties.invites.join');
    Route::post('/friends/requests', [FriendshipController::class, 'store'])
        ->name('friends.requests.store');
    Route::post('/friends/requests/{friendship}/accept', [FriendshipController::class, 'accept'])
        ->name('friends.requests.accept');
    Route::post('/friends/requests/{friendship}/reject', [FriendshipController::class, 'reject'])
        ->name('friends.requests.reject');
});

Route::prefix($adminPrefix)->name('admin.')->group(function () use ($adminLoginPath) {
    Route::middleware('admin.guest')->group(function () use ($adminLoginPath) {
        Route::get("/{$adminLoginPath}", [AdminAuthenticatedSessionController::class, 'create'])->name('login');
        Route::post("/{$adminLoginPath}", [AdminAuthenticatedSessionController::class, 'store'])->name('login.store');
    });

    Route::middleware('admin.auth')->group(function () {
        Route::get('/dashboard', AdminDashboardController::class)->name('dashboard');
        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::get('/characters', [AdminCharacterController::class, 'index'])->name('characters.index');
        Route::get('/characters/{character}', [AdminCharacterController::class, 'show'])->name('characters.show');
        Route::get('/map', [AdminMapPointController::class, 'index'])->name('map.index');
        Route::post('/map', [AdminMapPointController::class, 'store'])->name('map.store');
        Route::post('/map/{point}', [AdminMapPointController::class, 'update'])->name('map.update');
        Route::post('/map/{point}/toggle', [AdminMapPointController::class, 'toggle'])->name('map.toggle');
        Route::post('/map/{point}/delete', [AdminMapPointController::class, 'destroy'])->name('map.destroy');
        Route::get('/quests', [AdminQuestController::class, 'index'])->name('quests.index');
        Route::post('/quests', [AdminQuestController::class, 'store'])->name('quests.store');
        Route::get('/quests/{quest}/edit', [AdminQuestController::class, 'edit'])->name('quests.edit');
        Route::post('/quests/{quest}', [AdminQuestController::class, 'update'])->name('quests.update');
        Route::post('/quests/{quest}/toggle', [AdminQuestController::class, 'toggle'])->name('quests.toggle');
        Route::post('/quests/{quest}/delete', [AdminQuestController::class, 'destroy'])->name('quests.destroy');
        Route::get('/talents', [AdminTalentController::class, 'index'])->name('talents.index');
        Route::post('/talents', [AdminTalentController::class, 'store'])->name('talents.store');
        Route::get('/talents/{talent}/edit', [AdminTalentController::class, 'edit'])->name('talents.edit');
        Route::post('/talents/{talent}', [AdminTalentController::class, 'update'])->name('talents.update');
        Route::post('/talents/{talent}/toggle', [AdminTalentController::class, 'toggle'])->name('talents.toggle');
        Route::post('/talents/{talent}/delete', [AdminTalentController::class, 'destroy'])->name('talents.destroy');
        Route::get('/races', [AdminRaceController::class, 'index'])->name('races.index');
        Route::post('/races', [AdminRaceController::class, 'store'])->name('races.store');
        Route::get('/races/{race}/edit', [AdminRaceController::class, 'edit'])->name('races.edit');
        Route::post('/races/{race}', [AdminRaceController::class, 'update'])->name('races.update');
        Route::post('/races/{race}/toggle', [AdminRaceController::class, 'toggle'])->name('races.toggle');
        Route::get('/classes', [AdminCharacterClassController::class, 'index'])->name('classes.index');
        Route::post('/classes', [AdminCharacterClassController::class, 'store'])->name('classes.store');
        Route::get('/classes/{characterClass}/edit', [AdminCharacterClassController::class, 'edit'])->name('classes.edit');
        Route::post('/classes/{characterClass}', [AdminCharacterClassController::class, 'update'])->name('classes.update');
        Route::post('/classes/{characterClass}/toggle', [AdminCharacterClassController::class, 'toggle'])->name('classes.toggle');
        Route::post('/logout', [AdminAuthenticatedSessionController::class, 'destroy'])->name('logout');
    });
});

require __DIR__.'/auth.php';
