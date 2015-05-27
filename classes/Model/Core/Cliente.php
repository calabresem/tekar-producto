<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Modelo base de Cliente
 *
 * @package    tekar-producto
 * @author     Marcos Calabrese <marcosc@tekar.net>
 **/
abstract class Model_Core_Cliente extends ORM {


    protected $_table_name = 'cliente';


    protected $_created_column = array('column' => 'creado', 'format' => TRUE);
    protected $_updated_column = array('column' => 'modificado', 'format' => TRUE);


    /**
     * Definicion de tablas manualmente. Evitamos lectura extra y podemos usar PDO.
     **/
    protected $_table_columns = array(
        'id' => NULL,
        'email'     => NULL,
        'nombre'   => NULL,
        'numero'   => NULL,
        'codigo_fiscal'   => NULL,
        'direccion_calle'   => NULL,
        'direccion_numero'   => NULL,
        'direccion_piso'   => NULL,
        'direccion_dpto'   => NULL,
        'codigo_postal'   => NULL,
        'telefono'   => NULL,
        'ciudad'   => NULL,
        'pais_id'   => NULL,
        'clave'   => NULL,
        'ultima_ip'   => NULL,
        'creado'   => NULL,
        'modificado'   => NULL,
        'borrado'   => NULL,
        'confirmacion_fecha'   => NULL,
        'confirmacion_codigo'   => NULL,
    );


    /**
     * Relaciones
     **/
    protected $_has_many = array(
        'compras' => array(
            'model'   => 'Compra',
            'foreign_key' => 'cliente_id',
        ),
    );


    /**
     * Filtros varios:
     * - pais_id: para evitar que se intente guardar como 0, que da error de FK.
     **/
    public function filters()
    {
        return array(
            'pais_id' => array(
                array( function( $value ) {
                    return( empty( $value ) ? NULL : $value );
                }),
            ),
        );
    }


    /**
     * Sobreescribe la funcion de grabar, para agregar la IP.
     *
     * @return  mixed
     */
    public function save( Validation $validation = NULL )
    {
        $this->ultima_ip = ip2long( $_SERVER['REMOTE_ADDR'] );

        return parent::save( $validation );
    }



}
