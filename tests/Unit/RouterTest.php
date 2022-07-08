<?php

namespace SunflowerFuchs\FakeSso\Tests\Unit;

use Exception;
use PHPUnit\Framework\TestCase;
use SunflowerFuchs\FakeSso\Config;
use SunflowerFuchs\FakeSso\Router;
use SunflowerFuchs\FakeSso\SsoException;
use SunflowerFuchs\FakeSso\User;

/**
 * @runTestsInSeparateProcesses
 * @covers \SunflowerFuchs\FakeSso\Router
 */
class RouterTest extends TestCase
{
    /**
     * @test
     * @return void
     * @throws Exception
     */
    public function testAuthorizationPage()
    {
        // test with missing params
        $_GET = [];
        $return = $this->request('/authorize');
        self::assertInstanceOf(SsoException::class, $return);
        self::assertSame(400, $return->getCode());

        // test with empty state
        $_GET = [
            'redirect_uri' => 'https://example.org/',
        ];
        $return = $this->request('/authorize');
        self::assertIsString($return);
        self::assertStringContainsString('name="state" value=""', $return);

        // test with custom state
        $_GET = [
            'redirect_uri' => 'https://example.org/',
            'state' => 'test-state'
        ];
        $return = $this->request('/authorize');
        self::assertIsString($return);
        self::assertStringContainsString('name="state" value="test-state"', $return);

        // and test whether we get existing users shown
        $userId = bin2hex(random_bytes(4));
        new User($userId);
        $_GET = [
            'redirect_uri' => 'https://example.org/',
        ];
        $return = $this->request('/authorize');
        self::assertIsString($return);
        self::assertStringContainsString("<option>${userId}</option>", $return);
    }

    /**
     * @test
     * @throws Exception
     */
    public function testTokenPage()
    {
        // setup
        $config = Config::getInstance();
        $config->setOption('clientSecret', '');
        $_POST = [];

        // first we test with no parameters
        $return = $this->request('/token');
        self::assertInstanceOf(SsoException::class, $return);
        self::assertEquals(400, $return->getCode());

        // then we test the success case
        $userId = bin2hex(random_bytes(4));
        $_POST['code'] = $userId;
        $json = $this->request('/token');
        self::assertIsString($json);
        self::assertJson($json);
        $decoded = json_decode($json, true);
        self::assertIsArray($decoded);
        self::assertArrayHasKey('access_token', $decoded);

        // next we test with an empty required secret
        $config->setOption('clientSecret', 'secret');
        $return = $this->request('/token');
        self::assertInstanceOf(SsoException::class, $return);
        self::assertEquals(401, $return->getCode());

        // and then we test with an invalid required secret
        $_POST['client_secret'] = 'invalid';
        $return = $this->request('/token');
        self::assertInstanceOf(SsoException::class, $return);
        self::assertEquals(401, $return->getCode());
    }

    /**
     * @test
     * @return void
     * @throws SsoException
     */
    public function testUserInfo()
    {
        // setup
        $config = Config::getInstance();
        $config->setOption('additionalFields', false);
        $_SERVER['HTTP_AUTHORIZATION'] = null;
        $user = new User('test');

        // first we test the unauthorized case
        $return = $this->request('/me');
        self::assertInstanceOf(SsoException::class, $return);
        self::assertSame(401, $return->getCode());

        // next we test the success case
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . $user->getToken();
        $json = $this->request('/me');
        self::assertIsString($json);
        self::assertJson($json);
        $decoded = json_decode($json, true);
        self::assertIsArray($decoded);
        self::assertArrayHasKey('sub', $decoded);
        self::assertArrayHasKey('email', $decoded);
        self::assertArrayHasKey('name', $decoded);
        self::assertEquals($user->id, $decoded['sub']);

        // and finally we test the additional fields
        $config->setOption('additionalFields', true);
        $json = $this->request('/me');
        self::assertIsString($json);
        self::assertJson($json);
        $decoded = json_decode($json, true);
        self::assertIsArray($decoded);
        self::assertArrayHasKey('sub', $decoded);
        self::assertArrayHasKey('email', $decoded);
        self::assertArrayHasKey('name', $decoded);
        self::assertArrayHasKey('id', $decoded);
        self::assertEquals($user->id, $decoded['id']);
    }

    /**
     * @test
     * @return void
     */
    public function testHelpPage()
    {
        // there's not much to test here other than correct url display
        $_SERVER['HTTPS'] = 'off';
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'http';
        $_SERVER['HTTP_HOST'] = null;

        // first we test the fallback
        $baseUrl = 'http://host.docker.internal';
        $_SERVER['HTTP_HOST'] = null;
        $return = $this->request('/');
        self::assertIsString($return);
        self::assertStringContainsString("<li>${baseUrl}", $return);

        // then we test a plain http url
        $baseUrl = 'http://example.com';
        $_SERVER['HTTP_HOST'] = 'example.com';
        $return = $this->request('/');
        self::assertIsString($return);
        self::assertStringContainsString("<li>${baseUrl}", $return);

        // then we test a https url via the https header
        $baseUrl = 'https://example.com';
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['HTTP_HOST'] = 'example.com';
        $return = $this->request('/');
        self::assertIsString($return);
        self::assertStringContainsString("<li>${baseUrl}", $return);

        // then we test a https url via the forwarded proto header
        $baseUrl = 'https://example.com';
        $_SERVER['HTTPS'] = 'off';
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
        $_SERVER['HTTP_HOST'] = 'example.com';
        $return = $this->request('/');
        self::assertIsString($return);
        self::assertStringContainsString("<li>${baseUrl}", $return);
    }

    /**
     * @test
     * @return void
     */
    public function testUnknownRoute()
    {
        $return = $this->request('/this/is/invalid');
        self::assertInstanceOf(SsoException::class, $return);
        self::assertEquals(404, $return->getCode());
    }

    /**
     * @param string $url
     * @return string|SsoException
     */
    protected function request(string $url)
    {
        $_SERVER['REQUEST_URI'] = $url;

        ob_start();
        try {
            Router::start();
        } catch (SsoException $exception) {
            ob_end_clean();
            return $exception;
        }
        return ob_get_clean() ?: '';
    }
}