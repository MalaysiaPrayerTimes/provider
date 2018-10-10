<?php

namespace Providers\Jakim;

use Geocoder\Geocoder;
use Geocoder\Model\Address;
use Geocoder\Model\AddressCollection;
use Geocoder\Model\Country;
use Http\Discovery\HttpClientDiscovery;
use Http\Discovery\MessageFactoryDiscovery;
use Http\Mock\Client;
use Mpt\Exception\DataNotAvailableException;
use Mpt\Providers\Jakim\YiiJakimProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class YiiJakimProviderTest extends TestCase
{
    public function testInCountryCoordinates()
    {
        $geocoder = $this->getMockBuilder(Geocoder::class)
            ->getMock();

        $geocoder->expects($this->once())
            ->method('reverse')
            ->willReturn(new AddressCollection([
                new Address(null, null, null, null, null, 'Bagan Serai', null, null,
                    new Country('Malaysia', 'MY'))
            ]));

        $jp = $this->getJakimProvider($geocoder);

        $result = $jp->getCodeByCoordinates(5.00983, 100.647);
        $this->assertEquals('prk-1', $result);
    }

    public function testOutsideCountryCoordinates()
    {
        $geocoder = $this->getMockBuilder(Geocoder::class)
            ->getMock();

        $geocoder->expects($this->once())
            ->method('reverse')
            ->willReturn(new AddressCollection([
                new Address(null, null, null, null, null, 'Bagan Serai', null, null,
                    new Country('Singapore', 'SG'))
            ]));

        $jp = $this->getJakimProvider($geocoder);

        $this->expectException(DataNotAvailableException::class);
        $jp->getCodeByCoordinates(1.3147268, 103.8116508);
    }

    public function testValidCodes()
    {
        $response = $this->getMockResponse(file_get_contents(__DIR__ . '/Resources/sgr01-2018-10.json'));

        $client = new Client();
        $client->addResponse($response);

        $jp = $this->getJakimProvider(null, $client);

        $data = $jp->setMonth(10)
            ->setYear(2018)
            ->getTimesByCode('sgr-0');

        $this->assertEquals(1538343900, $data->getTimes()[0][0]);
    }

    public function testExtraCodes()
    {
        $response = $this->getMockResponse(file_get_contents(__DIR__ . '/Resources/png01-2018-10.json'));

        $client = new Client();
        $client->addResponse($response);

        $jp = $this->getJakimProvider(null, $client);

        $data = $jp->setMonth(10)
            ->setYear(2018)
            ->getTimesByCode('ext-376');

        $this->assertEquals(1538344200, $data->getTimes()[0][0]);
    }

    protected function getJakimProvider($geocoder = null, $client = null, $requestFactory = null)
    {
        if ($geocoder === null) {
            $geocoder = $this->getMockBuilder(Geocoder::class)
                ->getMock();
        }

        if ($client === null) {
            $client = HttpClientDiscovery::find();
        }

        if ($requestFactory === null) {
            $requestFactory = MessageFactoryDiscovery::find();
        }

        return new YiiJakimProvider($geocoder, $client, $requestFactory);
    }

    protected function getMockResponse($content)
    {
        $response = $this->getMockBuilder(ResponseInterface::class)
            ->getMock();

        $body = $this->getMockBuilder(StreamInterface::class)
            ->getMock();

        $response->expects($this->any())
            ->method('getBody')
            ->willReturn($body);

        $body->expects($this->once())
            ->method('getContents')
            ->willReturn($content);

        return $response;
    }
}
