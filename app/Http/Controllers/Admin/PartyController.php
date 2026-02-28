<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Party;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PartyController extends Controller
{
    public function index(): View
    {
        $parties = Party::query()
            ->with('owner:id,name,email')
            ->withCount(['members', 'characters'])
            ->orderByDesc('id')
            ->paginate(25);

        return view('admin.parties.index', [
            'parties' => $parties,
        ]);
    }

    public function edit(Party $party): View
    {
        $party->load([
            'owner:id,name,email',
            'members:id,name,email',
            'characters.user:id,name',
        ]);

        $users = User::query()
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        return view('admin.parties.edit', [
            'party' => $party,
            'users' => $users,
        ]);
    }

    public function update(Request $request, Party $party): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'owner_id' => ['required', 'integer', Rule::exists('users', 'id')],
            'status' => ['required', Rule::in(['lobby', 'started'])],
            'started_at' => ['nullable', 'date'],
        ]);

        $startedAt = null;
        if ($data['status'] === 'started') {
            $startedAt = !empty($data['started_at']) ? $data['started_at'] : now();
        }

        $party->update([
            'name' => $data['name'],
            'owner_id' => (int) $data['owner_id'],
            'started_at' => $startedAt,
        ]);

        $party->members()->syncWithoutDetaching([(int) $data['owner_id']]);

        return redirect()
            ->route('admin.parties.edit', $party)
            ->with('status', 'Party aktualisiert.');
    }
}
