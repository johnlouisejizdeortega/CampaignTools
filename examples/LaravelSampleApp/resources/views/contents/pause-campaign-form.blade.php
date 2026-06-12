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
        <h2 class="card-title">Pause a campaign</h2>
        <p class="card-description">Temporarily stop a live campaign from spending.</p>
    </div>
    <div class="card-content">
        <form action="{{ url('pause-campaign') }}" method="POST">
            {{ csrf_field() }}
            <div class="field">
                <label class="label" for="pauseCustomerId">Customer ID</label>
                <input type="text" class="input" id="pauseCustomerId" name="customerId"
                       placeholder="1234567890" required>
                <span class="hint">Your account ID, without dashes.</span>
            </div>
            <div class="field">
                <label class="label" for="campaignId">Campaign ID</label>
                <input type="text" class="input" id="campaignId" name="campaignId"
                       placeholder="1234567890" required>
                <span class="hint">The campaign you want to pause.</span>
            </div>
            <button type="submit" class="btn btn-primary">Pause campaign</button>
        </form>
    </div>
</div>
