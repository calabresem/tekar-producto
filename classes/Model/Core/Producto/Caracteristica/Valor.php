<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Clase modelo para el Tipo de Uso que se le va a dar
 *
 **/
abstract class Model_Core_Producto_Caracteristica_Valor extends ORM {

    protected $_primary_key = 'id';
    protected $_table_name = 'producto_caracteristica_valor';


    /**
     * Definicion de tablas manualmente. Evitamos lectura extra y podemos usar PDO.
     **/
    protected $_table_columns = array(
        'id' => NULL,
        'producto_id'     => NULL,
        'producto_tipo_caracteristica_id'   => NULL,
        'descripcion'   => NULL,
        'valor'   => NULL,
        'i18n'   => NULL,
    );



}

