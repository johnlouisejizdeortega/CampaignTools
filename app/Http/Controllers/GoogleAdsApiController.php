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

use App\Optimization\AccountSignalsFetcher;
use App\Optimization\OptimizationAnalyzer;
use App\Support\AuditLogger;
use Google\Ads\GoogleAds\Lib\V24\GoogleAdsClient;
use Google\Ads\GoogleAds\Lib\OAuth2TokenBuilder;
use Google\Ads\GoogleAds\Util\FieldMasks;
use Google\Ads\GoogleAds\Util\V24\ResourceNames;
use Google\Ads\GoogleAds\V24\Enums\CampaignStatusEnum\CampaignStatus;
use Google\Ads\GoogleAds\V24\Enums\RecommendationTypeEnum\RecommendationType;
use Google\Ads\GoogleAds\V24\Resources\Campaign;
use Google\Ads\GoogleAds\V24\Services\CampaignOperation;
use Google\Ads\GoogleAds\V24\Services\GoogleAdsRow;
use Google\Ads\GoogleAds\V24\Services\MutateCampaignsRequest;
use Google\Ads\GoogleAds\V24\Services\SearchGoogleAdsRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Throwable;

class GoogleAdsApiController extends Controller
{
    private const REPORT_TYPE_TO_DEFAULT_SELECTED_FIELDS = [
        'campaign' => ['campaign.id', 'campaign.name', 'campaign.status'],
        'customer' => ['customer.id']
    ];

    // The only metric fields a user may add to a report. Restricting to this
    // allowlist prevents arbitrary expressions from being injected into the GAQL
    // SELECT clause.
    private const ALLOWED_METRIC_FIELDS = [
        'metrics.impressions',
        'metrics.clicks',
        'metrics.ctr',
    ];

    /**
     * Builds the validated list of GAQL fields for a report: the default
     * resource fields for the report type plus any requested metric fields that
     * are on the allowlist (everything else is dropped). Public and static so it
     * can be unit-tested independently of the API.
     *
     * @param string $reportType the report type (already validated)
     * @param array<string, mixed> $requestedFields the raw extra request inputs
     * @return array<int, string> the safe field list
     */
    public static function selectFieldsFor(string $reportType, array $requestedFields): array
    {
        $metrics = array_values(array_intersect(
            array_values($requestedFields),
            self::ALLOWED_METRIC_FIELDS
        ));

        return array_merge(
            self::REPORT_TYPE_TO_DEFAULT_SELECTED_FIELDS[$reportType] ?? [],
            $metrics
        );
    }

    // The limit of the number of the returned results. This is set to prevent you from accidentally
    // fetching a very large number of campaigns and freezing your browser. Change it to a larger
    // number if you're sure that your request doesn't result in too many results.
    private const RESULTS_LIMIT = 1000;
    // Google Ads API default page size.
    private const DEFAULT_PAGE_SIZE = 10000;

