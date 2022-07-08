<?php

namespace SunflowerFuchs\FakeSso\Tests\Unit;

use Exception;
use SunflowerFuchs\FakeSso\SsoException;
use SunflowerFuchs\FakeSso\User;
use PHPUnit\Framework\TestCase;

/**
 * @covers \SunflowerFuchs\FakeSso\User
 */
class UserTest extends TestCase
{
    /**
     * @test
     * @throws SsoException
     * @throws Exception
     */
    public function createUser(): User
    {
        $userId = bin2hex(random_bytes(4));

        $user = new User($userId);
        self::assertEquals($userId, $user->id);

        return $user;
    }

    /**
     * @test
     * @throws SsoException
     * @throws Exception
     */
    public function createUserFromEmail(): User
    {
        $userId = bin2hex(random_bytes(4)) . '@example.test';

        $user = new User($userId);
        self::assertEquals($userId, $user->id);
        self::assertEquals($userId, $user->email);

        return $user;
    }

    /**
     * @test
     * @depends createUser
     * @throws SsoException
     */
    public function loadUser(User $existingUser): void
    {
        $loadedUser = new User($existingUser->id);

        self::assertEquals($existingUser->id, $loadedUser->id);
        self::assertEquals($existingUser->email, $loadedUser->email);
        self::assertEquals($existingUser->name, $loadedUser->name);
    }

    /**
     * @test
     * @depends createUser
     * @throws SsoException
     */
    public function createTokenFromUser(User $user): string
    {
        $token = $user->getToken();
        $decoded = base64_decode($token);
        self::assertIsString($decoded);
        self::assertStringStartsWith($user->id . '|', $decoded);

        return $token;
    }

    /**
     * @test
     * @depends createTokenFromUser
     * @throws SsoException
     */
    public function loadUserFromToken(string $token): void
    {
        $user = User::getByToken($token);
        self::assertInstanceOf(User::class, $user);
    }

    /**
     * @test
     * @throws Exception
     */
    public function getAllUserIdentifiers(): void
    {
        $users = [
            new User('a'),
            new User('b'),
            new User('c'),
        ];
        $expected = array_map(fn (User $user) => $user->id, $users);

        $identifiers = User::getAllIdentifiers();
        self::assertIsArray($identifiers);
        self::assertEquals($expected, array_values(array_intersect($identifiers, $expected)));

    }

    /**
     * @test
     * @return void
     */
    public function tryInvalidToken() : void
    {
        $token = '$invalid_token';
        self::expectException(SsoException::class);
        User::getByToken($token);
    }

    /**
     * @test
     * @return void
     */
    public function tryMalformedToken() : void
    {
        $token = base64_encode('invalid');
        self::expectException(SsoException::class);
        User::getByToken($token);
    }
}
