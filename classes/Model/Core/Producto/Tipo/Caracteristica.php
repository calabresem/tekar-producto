<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Clase modelo para el Tipo de Uso que se le va a dar
 *
 **/
abstract class Model_Core_Producto_Tipo_Caracteristica extends ORM {

    protected $_primary_key = 'id';
    protected $_table_name = 'producto_tipo_caracteristica';


    /**
     * Definicion de tablas manualmente. Evitamos lectura extra y podemos usar PDO.
     **/
    protected $_table_columns = array(
        'id' => NULL,
        'producto_tipo_id'     => NULL,
        'descripcion'   => NULL,
        'i18n'   => NULL,
        'activo'   => NULL,
        'defecto'   => NULL,
    );


    /**
     * Devuelve el ID de la caracteristica por defecto. Hay que indicar
     * el nivel de defecto.
     *
     * @return int
     */
    public static function busca_defecto( $producto_tipo_id, $nivel=1 )
    {
        return( ORM::factory( 'Producto_Tipo_Caracteristica' )
                        ->where( 'producto_tipo_id', '=', $producto_tipo_id )
                        ->where( 'defecto', '=', $nivel )->find()->id );
    }



}

