<?php

namespace elish\db;

class Expr
{
    protected string $_expression = '';

    public function __construct($value)
    {
        $this->_expression = $value;
    }

    public function get(): string
    {
        return $this->_expression;
    }

    public function __toString()
    {
        return $this->_expression;
    }
}