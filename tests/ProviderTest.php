<?php

use Mpt\Exception\DataNotAvailableException;
use Mpt\Exception\InvalidCodeException;
use Mpt\Model\PrayerData;
use Mpt\Provider;
use Mpt\Providers\PrayerTimeProvider;
use PHPUnit\Framework\TestCase;

class ProviderTest extends TestCase
{
    public function testNoRegisteredProviders()
    {
        $this->expectException(RuntimeException::class);

        $provider = new Provider();
        $provider->getTimesByCode('test-001');
    }

    public function testSettingYearAndMonths()
    {
        $provider = new Provider();

        $provider->setYear(2016);
        $this->assertEquals(2016, $provider->getYear());

        $provider->setMonth(7);
        $this->assertEquals(7, $provider->getMonth());
    }

    public function testMultiplePrayerProviderForFirst()
    {
        $myData = $this->getMockBuilder(PrayerData::class)
            ->setMockClassName('MyPrayerData')
            ->getMock();

        $myProvider = $this->getMockBuilder(PrayerTimeProvider::class)
            ->setMockClassName('MyProvider')
            ->getMock();

        $sgProvider = $this->getMockBuilder(PrayerTimeProvider::class)
            ->setMockClassName('SgProvider')
            ->getMock();

        $myProvider->expects($this->atLeastOnce())
            ->method('setYear')
            ->withAnyParameters()
            ->willReturn($myProvider);

        $myProvider->expects($this->atLeastOnce())
            ->method('setMonth')
            ->withAnyParameters()
            ->willReturn($myProvider);

        $myProvider->expects($this->atLeastOnce())
            ->method('getTimesByCode')
            ->with($this->equalTo('ext-352'))
            ->willReturn($myData);

        $sgProvider->expects($this->never())
            ->method('getTimesByCode');

        $myProvider->expects($this->once())
            ->method('getCodeByCoordinates')
            ->with($this->equalTo(3.04466), $this->equalTo(101.708))
            ->willReturn('ext-352');

        $sgProvider->expects($this->never())
            ->method('getCodeByCoordinates');

        $provider = new Provider();
        $provider->registerPrayerTimeProvider($myProvider);
        $provider->registerPrayerTimeProvider($sgProvider);

        $myTimes = $provider->getTimesByCode('ext-352');
        $this->assertEquals($myData, $myTimes);

        $myTimes2 = $provider->getTimesByCoordinate(3.04466, 101.708);
        $this->assertEquals($myData, $myTimes2);
    }

    public function testMultiplePrayerProviderForSecond()
    {
        $sgData = $this->getMockBuilder(PrayerData::class)
            ->setMockClassName('SgPrayerData')
            ->getMock();

        $myProvider = $this->getMockBuilder(PrayerTimeProvider::class)
            ->setMockClassName('MyProvider')
            ->getMock();

        $sgProvider = $this->getMockBuilder(PrayerTimeProvider::class)
            ->setMockClassName('SgProvider')
            ->getMock();

        $myProvider->expects($this->atLeastOnce())
            ->method('setYear')
            ->withAnyParameters()
            ->willReturn($myProvider);

        $myProvider->expects($this->atLeastOnce())
            ->method('setMonth')
            ->withAnyParameters()
            ->willReturn($myProvider);

        $sgProvider->expects($this->atLeastOnce())
            ->method('setYear')
            ->withAnyParameters()
            ->willReturn($sgProvider);

        $sgProvider->expects($this->atLeastOnce())
            ->method('setMonth')
            ->withAnyParameters()
            ->willReturn($sgProvider);

        $myProvider->expects($this->atLeastOnce())
            ->method('getTimesByCode')
            ->with($this->equalTo('sgr-1'))
            ->willThrowException(new InvalidCodeException());

        $sgProvider->expects($this->atLeastOnce())
            ->method('getTimesByCode')
            ->with($this->equalTo('sgr-1'))
            ->willReturn($sgData);

        $myProvider->expects($this->once())
            ->method('getCodeByCoordinates')
            ->with($this->equalTo(1.3147268), $this->equalTo(103.8116508))
            ->willThrowException(new DataNotAvailableException());

        $sgProvider->expects($this->once())
            ->method('getCodeByCoordinates')
            ->with($this->equalTo(1.3147268), $this->equalTo(103.8116508))
            ->willReturn('sgr-1');

        $provider = new Provider();
        $provider->registerPrayerTimeProvider($myProvider);
        $provider->registerPrayerTimeProvider($sgProvider);

        $sgTimes = $provider->getTimesByCode('sgr-1');
        $this->assertEquals($sgData, $sgTimes);

        $sgTimes2 = $provider->getTimesByCoordinate(1.3147268, 103.8116508);
        $this->assertEquals($sgData, $sgTimes2);
    }

