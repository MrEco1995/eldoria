<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::query()
            ->select('id', 'name', 'email', 'created_at')
            ->with([
                'ownedParties:id,name,owner_id,started_at',
                'parties:id,name,owner_id,started_at',
            ])
            ->orderByDesc('id')
            ->paginate(25);

        return view('admin.users.index', [
            'users' => $users,
        ]);
    }
}
