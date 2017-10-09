<?php

namespace PpsUpdater;

abstract class Bookmaker
{
    protected $_bookmaker = '',
              $_lastError = '',
              $_items     = [];


    abstract public function __construct();

    abstract public function getData(&$error = '');

    final public function getName()
    {
        return $this->_bookmaker;
    }

    final public function getLastError()
    {
        return $this->_lastError;
    }

    final public function getItems($format = 'array')
    {
        return $format === 'json' ? json_encode($this->_items) : $this->_items;
    }
}