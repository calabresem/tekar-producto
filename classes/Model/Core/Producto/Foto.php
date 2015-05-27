<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Modelo de relacion Producto-Foto
 *
 * @package tekar-producto
 * @author Marcos Calabrese <marcosc@tekar.net>
 * @license http://openzula.org/license-bsd-3c BSD 3-Clause License
 */
abstract class Model_Core_Producto_Foto extends ORM {


    protected $_primary_key = 'id';
    protected $_table_name = 'producto_foto';


    /**
     * Definicion de tablas manualmente. Evitamos lectura extra y podemos usar PDO.
     **/
    protected $_table_columns = array(
        'producto_id' => NULL,
        'foto_id'     => NULL,
    );


    /**
     * Relaciones.
     **/
    protected $_has_many = array(
        'productos' => array(
            'model'   => 'Producto',
            'through' => 'producto_foto',
            'foreign_key' => 'producto_id',
        ),
        'fotos' => array(
            'model'   => 'Foto',
            'through' => 'producto_foto',
            'foreign_key' => 'foto_id',
        ),
    );


