<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Clase modelo para el Tipo de Uso que se le va a dar
 *
 **/
abstract class Model_Core_Producto_Estado extends ORM {

    protected $_primary_key = 'id';
    protected $_table_name = 'producto_estado';


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
        'items' => array(
            'model'   => 'Producto',
            'foreign_key' => 'estado_id',
        ),
    );


}

