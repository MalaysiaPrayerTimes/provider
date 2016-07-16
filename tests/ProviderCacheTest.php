<?php

use Mpt\CacheInterface;
use Mpt\Model\LocationCache;
use Mpt\Model\PrayerData;
use Mpt\Provider;
use Mpt\Providers\PrayerTimeProvider;
use PHPUnit\Framework\TestCase;

class ProviderCacheTest extends TestCase
{
    public function testPrayerCacheNotAvailable()
    {
        $data = $this->getMockBuilder(PrayerData::class)
            ->getMock();

        $cache = $this->getMockBuilder(CacheInterface::class)
            ->getMock();

        $prayerProvider = $this->getMockBuilder(PrayerTimeProvider::class)
            ->getMock();

        $cache->expects($this->once())
            ->method('getPrayerData')
            ->willReturn(null);

        $prayerProvider->expects($this->once())
            ->method('setYear')
            ->withAnyParameters()
            ->willReturn($prayerProvider);

        $prayerProvider->expects($this->once())
            ->method('setMonth')
            ->withAnyParameters()
            ->willReturn($prayerProvider);

        $prayerProvider->expects($this->once())
            ->method('getTimesByCode')
            ->with($this->equalTo('test-001'))
            ->willReturn($data);

        $cache->expects($this->once())
            ->method('cachePrayerData')
            ->with($this->identicalTo($data));

        $provider = new Provider();
        $provider->setCache($cache);
        $provider->registerPrayerTimeProvider($prayerProvider);

        $times = $provider->getTimesByCode('test-001');

        $this->assertEquals($times, $data);
    }

    public function testPrayerCacheAvailable()
    {
        $data = $this->getMockBuilder(PrayerData::class)
            ->getMock();

        $cache = $this->getMockBuilder(CacheInterface::class)
            ->getMock();

        $prayerProvider = $this->getMockBuilder(PrayerTimeProvider::class)
            ->getMock();

        $cache->expects($this->once())
            ->method('getPrayerData')
            ->willReturn($data);

        $prayerProvider->expects($this->never())
            ->method('getTimesByCode');

        $cache->expects($this->never())
            ->method('cachePrayerData');

        $provider = new Provider();
        $provider->setCache($cache);
        $provider->registerPrayerTimeProvider($prayerProvider);

        $times = $provider->getTimesByCode('test-001');
        $this->assertEquals($times, $data);
    }

    public function testLocationCacheNotAvailable()
    {
        $data = $this->getMockBuilder(PrayerData::class)
            ->getMock();

        $cache = $this->getMockBuilder(CacheInterface::class)
            ->getMock();

        $prayerProvider = $this->getMockBuilder(PrayerTimeProvider::class)
            ->getMock();

        $cache->expects($this->once())
            ->method('getCodeByLocation')
            ->willReturn(null);

        $prayerProvider->expects($this->atLeastOnce())
            ->method('setYear')
            ->withAnyParameters()
            ->willReturn($prayerProvider);

        $prayerProvider->expects($this->atLeastOnce())
            ->method('setMonth')
            ->withAnyParameters()
            ->willReturn($prayerProvider);

        $prayerProvider->expects($this->once())
            ->method('getCodeByCoordinates')
            ->with($this->equalTo(3.04466), $this->equalTo(101.708))
            ->willReturn('ext-252');

        $prayerProvider->expects($this->once())
            ->method('getTimesByCode')
            ->with($this->equalTo('ext-252'))
            ->willReturn($data);

        $cache->expects($this->once())
            ->method('cacheLocation')
            ->with($this->callback(function (LocationCache $location) {
                return $location->code == 'ext-252' && $location->lat == 3.04466 && $location->lng == 101.708;
            }));

        $provider = new Provider();
        $provider->setCache($cache);
        $provider->registerPrayerTimeProvider($prayerProvider);

        $times = $provider->getTimesByCoordinate(3.04466, 101.708);
        $this->assertEquals($times, $data);
    }

    public function testLocationCacheAvailable()
    {
        $location = new LocationCache('ext-252', 3.04466, 101.708);

        $data = $this->getMockBuilder(PrayerData::class)
            ->getMock();

        $cache = $this->getMockBuilder(CacheInterface::class)
            ->getMock();

        $prayerProvider = $this->getMockBuilder(PrayerTimeProvider::class)
            ->getMock();

        $cache->expects($this->once())
            ->method('getCodeByLocation')
            ->willReturn($location);

        $prayerProvider->expects($this->atLeastOnce())
            ->method('setYear')
            ->withAnyParameters()
            ->willReturn($prayerProvider);

        $prayerProvider->expects($this->atLeastOnce())
            ->method('setMonth')
            ->withAnyParameters()
            ->willReturn($prayerProvider);

        $prayerProvider->expects($this->once())
            ->method('getTimesByCode')
            ->with($this->equalTo('ext-252'))
            ->willReturn($data);

        $prayerProvider->expects($this->never())
            ->method('getCodeByCoordinates');

        $cache->expects($this->never())
            ->method('cacheLocation');

        $provider = new Provider();
        $provider->setCache($cache);
        $provider->registerPrayerTimeProvider($prayerProvider);

        $times = $provider->getTimesByCoordinate(3.04466, 101.708);
        $this->assertEquals($times, $data);
    }
}
