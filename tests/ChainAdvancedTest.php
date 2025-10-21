<?php

declare(strict_types=1);

namespace tommyknocker\chain\tests;

use PHPUnit\Framework\TestCase;
use tommyknocker\chain\Chain;
use tommyknocker\chain\tests\fixtures\Calculator;
use tommyknocker\chain\tests\fixtures\DummyClass;
use tommyknocker\chain\tests\fixtures\User;

final class ChainAdvancedTest extends TestCase
{
    // ==================== Chain::of() Tests ====================

    public function testOfCreatesChainInstance(): void
    {
        $obj = new DummyClass('test');
        $chain = Chain::of($obj);

        $this->assertInstanceOf(Chain::class, $chain);
        $this->assertSame($obj, $chain->instance());
    }

    // ==================== get() Method Tests ====================

    public function testGetReturnsLastResult(): void
    {
        $calc = new Calculator(10);
        $result = Chain::of($calc)
            ->add(5)
            ->getValue()
            ->get();

        $this->assertSame(15.0, $result);
    }

    public function testGetReturnsInstanceWhenNoResult(): void
    {
        $calc = new Calculator(10);
        $chain = Chain::of($calc);

        $this->assertSame($calc, $chain->get());
    }

    public function testGetAfterTapReturnsInstance(): void
    {
        $calc = new Calculator(5);
        $result = Chain::of($calc)
            ->tap(fn ($c) => $c->add(10))
            ->get();

        $this->assertSame($calc, $result);
    }

    // ==================== when() Method Tests ====================

    public function testWhenExecutesCallbackWhenConditionTrue(): void
    {
        $calc = new Calculator(10);
        $executed = false;

        Chain::of($calc)
            ->when(true, function ($chain) use (&$executed) {
                $executed = true;
            });

        $this->assertTrue($executed);
    }

    public function testWhenDoesNotExecuteCallbackWhenConditionFalse(): void
    {
        $calc = new Calculator(10);
        $executed = false;

        Chain::of($calc)
            ->when(false, function ($chain) use (&$executed) {
                $executed = true;
            });

        $this->assertFalse($executed);
    }

    public function testWhenExecutesDefaultWhenConditionFalse(): void
    {
        $calc = new Calculator(10);
        $mainExecuted = false;
        $defaultExecuted = false;

        Chain::of($calc)
            ->when(
                false,
                function ($chain) use (&$mainExecuted) {
                    $mainExecuted = true;
                },
                function ($chain) use (&$defaultExecuted) {
                    $defaultExecuted = true;
                }
            );

        $this->assertFalse($mainExecuted);
        $this->assertTrue($defaultExecuted);
    }

    public function testWhenWithCallableCondition(): void
    {
        $user = new User('Alice', 25);
        $grantedAccess = false;

        Chain::of($user)
            ->when(
                fn ($u) => $u->isAdult(),
                function ($chain) use (&$grantedAccess) {
                    $grantedAccess = true;
                }
            );

        $this->assertTrue($grantedAccess);
    }

    public function testWhenCanModifyChain(): void
    {
        $calc = new Calculator(10);

        $result = Chain::of($calc)
            ->when(true, fn ($chain) => $chain->add(5))
            ->getValue()
            ->get();

        $this->assertSame(15.0, $result);
    }

    // ==================== unless() Method Tests ====================

    public function testUnlessExecutesCallbackWhenConditionFalse(): void
    {
        $calc = new Calculator(10);
        $executed = false;

        Chain::of($calc)
            ->unless(false, function ($chain) use (&$executed) {
                $executed = true;
            });

        $this->assertTrue($executed);
    }

    public function testUnlessDoesNotExecuteCallbackWhenConditionTrue(): void
    {
        $calc = new Calculator(10);
        $executed = false;

        Chain::of($calc)
            ->unless(true, function ($chain) use (&$executed) {
                $executed = true;
            });

        $this->assertFalse($executed);
    }

    public function testUnlessWithCallableCondition(): void
    {
        $user = new User('Dave', 16);
        $restricted = false;

        Chain::of($user)
            ->unless(
                fn ($u) => $u->isAdult(),
                function ($chain) use (&$restricted) {
                    $restricted = true;
                }
            );

        $this->assertTrue($restricted);
    }

    public function testUnlessCanModifyChain(): void
    {
        $user = new User('Eve', 20);

        Chain::of($user)
            ->unless(
                fn ($u) => $u->getEmail() !== null,
                fn ($chain) => $chain->setEmail('default@example.com')
            );

        $this->assertSame('default@example.com', $user->getEmail());
    }

    // ==================== pipe() Method Tests ====================

    public function testPipeExecutesSingleFunction(): void
    {
        $calc = new Calculator(10);

        $result = Chain::of($calc)
            ->pipe(fn ($c) => $c->add(5))
            ->getValue()
            ->get();

        $this->assertSame(15.0, $result);
    }

    public function testPipeExecutesMultipleFunctions(): void
    {
        $calc = new Calculator(10);

        $result = Chain::of($calc)
            ->pipe(
                fn ($c) => $c->add(5),
                fn ($c) => $c->multiply(2),
                fn ($c) => $c->subtract(10)
            )
            ->getValue()
            ->get();

        $this->assertSame(20.0, $result);
    }

