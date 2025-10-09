<?php
declare(strict_types=1);

namespace tommyknocker\chain\tests;

use PHPUnit\Framework\TestCase;
use tommyknocker\chain\Chain;
use tommyknocker\chain\Registry;
use tommyknocker\chain\tests\fixtures\DummyClass;

final class ChainTest extends TestCase
{
    public function testBasic(): void
    {
        $obj = new class {
            public function hello(): string { return "world"; }
        };

        $chain = Chain::from($obj)->hello();
        $this->assertSame("world", $chain->result());
    }

    public function testSwitchBetweenObjectsFromRegistry(): void
    {
        // Prepare objects
        $userAlice = new class('Alice') {
            public function __construct(private string $name) {}
            public function getName(): string { return $this->name; }
        };

        $userBob = new class('Bob') {
            public function __construct(private string $name) {}
            public function getName(): string { return $this->name; }
        };

        $order = new class(99.95) {
            public function __construct(private float $total) {}
            public function getTotal(): float { return $this->total; }
        };

        // Setup Registry
        $registry = new Registry();
        $registry->set('user', $userAlice);
        $registry->set('order', $order);

        Chain::setResolver($registry);

        $chain = Chain::from($userBob)
            ->getName()        // "Bob"
            ->change('order')  // switch to Order
            ->getTotal()       // 99.95
            ->change('user')   // fallback to Alice
            ->getName();       // "Alice"

        $this->assertSame('Alice', $chain->result());
        $this->assertSame($userAlice, $chain->instance());
    }

    public function testTapExecutesCallbackWithoutChangingInstance(): void
    {
        $user = new class('Alice') {
            public function __construct(private string $name) {}
            public function getName(): string { return $this->name; }
        };

        $called = false;

        $chain = Chain::from($user)
            ->tap(function ($instance) use (&$called, $user) {
                $this->assertSame($user, $instance, 'tap() should receive the current instance');
                $called = true;
            })
            ->getName();

        $this->assertTrue($called, 'tap() should call the provided callback');
        $this->assertSame('Alice', $chain->result(), 'Result should be the return value of getName()');
        $this->assertSame($user, $chain->instance(), 'tap() must not change the instance');
    }

    public function testMapReplacesInstance(): void
    {
        $user = new class('Alice') {
            public function __construct(private string $name) {}
            public function getName(): string { return $this->name; }
        };

        $profile = new class('AliceProfile') {
            public function __construct(private string $profileName) {}
            public function getProfileName(): string { return $this->profileName; }
        };

        $chain = Chain::from($user)
            ->map(fn($u) => $profile) // replace User with Profile
            ->getProfileName();

        $this->assertSame('AliceProfile', $chain->result(), 'map() should replace instance with new object');
        $this->assertSame($profile, $chain->instance(), 'map() should set the new object as instance');
    }

    public function testMapThrowsIfNotObject(): void
    {
        $user = new class('Bob') {
            public function __construct(private string $name) {}
            public function getName(): string { return $this->name; }
        };

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('map() must return an object.');

        Chain::from($user)->map(fn($u) => 'not-an-object');
    }

    public function testMakeCreatesNewInstance(): void
    {
        $chain = Chain::make(DummyClass::class, 'Hello');

        $this->assertInstanceOf(DummyClass::class, $chain->instance(), 'make() should create a new instance');
        $this->assertSame('Hello', $chain->instance()->getValue());
    }

    public function testMakeAndChainMethods(): void
    {
        $chain = Chain::make(DummyClass::class, 'World')
            ->getValue();

        $this->assertSame('World', $chain->result(), 'make() should allow chaining methods on the created instance');
    }
}
