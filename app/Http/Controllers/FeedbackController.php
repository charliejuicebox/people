<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use App\Models\User;
use App\Notifications\FeedbackReceived;
use App\Services\Audit;
use App\Support\Values;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class FeedbackController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('feedback.index', [
            'received' => $user->feedbackReceived()->with('author')->latest()->get(),
            'given' => Feedback::with('recipient')->where('author_id', $user->id)->latest()->get(),
            'people' => User::active()->where('id', '!=', $user->id)->orderBy('name')->get(),
            'values' => Values::ALL,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'recipient_id' => ['required', Rule::exists('users', 'id')->where('active', true), Rule::notIn([$request->user()->id])],
            'value_tag' => ['nullable', Rule::in(Values::keys())],
            'body' => ['required', 'string', 'min:10', 'max:5000'],
        ]);

        $feedback = Feedback::create($data + ['author_id' => $request->user()->id]);
        $feedback->recipient->notify(new FeedbackReceived($feedback));
        Audit::log('created', $feedback);

        return back()->with('status', __('Feedback sent to :name.', ['name' => $feedback->recipient->firstName()]));
    }
}
