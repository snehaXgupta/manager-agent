<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\DeveloperToken;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Carbon;

class DeveloperController extends Controller
{
    /**
     * Display the developer tools dashboard.
     */
    public function index()
    {
        $tokens = DeveloperToken::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($token) {
                try {
                    $token->raw_token = Crypt::decryptString($token->token_encrypted);
                } catch (\Exception $e) {
                    $token->raw_token = 'Error decrypting key';
                }
                return $token;
            });

        return view('dashboard.developer.index', compact('tokens'));
    }

    /**
     * Generate a new developer API key.
     */
    public function store(Request $request)
    {
        // Generate a premium prefixed key, e.g. ma_live_...
        $rawToken = 'ma_live_' . Str::random(40);
        $tokenHash = hash('sha256', $rawToken);
        $tokenEncrypted = Crypt::encryptString($rawToken);

        DeveloperToken::create([
            'user_id' => auth()->id(),
            'name' => 'API Key', // default name
            'token_hash' => $tokenHash,
            'token_encrypted' => $tokenEncrypted,
        ]);

        return redirect()->route('dashboard.developer.index')->with('success', 'API Key generated successfully.');
    }

    /**
     * Revoke / Delete a developer API key.
     */
    public function destroy($id)
    {
        $token = DeveloperToken::where('user_id', auth()->id())->findOrFail($id);
        $token->delete();

        return redirect()->route('dashboard.developer.index')->with('success', 'API Key revoked successfully.');
    }
}
