<?php

namespace SunflowerFuchs\FakeSso\Tests\Integration;

use Exception;
use PHPUnit\Framework\TestCase;
use SunflowerFuchs\FakeSso\Config;
use SunflowerFuchs\FakeSso\Router;
use SunflowerFuchs\FakeSso\SsoException;

/**
 * @coversNothing
 */
class AuthFlowTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        $config = Config::getInstance();
        $config->setOption('showKnown', true);
        $config->setOption('additionalFields', true);
        $config->setOption('clientSecret', '');
    }

    /**
     * @test
     * @return string
     */
    public function getAuthPage() : void
    {
        $_GET = [
            'redirect_uri' => 'https://example.org/',
            'state' => 'test-state',
        ];
        $return = $this->request('/authorize');

        self::assertIsString($return);
        self::assertStringContainsString('name="state" value="test-state"', $return);
    }

    /**
     * @test
     * the dependency here is just for having the correct flow, in reality it doesn't make a difference
     * @depends getAuthPage
     * @runInSeparateProcess due to header() call
     * @throws Exception
     * @return array
     */
    public function getAccessToken() : array
    {
        $userId = bin2hex(random_bytes(4));

        $_POST = [
            'code' => $userId
        ];
        $json = $this->request('/token');
        self::assertIsString($json);
        self::assertJson($json);
        $decoded = json_decode($json, true);
        self::assertIsArray($decoded);
        self::assertArrayHasKey('access_token', $decoded);

        return [
            'token' => $decoded['access_token'],
            'userId' => $userId
        ];
    }

    /**
     * @test
     * @runInSeparateProcess due to header() call
     * @depends getAccessToken
     */
    public function getUserInfo(array $accessData)
    {
        ['userId' => $userId, 'token' => $accessToken] = $accessData;
        $_SERVER['HTTP_AUTHORIZATION'] = $accessToken;
        $userInfo = $this->request('/me');

        self::assertIsString($userInfo);
        self::assertJson($userInfo);
        $decoded = json_decode($userInfo, true);
        self::assertIsArray($decoded);
        self::assertArrayHasKey('email', $decoded);
        self::assertArrayHasKey('sub', $decoded);
        self::assertEquals($userId, $decoded['sub']);
    }

    /**
     * @param string $url
     * @return string|SsoException
     */
    protected function request(string $url) {
        $_SERVER['REQUEST_URI'] = $url;

        ob_start();
        try{
            Router::start();
        } catch (SsoException $exception) {
            ob_end_clean();
            return $exception;
        }
        return ob_get_clean() ?: '';
    }
}