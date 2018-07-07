<?php

namespace Honeybadger\Tests;

use Mockery;
use Exception;
use GuzzleHttp\Client;
use Honeybadger\Config;
use Honeybadger\Honeybadger;
use PHPUnit\Framework\TestCase;
use Honeybadger\HoneybadgerClient;
use Honeybadger\Exceptions\ServiceException;

class HoneybadgerClientTest extends TestCase
{
    /** @test */
    public function throws_generic_exception_for_notifications()
    {
        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('There was an error sending the payload to Honeybadger.');

        $config = new Config(['api_key' => '1234']);
        $mock = Mockery::mock(Client::class)->makePartial();
        $mock->shouldReceive('post')->andThrow(new Exception);

        $client = new HoneybadgerClient($config, $mock);
        $client->notification([]);
    }

    /** @test */
    public function throws_generic_exception_for_checkins()
    {
        $this->expectException(ServiceException::class);
        $this->expectExceptionMessage('There was an error sending the payload to Honeybadger.');

        $config = new Config(['api_key' => '1234']);
        $mock = Mockery::mock(Client::class)->makePartial();
        $mock->shouldReceive('head')->andThrow(new Exception);

        $client = new HoneybadgerClient($config, $mock);
        $client->checkin('1234');
    }
}
