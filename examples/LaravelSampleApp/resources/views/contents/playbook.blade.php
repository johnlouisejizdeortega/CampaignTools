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
@php
    $playbook = [
        [
            'badge' => ['warning', 'High impact'],
            'title' => 'Conversion tracking is missing or broken',
            'problem' => "Without conversion tracking, Google can't optimize bids and you can't tell which clicks turn into sales or leads.",
            'fix' => "Set up conversion actions (purchase, lead form, call). Verify the tag fires with Google Tag Assistant, then let Smart Bidding learn for 1–2 weeks.",
        ],
        [
            'badge' => ['destructive', 'Wasting spend'],
            'title' => 'No negative keywords',
            'problem' => "Broad and phrase keywords match irrelevant searches (e.g. \"free\", \"jobs\", competitor names), draining budget on clicks that never convert.",
            'fix' => "Open the Search Terms report, add irrelevant queries as negative keywords, and build a shared negative list you reuse across campaigns.",
        ],
        [
            'badge' => ['warning', 'High impact'],
            'title' => 'Low Quality Score / low Ad Strength',
            'problem' => "Low relevance means you pay more per click and rank lower. Responsive search ads with \"Poor\" strength under-deliver.",
            'fix' => "Tighten ad groups around a single theme, mirror the keyword in headlines, add 8–10 headlines and 3–4 descriptions, and improve landing-page relevance.",
        ],
        [
            'badge' => ['info', 'Growth'],
            'title' => 'Campaign is limited by budget',
            'problem' => "\"Limited by budget\" means your ads stop showing before the day ends — you're leaving conversions on the table.",
            'fix' => "Raise the daily budget on profitable campaigns, or improve efficiency (better targeting, higher Quality Score) so each dollar buys more clicks.",
        ],
        [
            'badge' => ['info', 'Growth'],
            'title' => 'Manual bidding with enough conversion data',
            'problem' => "Manual CPC can't react to context (device, time, audience) as fast as automated bidding once you have history.",
            'fix' => "Once you have ~15–30 conversions/month, switch to Maximize Conversions or Target CPA/ROAS and monitor for two weeks before judging.",
        ],
        [
            'badge' => ['warning', 'Quick win'],
            'title' => 'Few or no ad assets (extensions)',
            'problem' => "Missing sitelinks, callouts, and structured snippets means smaller, less clickable ads and a lower CTR.",
            'fix' => "Add at least 4 sitelinks, 4 callouts, and a structured snippet per campaign. Add call and location assets where relevant.",
        ],
        [
            'badge' => ['destructive', 'Wasting spend'],
            'title' => 'Only one ad per ad group',
            'problem' => "With a single ad there's nothing to test, so CTR and conversion rate stagnate.",
            'fix' => "Run 2 responsive search ads per ad group, keep ad rotation on \"Optimize\", and refresh the lowest performer monthly.",
        ],
        [
            'badge' => ['info', 'Targeting'],
            'title' => 'Broad geo / schedule with no adjustments',
            'problem' => "Showing everywhere, all the time, spends budget on low-value locations and hours.",
            'fix' => "Review Locations and Ad Schedule reports; add bid adjustments (or exclusions) for the geos, devices, and dayparts that convert worst.",
        ],
    ];
@endphp

@foreach ($playbook as $tip)
    <div class="tip">
        <div class="tip-head">
            <p class="tip-title">{{ $tip['title'] }}</p>
            <span class="badge badge-{{ $tip['badge'][0] }}">{{ $tip['badge'][1] }}</span>
        </div>
        <p class="tip-problem">{{ $tip['problem'] }}</p>
        <p class="tip-fix"><strong>Fix:</strong> {{ $tip['fix'] }}</p>
    </div>
@endforeach
