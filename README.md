
# Laravel Plausible

[![Latest Version on Packagist](https://img.shields.io/packagist/v/kilobyteno/laravel-plausible.svg?style=flat-square)](https://packagist.org/packages/kilobyteno/laravel-plausible)
[![GitHub Tests Action Status](https://img.shields.io/github/workflow/status/kilobyteno/laravel-plausible/run-tests?label=tests)](https://github.com/kilobyteno/laravel-plausible/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/workflow/status/kilobyteno/laravel-plausible/Check%20&%20fix%20styling?label=code%20style)](https://github.com/kilobyteno/laravel-plausible/actions?query=workflow%3A"Check+%26+fix+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/kilobyteno/laravel-plausible.svg?style=flat-square)](https://packagist.org/packages/kilobyteno/laravel-plausible)

A simple package for communicating with the Plausible API within Laravel.

## Installation

You can install the package via composer:

```bash
composer require kilobyteno/laravel-plausible
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="plausible-config"
```

This is the contents of the published config file:

```php
return [
    'api_url' => env('PLAUSIBLE_API_URL', 'https://plausible.io/api/v1'),
    'api_key' => env('PLAUSIBLE_API_KEY', ''),
];
```

## Usage

```php
use Kilobyteno\LaravelPlausible\Plausible;

// Show the stats for the default period (30d), the period is optional for all methods
$visitors = Plausible::getVisitors($domain->name);

// Show the stats for the last 12 months
$pageviews = Plausible::getPageviews($domain->name, '12mo');

// Get bounce rates for the last 7 days
$bounceRate = Plausible::getBounceRate($domain->name, '7d');

// Get visit duration for the last days
$visitDuration = Plausible::getVisitDuration($domain->name, 'day');

// Get realtime visitors
$realtimeVisitors = Plausible::getRealtimeVisitors($domain->name);

// Add a custom period and metrics
$stats = Plausible::get($domain->name, 'month', ['visitors', 'pageviews']);

$availablePeriods = Plausible::getAllowedPeriods();
// returns: ['12mo', '6mo', 'month', '30d', '7d', 'day']

$allowedMetrics = Plausible::getAllowedMetrics();
// returns: ['visitors', 'pageviews', 'bounce_rate', 'visit_duration', 'visits', 'events']

$allowedProperties = Plausible::getAllowedProperties();
/* 
returns [
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
*/

```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Kilobyte AS](https://github.com/kilobyteno)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