    // Maps each Google Ads recommendation type to plain-English guidance shown to
    // the user: a human-readable title, why it matters, and how to act on it. The
    // 'badge' controls the colored severity pill in the UI.
    private const RECOMMENDATION_GUIDANCE = [
        'CAMPAIGN_BUDGET' => [
            'title' => 'Raise a budget-limited campaign',
            'why' => 'A campaign is losing impressions because its daily budget runs out early.',
            'fix' => 'Increase the daily budget on this campaign, or shift spend from a lower-performing one.',
            'badge' => 'info',
        ],
        'KEYWORD' => [
            'title' => 'Add suggested keywords',
            'why' => 'Relevant searches exist that your current keywords are not capturing.',
            'fix' => 'Review the suggested keywords and add the ones that match your offering.',
            'badge' => 'info',
        ],
        'TEXT_AD' => [
            'title' => 'Add another ad to an ad group',
            'why' => 'Ad groups with too few ads have nothing to test and tend to stagnate.',
            'fix' => 'Add the suggested ad so Google can rotate and optimize creatives.',
            'badge' => 'warning',
        ],
        'RESPONSIVE_SEARCH_AD' => [
            'title' => 'Add a responsive search ad',
            'why' => 'Responsive search ads adapt headlines and descriptions to each query and usually lift CTR.',
            'fix' => 'Create the suggested responsive search ad with strong, distinct headlines.',
            'badge' => 'warning',
        ],
        'RESPONSIVE_SEARCH_AD_ASSET' => [
            'title' => 'Improve responsive search ad strength',
            'why' => 'Low Ad Strength limits reach and performance.',
            'fix' => 'Add the suggested headlines/descriptions to raise Ad Strength to "Good" or "Excellent".',
            'badge' => 'warning',
        ],
        'TARGET_CPA_OPT_IN' => [
            'title' => 'Switch to Target CPA bidding',
            'why' => 'You have enough conversion data for automated bidding to outperform manual bids.',
            'fix' => 'Adopt Target CPA at the suggested target, then let it learn for ~2 weeks.',
            'badge' => 'info',
        ],
        'MAXIMIZE_CONVERSIONS_OPT_IN' => [
            'title' => 'Switch to Maximize Conversions',
            'why' => 'Automated bidding can capture more conversions within your budget.',
            'fix' => 'Enable Maximize Conversions and monitor cost-per-conversion as it learns.',
            'badge' => 'info',
        ],
        'MAXIMIZE_CLICKS_OPT_IN' => [
            'title' => 'Switch to Maximize Clicks',
            'why' => 'Helpful when the goal is traffic and manual bids are under-delivering.',
            'fix' => 'Enable Maximize Clicks, optionally with a max CPC cap.',
            'badge' => 'info',
        ],
        'ENHANCED_CPC_OPT_IN' => [
            'title' => 'Enable Enhanced CPC',
            'why' => 'Adjusts your manual bids in real time toward conversions.',
            'fix' => 'Turn on Enhanced CPC as a low-risk step toward automated bidding.',
            'badge' => 'info',
        ],
        'OPTIMIZE_AD_ROTATION' => [
            'title' => 'Optimize ad rotation',
            'why' => 'Rotating ads evenly prevents Google from favoring your best performers.',
            'fix' => 'Set ad rotation to "Optimize" so higher-performing ads show more often.',
            'badge' => 'warning',
        ],
        'SEARCH_PARTNERS_OPT_IN' => [
            'title' => 'Expand to Search Partners',
            'why' => 'Additional, often cheaper, search inventory beyond Google Search.',
            'fix' => 'Opt in to Search Partners and watch performance for two weeks.',
            'badge' => 'info',
        ],
        'SITELINK_ASSET' => [
            'title' => 'Add sitelink assets',
            'why' => 'Sitelinks make ads larger and more clickable, lifting CTR.',
            'fix' => 'Add at least four relevant sitelinks pointing to key pages.',
            'badge' => 'warning',
        ],
        'CALLOUT_ASSET' => [
            'title' => 'Add callout assets',
            'why' => 'Callouts highlight selling points and improve ad quality.',
            'fix' => 'Add four or more callouts (e.g. "Free shipping", "24/7 support").',
            'badge' => 'warning',
        ],
        'MOVE_UNUSED_BUDGET' => [
            'title' => 'Move unused budget',
            'why' => 'Budget sits idle in one campaign while another is constrained.',
            'fix' => 'Reallocate the unused budget to the budget-limited campaign.',
            'badge' => 'info',
        ],
    ];

    // Shown when a recommendation type has no specific entry above.
    private const RECOMMENDATION_GUIDANCE_DEFAULT = [
        'why' => 'Google has identified an opportunity to improve this account.',
        'fix' => 'Open this recommendation in the Google Ads UI to review and apply it.',
        'badge' => 'info',
    ];

