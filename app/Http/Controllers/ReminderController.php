<?php

namespace App\Http\Controllers;

use App\Http\Presenters\Present;
use App\Models\Reminder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ReminderController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('reminders/index', [
            'reminders' => Reminder::forUser(auth()->user())->orderBy('due_date')->get()->map(Present::reminder(...)),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Reminder::create([
            ...$request->validate([
                'title' => ['required', 'string', 'max:160'],
                'due_date' => ['required', 'date'],
                'due_time' => ['nullable', 'date_format:H:i'],
                'notify_in_app' => ['sometimes', 'boolean'],
            ]),
            'user_id' => auth()->id(),
            'status' => 'pending',
            'notify_in_app' => $request->boolean('notify_in_app', true),
        ]);

        return back()->with('status', 'یادآوری ثبت شد.');
    }

    public function update(Request $request, Reminder $reminder): RedirectResponse
    {
        $this->authorize('update', $reminder);

        $reminder->update($request->validate([
            'status' => ['required', Rule::in(['pending', 'done', 'dismissed'])],
        ]));

        return back()->with('status', 'یادآوری به‌روز شد.');
    }
}
