<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Modelo de Proveedor
 *
 * @package    tekar/tekar-producto
 * @author     Marcos Calabrese <marcosc@tekar.net>
 * @copyright  Copyright (c) 2013 Tekar
 * @license    ??
 **/
abstract class Model_Core_Proveedor extends ORM {


    protected $_primary_key = 'id';
    protected $_table_name = 'proveedor';

    protected $_created_column = array('column' => 'creado', 'format' => TRUE);
    protected $_updated_column = array('column' => 'modificado', 'format' => TRUE);



    /**
     * Definicion de tablas manualmente. Evitamos lectura extra y podemos usar PDO.
     **/
    protected $_table_columns = array(
        'id' => NULL,
        'nombre'     => NULL,
        'activo'   => NULL,
    );


    /**
     * Relaciones
     **/
    protected $_has_many = array(
        'productos' => array(
            'model'   => 'Producto',
            'foreign_key' => 'marca_id',
        ),
    );



}