    /**
     * Controls a POST or GET request submitted in the context of the "Show Report" form.
     *
     * @param Request $request the HTTP request
     * @return InertiaResponse the Inertia page response
     */
    public function showReportAction(
        Request $request
    ): InertiaResponse|RedirectResponse {
        if ($request->method() === 'POST') {
            // Validates the form inputs before touching the API.
            $request->validate([
                'customerId' => ['required', 'regex:/^\d{10}$/'],
                'reportType' => ['required', 'in:campaign,customer'],
                'reportRange' => ['required', 'in:YESTERDAY,LAST_7_DAYS,LAST_WEEK_MON_SUN,LAST_MONTH'],
                'entriesPerPage' => ['required', 'in:20,50,100'],
            ], [
                'customerId.regex' => 'Enter a 10-digit Customer ID without dashes.',
            ]);

            // Retrieves the form inputs.
            $customerId = $request->input('customerId');
            $reportType = $request->input('reportType');
            $reportRange = $request->input('reportRange');
            $entriesPerPage = $request->input('entriesPerPage');

            // Builds the field list from the request, but only from a strict
            // allowlist so that nothing arbitrary can be injected into the GAQL
            // SELECT clause. The legitimate UI only ever sends the metric values
            // below; anything else is discarded.
            $selectedFields = self::selectFieldsFor(
                $reportType,
                $request->except(
                    [
                        '_token',
                        'customerId',
                        'reportType',
                        'reportRange',
                        'entriesPerPage'
                    ]
                )
            );

            // Builds the GAQL query.
            $query = sprintf(
                "SELECT %s FROM %s WHERE metrics.impressions > 0 AND segments.date " .
                "DURING %s LIMIT %d",
                join(", ", $selectedFields),
                $reportType,
                $reportRange,
                self::RESULTS_LIMIT
            );

            // Initializes the list of page tokens. Page tokens are used to request specific pages
            // of results from the API. They are especially useful to optimize navigation between
            // pages as there is no need to cache all the results before displaying.
            // More details can be found here:
            // https://developers.google.com/google-ads/api/docs/reporting/paging.
            //
            // The first page's token is always an empty string.
            $pageTokens = [''];

            // Updates the session with all the information that is necessary to process any
            // future requests (report result pages).
            $request->session()->put('customerId', $customerId);
            $request->session()->put('selectedFields', $selectedFields);
            $request->session()->put('entriesPerPage', $entriesPerPage);
            $request->session()->put('query', $query);
            $request->session()->put('pageTokens', $pageTokens);
        } else {
            // Loads from the session all the information that is necessary to process any
            // requests (report result page).
            $customerId = $request->session()->get('customerId');
            $selectedFields = $request->session()->get('selectedFields');
            $entriesPerPage = $request->session()->get('entriesPerPage');
            $query = $request->session()->get('query');
            $pageTokens = $request->session()->get('pageTokens');
        }

        try {
            // Resolves the API client only after validation has passed, so invalid
            // input is rejected without ever building a (credential-dependent) client.
            $googleAdsClient = app(GoogleAdsClient::class);

            $pageNo = max(1, (int) ($request->input('page') ?: 1));

            // The query already caps results with a LIMIT clause, so fetching all
            // matching rows (the client auto-paginates) is bounded. This lets us
            // page through them in the UI without a server-side total-count call,
            // which isn't available on this transport/library.
            $rows = [];
            $response = $googleAdsClient->getGoogleAdsServiceClient()->search(
                SearchGoogleAdsRequest::build($customerId, $query)
            );
            foreach ($response->iterateAllElements() as $googleAdsRow) {
                /** @var GoogleAdsRow $googleAdsRow */
                // Converts each result to a Plain Old PHP Object (POPO) via JSON.
                $rows[] = json_decode($googleAdsRow->serializeToJsonString(), true);
                if (count($rows) >= self::RESULTS_LIMIT) {
                    break;
                }
            }

            // Extracts the subset of results for the requested UI page.
            $pageResults = array_slice(
                $rows,
                ($pageNo - 1) * (int) $entriesPerPage,
                (int) $entriesPerPage
            );

            // Creates a length aware paginator to supply the page of results.
            $paginatedResults = new LengthAwarePaginator(
                $pageResults,
                count($rows),
                (int) $entriesPerPage,
                $pageNo,
                ['path' => url('show-report')]
            );

            // Renders the page that displays fields of paginated report results.
            return Inertia::render('ReportResult', [
                'results' => $paginatedResults,
                'selectedFields' => $selectedFields,
            ]);
        } catch (Throwable $e) {
            return back()->with('error', $this->friendlyApiError($e));
        }
    }

