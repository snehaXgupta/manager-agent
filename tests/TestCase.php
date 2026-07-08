<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Create a developer token for a user and return the raw token.
     */
    protected function createDeveloperTokenForUser(\App\Models\User $user, string $name = 'Test Token'): string
    {
        $rawToken = 'ma_live_test_' . \Illuminate\Support\Str::random(40);
        $tokenHash = hash('sha256', $rawToken);
        $tokenEncrypted = \Illuminate\Support\Facades\Crypt::encryptString($rawToken);

        \App\Models\DeveloperToken::create([
            'user_id' => $user->id,
            'name' => $name,
            'token_hash' => $tokenHash,
            'token_encrypted' => $tokenEncrypted,
        ]);

        return $rawToken;
    }
}
