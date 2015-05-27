<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Modelo de Estado de Orden de Compra
 *
 * @package tekar-producto
 * @author Marcos Calabrese <marcosc@tekar.net>
 * @since 20131023
 * @license http://openzula.org/license-bsd-3c BSD 3-Clause License
 */
abstract class Model_Core_Compra_Estado extends ORM {

    protected $_primary_key = 'id';
    protected $_table_name = 'compra_estado';

    protected $_created_column = array( 'column' => 'creado', 'format' => TRUE );
    protected $_updated_column = array( 'column' => 'modificado', 'format' => TRUE );


    /**
     * Definicion de tablas manualmente. Evitamos lectura extra y podemos usar PDO.
     **/
    protected $_table_columns = array(
        'id' => NULL,
        'descripcion'     => NULL,
    );


    /**
     * Relaciones
     **/
    protected $_has_many = array(
        'compra' => array(
            'model' => 'Compra',
            'foreign_key' => 'estado_id',
        ),
    );




    /**
     * Devuelve el ID del estado inicial de una orden.
     *
     * @return int
     */
    public static function estado_inicial()
    {
        return( 1 );
    }

    /**
     * Devuelve el ID del estado de confirmacion de orden.
     *
     * @return int
     */
    public static function estado_confirmada()
    {
        return( 2 );
    }

    /**
     * Devuelve el ID del estado de aprobacion de una orden.
     *
     * @return int
     */
    public static function estado_pagada()
    {
        return( 3 );
    }

    /**
     * Devuelve el ID del estado de rechazo de una orden.
     *
     * @return int
     */
    public static function estado_rechazada()
    {
        return( 4 );
    }



}
