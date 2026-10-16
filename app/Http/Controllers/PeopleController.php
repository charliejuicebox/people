<?php

namespace App\Http\Controllers;

use App\Models\RecordAccess;
use App\Models\User;
use App\Services\Audit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PeopleController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $query = User::active()->with(['department', 'teamLead'])->orderBy('name');

        if (! $user->seesEveryone()) {
            $query->where(fn ($q) => $q->where('team_lead_id', $user->id)
                ->orWhereIn('id', $user->sharedAccess()->select('person_id')));
        }

        if ($request->filled('entity')) {
            $query->where('entity', $request->string('entity'));
        }
        if ($request->filled('department')) {
            $query->where('department_id', $request->integer('department'));
        }

        return view('people.index', ['people' => $query->get(), 'user' => $user]);
    }

    public function show(Request $request, User $person): View
    {
        abort_unless($request->user()->canSeeRecordsOf($person), 403);

        $viewer = $request->user();
        Audit::log('viewed_profile', $person);

        $reviews = $person->reviews()->with('reviewer')
            ->orderByDesc('year')->orderByDesc('quarter')->get()
            ->filter(fn ($review) => $viewer->can('view', $review));

        return view('people.show', [
            'person' => $person->load(['department', 'teamLead']),
            'reviews' => $reviews,
            'feedback' => $person->feedbackReceived()->with('author')->latest()->get(),
            'praise' => $person->praiseReceived()->with('author')->latest()->get(),
            'canReview' => $viewer->can('create', [\App\Models\QuarterlyReview::class, $person]),
            'accessGrants' => $person->accessGrants()->with(['user', 'grantedBy'])->get(),
            'grantable' => $viewer->managesAccess()
                ? User::active()->whereNotIn('id', [$person->id, (int) $person->team_lead_id])
                    ->whereNotIn('id', $person->accessGrants()->select('user_id'))->orderBy('name')->get()
                : collect(),
        ]);
    }

    public function grantAccess(Request $request, User $person): RedirectResponse
    {
        abort_unless($request->user()->managesAccess(), 403);

        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id', Rule::notIn([$person->id])],
            'reason' => ['nullable', 'string', 'max:100'],
        ]);

        $grant = RecordAccess::updateOrCreate(
            ['person_id' => $person->id, 'user_id' => $data['user_id']],
            ['reason' => $data['reason'] ?? null, 'granted_by' => $request->user()->id],
        );
        Audit::log('granted_access', $person, ['user_id' => $grant->user_id, 'user' => $grant->user->name, 'reason' => $grant->reason]);

        return back()->with('status', __(':name can now see :person\'s reviews and feedback.', ['name' => $grant->user->name, 'person' => $person->name]));
    }

    public function revokeAccess(Request $request, User $person, RecordAccess $grant): RedirectResponse
    {
        abort_unless($request->user()->managesAccess() && $grant->person_id === $person->id, 403);

        $grant->delete();
        Audit::log('revoked_access', $person, ['user_id' => $grant->user_id, 'user' => $grant->user->name]);

        return back()->with('status', __(':name no longer has access to :person\'s records.', ['name' => $grant->user->name, 'person' => $person->name]));
    }
}
