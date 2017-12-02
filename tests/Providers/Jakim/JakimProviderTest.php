<?php

use Geocoder\Geocoder;
use Geocoder\Model\Address;
use Geocoder\Model\AddressCollection;
use Geocoder\Model\AdminLevel;
use Geocoder\Model\AdminLevelCollection;
use Geocoder\Model\Country;
use Goutte\Client;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use Mpt\Exception\DataNotAvailableException;
use Mpt\Exception\InvalidCodeException;
use Mpt\Model\PrayerCode;
use Mpt\Providers\Jakim\JakimProvider;
use PHPUnit\Framework\TestCase;

class JakimProviderTest extends TestCase
{
    protected $history;

    /** @var MockHandler */
    protected $mock;

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
        $guzzle = $this->getGuzzle([
            new GuzzleResponse(200, [], file_get_contents(__DIR__ . '/Resources/kdh01-2016-06.html'))
        ]);

        $goutte = new Client();
        $goutte->setClient($guzzle);

        $jp = $this->getJakimProvider(null, $goutte);
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

        $jp = $this->getJakimProvider(null, $goutte);
        $data = $jp->getTimesByCode('ext-516');

        $this->assertEquals('ext-515', $data->getCode());
    }

    public function testSupportedCodes()
    {
        $jp = $this->getJakimProvider();

        /** @var PrayerCode[] $codes */
        $codes = $jp->getSupportedCodes();

        foreach ($codes as $code) {
            $this->assertNotEmpty($code->getCode(), 'Code is empty: ' . print_r($code, true));
            $this->assertNotEmpty($code->getCity(), 'City is empty: ' . print_r($code, true));
            $this->assertNotEmpty($code->getState(), 'State is empty: ' . print_r($code, true));
        }
    }

    public function testEmptyJakimPage()
    {
        $guzzle = $this->getGuzzle([
            new GuzzleResponse(200, [], file_get_contents(__DIR__ . '/Resources/sgr03-2018-01-empty.html'))
        ]);

        $goutte = new Client();
        $goutte->setClient($guzzle);

        $jp = $this->getJakimProvider(null, $goutte);
        $this->expectException(\Mpt\Exception\InvalidDataException::class);
        $jp->getTimesByCode('ext-306');
    }

    public function testFixableMalformedJakimPage()
    {
        $guzzle = $this->getGuzzle([
            new GuzzleResponse(200, [], file_get_contents(__DIR__ . '/Resources/sbh02-2018-01-fixable.html'))
        ]);

        $goutte = new Client();
        $goutte->setClient($guzzle);

        $jp = $this->getJakimProvider(null, $goutte);
        $data = $jp->getTimesByCode('sbh-3');
        $this->assertEquals(1512075540, $data->getTimes()[0][0]);
    }

    public function testMalformedJakimPage()
    {
        $guzzle = $this->getGuzzle([
            new GuzzleResponse(200, [], file_get_contents(__DIR__ . '/Resources/sbh02-2018-01-malformed.html'))
        ]);

        $goutte = new Client();
        $goutte->setClient($guzzle);

        $jp = $this->getJakimProvider(null, $goutte);
        $this->expectException(\Mpt\Exception\InvalidDataException::class);
        print_r($jp->getTimesByCode('sbh-3'));
    }

    public function testAdminLevelFallback()
    {
        $admin = new AdminLevel(1, 'Pulau Pinang', 'Pulau Pinang');
        $collection = new AdminLevelCollection([$admin]);

        $geocoder = $this->getMockBuilder(Geocoder::class)
            ->getMock();

        $geocoder->expects($this->once())
            ->method('reverse')
            ->willReturn(new AddressCollection([
                new Address(null, null, null, null, null, 'Batu Maung', null, $collection,
                    new Country('Malaysia', 'MY'))
            ]));

        $jp = $this->getJakimProvider($geocoder);

        $result = $jp->getCodeByCoordinates(5.2849237, 100.2752612);
        $this->assertEquals('png-0', $result);
    }

    protected function getJakimProvider($geocoder = null, $goutte = null)
    {
        if (is_null($geocoder)) {
            $geocoder = $this->getMockBuilder(Geocoder::class)
                ->getMock();
        }

        if (is_null($goutte)) {
            $goutte = $this->getMockBuilder(Client::class)
                ->getMock();
        }

        return new JakimProvider($geocoder, $goutte);
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
