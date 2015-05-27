<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Modelo de datos de Pais
 *
 * @package    tekar/tekar-producto
 * @author     Marcos Calabrese <marcosc@tekar.net>
 * @copyright  Copyright (c) 2013 Tekar
 * @license    ??
 **/
abstract class Model_Core_Pais extends ORM {


    protected $_table_name = 'pais';


    protected $_has_many = array(
        'cliente' => array(
            'model'   => 'Cliente',
            'foreign_key' => 'pais_id',
        ),
        'compra' => array(
            'model'   => 'Compra',
            'foreign_key' => 'pais_id',
        ),
    );


    /**
     * Definicion de tablas manualmente. Evitamos lectura extra y podemos usar PDO.
     **/
    protected $_table_columns = array(
        'id' => NULL,
        'nombre'     => NULL,
        'codigo'   => NULL,
        'x'   => NULL,
        'y'   => NULL,
        'activo'   => NULL,
    );


    /**
     * Devuelve la lista de paises ordenamos x nombre
     * @version 20131017
     **/
    public static function lista()
    {

        return DB::select( 'id', 'nombre' )
            ->from( 'pais' )
            ->where( 'activo', '=', '1' )
            ->order_by( 'nombre', 'asc' )
            ->execute()
            ->as_array( 'id', 'nombre' );

    }


}