    public function testNoSupportedCodeProvider()
    {
        $myProvider = $this->getMockBuilder(PrayerTimeProvider::class)
            ->setMockClassName('MyProvider')
            ->getMock();

        $sgProvider = $this->getMockBuilder(PrayerTimeProvider::class)
            ->setMockClassName('SgProvider')
            ->getMock();

        $myProvider->expects($this->atLeastOnce())
            ->method('setYear')
            ->withAnyParameters()
            ->willReturn($myProvider);

        $myProvider->expects($this->atLeastOnce())
            ->method('setMonth')
            ->withAnyParameters()
            ->willReturn($myProvider);

        $sgProvider->expects($this->atLeastOnce())
            ->method('setYear')
            ->withAnyParameters()
            ->willReturn($sgProvider);

        $sgProvider->expects($this->atLeastOnce())
            ->method('setMonth')
            ->withAnyParameters()
            ->willReturn($sgProvider);

        $myProvider->expects($this->once())
            ->method('getTimesByCode')
            ->with($this->equalTo('bru-1'))
            ->willThrowException(new InvalidCodeException());

        $sgProvider->expects($this->once())
            ->method('getTimesByCode')
            ->with($this->equalTo('bru-1'))
            ->willThrowException(new InvalidCodeException());

        $provider = new Provider();
        $provider->registerPrayerTimeProvider($myProvider);
        $provider->registerPrayerTimeProvider($sgProvider);

        $this->expectException(InvalidCodeException::class);
        $provider->getTimesByCode('bru-1');
    }

    public function testNoSupportedLocationProvider()
    {
        $myProvider = $this->getMockBuilder(PrayerTimeProvider::class)
            ->setMockClassName('MyProvider')
            ->getMock();

        $sgProvider = $this->getMockBuilder(PrayerTimeProvider::class)
            ->setMockClassName('SgProvider')
            ->getMock();

        $myProvider->expects($this->atLeastOnce())
            ->method('setYear')
            ->withAnyParameters()
            ->willReturn($myProvider);

        $myProvider->expects($this->atLeastOnce())
            ->method('setMonth')
            ->withAnyParameters()
            ->willReturn($myProvider);

        $sgProvider->expects($this->atLeastOnce())
            ->method('setYear')
            ->withAnyParameters()
            ->willReturn($sgProvider);

        $sgProvider->expects($this->atLeastOnce())
            ->method('setMonth')
            ->withAnyParameters()
            ->willReturn($sgProvider);

        $myProvider->expects($this->once())
            ->method('getCodeByCoordinates')
            ->withAnyParameters()
            ->willThrowException(new DataNotAvailableException());

        $sgProvider->expects($this->once())
            ->method('getCodeByCoordinates')
            ->withAnyParameters()
            ->willThrowException(new DataNotAvailableException());

        $provider = new Provider();
        $provider->registerPrayerTimeProvider($myProvider);
        $provider->registerPrayerTimeProvider($sgProvider);

        $this->expectException(DataNotAvailableException::class);
        $provider->getTimesByCoordinate(4.5240321, 114.1578469);
    }

    public function testGetSupportedCodes()
    {
        $myCodes = [
            'c1',
            'c2',
        ];

        $sgCodes = [
            's1',
            's2',
        ];

        $myProvider = $this->getMockBuilder(PrayerTimeProvider::class)
            ->setMockClassName('MyProvider')
            ->getMock();

        $sgProvider = $this->getMockBuilder(PrayerTimeProvider::class)
            ->setMockClassName('SgProvider')
            ->getMock();

        $myProvider->expects($this->once())
            ->method('getName')
            ->willReturn('my');

        $sgProvider->expects($this->once())
            ->method('getName')
            ->willReturn('sg');

        $myProvider->expects($this->once())
            ->method('getSupportedCodes')
            ->willReturn($myCodes);

        $sgProvider->expects($this->once())
            ->method('getSupportedCodes')
            ->willReturn($sgCodes);

        $provider = new Provider();
        $provider->registerPrayerTimeProvider($myProvider);
        $provider->registerPrayerTimeProvider($sgProvider);

        $codes = $provider->getSupportedCodes();

        $this->assertArrayHasKey('my', $codes);
        $this->assertArrayHasKey('sg', $codes);

        $this->assertEquals($myCodes, $codes['my']);
        $this->assertEquals($sgCodes, $codes['sg']);
    }
}