    /**
     * Controls a POST request submitted in the context of the "Optimization
     * suggestions" form. Fetches the account's live recommendations from the
     * Google Ads API and pairs each one with plain-English guidance.
     *
     * @param Request $request the HTTP request
     * @return InertiaResponse the Inertia page response
     */
    /**
     * Renders the Google Ads-style Overview. When a valid Customer ID is passed
     * as a query parameter it loads the account's real last-30-day metrics and a
     * daily series for the chart; otherwise it shows the "connect an account"
     * state. The page always renders (tools below the fold stay usable).
     */
    /**
     * Temporary diagnostic (behind team auth): reports the *lengths* of the
     * configured Google Ads credentials (never the values) and whether the
     * server can exchange the refresh token for an access token. Used to pin
     * down "UNAUTHENTICATED" issues without exposing secrets.
     */
    public function googleAdsDiagnostic(Request $request): \Illuminate\Http\JsonResponse
    {
        $report = [
            'lengths' => [
                'developer_token' => strlen((string) config('google_ads.developer_token')),
                'client_id' => strlen((string) config('google_ads.oauth2.client_id')),
                'client_secret' => strlen((string) config('google_ads.oauth2.client_secret')),
                'refresh_token' => strlen((string) config('google_ads.oauth2.refresh_token')),
            ],
            'expected_lengths' => [
                'developer_token' => 22,
                'client_id' => 72,
                'client_secret' => 35,
                'refresh_token' => 103,
            ],
            'login_customer_id' => (string) config('google_ads.login_customer_id'),
            'config_cached' => app()->configurationIsCached(),
        ];

        try {
            $credential = (new OAuth2TokenBuilder())
                ->withClientId(config('google_ads.oauth2.client_id'))
                ->withClientSecret(config('google_ads.oauth2.client_secret'))
                ->withRefreshToken(config('google_ads.oauth2.refresh_token'))
                ->build();
            $token = $credential->fetchAuthToken();
            $report['token_fetch'] = (is_array($token) && !empty($token['access_token']))
                ? 'SUCCESS: server obtained an access token'
                : ('NO ACCESS TOKEN returned: ' . json_encode($token));
        } catch (Throwable $e) {
            $report['token_fetch'] = 'ERROR: ' . $e->getMessage();
        }

        // Live end-to-end API call through the bound client (same path the
        // Overview uses), so we can see the full, untruncated error and confirm
        // whether the REST transport is in effect. Add ?customerId=##########.
        $cid = preg_replace('/\D/', '', (string) $request->query('customerId', ''));
        if (strlen((string) $cid) === 10) {
            try {
                $client = app(GoogleAdsClient::class);
                $resp = $client->getGoogleAdsServiceClient()->search(
                    SearchGoogleAdsRequest::build($cid, 'SELECT customer.id, customer.descriptive_name, customer.currency_code FROM customer LIMIT 1')
                );
                $out = [];
                foreach ($resp->iterateAllElements() as $row) {
                    $c = $row->getCustomer();
                    $out[] = ['id' => $c->getId(), 'name' => $c->getDescriptiveName(), 'currency' => $c->getCurrencyCode()];
                }
                $report['api_call'] = ['result' => 'SUCCESS', 'rows' => $out];
            } catch (Throwable $e) {
                $report['api_call'] = ['result' => 'ERROR', 'message' => $e->getMessage()];
            }
        } else {
            $report['api_call'] = 'skipped — add ?customerId=1468333005 to run a live call';
        }

        return response()->json($report);
    }

