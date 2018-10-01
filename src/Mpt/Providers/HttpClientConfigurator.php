<?php

namespace Mpt\Providers;

use Http\Client\Common\Plugin;
use Http\Client\Common\PluginClient;
use Http\Discovery\HttpClientDiscovery;

class HttpClientConfigurator
{
    public static function getHttpClient()
    {
        $httpClient = HttpClientDiscovery::find();

        $plugins = [
            new Plugin\HeaderDefaultsPlugin([
                'User-Agent' => 'mpt-jakim/2.0.0',
            ]),
            new Plugin\DecoderPlugin(),
        ];

        return new PluginClient($httpClient, $plugins);
    }
}
