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
        <h2 class="card-title">Show a report</h2>
        <p class="card-description">Pull live performance data for an account.</p>
    </div>
    <div class="card-content">
        <form action="{{ url('show-report') }}" method="POST">
            {{ csrf_field() }}
            <div class="field">
                <label class="label" for="reportCustomerId">Customer ID</label>
                <input type="text" class="input" id="reportCustomerId" name="customerId"
                       placeholder="1234567890" required>
                <span class="hint">Your account ID, without dashes.</span>
            </div>
            <div class="field">
                <label class="label" for="reportType">Report type</label>
                <select class="select" id="reportType" name="reportType">
                    <option selected>campaign</option>
                    <option>customer</option>
                </select>
            </div>
            <div class="field">
                <label class="label">Metrics</label>
                <div class="check-list">
                    <label class="check"><input type="checkbox" name="impressions" value="metrics.impressions" checked> Impressions</label>
                    <label class="check"><input type="checkbox" name="clicks" value="metrics.clicks" checked> Clicks</label>
                    <label class="check"><input type="checkbox" name="ctr" value="metrics.ctr" checked> CTR (click-through rate)</label>
                </div>
            </div>
            <div class="field">
                <label class="label" for="reportRange">Date range</label>
                <select class="select" id="reportRange" name="reportRange">
                    <option selected>YESTERDAY</option>
                    <option>LAST_7_DAYS</option>
                    <option>LAST_WEEK_MON_SUN</option>
                    <option>LAST_MONTH</option>
                </select>
            </div>
            <div class="field">
                <label class="label" for="entriesPerPage">Rows per page</label>
                <select class="select" id="entriesPerPage" name="entriesPerPage">
                    <option selected>20</option>
                    <option>50</option>
                    <option>100</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Show report</button>
        </form>
    </div>
</div>
