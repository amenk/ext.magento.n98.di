<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @copyright Copyright (c) 2014 X.commerce, Inc. (http://www.magentocommerce.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class Magento_Framework_ObjectManager_Factory_Factory implements Magento_Framework_ObjectManager_Factory
{
    /**
     * Object manager
     *
     * @var Magento_Framework_ObjectManager_ObjectManager
     */
    protected $objectManager;

    /**
     * Object manager config
     *
     * @var Magento_Framework_ObjectManager_Config
     */
    protected $config;

    /**
     * Definition list
     *
     * @var Magento_Framework_ObjectManager_Definition
     */
    protected $definitions;

    /**
     * Object creation stack
     *
     * @var array
     */
    protected $creationStack = array();

    /**
     * @param Magento_Framework_ObjectManager_Config $config
     * @param Magento_Framework_ObjectManager $objectManager
     * @param Magento_Framework_ObjectManager_Definition $definitions
     * @param array $globalArguments
     */
    public function __construct(
        Magento_Framework_ObjectManager_Config $config,
        Magento_Framework_ObjectManager $objectManager = null,
        Magento_Framework_ObjectManager_Definition $definitions = null,
        $globalArguments = array()
    ) {
        $this->config = $config;
        $this->objectManager = $objectManager;
        if ($definitions === null) {
            $definitions = new Magento_Framework_ObjectManager_Definition_Runtime();
        }
        $this->definitions = $definitions;
        $this->globalArguments = $globalArguments;
    }

    /**
     * Set object manager
     *
     * @param Magento_Framework_ObjectManager $objectManager
     * @return void
     */
    public function setObjectManager(Magento_Framework_ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Resolve constructor arguments
     *
     * @param string $requestedType
     * @param array $parameters
     * @param array $arguments
     * @return array
     * @throws \UnexpectedValueException
     * @throws \BadMethodCallException
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    protected function _resolveArguments($requestedType, array $parameters, array $arguments = array())
    {
        $resolvedArguments = array();
        $arguments = count($arguments)
            ? array_replace($this->config->getArguments($requestedType), $arguments)
            : $this->config->getArguments($requestedType);
        foreach ($parameters as $parameter) {
            list($paramName, $paramType, $paramRequired, $paramDefault) = $parameter;
            $argument = null;
            if (array_key_exists($paramName, $arguments)) {
                $argument = $arguments[$paramName];
            } else if ($paramRequired) {
                if ($paramType) {
                    $argument = array('instance' => $paramType);
                } else {
                    $this->creationStack = array();
                    throw new \BadMethodCallException(
                        'Missing required argument $' . $paramName . ' of ' . $requestedType . '.'
                    );
                }
            } else {
                $argument = $paramDefault;
            }
            if ($paramType && !is_object($argument) && $argument !== $paramDefault) {
                if (!is_array($argument) || !isset($argument['instance'])) {
                    throw new \UnexpectedValueException(
                        'Invalid parameter configuration provided for $' . $paramName . ' argument of ' . $requestedType
                    );
                }
                $argumentType = $argument['instance'];
                $isShared = (isset($argument['shared']) ? $argument['shared'] :$this->config->isShared($argumentType));
                $argument = $isShared
                    ? $this->objectManager->get($argumentType)
                    : $this->objectManager->create($argumentType);
            } else if (is_array($argument)) {
                if (isset($argument['argument'])) {
                    $argKey = $argument['argument'];
                    $argument =
                        isset($this->globalArguments[$argKey]) ? $this->globalArguments[$argKey] : $paramDefault;
                } else if (!empty($argument)) {
                    $this->parseArray($argument);
                }
            }
            $resolvedArguments[] = $argument;
        }
        return $resolvedArguments;
    }

    /**
     * Parse array argument
     *
     * @param array $array
     * @return void
     */
    protected function parseArray(&$array)
    {
        foreach ($array as $key => $item) {
            if (is_array($item)) {
                if (isset($item['instance'])) {
                    $itemType = $item['instance'];
                    $isShared = (isset($item['shared'])) ? $item['shared'] : $this->config->isShared($itemType);
                    $array[$key] = $isShared
                        ? $this->objectManager->get($itemType)
                        : $this->objectManager->create($itemType);
                } elseif (isset($item['argument'])) {
                    $array[$key] = isset($this->globalArguments[$item['argument']])
                        ? $this->globalArguments[$item['argument']]
                        : null;
                } else {
                    $this->parseArray($item);
                }
            }
        }
    }

    /**
     * Create instance with call time arguments
     *
     * @param string $requestedType
     * @param array $arguments
     * @return object
     * @throws \Exception
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function create($requestedType, array $arguments = array())
    {
        $type = $this->config->getInstanceType($requestedType);
        $parameters = $this->definitions->getParameters($type);
        if ($parameters == null) {
            return new $type();
        }
        if (isset($this->creationStack[$requestedType])) {
            $lastFound = end($this->creationStack);
            $this->creationStack = array();
            throw new \LogicException("Circular dependency: {$requestedType} depends on {$lastFound} and vice versa.");
        }
        $this->creationStack[$requestedType] = $requestedType;
        try {
            $args = $this->_resolveArguments($requestedType, $parameters, $arguments);
            unset($this->creationStack[$requestedType]);
        } catch (\Exception $e) {
            unset($this->creationStack[$requestedType]);
            throw $e;
        }
        switch (count($args)) {
            case 1:
                return new $type($args[0]);
            case 2:
                return new $type($args[0], $args[1]);
            case 3:
                return new $type($args[0], $args[1], $args[2]);
            case 4:
                return new $type($args[0], $args[1], $args[2], $args[3]);
            case 5:
                return new $type($args[0], $args[1], $args[2], $args[3], $args[4]);
            case 6:
                return new $type($args[0], $args[1], $args[2], $args[3], $args[4], $args[5]);
            case 7:
                return new $type($args[0], $args[1], $args[2], $args[3], $args[4], $args[5], $args[6]);
            case 8:
                return new $type($args[0], $args[1], $args[2], $args[3], $args[4], $args[5], $args[6], $args[7]);
            default:
                $reflection = new \ReflectionClass($type);
                return $reflection->newInstanceArgs($args);
        }
    }

    /**
     * Set global arguments
     *
     * @param array $arguments
     * @return void
     */
    public function setArguments($arguments)
    {
        $this->globalArguments = $arguments;
    }
}
