<?php

namespace SunflowerFuchs\FakeSso\Tests\Unit;

use PHPUnit\Framework\TestCase;
use SunflowerFuchs\FakeSso\Config;

/**
 * @runTestsInSeparateProcesses
 * @covers \SunflowerFuchs\FakeSso\Config
 */
class ConfigTest extends TestCase
{
    /**
     * @test
     * @return void
     */
    public function initializeWithDefaults()
    {
        $_ENV = [];
        $config = Config::getInstance();

        foreach (Config::$defaults as $option => $default) {
            self::assertEquals($default, $config->getOption($option));
        }
    }

    /**
     * @test
     * @return void
     */
    public function initializeFromEnv()
    {
        $_ENV = [
            'SHOW_KNOWN' => false,
            'ADDITIONAL_FIELDS' => false,
            'CLIENT_SECRET' => 'secret',
            'DB_FILE' => '/tmp/users.sqlite'
        ];
        $config = Config::getInstance();

        self::assertEquals($_ENV['SHOW_KNOWN'], $config->getOption('showKnown'));
        self::assertEquals($_ENV['ADDITIONAL_FIELDS'], $config->getOption('additionalFields'));
        self::assertEquals($_ENV['CLIENT_SECRET'], $config->getOption('clientSecret'));
        self::assertEquals($_ENV['DB_FILE'], $config->getOption('dbFile'));
    }
}