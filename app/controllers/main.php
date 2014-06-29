<?php

namespace Controllers;

class Main extends \Core\C
{

    public function index()
    {
        $data = array(
            'url' => '++',
            'space_all' => 20.0,
            'space_living' => 10.0,
            'space_kitchen' => 10.0,
            'floor' => 1,
            'floors' => 6,
            'create_date' => '2014-06-28',
            'price' => 7000,
            'address' => 'адрес2',
            'ceil_height' => 32.234,
            'comment' => 'sdf',
            'is_actual' => 0,
            'space_rooms' => '',
        );
//        $id = \Core\DB::insert('object', $data);
//        echo $id;
//        \Core\DB::update('object', $data, 20);
//        $obj = \Core\DB::select('SELECT * from object where is_actual = :is_actual', array('is_actual' => 1));

        $obj = \Models\Object::find_by_params(array('is_actual' => 1));

        print_r($obj);


//        $object = \Models\Object::find_by_pk(1);
//        $object->debug();

    }


}