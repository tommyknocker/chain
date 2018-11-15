<?php

/**
 * Test app callable support
 *
 * @author Tommyknocker <me@isfrom.space>
 * @license http://www.gnu.org/licenses/lgpl.txt LGPLv3
 */
class TestCallable
{

    use \Tommyknocker\Chain\Traits\CallMethod;

    private $calledName = null;
    private $calledArguments = null;

    public function __call($name, $arguments)
    {
        $this->calledName = $name;
        $this->calledArguments = $arguments;
    }

    public function method1() {
        return $this->calledName;
    }

    public function method2() {
        return $this->calledArguments;
    }
}