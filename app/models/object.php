<?php

namespace Models;

class Object extends \Core\AR
{
    const table_for_read = 'object';
    const table_for_write = 'object';

    public function __construct($data = array())
    {
        parent::__construct($data);
    }


}