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
@section('title', 'Optimization suggestions')
@section('content')
    <div class="page-header">
        <h1 class="page-title">Optimization suggestions</h1>
        <p class="page-subtitle">For account <code>{{ $customerId }}</code></p>
    </div>

    @if ($error)
        <div class="alert alert-destructive" style="margin-bottom:1.25rem;">
            <span>
                <strong>Couldn't fetch live recommendations.</strong>
                This usually means the server isn't connected to Google Ads yet, or the
                Customer ID is invalid. You can still use the playbook below.
                <br><span class="text-muted" style="font-size:0.8rem;">Details: {{ \Illuminate\Support\Str::limit($error, 240) }}</span>
            </span>
        </div>
    @elseif (count($recommendations) === 0)
        <div class="alert alert-info" style="margin-bottom:1.25rem;">
            <span>No active recommendations right now — nice, the account is in good shape. Keep an eye on the playbook below.</span>
        </div>
    @else
        <div class="card" style="margin-bottom:1.5rem;">
            <div class="card-header">
                <h2 class="card-title">
                    Live recommendations
                    <span class="badge badge-success">{{ count($recommendations) }} found</span>
                </h2>
                <p class="card-description">Straight from Google Ads for this account, with what each one means.</p>
            </div>
            <div class="card-content">
                @foreach ($recommendations as $rec)
                    <div class="tip">
                        <div class="tip-head">
                            <p class="tip-title">
                                {{ $rec['title'] }}
                                @if ($rec['campaignId'])
                                    <span class="text-muted" style="font-weight:400;">· campaign {{ $rec['campaignId'] }}</span>
                                @endif
                            </p>
                            <span class="badge badge-{{ $rec['badge'] }}">{{ str_replace('_', ' ', $rec['type']) }}</span>
                        </div>
                        <p class="tip-problem">{{ $rec['why'] }}</p>
                        <p class="tip-fix"><strong>How to fix:</strong> {{ $rec['fix'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Optimization playbook</h2>
            <p class="card-description">Common problems and how to fix them — useful for any account.</p>
        </div>
        <div class="card-content">
            @include('contents.playbook')
        </div>
    </div>

    @include('includes.result-back')
@stop
