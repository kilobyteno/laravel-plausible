<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Kilobyteno\LaravelPlausible\Exceptions\PlausibleAPIException;
use Kilobyteno\LaravelPlausible\Plausible;

beforeEach(function () {
    config([
        'plausible.api_url' => 'https://plausible.test/api/v1',
        'plausible.api_key' => 'test-api-key',
    ]);

    $this->app->instance('request', Request::create('/', 'GET', [], [], [], [
        'REMOTE_ADDR' => '192.168.1.50',
        'HTTP_USER_AGENT' => 'PlausibleTest/1.0',
    ]));
});

it('builds headers from config and request', function () {
    expect(Plausible::getHeaders())->toBe([
        'X-Forwarded-For' => '192.168.1.50',
        'User-Agent' => 'PlausibleTest/1.0',
        'Authorization' => 'Bearer test-api-key',
    ]);
});

it('returns base url from config', function () {
    expect(Plausible::getBaseUrl())->toBe('https://plausible.test/api/v1');
});

it('exposes allowed periods metrics and properties', function () {
    expect(Plausible::getAllowedPeriods())->toContain('30d', '12mo', 'day');
    expect(Plausible::getAllowedMetrics())->toContain('visitors', 'pageviews');
    expect(Plausible::getAllowedProperties())->toContain('visit:source', 'event:page');
});

it('fetches visitors from aggregate endpoint', function () {
    Http::fake([
        'https://plausible.test/api/v1/stats/aggregate*' => Http::response([
            'results' => [
                'visitors' => ['value' => 42],
            ],
        ], 200),
    ]);

    expect(Plausible::getVisitors('example.com'))->toBe(42);

    Http::assertSent(function ($request) {
        parse_str(parse_url($request->url(), PHP_URL_QUERY), $query);

        return str_contains($request->url(), '/stats/aggregate')
            && $query['site_id'] === 'example.com'
            && $query['period'] === '30d'
            && str_contains($query['metrics'], 'visitors');
    });
});

it('uses unknown period as 30d', function () {
    Http::fake([
        'https://plausible.test/api/v1/stats/aggregate*' => Http::response([
            'results' => [
                'visitors' => ['value' => 1],
            ],
        ], 200),
    ]);

    Plausible::getVisitors('example.com', 'invalid-period');

    Http::assertSent(function ($request) {
        parse_str(parse_url($request->url(), PHP_URL_QUERY), $query);

        return $query['period'] === '30d';
    });
});

it('fetches pageviews bounce rate and visit duration', function () {
    Http::fake([
        'https://plausible.test/api/v1/stats/aggregate*' => Http::response([
            'results' => [
                'pageviews' => ['value' => 200],
                'bounce_rate' => ['value' => 0.45],
                'visit_duration' => ['value' => 120.5],
            ],
        ], 200),
    ]);

    expect(Plausible::getPageviews('example.com'))->toBe(200);
    expect(Plausible::getBounceRate('example.com'))->toBe(0.45);
    expect(Plausible::getVisitDuration('example.com'))->toBe(120.5);
});

it('maps get() to metric values only', function () {
    Http::fake([
        'https://plausible.test/api/v1/stats/aggregate*' => Http::response([
            'results' => [
                'visitors' => ['value' => 10],
                'pageviews' => ['value' => 25],
            ],
        ], 200),
    ]);

    expect(Plausible::get('example.com', '7d', ['visitors', 'pageviews']))->toBe([
        'visitors' => 10,
        'pageviews' => 25,
    ]);

    Http::assertSent(function ($request) {
        parse_str(parse_url($request->url(), PHP_URL_QUERY), $query);

        return $query['period'] === '7d'
            && str_contains($query['metrics'], 'visitors')
            && str_contains($query['metrics'], 'pageviews');
    });
});

it('filters disallowed metrics from get()', function () {
    Http::fake([
        'https://plausible.test/api/v1/stats/aggregate*' => Http::response([
            'results' => [
                'visitors' => ['value' => 3],
            ],
        ], 200),
    ]);

    Plausible::get('example.com', '30d', ['visitors', 'not_a_real_metric']);

    Http::assertSent(function ($request) {
        parse_str(parse_url($request->url(), PHP_URL_QUERY), $query);

        return ! str_contains($query['metrics'], 'not_a_real_metric');
    });
});

it('throws PlausibleAPIException when aggregate returns error', function () {
    Http::fake([
        'https://plausible.test/api/v1/stats/aggregate*' => Http::response([
            'error' => 'Invalid site_id',
        ], 200),
    ]);

    Plausible::getVisitors('bad.site');
})->throws(PlausibleAPIException::class, 'Invalid site_id');

it('fetches realtime visitors', function () {
    Http::fake([
        'https://plausible.test/api/v1/stats/realtime/visitors*' => Http::response(5, 200),
    ]);

    expect(Plausible::getRealtimeVisitors('example.com'))->toBe(5);
});

it('throws on realtime error responses', function () {
    Http::fake([
        'https://plausible.test/api/v1/stats/realtime/visitors*' => Http::response([
            'error' => 'Unauthorized',
        ], 200),
    ]);

    Plausible::getRealtimeVisitors('example.com');
})->throws(PlausibleAPIException::class, 'Unauthorized');

it('fetches timeseries results', function () {
    $series = [['date' => '2025-01-01', 'visitors' => 1]];
    Http::fake([
        'https://plausible.test/api/v1/stats/timeseries*' => Http::response([
            'results' => $series,
        ], 200),
    ]);

    expect(Plausible::getTimeseries('example.com', 'month'))->toBe($series);
});

it('fetches breakdown with allowed property', function () {
    $rows = [['source' => 'direct', 'visitors' => 9]];
    Http::fake([
        'https://plausible.test/api/v1/stats/breakdown*' => Http::response([
            'results' => $rows,
        ], 200),
    ]);

    expect(Plausible::getBreakdown('example.com', 'event:page', '7d', ['visitors']))
        ->toBe($rows);

    Http::assertSent(function ($request) {
        parse_str(parse_url($request->url(), PHP_URL_QUERY), $query);

        return $query['property'] === 'event:page'
            && $query['period'] === '7d';
    });
});

it('defaults unknown breakdown property to visit:source', function () {
    Http::fake([
        'https://plausible.test/api/v1/stats/breakdown*' => Http::response(['results' => []], 200),
    ]);

    Plausible::getBreakdown('example.com', 'not-a-valid-property');

    Http::assertSent(function ($request) {
        parse_str(parse_url($request->url(), PHP_URL_QUERY), $query);

        return $query['property'] === 'visit:source';
    });
});
