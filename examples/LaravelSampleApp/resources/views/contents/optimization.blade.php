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
<div class="card">
    <div class="card-header">
        <h2 class="card-title">
            Optimization suggestions
            <span class="badge badge-info">AI-assisted</span>
        </h2>
        <p class="card-description">
            Fetch Google's live recommendations for an account, with plain-English
            guidance on what to do. Then use the playbook below to fix common issues.
        </p>
    </div>
    <div class="card-content">
        <form action="{{ url('recommendations') }}" method="POST">
            {{ csrf_field() }}
            <div class="field" style="max-width:420px;">
                <label class="label" for="optCustomerId">Customer ID</label>
                <input type="text" class="input" id="optCustomerId" name="customerId"
                       placeholder="1234567890" required>
                <span class="hint">We'll pull active recommendations from this account.</span>
            </div>
            <button type="submit" class="btn btn-primary">Get suggestions</button>
        </form>

        <hr style="border:none;border-top:1px solid var(--border);margin:1.5rem 0;">

        <h3 style="font-size:0.95rem;font-weight:600;margin:0 0 0.85rem;">
            Optimization playbook — common problems &amp; how to fix them
        </h3>
        @include('contents.playbook')
    </div>
</div>
