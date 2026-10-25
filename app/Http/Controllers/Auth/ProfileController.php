<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\EmailChangeRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        $pending = EmailChangeRequest::where('user_id', $request->user()->id)
            ->where('expires_at', '>', now())->first();

        return view('profile.staff', [
            'pendingEmail' => $pending?->new_email,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^[0-9+()\s-]{7,25}$/'],
        ], [
            'phone.regex' => 'Escribe un teléfono válido.',
        ]);

        $request->user()->fill($data)->save();

        return redirect()->route('profile.show')->with('status', 'Tus datos se actualizaron correctamente.');
    }
}
