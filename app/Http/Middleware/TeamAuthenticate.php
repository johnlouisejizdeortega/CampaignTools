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

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Gates the application behind a single shared team password. This is a
 * lightweight access control suitable for an internal tool hosted for a small
 * team: it requires no user database, only the TEAM_ACCESS_PASSWORD value set
 * in the environment. Requests without an authenticated session are redirected
 * to the login page.
 */
class TeamAuthenticate
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request the incoming HTTP request
     * @param Closure $next the next middleware to run
     * @return mixed the HTTP response
     */
    public function handle(Request $request, Closure $next)
    {
        // If no team password is configured, the gate is effectively disabled so
        // that local development still works out of the box. Hosted deployments
        // must set TEAM_ACCESS_PASSWORD to protect the app.
        $configuredPassword = config('app.team_access_password');
        if (empty($configuredPassword)) {
            return $next($request);
        }

        if ($request->session()->get('team_authenticated') === true) {
            return $next($request);
        }

        return redirect()->route('login');
    }
}
