<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Clase modelo para la asociacion: Producto-Categoria
 *
 **/
abstract class Model_Core_Producto_Categoria extends ORM {

    protected $_primary_key = '';
    protected $_table_name = 'producto_categoria';


    /**
     * Definicion de tablas manualmente. Evitamos lectura extra y podemos usar PDO.
     **/
    protected $_table_columns = array(
        'producto_id' => NULL,
        'categoria_id'     => NULL,
    );

}

