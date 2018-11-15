<?php

use Tommyknocker\Chain\Chain;
use PHPUnit\Framework\TestCase;

/**
 * Class ChainTest
 * @author Tommyknocker <me@isfrom.space>
 */
class ChainTest extends TestCase
{

    public function setUp()
    {
        require_once 'files/TestChaining.php';
        require_once 'files/TestCallable.php';
        require_once 'files/TestNoSingleton.php';
    }

    public function testArgumentsPassedToConstruct()
    {
        $this->assertTrue(Chain::TestChaining(1, 2)->method1()->result === 1);
    }

    public function testResultIsPassedToAnotherMethod()
    {
        $this->assertTrue(Chain::TestChaining(1, 2)->method1()->method2('result')->result === 1);
    }

    public function testObjectCanBeInsertedManually()
    {
        $testChaining = new TestChaining(1, 2);
        Chain::insert('TestChaining2', $testChaining);
        $this->assertTrue(Chain::TestChaining2()->method1()->result === 1);
    }

    public function testCanSwitchToOtherObjectInChain()
    {
        $this->assertTrue(Chain::TestChaining()->method3(3)->change('TestChaining2')->method1()->result === 1);
    }

    public function testResultIsPassedToOtherObject()
    {
        $this->assertTrue(Chain::TestChaining()->method3(4)->method1()->change('TestChaining2')->method3('result')->method1()->result === 4);
    }

    public function testCanCallChainInChain()
    {
        Chain::TestChaining()->method3(5);
        $this->assertTrue(Chain::TestChaining2()->method3(Chain::TestChaining()->method1()->result)->method1()->result === 5);
    }

    public function testCanSetAndGetParamFromObject()
    {
        Chain::TestChaining()->param = 'value';
        Chain::TestChaining2()->param = 'value2';
        $this->assertFalse(Chain::TestChaining2()->param === 'value');
        $this->assertTrue(Chain::TestChaining()->param === 'value');
    }

    public function testCanUnsetObjectParam() {
        unset(Chain::TestChaining2()->param);
        $this->assertNull(@Chain::TestChaining2()->param);
    }

    public function testCanUnsetObjectIntance() {
        unset(Chain::TestChaining2()->instance);
        try{
            Chain::TestChaining2()->instance;
        } catch (Exception $ex) {
            $this->assertEquals('Class TestChaining2 does not exist', $ex->getMessage());
        }
    }

    public function testObjectWillBeInitializedInEachCallIfTNoSingletonTraitIsUsed()
    {
        $this->assertTrue(Chain::TestNoSingleton(1)->method1()->result === 1);
        $this->assertTrue(Chain::TestNoSingleton(2)->method1()->result === 2);
    }

    public function testObjectCanUseOwnMagicCallMethodifTCallableTraitIsUsed()
    {
        Chain::TestCallable()->testMethod('param1', 'param2');

        $this->assertTrue(Chain::TestCallable()->method1()->result === 'testMethod');
        $this->assertTrue(Chain::TestCallable()->method2()->result === ['param1', 'param2']);
    }

}