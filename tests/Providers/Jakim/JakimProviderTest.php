<?php

use Geocoder\Model\Address;
use Geocoder\Model\Country;
use Geocoder\ProviderAggregator;
use Goutte\Client;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use League\Geotools\Batch\BatchGeocoded;
use League\Geotools\Batch\BatchInterface;
use League\Geotools\Geotools;
use Mpt\Exception\DataNotAvailableException;
use Mpt\Exception\InvalidCodeException;
use Mpt\Providers\Jakim\JakimProvider;
use PHPUnit\Framework\TestCase;

class JakimProviderTest extends TestCase
{
    protected $history;

    /** @var MockHandler */
    protected $mock;

    public function testInCountryCoordinates()
    {
        $batch = $this->getMockBuilder(BatchInterface::class)
            ->getMock();

        $geotools = $this->getMockBuilder(Geotools::class)
            ->getMock();

        $bgr = $this->getMockBuilder(BatchGeocoded::class)
            ->getMock();

        $geotools->method('batch')
            ->willReturn($batch);

        $batch->method('reverse')
            ->willReturnSelf();

        $batch->expects($this->once())
            ->method('parallel')
            ->willReturn([$bgr]);

        $bgr->method('getAddress')
            ->willReturn(new Address(null, null, null, null, null, 'Bagan Serai', null, null,
                new Country('Malaysia', 'MY')));

        $jp = $this->getJakimProvider($geotools);

        $result = $jp->getCodeByCoordinates(5.00983, 100.647);
        $this->assertEquals('prk-1', $result);
    }

    public function testOutsideCountryCoordinates()
    {
        $batch = $this->getMockBuilder(BatchInterface::class)
            ->getMock();

        $geotools = $this->getMockBuilder(Geotools::class)
            ->getMock();

        $bgr = $this->getMockBuilder(BatchGeocoded::class)
            ->getMock();

        $geotools->method('batch')
            ->willReturn($batch);

        $batch->method('reverse')
            ->willReturnSelf();

        $batch->expects($this->once())
            ->method('parallel')
            ->willReturn([$bgr]);

        $bgr->method('getAddress')
            ->willReturn(new Address(null, null, null, null, null, 'Bagan Serai', null, null,
                new Country('Singapore', 'SG')));

        $jp = $this->getJakimProvider($geotools);

        $this->expectException(DataNotAvailableException::class);
        $jp->getCodeByCoordinates(1.3147268, 103.8116508);
    }

    public function testValidCodes()
    {
        $guzzle = $this->getGuzzle([
            new GuzzleResponse(200, [], file_get_contents(__DIR__ . '/Resources/kdh01-2016-06.html'))
        ]);

        $goutte = new Client();
        $goutte->setClient($guzzle);

        $jp = $this->getJakimProvider(null, null, $goutte);
        $data = $jp->setMonth(6)
            ->setYear(2016)
            ->getTimesByCode('ext-153');

        $this->assertEquals(1464730740, $data->getTimes()[0][0]);
    }

    public function testInvalidCodes()
    {
        $jp = $this->getJakimProvider();
        $this->expectException(InvalidCodeException::class);
        $jp->getTimesByCode('sgp-1');
    }

    public function testDuplicateCode()
    {
        $guzzle = $this->getGuzzle([
            new GuzzleResponse(200, [], file_get_contents(__DIR__ . '/Resources/kdh01-2016-06.html'))
        ]);

        $goutte = new Client();
        $goutte->setClient($guzzle);

        $jp = $this->getJakimProvider(null, null, $goutte);
        $data = $jp->getTimesByCode('ext-516');

        $this->assertEquals('ext-515', $data->getCode());
    }

    protected function getJakimProvider($geotools = null, $geocoder = null, $goutte = null)
    {
        if (is_null($geotools)) {
            $geotools = $this->getMockBuilder(Geotools::class)
                ->getMock();
        }

        if (is_null($geocoder)) {
            $geocoder = $this->getMockBuilder(ProviderAggregator::class)
                ->getMock();
        }

        if (is_null($goutte)) {
            $goutte = $this->getMockBuilder(Client::class)
                ->getMock();
        }

        return new JakimProvider($geotools, $geocoder, $goutte);
    }

    protected function getGuzzle(array $responses = [])
    {
        if (empty($responses)) {
            $responses = [new GuzzleResponse(200, [], '<html><body><p>Hi</p></body></html>')];
        }

        $this->mock = new MockHandler($responses);
        $handlerStack = HandlerStack::create($this->mock);
        $this->history = [];
        $handlerStack->push(Middleware::history($this->history));
        $guzzle = new GuzzleClient(array('redirect.disable' => true, 'base_uri' => '', 'handler' => $handlerStack));
        return $guzzle;
    }
}
