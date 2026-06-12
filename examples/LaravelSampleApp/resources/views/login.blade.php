<!--
  Copyright 2020 Google LLC

  Licensed under the Apache License, Version 2.0 (the "License");
  you may not use this file except in compliance with the License.
  You may obtain a copy of the License at

      https://www.apache.org/licenses/LICENSE-2.0

  Unless required by applicable law or agreed to in writing, software
  distributed under the License is distributed on an "AS IS" BASIS,
  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
  See the License for the specific language governing permissions and
  limitations under the License.
-->
@extends('layouts.auth')
@section('title', 'Sign in')
@section('content')
    <div class="login-wrap">
        <div class="login-card">
            <div class="login-brand">
                <span class="brand-logo">Ads</span>
                <span>Google Ads Dashboard</span>
            </div>
            <div class="card">
                <div class="card-header">
                    <h1 class="card-title">Sign in</h1>
                    <p class="card-description">Enter the team password to continue.</p>
                </div>
                <div class="card-content">
                    @if ($errors->any())
                        <div class="alert alert-destructive mt-1" style="margin-bottom:1rem;">
                            {{ $errors->first('password') }}
                        </div>
                    @endif
                    <form method="POST" action="{{ route('login.submit') }}">
                        @csrf
                        <div class="field">
                            <label class="label" for="password">Password</label>
                            <input type="password" class="input" id="password"
                                   name="password" placeholder="••••••••" autofocus required>
                        </div>
                        <button type="submit" class="btn btn-primary w-full">Sign in</button>
                    </form>
                </div>
            </div>
            <p class="text-muted mt-2" style="text-align:center;font-size:0.8rem;">
                Internal tool · Access is restricted to your team.
            </p>
        </div>
    </div>
@endsection
