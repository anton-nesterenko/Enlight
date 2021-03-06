<?php
/**
 * Enlight
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://enlight.de/license
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@shopware.de so we can send you a copy immediately.
 *
 * @category   Enlight
 * @package    Enlight_Hook
 * @copyright  Copyright (c) 2011, shopware AG (http://www.shopware.de)
 * @license    http://enlight.de/license     New BSD License
 * @version    $Id$
 * @author     Heiner Lohaus
 * @author     $Author$
 */

/**
 * Contains all data about the hook. (Hooked class, method, listener, position)
 *
 * The Enlight_Hook_HookHandler represents an single hook. The hook handler is registered
 * by the Enlight_Hook_Subscriber and is executed by the Enlight_Hook_Manager if the corresponding
 * original class method was executed.
 *
 * @category   Enlight
 * @package    Enlight_Hook
 * @copyright  Copyright (c) 2011, shopware AG (http://www.shopware.de)
 * @license    http://enlight.de/license     New BSD License
 */
class Enlight_Hook_HookHandler
{
    /**
     * @var mixed class to which the hook is created.
     */
    protected $class;

    /**
     * @var mixed method on which the hook is created.
     */
    protected $method;

    /**
     * @var mixed property for the hook class
     */
    protected $hook;

    /**
     * @var int type of the hook (before, replace or after)
     */
    protected $type;

    /**
     * @var int position of the hook. If more than one hook exists on the same
     * class method the hooks are called sequentially by the position.
     */
    protected $position;

    /**
     * @var object the plugin which creates the hook object.
     */
    protected $plugin;

    /**
     * Constant that defines that the class method should be overwritten.
     */
    const TypeReplace = 1;

    /**
     * Constant that defines that the hook method must be called before the original method.
     */
    const TypeBefore = 2;

    /**
     * Constant that defines that the hook method must be called after the original method.
     */
    const TypeAfter = 3;

    /**
     * Class constructor for a hook. The class, method and the listener are required.
     * The given parameter will be set in the internal properties.
     *
     * @param      $class
     * @param      $method
     * @param      $listener
     * @param int  $type
     * @param int  $position
     * @param null $plugin
     */
    public function __construct($class, $method, $listener, $type = self::TypeAfter, $position = 0, $plugin = null)
    {
        if (empty($class) || empty($method) || empty($listener)) {
            throw new Enlight_Exception('Some parameters are empty');
        }
        if (!is_callable($listener, true, $listener_name)) {
            throw new Enlight_Exception('Listener "' . $listener_name . '" is not callable');
        }
        $this->class = $class;
        $this->method = $method;
        $this->listener = $listener;
        $this->setType($type);
        $this->setPosition($position);
        $this->setPlugin($plugin);
    }

    /**
     * Default setter function for the type property. If the given type is null the default type (typeAfter) is set.
     * If the given type isn't one of the supported hook types, an exception is thrown.
     *
     * @param $type
     * @return Enlight_Hook_HookHandler
     * @throws Enlight_Exception
     */
    public function setType($type)
    {
        if ($type === null) {
            $type = self::TypeAfter;
        }
        if (!in_array($type, array(self::TypeReplace, self::TypeBefore, self::TypeAfter))) {
            throw new Enlight_Exception('Hook type is unknown');
        }
        $this->type = $type;
        return $this;
    }

    /**
     * Default setter method of the position property. If the given position isn't numeric an exception is thrown.
     * @param $position
     * @return Enlight_Hook_HookHandler
     * @throws Enlight_Exception
     */
    public function setPosition($position)
    {
        if (!is_numeric($position)) {
            throw new Enlight_Exception('Position is not numeric');
        }
        $this->position = $position;
        return $this;
    }

    /**
     * Default setter function of the plugin property
     *
     * @param $plugin
     * @return Enlight_Hook_HookHandler
     */
    public function setPlugin($plugin)
    {
        $this->plugin = $plugin;
        return $this;
    }

    /**
     * Returns the class and method name, concated with '::'
     * @return string
     */
    public function getName()
    {
        return $this->class . '::' . $this->method;
    }

    /**
     * Default getter function of the class property.
     * @return class
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * Default getter function of the method property.
     * @return method
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Default getter function of the listener property.
     * @return mixed
     */
    public function getListener()
    {
        return $this->listener;
    }

    /**
     * Default getter function of the type property.
     * @return type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Default getter function of the position property.
     * @return position
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * Default getter function of the plugin property.
     * @return object
     */
    public function getPlugin()
    {
        return $this->plugin;
    }

    /**
     * Executes the listener with the given arguments.
     * @param null $args
     * @return mixed
     */
    public function execute($args = null)
    {
        return call_user_func($this->listener, $args);
    }
}