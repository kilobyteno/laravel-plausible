<?php

namespace Kilobyteno\LaravelPlausible;

use Illuminate\Support\Facades\Http;

class Plausible
{
    protected static array $allowedMetrics = ['visitors', 'pageviews', 'bounce_rate', 'visit_duration', 'visits', 'events'];
    protected static array $allowedPeriods = ['12mo', '6mo', 'month', '30d', '7d', 'day'];
    protected static array $allowedProperties = [
        'event:name',
        'event:page',
        'visit:entry_page',
        'visit:exit_page',
        'visit:source',
        'visit:referrer',
        'visit:utm_medium',
        'visit:utm_source',
        'visit:utm_campaign',
        'visit:utm_content',
        'visit:utm_term',
        'visit:device',
        'visit:browser',
        'visit:browser_version',
        'visit:os',
        'visit:os_version',
        'visit:country',
    ];

    public static function getHeaders(): array
    {
        return [
            'X-Forwarded-For' => request()->ip(),
            'User-Agent' => request()->userAgent(),
            'Authorization' => 'Bearer ' . config('plausible.api_key'),
        ];
    }

    public static function getBaseUrl(): string
    {
        return config('plausible.api_url');
    }

    /**
     * @return array
     */
    public static function getAllowedPeriods(): array
    {
        return self::$allowedPeriods;
    }

    /**
     * @return array
     */
    public static function getAllowedMetrics(): array
    {
        return self::$allowedMetrics;
    }

    /**
     * @return array
     */
    public static function getAllowedProperties(): array
    {
        return self::$allowedProperties;
    }

    /**
     *
     * @param string $domain
     * @param string $period
     * @param array $metrics
     * @return array
     */
    private static function getAggregate(string $domain, string $period = '30d', array $metrics = ['visitors']): array
    {
        $metrics = array_intersect($metrics, self::$allowedMetrics);
        $period = in_array($period, self::$allowedPeriods) ? $period : '30d';
        $result = Http::withHeaders(self::getHeaders())
                ->get(
                    self::getBaseUrl() . '/stats/aggregate',
                    [
                        'site_id' => $domain,
                        'period' => $period,
                        'metrics' => implode(',', $metrics),
                    ]
                )
                ->json()['results'] ?? [];

        return $result;
    }

    /**
     *
     * @param string $domain
     * @param string $period
     * @param array $metrics
     * @return array
     */
    public static function get(string $domain, string $period = '30d', array $metrics = ['visitors']): array
    {
        $metrics = array_intersect($metrics, self::$allowedMetrics);
        $period = in_array($period, self::$allowedPeriods) ? $period : '30d';
        $result = self::getAggregate($domain, $period, $metrics);
        return array_map(function ($item) {
            return $item['value'];
        }, $result);
    }

    /**
     *
     * @param string $domain
     * @param string $period
     * @return int
     */
    public static function getVisitors(string $domain, string $period = '30d'): int
    {
        $period = in_array($period, self::$allowedPeriods) ? $period : '30d';

        return self::getAggregate($domain, $period, ['visitors'])['visitors']['value'] ?? 0;
    }

    /**
     *
     * @param string $domain
     * @param string $period
     * @return int
     */
    public static function getPageviews(string $domain, string $period = '30d'): int
    {
        $period = in_array($period, self::$allowedPeriods) ? $period : '30d';

        return self::getAggregate($domain, $period, ['pageviews'])['pageviews']['value'] ?? 0;
    }

    /**
     *
     * @param string $domain
     * @param string $period
     * @return float
     */
    public static function getBounceRate(string $domain, string $period = '30d'): float
    {
        $period = in_array($period, self::$allowedPeriods) ? $period : '30d';

        return self::getAggregate($domain, $period, ['bounce_rate'])['bounce_rate']['value'] ?? 0.0;
    }

    /**
     *
     * @param string $domain
     * @param string $period
     * @return float
     */
    public static function getVisitDuration(string $domain, string $period = '30d'): float
    {
        $period = in_array($period, self::$allowedPeriods) ? $period : '30d';

        return self::getAggregate($domain, $period, ['visit_duration'])['visit_duration']['value'] ?? 0.0;
    }

    /**
     *
     * @param string $domain
     * @return int
     */
    public static function getRealtimeVisitors(string $domain): int
    {
        $result = Http::withHeaders(self::getHeaders())
            ->get(
                self::getBaseUrl() . '/stats/realtime/visitors',
                [
                    'site_id' => $domain,
                ]
            )
            ->json();

        return $result;
    }

    /**
     *
     * @param string $domain
     * @param string $period
     * @return array
     */
    public static function getTimeseries(string $domain, string $period = '30d'): array
    {
        $period = in_array($period, self::$allowedPeriods) ? $period : '30d';
        $result = Http::withHeaders(self::getHeaders())
                ->get(
                    self::getBaseUrl() . '/stats/timeseries',
                    [
                        'site_id' => $domain,
                        'period' => $period,
                    ]
                )
                ->json()['results'] ?? [];

        return $result;
    }

    /**
     *
     * @param string $domain
     * @param string $property
     * @param string $period
     * @param array $metrics
     * @return array
     */
    public static function getBreakdown(string $domain, string $property = 'visit:source', string $period = '30d', array $metrics = ['visitors']): array
    {
        $metrics = array_intersect($metrics, self::$allowedMetrics);
        $property = in_array($property, self::$allowedPeriods) ? $property : 'visit:source';
        $period = in_array($period, self::$allowedPeriods) ? $period : '30d';
        $result = Http::withHeaders(self::getHeaders())
                ->get(
                    self::getBaseUrl() . '/stats/breakdown',
                    [
                        'site_id' => $domain,
                        'property' => $property,
                        'period' => $period,
                        'metrics' => implode(',', $metrics),
                    ]
                )
                ->json()['results'] ?? [];

        return $result;
    }
}