    public function overviewAction(Request $request): InertiaResponse
    {
        $customerId = (string) $request->query('customerId', '');
        $overview = null;

        if ($customerId !== '') {
            // Invalid IDs short-circuit to a friendly error rather than an API call.
            if (!preg_match('/^\d{10}$/', $customerId)) {
                $overview = ['customerId' => $customerId, 'error' => 'Enter a 10-digit Customer ID without dashes.'];

                return Inertia::render('Dashboard', ['overview' => $overview]);
            }

            // Building the client is the only step that depends on credentials
            // being configured; if it fails the account genuinely can't load.
            try {
                $googleAdsClient = app(GoogleAdsClient::class);
                $service = $googleAdsClient->getGoogleAdsServiceClient();
            } catch (Throwable $e) {
                return Inertia::render('Dashboard', [
                    'overview' => ['customerId' => $customerId, 'error' => $this->friendlyApiError($e)],
                ]);
            }

            $currency = 'USD';
            $totals = ['clicks' => 0.0, 'impressions' => 0.0, 'cost' => 0.0, 'avgCpc' => 0.0, 'conversions' => 0.0];
            $series = [];
            $optimizationScore = null;
            $gotTotals = false;
            $errors = [];

            // Account currency + 30-day totals (one aggregated row). Each query is
            // isolated so a failure in one never blanks out the whole Overview.
            try {
                $resp = $service->search(SearchGoogleAdsRequest::build(
                    $customerId,
                    'SELECT customer.currency_code, metrics.clicks, metrics.impressions, '
                    . 'metrics.cost_micros, metrics.average_cpc, metrics.conversions '
                    . 'FROM customer WHERE segments.date DURING LAST_30_DAYS'
                ));
                foreach ($resp->iterateAllElements() as $row) {
                    $m = $row->getMetrics();
                    $currency = $row->getCustomer()->getCurrencyCode() ?: 'USD';
                    $totals = [
                        'clicks' => (float) $m->getClicks(),
                        'impressions' => (float) $m->getImpressions(),
                        'cost' => $m->getCostMicros() / 1_000_000,
                        'avgCpc' => $m->getAverageCpc() / 1_000_000,
                        'conversions' => (float) $m->getConversions(),
                    ];
                    $gotTotals = true;
                }
            } catch (Throwable $e) {
                $errors[] = 'metrics: ' . $e->getMessage();
            }

            // Daily series for the Clicks/Impressions chart.
            try {
                $resp = $service->search(SearchGoogleAdsRequest::build(
                    $customerId,
                    'SELECT segments.date, metrics.clicks, metrics.impressions, '
                    . 'metrics.cost_micros FROM customer '
                    . 'WHERE segments.date DURING LAST_30_DAYS ORDER BY segments.date'
                ));
                foreach ($resp->iterateAllElements() as $row) {
                    $m = $row->getMetrics();
                    $series[] = [
                        'date' => date('M j', strtotime($row->getSegments()->getDate())),
                        'clicks' => (float) $m->getClicks(),
                        'impressions' => (float) $m->getImpressions(),
                        'cost' => $m->getCostMicros() / 1_000_000,
                    ];
                }
            } catch (Throwable $e) {
                $errors[] = 'series: ' . $e->getMessage();
            }

            // Optimization score (best-effort; never fatal to the page).
            try {
                $signals = (new AccountSignalsFetcher($googleAdsClient))->fetch($customerId);
                $optimizationScore = $signals['optimizationScore'] ?? null;
            } catch (Throwable $e) {
                // Leave the score null.
            }

            $overview = [
                'customerId' => $customerId,
                'currency' => $currency,
                'totals' => $totals,
                'optimizationScore' => $optimizationScore,
                'series' => $series,
                // Only surface an error if we couldn't get the headline metrics at
                // all; include the raw API detail so the cause is diagnosable.
                'error' => $gotTotals
                    ? null
                    : ('Connected, but no metrics were returned for this account.'
                        . ($errors ? ' Details: ' . implode(' | ', $errors) : '')),
            ];
        }

        return Inertia::render('Dashboard', ['overview' => $overview]);
    }

