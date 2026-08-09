<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Laravel\Sanctum\PersonalAccessToken;

class ApiTokenController extends Controller
{
    public function index(): View
    {
        $tokens = PersonalAccessToken::query()
            ->latest()
            ->get();
        $summary = [
            'active_tokens' => $tokens->count(),
            'last_request' => $tokens->max('last_used_at'),
            'api_status' => 'Aktif',
        ];

        return view('api-tokens.index', compact('tokens', 'summary'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
        ]);

        $token = $request->user()->createToken($validated['name'], ['sensor:write', 'sensor:read']);

        return back()
            ->with('status', 'Token API berhasil dibuat.')
            ->with('plain_token', $token->plainTextToken);
    }

    public function destroy(PersonalAccessToken $token): RedirectResponse
    {
        $token->delete();

        return back()->with('status', 'Token API berhasil dicabut.');
    }
}
