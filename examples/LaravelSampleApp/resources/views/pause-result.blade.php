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
@section('title', 'Campaign paused')
@section('content')
    <div class="page-header">
        <h1 class="page-title">Campaign paused</h1>
        <p class="page-subtitle">The campaign below is now paused and will stop spending.</p>
    </div>

    <div class="card" style="max-width:560px;">
        <div class="card-content">
            <div class="field">
                <span class="label">Campaign ID</span>
                <div class="input" style="display:flex;align-items:center;background:var(--muted);">{{ $campaign['id'] }}</div>
            </div>
            <div class="field">
                <span class="label">Campaign name</span>
                <div class="input" style="display:flex;align-items:center;background:var(--muted);">{{ $campaign['name'] }}</div>
            </div>
            <div class="field mb-0">
                <span class="label">Status</span>
                <div><span class="badge badge-warning">{{ $campaign['status'] }}</span></div>
            </div>
        </div>
    </div>

    @include('includes.result-back')
@stop
