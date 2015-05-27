<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Modelo de Descuentos.
 *
 * @package    tekar/tekar-producto
 * @author     Marcos Calabrese <marcosc@tekar.net>
 * @copyright  Copyright (c) 2013 Tekar
 * @license    ??
 **/
abstract class Model_Core_Descuento extends ORM {


    protected $_table_name = 'descuento';



    /**
     * Definicion de tablas manualmente. Evitamos lectura extra y podemos usar PDO.
     **/
    protected $_table_columns = array(
        'id' => NULL,
        'nombre'     => NULL,
        'activo' => NULL,
    );

}
