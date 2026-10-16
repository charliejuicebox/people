<?php

namespace App\Http\Controllers;

use App\Jobs\PostPraiseToSlack;
use App\Models\Praise;
use App\Models\User;
use App\Services\Audit;
use App\Support\Values;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PraiseController extends Controller
{
    public function index(Request $request): View
    {
        return view('praise.index', [
            'praise' => Praise::with(['author', 'recipient'])->latest()->paginate(20),
            'people' => User::active()->where('id', '!=', $request->user()->id)->orderBy('name')->get(),
            'values' => Values::ALL,
            'channel' => config('people.slack.praise_channel'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'recipient_id' => ['required', Rule::exists('users', 'id')->where('active', true), Rule::notIn([$request->user()->id])],
            'value_tag' => ['required', Rule::in(Values::keys())],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        $praise = Praise::create($data + ['author_id' => $request->user()->id]);
        Audit::log('created', $praise);

        PostPraiseToSlack::dispatch($praise);

        return back()->with('status', __('Praise sent! It will appear in :channel.', ['channel' => config('people.slack.praise_channel')]));
    }
}
