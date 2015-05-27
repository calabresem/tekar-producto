<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Modelo de datos de Idioma
 *
 * @package    tekar/tekar-producto
 * @author     Marcos Calabrese <marcosc@tekar.net>
 * @copyright  Copyright (c) 2014 Tekar
 * @license    ??
 **/
abstract class Model_Core_Idioma extends ORM {


    protected $_table_name = 'idioma';


    protected $_has_many = array(
        'busqueda' => array(
            'model'   => 'Busqueda',
            'foreign_key' => 'idioma_id',
        ),
    );


    /**
     * Definicion de tablas manualmente. Evitamos lectura extra y podemos usar PDO.
     **/
    protected $_table_columns = array(
        'id' => NULL,
        'codigo'   => NULL,
        'nombre'   => NULL,
        'activo'   => NULL,
    );

}