    public function showRecommendationsAction(
        Request $request
    ): InertiaResponse {
        $request->validate([
            'customerId' => ['required', 'regex:/^\d{10}$/'],
            'industry' => ['nullable', 'string', 'max:100'],
        ], [
            'customerId.regex' => 'Enter a 10-digit Customer ID without dashes.',
        ]);

        $customerId = $request->input('customerId');
        $industry = $request->input('industry');
        $recommendations = [];
        $findings = [];
        $optimizationScore = null;
        $benchmark = null;
        $error = null;

        try {
            // Resolves the API client only after validation has passed.
            $googleAdsClient = app(GoogleAdsClient::class);

            // Retrieves all active recommendations for the account.
            $query = 'SELECT recommendation.type, recommendation.campaign '
                . 'FROM recommendation';
            $response = $googleAdsClient->getGoogleAdsServiceClient()->search(
                SearchGoogleAdsRequest::build($customerId, $query)
            );

            foreach ($response->iterateAllElements() as $googleAdsRow) {
                /** @var GoogleAdsRow $googleAdsRow */
                $recommendation = $googleAdsRow->getRecommendation();
                $typeName = RecommendationType::name($recommendation->getType());
                $guidance = self::RECOMMENDATION_GUIDANCE[$typeName]
                    ?? self::RECOMMENDATION_GUIDANCE_DEFAULT;

                // Extracts the campaign ID from the resource name when present,
                // e.g. "customers/123/campaigns/456" -> "456".
                $campaign = $recommendation->getCampaign();
                $campaignId = $campaign ? substr($campaign, strrpos($campaign, '/') + 1) : null;

                $recommendations[] = [
                    'type' => $typeName,
                    'title' => $guidance['title'] ?? ucwords(strtolower(str_replace('_', ' ', $typeName))),
                    'why' => $guidance['why'],
                    'fix' => $guidance['fix'],
                    'badge' => $guidance['badge'],
                    'campaignId' => $campaignId,
                ];
            }

            // Runs the deterministic, source-cited rule engine over the account's
            // real signals (conversion tracking, optimization score, impression
            // share, Ad Strength, CTR vs the account benchmark).
            $signals = (new AccountSignalsFetcher($googleAdsClient))->fetch($customerId);
            $optimizationScore = $signals['optimizationScore'];
            $findings = OptimizationAnalyzer::fromJsonFile(
                resource_path('knowledge/rules.json')
            )->analyze($signals);

            // Optional directional comparison against industry benchmarks.
            $benchmark = $this->buildBenchmark($industry, $signals['account'] ?? null);
        } catch (Throwable $e) {
            // Surfaces a friendly message but still shows the static playbook so
            // the page is useful even when the API call cannot be made (e.g.
            // missing credentials or an invalid customer ID).
            $error = $e->getMessage();
        }

        return Inertia::render('RecommendationsResult', [
            'customerId' => $customerId,
            'industry' => $industry,
            'recommendations' => $recommendations,
            'findings' => $findings,
            'optimizationScore' => $optimizationScore,
            'benchmark' => $benchmark,
            'industries' => array_keys($this->benchmarkData()['industries'] ?? []),
            'meta' => $this->knowledgeMeta(),
            'error' => $error,
        ]);
    }

    /**
     * Builds the "data freshness" metadata shown on the optimization page so
     * users always know how current the analysis and datasets are.
     *
     * @return array<string, string|null> the freshness metadata
     */
    private function knowledgeMeta(): array
    {
        $rulesPath = resource_path('knowledge/rules.json');
        $rules = is_file($rulesPath) ? json_decode((string) file_get_contents($rulesPath), true) : [];

        return [
            'analyzedAt' => now()->toDayDateTimeString(),
            'dataWindow' => 'Last 30 days',
            'rulesVersion' => $rules['version'] ?? null,
            'benchmarksReviewed' => $this->benchmarkData()['reviewed'] ?? null,
        ];
    }

    /**
     * Loads the industry benchmark dataset (resources/knowledge/benchmarks.json).
     *
     * @return array<string, mixed> the decoded benchmark dataset
     */
    private function benchmarkData(): array
    {
        $path = resource_path('knowledge/benchmarks.json');
        $decoded = is_file($path) ? json_decode((string) file_get_contents($path), true) : null;
        return is_array($decoded) ? $decoded : [];
    }

    /**
     * Builds a directional comparison of the account's aggregate metrics against
     * the published benchmark for the chosen industry. Returns null when the
     * industry is unknown or the account has no aggregate metrics.
     *
     * @param string|null $industry the selected industry
     * @param array<string, mixed>|null $account the account aggregate metrics
     * @return array<string, mixed>|null the comparison, or null
     */
    private function buildBenchmark(?string $industry, ?array $account): ?array
    {
        $data = $this->benchmarkData();
        if (
            $industry === null
            || $account === null
            || !isset($data['industries'][$industry])
        ) {
            return null;
        }
        $b = $data['industries'][$industry];

        return [
            'industry' => $industry,
            'currency' => $b['currency'] ?? $data['currency'] ?? 'USD',
            'source' => $b['source'] ?? $data['source'] ?? null,
            'reviewed' => $b['reviewed'] ?? $data['reviewed'] ?? null,
            'note' => $b['note'] ?? null,
            'metrics' => [
                ['label' => 'CTR', 'account' => $account['ctr'] ?? null, 'benchmark' => $b['avgCtr'], 'format' => 'percent', 'betterWhenHigher' => true],
                ['label' => 'Avg. CPC', 'account' => $account['cpc'] ?? null, 'benchmark' => $b['avgCpc'], 'format' => 'currency', 'betterWhenHigher' => false],
                ['label' => 'Conversion rate', 'account' => $account['cvr'] ?? null, 'benchmark' => $b['avgCvr'], 'format' => 'percent', 'betterWhenHigher' => true],
                ['label' => 'Cost / conversion', 'account' => $account['cpa'] ?? null, 'benchmark' => $b['avgCpa'], 'format' => 'currency', 'betterWhenHigher' => false],
            ],
        ];
    }

