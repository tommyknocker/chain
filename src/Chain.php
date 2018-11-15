<?php

namespace Tommyknocker\Chain;

use ReflectionClass;
use ReflectionMethod;
use ReflectionException;
use ReflectionObject;
use Exception;

/**
 * Class Chain
 * @package Tommyknocker\Chain
 * @author Tommyknocker <me@isfrom.space>
 * @license http://www.gnu.org/licenses/lgpl.txt LGPLv3
 */
class Chain
{

    /**
     * Current object's name
     * @var string
     */
    private $currentObject = null;

    /**
     * Protect from creating object
     */
    private function __construct($currentObject)
    {
        $this->currentObject = $currentObject;
    }

    /**
     * Call class method
     * @param string $method
     * @param array $params
     * @throws Exception
     * @return self
     */
    public function __call($method, $params)
    {
        $objectData = State::get($this->currentObject);

        if (in_array('result', $params, true)) {
            $params[array_search('result', $params)] = $objectData['result'];
        }

        if ($objectData['callable'] && !method_exists($objectData['instance'], $method)) {
            $params = array_merge([$method], [$params]);
            $method = '__call';
        }

        $objectReflectionMethod = new ReflectionMethod($objectData['instance'], $method);
        State::setResult($this->currentObject, $objectReflectionMethod->invokeArgs($objectData['instance'], $params));

        return $this;
    }

    /**
     * Magic __callStatic method
     * @param string $name name of a class
     * @param array $args Optional arguments
     * @return self
     * @throws Exception
     */
    public static function __callStatic($name, $args)
    {

        $chain = new self($name);

        $objectData = State::get($name);

        if ($objectData && $objectData['singleton']) {
            return $chain;
        }

        try {
            $objectInstance = new ReflectionClass($name);
        } catch (ReflectionException $e) {
            if (!class_exists($name)) {
                throw new Exception('Class ' . $name . ' does not exist');
            } else {
                $objectInstance = new ReflectionClass($name);
            }
        }

        if (!$objectInstance->isInstantiable()) {
            throw new Exception('Cannot create object from class: ' . $name);
        }

        State::set($name,
            $objectInstance->getConstructor() ? $objectInstance->newInstanceArgs($args) : $objectInstance->newInstance(),
            self::getObjectParams($name, $objectInstance));

        return $chain;
    }

    /**
     *  Protect from cloning
     */
    private function __clone()
    {

    }

    /**
     * Get value from class
     * @param string $param
     * @return mixed
     */
    public function __get($param)
    {
        $objectData = State::get($this->currentObject);

        switch ($param) {
            case 'instance':
                return $objectData['instance'];
            case 'result':
                return $objectData['result'];
            default:
                return $objectData['instance']->$param;
        }
    }

    /**
     * Set class variable
     * @param string $param
     * @param mixed $value
     */
    public function __set($param, $value)
    {
        $objectData = State::get($this->currentObject);
        $objectData['instance']->$param = $value;
    }

    /**
     * Return the App version
     * @return string
     */
    public function __toString()
    {
        return 'Chain library. Version 0.0.1. Written by Tommyknocker <me@isfrom.space>';
    }

    /**
     * unset() overloading
     * @param string $name
     */
    public function __unset($name)
    {
        $objectData = State::get($this->currentObject);

        switch ($name) {
            case 'instance':
                State::delete($name);
                break;
            default:
                unset($objectData['instance']->$name);
        }
    }

    /**
     * Protect from unserializing
     */
    private function __wakeup()
    {

    }

    /**
     * Change current object in chain
     * @param string $name
     * @param array $args
     * @return self
     * @throws Exception
     */
    public function change($name, $args = [])
    {
        $objectData = State::get($this->currentObject);
        $previousObjectResult = $objectData['result'];
        $chain = self::__callStatic($name, $args);
        State::setResult($name, $previousObjectResult);
        return $chain;
    }

    /**
     * Collect all trait names from object
     * @param \ReflectionClass $reflectionObject
     * @return array
     */
    private static function getTraitNamesRecursive($reflectionObject)
    {
        $names = [];
        foreach ($reflectionObject->getTraits() as $trait) {
            $names[] = $trait->name;
            $names = array_merge($names, self::getTraitNamesRecursive($trait));
        }
        return $names;
    }

    /**
     * Get object options
     * @param string $name
     * @param ReflectionObject $object
     * @return array
     */
    private static function getObjectParams($name, $object)
    {
        $traits = self::getTraitNamesRecursive($object);

        return [
            'singleton' => is_array($traits) && in_array('Tommyknocker\Chain\Traits\NoSingleton', $traits, true) ? false : true,
            'callable' => is_array($traits) && in_array('Tommyknocker\Chain\Traits\CallMethod', $traits, true)
        ];
    }

    /**
     * Manually put object into objects storage
     * @param string $name Object's alias
     * @param object $object Object
     * @param bool $overwrite Optional Overwrite protection
     * @throws \Exception
     */
    public static function insert($name, $object, $overwrite = false)
    {
        if (!is_string($name) || !is_object($object)) {
            throw new Exception('Wrong params passed');
        }
        $objectData = State::get($name);
        if ($objectData && !$overwrite) {
            throw new Exception('Object is already exists while overwrite is not allowed');
        }
        $reflection = new ReflectionObject($object);
        State::set($name, $object, self::getObjectParams($name, $reflection));
    }

}