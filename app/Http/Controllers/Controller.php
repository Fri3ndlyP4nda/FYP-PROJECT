<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

abstract class Controller
{
    /**
     * Fold the request's email down to its canonical form before validation runs.
     *
     * Every email in this application is stored lowercased, but the validation
     * rules run against the raw request value. Because MongoDB string equality is
     * case-sensitive, "unique:users,email" would look up ARIF@example.com, find
     * nothing, pass, and then insert a second document keyed to the already-taken
     * arif@example.com. Normalising first makes the uniqueness check compare the
     * same value that will actually be written.
     */
    protected function normalizeEmail(Request $request): void
    {
        if ($request->has('email')) {
            $request->merge([
                'email' => strtolower(trim((string) $request->input('email'))),
            ]);
        }
    }
}
