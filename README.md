# MPT Provider

[![Build Status](https://travis-ci.org/MalaysiaPrayerTimes/provider.svg?branch=master)](https://travis-ci.org/MalaysiaPrayerTimes/provider)
[![codecov](https://codecov.io/gh/MalaysiaPrayerTimes/provider/branch/master/graph/badge.svg)](https://codecov.io/gh/MalaysiaPrayerTimes/provider)

This library is used by MPT to retrieve and properly format prayer data from external sources.

## Supported Providers
- JAKIM (www.e-solat.gov.my)

## Requirements
- PHP 7+

## Usage

### Setup
```php
$adapter = new \Ivory\HttpAdapter\CurlHttpAdapter();
$geotools = new \League\Geotools\Geotools();
$geocoder = new \Geocoder\ProviderAggregator();
$goutte = new \Goutte\Client();

$geocoder->registerProviders([
    new \Geocoder\Provider\GoogleMaps($adapter, null, null, true, '<api-key>'),
]);

$provider = new \Mpt\Provider();
$provider->registerPrayerTimeProvider($jp);
```

### Add Providers
```php
$jp = new \Mpt\Providers\Jakim\JakimProvider($geotools, $geocoder, $goutte);

// or any other providers implementing PrayerTimeProvider
// $sg = new CustomProvider();

$provider->registerPrayerTimeProvider($jp);
```

### Get Prayer Data
```php
/**
 * Get prayer data by provider's code
 *
 * @var PrayerData
 */
$times = $provider->getTimesByCode('ext-352');

/**
 * Get prayer data by coordinates
 *
 * @var PrayerData
 */
$times = $provider->getTimesByCoordinates(3.04466, 101.707);
```
