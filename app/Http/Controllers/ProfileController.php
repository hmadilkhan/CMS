<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Services\NotificationTemplateService;
use App\Traits\MediaTrait;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    use MediaTrait;

    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $user = $request->user();

        $user->fill([
            'name' => $validated['name'] ?? $user->name,
            'email' => $validated['email'] ?? $user->email,
        ]);

        // Explicitly set email_preference from the request
        $user->email_preference = $request->boolean('email_preference', true);

        // Per-email switches. With the master off the inputs are disabled and not sent,
        // so the stored choices are kept; a form that never carried them changes nothing.
        if ($user->email_preference && $request->has('email_templates_submitted')) {
            $switchedOn = array_keys(array_filter((array) $request->input('email_templates', [])));
            $user->email_opt_outs = array_values(array_diff(NotificationTemplateService::userSwitchableKeys(), $switchedOn));
        }

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        if ($request->hasFile('file')) {
            $result = $this->uploads($request->file('file'), 'users/', $user->image);
            $user->image = $result['fileName'] ?? $user->image;
        }

        $user->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    public function logout()
    {
         auth()->logout();
         return redirect("/");
    }
}
