<?php

namespace Linio\Component\Input;

use Doctrine\Common\Inflector\Inflector;
use Linio\Component\Input\Handler\DummyHandler;

class Factory
{
    /**
     * @var string
     */
    protected $handlerNamespace;

    /**
     * @var bool
     */
    protected $enabled;

    /**
     * @var \Linio\Component\Input\TypeHandler
     */
    protected $typeHandler;

    public function getHandler($alias)
    {
        if (!$this->enabled) {
            return new DummyHandler($this->typeHandler);
        }

        $className = Inflector::classify($alias);
        $handlerClass = sprintf('\%s\%sHandler', $this->handlerNamespace, $className);

        if (!class_exists($handlerClass)) {
            throw new \RuntimeException('The specified handler class does not exist: ' . $handlerClass);
        }

        return new $handlerClass($this->typeHandler);
    }

    /**
     * @return string
     */
    public function getHandlerNamespace()
    {
        return $this->handlerNamespace;
    }

    /**
     * @param string $handlerNamespace
     */
    public function setHandlerNamespace($handlerNamespace)
    {
        $this->handlerNamespace = $handlerNamespace;

        return $this;
    }

    /**
     * @return \Linio\Component\Input\TypeHandler
     */
    public function getTypeHandler()
    {
        return $this->typeHandler;
    }

    /**
     * @param \Linio\Component\Input\TypeHandler $typeHandler
     */
    public function setTypeHandler($typeHandler)
    {
        $this->typeHandler = $typeHandler;

        return $this;
    }

    /**
     * @param bool
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;

        return $this;
    }
}