    /**
     * Controls a POST request submitted in the context of the "Pause Campaign" form.
     *
     * @param Request $request the HTTP request
     * @return InertiaResponse the Inertia page response
     */
    public function pauseCampaignAction(
        Request $request
    ): InertiaResponse|RedirectResponse {
        // Validates the form inputs before touching the API.
        $request->validate([
            'customerId' => ['required', 'regex:/^\d{10}$/'],
            'campaignId' => ['required', 'regex:/^\d+$/'],
        ], [
            'customerId.regex' => 'Enter a 10-digit Customer ID without dashes.',
            'campaignId.regex' => 'Enter a numeric Campaign ID.',
        ]);

        $customerId = $request->input('customerId');
        $campaignId = $request->input('campaignId');

        try {
        // Resolves the API client only after validation has passed.
        $googleAdsClient = app(GoogleAdsClient::class);

        // Deducts the campaign resource name from the given IDs.
        $campaignResourceName = ResourceNames::forCampaign($customerId, $campaignId);

        // Creates a campaign object and sets its status to PAUSED.
        $campaign = new Campaign();
        $campaign->setResourceName($campaignResourceName);
        $campaign->setStatus(CampaignStatus::PAUSED);

        // Constructs an operation that will pause the campaign with the specified resource
        // name, using the FieldMasks utility to derive the update mask. This mask tells the
        // Google Ads API which attributes of the campaign need to change.
        $campaignOperation = new CampaignOperation();
        $campaignOperation->setUpdate($campaign);
        $campaignOperation->setUpdateMask(FieldMasks::allSetFieldsOf($campaign));

        // Issues a mutate request to pause the campaign.
        $googleAdsClient->getCampaignServiceClient()->mutateCampaigns(
            MutateCampaignsRequest::build($customerId, [$campaignOperation])
        );

        // Builds the GAQL query to retrieve more information about the now paused campaign.
        $query = sprintf(
            "SELECT campaign.id, campaign.name, campaign.status FROM campaign " .
            "WHERE campaign.resource_name = '%s' LIMIT 1",
            $campaignResourceName
        );

        // Searches the result.
        $response = $googleAdsClient->getGoogleAdsServiceClient()->search(
            SearchGoogleAdsRequest::build($customerId, $query)
        );

        // Fetches and converts the result as a POPO using JSON.
        $campaign = json_decode(
            $response->iterateAllElements()->current()->getCampaign()->serializeToJsonString(),
            true
        );

        // Records the destructive action in the audit log for accountability.
        AuditLogger::record($request, 'campaign.pause', [
            'customerId' => $customerId,
            'campaignId' => $campaignId,
        ]);

        return Inertia::render('PauseResult', [
            'customerId' => $customerId,
            'campaign' => $campaign,
        ]);
        } catch (Throwable $e) {
            return back()->with('error', $this->friendlyApiError($e));
        }
    }

    /**
     * Turns a raw exception from the Google Ads API into a short, user-friendly
     * message (full details still go to the logs).
     *
     * @param Throwable $e the caught exception
     * @return string the friendly message
     */
    private function friendlyApiError(Throwable $e): string
    {
        Log::warning('Google Ads API request failed', ['exception' => $e->getMessage()]);
        $message = $e->getMessage();
        if (stripos($message, 'invalid_client') !== false || stripos($message, 'oauth') !== false) {
            return 'The server is not connected to Google Ads yet (check the credentials file).';
        }
        if (stripos($message, 'PERMISSION_DENIED') !== false || stripos($message, 'USER_PERMISSION_DENIED') !== false) {
            return 'Access to this account was denied. Check the Customer ID and your account permissions.';
        }
        if (stripos($message, 'NOT_FOUND') !== false || stripos($message, 'invalid customer') !== false) {
            return 'That Customer ID could not be found.';
        }
        if (stripos($message, 'RESOURCE_EXHAUSTED') !== false || stripos($message, 'quota') !== false) {
            return 'Google Ads API quota was exceeded. Please try again shortly.';
        }
        return 'Could not complete the request against Google Ads. Please try again.';
    }
}