    public function testPipeWithValueTransformations(): void
    {
        $calc = new Calculator(2);
        $chain = Chain::of($calc)
            ->pipe(
                fn ($c) => $c->getValue(),
                fn ($v) => $v * $v,
                fn ($v) => $v + 10
            );

        $this->assertSame(14.0, $chain->get());
    }

    public function testPipeWithEmptyPipes(): void
    {
        $calc = new Calculator(10);

        $chain = Chain::of($calc)->pipe();

        $this->assertSame($calc, $chain->instance());
    }

    // ==================== clone() Method Tests ====================

    public function testCloneCreatesNewChainInstance(): void
    {
        $calc = new Calculator(10);
        $chain1 = Chain::of($calc);
        $chain2 = $chain1->clone();

        $this->assertNotSame($chain1, $chain2);
    }

    public function testCloneCreatesNewObjectInstance(): void
    {
        $calc = new Calculator(10);
        $chain1 = Chain::of($calc);
        $chain2 = $chain1->clone();

        $this->assertNotSame($chain1->instance(), $chain2->instance());
    }

    public function testCloneAllowsIndependentModifications(): void
    {
        $calc = new Calculator(10);
        $chain1 = Chain::of($calc);
        $chain2 = $chain1->clone();

        $chain1->add(5);
        $chain2->add(20);

        $this->assertSame(15.0, $chain1->instance()->getValue());
        $this->assertSame(30.0, $chain2->instance()->getValue());
    }

    public function testCloneCanBranchChains(): void
    {
        $calc = new Calculator(100);
        $baseChain = Chain::of($calc);

        $withDiscount = $baseChain->clone()->multiply(0.9);
        $withTax = $baseChain->clone()->multiply(1.2);

        $this->assertSame(90.0, $withDiscount->instance()->getValue());
        $this->assertSame(120.0, $withTax->instance()->getValue());
        $this->assertSame(100.0, $baseChain->instance()->getValue());
    }

    // ==================== Integration Tests ====================

    public function testComplexChainWithAllNewMethods(): void
    {
        $user = new User('Helen', 25);

        $chain = Chain::of($user)
            ->when(
                fn ($u) => $u->isAdult(),
                fn ($c) => $c->setAdmin(true)
            )
            ->unless(
                fn ($u) => $u->getEmail() !== null,
                fn ($c) => $c->setEmail('helen@example.com')
            );

        $result = $chain->pipe(
            fn ($u) => $u->getName(),
            fn ($name) => strtoupper($name)
        )->get();

        $this->assertTrue($user->isAdmin());
        $this->assertSame('helen@example.com', $user->getEmail());
        $this->assertSame('HELEN', $result);
    }

    public function testMultipleBranchesWithClone(): void
    {
        $calc = new Calculator(50);
        $baseChain = Chain::of($calc);

        $path1 = $baseChain->clone()
            ->when(true, fn ($c) => $c->add(10))
            ->getValue()
            ->get();

        $path2 = $baseChain->clone()
            ->when(false, fn ($c) => $c->add(10), fn ($c) => $c->subtract(10))
            ->getValue()
            ->get();

        $this->assertSame(60.0, $path1);
        $this->assertSame(40.0, $path2);
        $this->assertSame(50.0, $baseChain->instance()->getValue());
    }

    public function testPipeWithConditionals(): void
    {
        $calc = new Calculator(15);

        $result = Chain::of($calc)
            ->pipe(
                fn ($c) => $c->subtract(5),
                fn ($c) => $c->multiply(2)
            )
            ->when(
                fn ($c) => $c->isPositive(),
                fn ($chain) => $chain->add(10)
            )
            ->getValue()
            ->get();

        $this->assertSame(30.0, $result);
    }

    // ==================== Edge Cases ====================

    public function testGetWithNullResult(): void
    {
        $obj = new class () {
            public function returnNull(): ?string
            {
                return null;
            }
        };
        $chain = Chain::of($obj)->returnNull();
        // get() returns instance when last result is null
        $this->assertSame($obj, $chain->get());
    }

    public function testWhenWithFalsyValue(): void
    {
        $calc = new Calculator(0);
        $executed = false;

        Chain::of($calc)
            ->when(
                fn ($c) => $c->getValue(),
                function ($chain) use (&$executed) {
                    $executed = true;
                }
            );

        $this->assertFalse($executed);
    }

    public function testPipeWithException(): void
    {
        $calc = new Calculator(10);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Cannot divide by zero');

        Chain::of($calc)
            ->pipe(
                fn ($c) => $c->add(5),
                fn ($c) => $c->divide(0)
            );
    }

    public function testChainabilityOfAllMethods(): void
    {
        $calc = new Calculator(1);

        $result = Chain::of($calc)
            ->add(1)
            ->when(true, fn ($c) => $c->multiply(2))
            ->unless(false, fn ($c) => $c->add(1))
            ->pipe(fn ($c) => $c->subtract(2))
            ->clone()
            ->add(7)
            ->getValue()
            ->get();

        $this->assertSame(10.0, $result);
    }
}
