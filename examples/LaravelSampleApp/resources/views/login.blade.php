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
@extends('layouts.default')
@section('content')
    <div class="row justify-content-center mt-4">
        <div class="col-md-5">
            <div class="card">
                <div class="card-header">Team sign in</div>
                <div class="card-body">
                    <p class="text-muted">
                        Enter the team access password to use the Google Ads dashboard.
                    </p>
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            {{ $errors->first('password') }}
                        </div>
                    @endif
                    <form method="POST" action="{{ route('login.submit') }}">
                        @csrf
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" class="form-control" id="password"
                                   name="password" autofocus required>
                        </div>
                        <button type="submit" class="btn btn-primary">Sign in</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
