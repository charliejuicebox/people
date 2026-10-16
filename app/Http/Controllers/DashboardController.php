<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Praise;
use App\Models\QuarterlyReview;
use App\Models\User;
use App\Support\Quarter;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $quarter = Quarter::current();

        $scope = $this->peopleInScope($user);
        $reviews = QuarterlyReview::where('year', $quarter->year)->where('quarter', $quarter->quarter)
            ->whereIn('employee_id', $scope->pluck('id'))
            ->get()->keyBy('employee_id');

        $rows = $scope->map(fn (User $person) => [
            'person' => $person,
            'review' => $reviews->get($person->id),
        ]);

        return view('dashboard.index', [
            'user' => $user,
            'quarter' => $quarter,
            'daysLeft' => (int) now(config('people.timezone'))->diffInDays($quarter->end(), false),
            'rows' => $rows,
            'summary' => $this->summarise($rows),
            'byDepartment' => $user->seesEveryone() ? $this->byGroup($rows, fn ($r) => $r['person']->department?->name ?? '—') : collect(),
            'byEntity' => $user->seesEveryone() ? $this->byGroup($rows, fn ($r) => $r['person']->entity) : collect(),
            'myReviews' => $user->reviews()->where('status', '!=', 'draft')->orderByDesc('year')->orderByDesc('quarter')->limit(4)->get(),
            'toAcknowledge' => $user->reviews()->where('status', 'shared')->get(),
            'recentPraise' => Praise::with(['author', 'recipient'])->latest()->limit(5)->get(),
            'departments' => Department::orderBy('name')->get(),
        ]);
    }

    /** People whose quarterly review this user is responsible for, or oversees. */
    private function peopleInScope(User $user): Collection
    {
        $query = User::active()->with(['department', 'teamLead'])->orderBy('name');

        if ($user->seesEveryone()) {
            return $query->whereNotNull('team_lead_id')->get();
        }

        return $query->where('team_lead_id', $user->id)->get();
    }

    private function summarise(Collection $rows): array
    {
        $total = $rows->count();
        $acknowledged = $rows->filter(fn ($r) => $r['review']?->isAcknowledged())->count();
        $shared = $rows->filter(fn ($r) => $r['review']?->isShared())->count();
        $draft = $rows->filter(fn ($r) => $r['review']?->isDraft())->count();

        return [
            'total' => $total,
            'acknowledged' => $acknowledged,
            'shared' => $shared,
            'draft' => $draft,
            'not_started' => $total - $acknowledged - $shared - $draft,
            'percent' => $total ? (int) round($acknowledged / $total * 100) : 0,
        ];
    }

    private function byGroup(Collection $rows, callable $key): Collection
    {
        return $rows->groupBy($key)->map(fn (Collection $group) => $this->summarise($group))->sortKeys();
    }
}
