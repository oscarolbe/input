<?php

namespace Linio\Component\Input\Handler;

class DummyHandler extends AbstractHandler
{
    public function define()
    {
        
    }

    /**
     * @return bool
     */
    public function isValid()
    {
        return true;
    }
}
