<?php

namespace App\Http\Controllers;

use App\Models\QuarterlyReview;
use App\Models\User;
use App\Notifications\BelowExpectationsFlagged;
use App\Notifications\ReviewShared;
use App\Services\Audit;
use App\Support\Quarter;
use App\Support\Ratings;
use App\Support\Values;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReviewController extends Controller
{
    /** Start (or reopen) this quarter's review for a person. */
    public function start(Request $request, User $person): RedirectResponse
    {
        $this->authorize('create', [QuarterlyReview::class, $person]);

        $quarter = Quarter::current();
        $review = QuarterlyReview::firstOrCreate(
            ['employee_id' => $person->id, 'year' => $quarter->year, 'quarter' => $quarter->quarter],
            ['reviewer_id' => $request->user()->id, 'status' => 'draft'],
        );

        if ($review->wasRecentlyCreated) {
            Audit::log('created', $review);
        }

        return redirect()->route('reviews.show', $review);
    }

    public function show(Request $request, QuarterlyReview $review): View
    {
        $this->authorize('view', $review);
        Audit::log('viewed', $review);

        return view('reviews.show', [
            'review' => $review->load(['employee.department', 'reviewer']),
            'previous' => $review->previousReview(),
            'values' => Values::ALL,
            'ratings' => Ratings::OVERALL,
            'user' => $request->user(),
        ]);
    }

    public function update(Request $request, QuarterlyReview $review): RedirectResponse
    {
        $user = $request->user();

        if ($user->can('updateAsLead', $review)) {
            $data = $request->validate($this->leadRules());
            $review->fill($data);
        } elseif ($user->can('updateAsEmployee', $review)) {
            $data = $request->validate([
                'self_reflection' => ['nullable', 'string', 'max:10000'],
                'employee_comment' => ['nullable', 'string', 'max:10000'],
            ]);
            $review->fill($data);
        } else {
            abort(403);
        }

        $changed = array_keys($review->getDirty());
        $review->save();

        if ($changed) {
            // Log which fields changed, not their contents.
            Audit::log('updated', $review, ['fields' => $changed]);
        }

        return back()->with('status', __('Saved.'));
    }

    public function share(Request $request, QuarterlyReview $review): RedirectResponse
    {
        $this->authorize('share', $review);

        $request->validate(['confirm_expectation_communicated' => ['nullable', 'boolean']]);

        foreach (['overall_rating', 'expectations_next_quarter'] as $required) {
            if (blank($review->{$required})) {
                return back()->withErrors(['share' => __('Add an overall rating and expectations for next quarter before sharing.')]);
            }
        }

        if ($review->needsNoSurprisesConfirmation()) {
            if (! $request->boolean('confirm_expectation_communicated')) {
                return back()->withErrors(['share' => __('This is a below-expectations rating and the last review raised no concern. Confirm the expectation was clearly communicated before sharing.')]);
            }

            $review->expectation_communicated_confirmed = true;
            $review->flagged_to_hr_at = now();
            Notification::send(User::active()->where('role', 'hr')->get(), new BelowExpectationsFlagged($review));
            Audit::log('flagged_to_hr', $review, ['reason' => 'below_without_prior_concern']);
        }

        $review->status = 'shared';
        $review->shared_at = now();
        $review->save();

        $review->employee->notify(new ReviewShared($review));
        Audit::log('shared', $review);

        return redirect()->route('reviews.show', $review)->with('status', __('Shared with :name.', ['name' => $review->employee->firstName()]));
    }

    public function acknowledge(Request $request, QuarterlyReview $review): RedirectResponse
    {
        $this->authorize('acknowledge', $review);

        $data = $request->validate(['employee_comment' => ['nullable', 'string', 'max:10000']]);

        $review->fill($data);
        $review->status = 'acknowledged';
        $review->acknowledged_at = now();
        $review->save();

        Audit::log('acknowledged', $review);

        return redirect()->route('reviews.show', $review)->with('status', __('Thanks. Your review is acknowledged.'));
    }

    private function leadRules(): array
    {
        $rules = [
            'went_well' => ['nullable', 'string', 'max:10000'],
            'concerns' => ['nullable', 'string', 'max:10000'],
            'expectations_next_quarter' => ['nullable', 'string', 'max:10000'],
            'flexibility_check' => ['nullable', 'string', 'max:5000'],
            'overall_rating' => ['nullable', Rule::in(Ratings::keys())],
            'discussed_on' => ['nullable', 'date'],
        ];

        foreach (Values::keys() as $value) {
            $rules["{$value}_rating"] = ['nullable', Rule::in(Ratings::keys())];
            $rules["{$value}_comment"] = ['nullable', 'string', 'max:5000'];
        }

        return $rules;
    }
}
