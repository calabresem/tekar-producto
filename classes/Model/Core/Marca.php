<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Modelo de Marca
 *
 * @package    tekar/tekar-producto
 * @author     Marcos Calabrese <marcosc@tekar.net>
 * @copyright  Copyright (c) 2013 Tekar
 * @license    ??
 **/
abstract class Model_Core_Marca extends ORM {


    protected $_table_name = 'marca';


    protected $_has_many = array(
        'productos' => array(
            'model'   => 'Producto',
            'foreign_key' => 'marca_id',
        ),
    );


    /**
     * Definicion de tablas manualmente. Evitamos lectura extra y podemos usar PDO.
     **/
    protected $_table_columns = array(
        'id' => NULL,
        'descripcion'     => NULL,
        'activo' => NULL,
    );

}
