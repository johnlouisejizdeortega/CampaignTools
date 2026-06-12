<?php

/**
 * Copyright 2020 Google LLC
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     https://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Handles the shared-password login flow that gates the application for team
 * use. See {@see \App\Http\Middleware\TeamAuthenticate}.
 */
class TeamAccessController extends Controller
{
    /**
     * Shows the login form. If access control is not configured or the visitor
     * is already authenticated, sends them straight to the dashboard.
     *
     * @param Request $request the HTTP request
     * @return \Inertia\Response|RedirectResponse the login page or a redirect
     */
    public function showLogin(Request $request)
    {
        if (
            empty(config('app.team_access_password'))
            || $request->session()->get('team_authenticated') === true
        ) {
            return redirect('/');
        }
        return Inertia::render('Login');
    }

    /**
     * Verifies the submitted password against the configured team password.
     *
     * @param Request $request the HTTP request
     * @return RedirectResponse a redirect to the dashboard or back to login
     */
    public function login(Request $request): RedirectResponse
    {
        $request->validate(['password' => 'required|string']);

        $configuredPassword = config('app.team_access_password');
        // Constant-time comparison to avoid leaking the password via timing.
        if (
            !empty($configuredPassword)
            && hash_equals((string) $configuredPassword, (string) $request->input('password'))
        ) {
            // Prevents session fixation by rotating the session ID on login.
            $request->session()->regenerate();
            $request->session()->put('team_authenticated', true);
            return redirect()->intended('/');
        }

        return redirect()->route('login')->withErrors(
            ['password' => 'Incorrect password. Please try again.']
        );
    }

    /**
     * Logs the current visitor out.
     *
     * @param Request $request the HTTP request
     * @return RedirectResponse a redirect to the login page
     */
    public function logout(Request $request): RedirectResponse
    {
        $request->session()->forget('team_authenticated');
        $request->session()->regenerate();
        return redirect()->route('login');
    }
}
