<?php

namespace App\Services\GoogleAds;

use Google\Ads\GoogleAds\Lib\V24\GoogleAdsClient;
use Google\Ads\GoogleAds\Util\V24\ResourceNames;
use Google\Ads\GoogleAds\V24\Enums\LocalServicesLeadSurveyAnswerEnum\SurveyAnswer;
use Google\Ads\GoogleAds\V24\Enums\LocalServicesLeadSurveyDissatisfiedReasonEnum\SurveyDissatisfiedReason;
use Google\Ads\GoogleAds\V24\Services\ProvideLeadFeedbackRequest;
use Google\Ads\GoogleAds\V24\Services\SurveyDissatisfied;
use Google\Ads\GoogleAds\V24\Services\SearchGoogleAdsRequest;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Thin wrapper over the Google Ads API for Local Services Ads (LSA) data.
 *
 * LSA is exposed through the main Google Ads API's read-only
 * `local_services_lead` resources (available for UK accounts), not the
 * US/Canada-only standalone Local Services API. There is one Local Services
 * campaign per account, so queries don't need a campaign id, and the API does
 * not support an MCC-level query for all sub-accounts — callers loop per client
 * customer id.
 *
 * Rows are read back from the API's own JSON serialization, which yields the
 * canonical enum names ("NEW", "MESSAGE", …) and camelCase field names,
 * insulating us from generated-getter quirks.
 */
class LsaClient
{
    public function __construct(private readonly GoogleAdsClient $client)
    {
    }

    /**
     * Returns the Local Services campaign id for an account, or null if none —
     * a cheap sanity check that LSA is actually running on the account.
     */
    public function findLocalServicesCampaign(string $customerId): ?string
    {
        $rows = $this->search($customerId,
            "SELECT campaign.id FROM campaign "
            . "WHERE campaign.advertising_channel_type = 'LOCAL_SERVICES' LIMIT 1"
        );

        return $rows[0]['campaign']['id'] ?? null;
    }

    /**
     * Fetches an account's display name and currency.
     *
     * @return array{name: ?string, currency: ?string}
     */
    public function fetchAccountInfo(string $customerId): array
    {
        $rows = $this->search($customerId,
            'SELECT customer.descriptive_name, customer.currency_code FROM customer LIMIT 1'
        );
        $c = $rows[0]['customer'] ?? [];

        return [
            'name' => $c['descriptiveName'] ?? null,
            'currency' => $c['currencyCode'] ?? null,
        ];
    }

    /**
     * Fetches leads created on/after $startDate (Y-m-d) for an account.
     *
     * @return array<int, array<string, mixed>> normalized lead rows
     */
    public function fetchLeads(string $customerId, string $startDate, int $limit = 5000): array
    {
        $rows = $this->search($customerId, sprintf(
            "SELECT local_services_lead.id, local_services_lead.category_id, "
            // contact_details sub-fields aren't individually selectable (PII); the
            // whole message is, and the response still nests phone/email/name.
            . "local_services_lead.service_id, local_services_lead.contact_details, "
            . "local_services_lead.lead_type, local_services_lead.lead_status, "
            . "local_services_lead.creation_date_time, local_services_lead.locale, "
            . "local_services_lead.lead_charged, local_services_lead.note.description "
            . "FROM local_services_lead "
            . "WHERE local_services_lead.creation_date_time >= '%s 00:00:00' "
            . "ORDER BY local_services_lead.creation_date_time DESC LIMIT %d",
            $startDate,
            $limit
        ));

        return array_map(function (array $row): array {
            $lead = $row['localServicesLead'] ?? [];
            $contact = $lead['contactDetails'] ?? [];

            return [
                'id' => (string) ($lead['id'] ?? ''),
                'category_id' => $lead['categoryId'] ?? null,
                'service_id' => $lead['serviceId'] ?? null,
                'contact_name' => $contact['consumerName'] ?? null,
                'contact_phone' => $contact['phoneNumber'] ?? null,
                'contact_email' => $contact['email'] ?? null,
                'lead_type' => $lead['leadType'] ?? null,
                'lead_status' => $lead['leadStatus'] ?? null,
                'creation_date_time' => $lead['creationDateTime'] ?? null,
                'locale' => $lead['locale'] ?? null,
                'lead_charged' => (bool) ($lead['leadCharged'] ?? false),
                'note' => $lead['note']['description'] ?? null,
            ];
        }, $rows);
    }

