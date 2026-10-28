<?php

namespace App\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\PasswordHasher\Hasher\NativePasswordHasher;

class PasswordHasherTest extends TestCase
{
    public function testPasswordCanBeHashedAndVerified(): void
    {
        $hasher = new NativePasswordHasher();

        $password = 'BiblioConnect-Test-2026!';
        $hash = $hasher->hash($password);

        $this->assertNotSame($password, $hash);
        $this->assertTrue($hasher->verify($hash, $password));
    }

    public function testIncorrectPasswordIsRejected(): void
    {
        $hasher = new NativePasswordHasher();

        $hash = $hasher->hash('CorrectPassword123!');

        $this->assertFalse(
            $hasher->verify($hash, 'WrongPassword123!')
        );
    }
}
