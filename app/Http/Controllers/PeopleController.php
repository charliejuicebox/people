<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Audit;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PeopleController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $query = User::active()->with(['department', 'teamLead'])->orderBy('name');

        if (! $user->seesEveryone()) {
            $query->where('team_lead_id', $user->id);
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
        ]);
    }
}