    /**
     * Fetches all lead conversations for an account (message/call threads).
     *
     * @return array<int, array<string, mixed>> normalized conversation rows
     */
    public function fetchConversations(string $customerId, int $limit = 10000): array
    {
        $rows = $this->search($customerId,
            "SELECT local_services_lead_conversation.id, "
            . "local_services_lead_conversation.conversation_channel, "
            . "local_services_lead_conversation.participant_type, "
            . "local_services_lead_conversation.event_date_time, "
            . "local_services_lead_conversation.message_details.text, "
            . "local_services_lead_conversation.lead "
            . "FROM local_services_lead_conversation "
            . "ORDER BY local_services_lead_conversation.event_date_time DESC LIMIT " . $limit
        );

        return array_map(function (array $row): array {
            $c = $row['localServicesLeadConversation'] ?? [];
            // "customers/123/localServicesLeads/456" -> "456"
            $leadRes = (string) ($c['lead'] ?? '');
            $leadId = $leadRes !== '' ? substr($leadRes, strrpos($leadRes, '/') + 1) : null;

            return [
                'id' => (string) ($c['id'] ?? ''),
                'lead_id' => $leadId,
                'type' => $c['conversationChannel'] ?? null,
                'body' => $c['messageDetails']['text'] ?? null,
                'occurred_at' => $c['eventDateTime'] ?? null,
            ];
        }, $rows);
    }

    /**
     * Fetches conversations for a single lead (on-demand detail view).
     *
     * @return array<int, array<string, mixed>>
     */
    public function fetchLeadConversations(string $customerId, string $leadId): array
    {
        return array_values(array_filter(
            $this->fetchConversations($customerId),
            static fn (array $c) => ($c['lead_id'] ?? null) === $leadId
        ));
    }

    /**
     * Fetches the LSA campaign's daily cost between two dates (Y-m-d).
     *
     * @return array<int, array{date: string, cost: float, currency: string}>
     */
    public function fetchDailyCosts(string $customerId, string $startDate, string $endDate): array
    {
        $rows = $this->search($customerId, sprintf(
            "SELECT segments.date, metrics.cost_micros, customer.currency_code "
            . "FROM campaign WHERE campaign.advertising_channel_type = 'LOCAL_SERVICES' "
            . "AND segments.date BETWEEN '%s' AND '%s'",
            $startDate,
            $endDate
        ));

        $byDate = [];
        foreach ($rows as $row) {
            $date = $row['segments']['date'] ?? null;
            if ($date === null) {
                continue;
            }
            $costMicros = (int) ($row['metrics']['costMicros'] ?? 0);
            $byDate[$date] = [
                'date' => $date,
                'cost' => ($byDate[$date]['cost'] ?? 0) + $costMicros / 1_000_000,
                'currency' => $row['customer']['currencyCode'] ?? null,
            ];
        }

        return array_values($byDate);
    }

    /**
     * Submits satisfaction feedback for a lead — the only write the LSA API
     * permits. $surveyAnswer is one of the SurveyAnswer enum names, e.g.
     * VERY_SATISFIED, SATISFIED, NEUTRAL, DISSATISFIED, VERY_DISSATISFIED.
     */
    public function provideLeadFeedback(
        string $customerId,
        string $leadId,
        string $surveyAnswer,
        ?string $dissatisfiedReason = null,
        ?string $comment = null
    ): void {
        $request = new ProvideLeadFeedbackRequest();
        $request->setResourceName(ResourceNames::forLocalServicesLead($customerId, $leadId));
        $request->setSurveyAnswer(SurveyAnswer::value($surveyAnswer));

        // When reporting a bad lead, attach the dissatisfaction reason Google
        // reviews for a possible credit (spam/duplicate/geo mismatch, etc.).
        if ($dissatisfiedReason !== null) {
            $detail = new SurveyDissatisfied();
            $detail->setSurveyDissatisfiedReason(SurveyDissatisfiedReason::value($dissatisfiedReason));
            if ($comment !== null && $comment !== '') {
                $detail->setOtherReasonComment($comment);
            }
            $request->setSurveyDissatisfied($detail);
        }

        $this->client->getLocalServicesLeadServiceClient()->provideLeadFeedback($request);
    }

    /**
     * Runs a GAQL query and returns each row decoded from its JSON form.
     *
     * @return array<int, array<string, mixed>>
     */
    private function search(string $customerId, string $query): array
    {
        try {
            $response = $this->client->getGoogleAdsServiceClient()->search(
                SearchGoogleAdsRequest::build($customerId, $query)
            );

            $rows = [];
            foreach ($response->iterateAllElements() as $row) {
                $rows[] = json_decode($row->serializeToJsonString(), true);
            }

            return $rows;
        } catch (Throwable $e) {
            $requestId = method_exists($e, 'getRequestId') ? $e->getRequestId() : null;
            Log::warning('LSA API query failed', [
                'customerId' => $customerId,
                'requestId' => $requestId,
                'exception' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
