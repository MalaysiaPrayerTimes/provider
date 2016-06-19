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

        $geocoder = $this->getMockBuilder(ProviderAggregator::class)
            ->getMock();

        $goutte = $this->getMockBuilder(Client::class)
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

        $jp = new JakimProvider($geotools, $geocoder, $goutte);

        $result = $jp->getCodeByCoordinates(5.00983, 100.647);
        $this->assertEquals('prk-1', $result);
    }

    public function testOutsideCountryCoordinates()
    {
        $batch = $this->getMockBuilder(BatchInterface::class)
            ->getMock();

        $geotools = $this->getMockBuilder(Geotools::class)
            ->getMock();

        $geocoder = $this->getMockBuilder(ProviderAggregator::class)
            ->getMock();

        $goutte = $this->getMockBuilder(Client::class)
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

        $jp = new JakimProvider($geotools, $geocoder, $goutte);

        $this->expectException(DataNotAvailableException::class);
        $jp->getCodeByCoordinates(1.3147268, 103.8116508);
    }

    public function testValidCodes()
    {
        $geotools = $this->getMockBuilder(Geotools::class)
            ->getMock();

        $geocoder = $this->getMockBuilder(ProviderAggregator::class)
            ->getMock();

        $guzzle = $this->getGuzzle([
            new GuzzleResponse(200, [], file_get_contents(__DIR__ . '/Resources/kdh01-2016-06.html'))
        ]);
        
        $goutte = new Client();
        $goutte->setClient($guzzle);

        $jp = new JakimProvider($geotools, $geocoder, $goutte);
        $data = $jp->getTimesByCode('ext-153');
        
        $this->assertEquals(1464730740, $data->getTimes()[0][0]);
    }

    public function testInvalidCodes()
    {
        $geotools = $this->getMockBuilder(Geotools::class)
            ->getMock();

        $geocoder = $this->getMockBuilder(ProviderAggregator::class)
            ->getMock();

        $goutte = $this->getMockBuilder(Client::class)
            ->getMock();

        $jp = new JakimProvider($geotools, $geocoder, $goutte);
        
        $this->expectException(InvalidCodeException::class);
        $jp->getTimesByCode('sgp-1');
    }

    public function testDuplicateCode()
    {
        $geotools = $this->getMockBuilder(Geotools::class)
            ->getMock();

        $geocoder = $this->getMockBuilder(ProviderAggregator::class)
            ->getMock();

        $guzzle = $this->getGuzzle([
            new GuzzleResponse(200, [], file_get_contents(__DIR__ . '/Resources/kdh01-2016-06.html'))
        ]);

        $goutte = new Client();
        $goutte->setClient($guzzle);

        $jp = new JakimProvider($geotools, $geocoder, $goutte);
        $data = $jp->getTimesByCode('ext-516');

        $this->assertEquals('ext-515', $data->getCode());
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
