<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Modelo de Item de Orden de Compra
 *
 * @package tekar-producto
 * @author Marcos Calabrese <marcosc@tekar.net>
 * @since 20131015
 * @license http://openzula.org/license-bsd-3c BSD 3-Clause License
 */
abstract class Model_Core_Compra_Item extends ORM {

    protected $_primary_key = 'id';
    protected $_table_name = 'compra_item';

    protected $_created_column = array( 'column' => 'creado', 'format' => TRUE );
    protected $_updated_column = array( 'column' => 'modificado', 'format' => TRUE );


    /**
     * Definicion de tablas manualmente. Evitamos lectura extra y podemos usar PDO.
     **/
    protected $_table_columns = array(
        'id' => NULL,
        'compra_id'     => NULL,
        'producto_id'   => NULL,
        'cantidad'   => NULL,
        'precio' => NULL,
        'descuento'     => NULL,
        'creado'   => NULL,
        'modificado'   => NULL,
    );


    /**
     * Relaciones
     **/
    protected $_belongs_to = array(
        'compra' => array(
            'model' => 'Compra',
            'foreign_key' => 'compra_id',
        ),
        'producto' => array(
            'model'   => 'Producto',
            'foreign_key' => 'producto_id',
        ),
    );



    /**
     * Indica el uso del item.
     **/
    public function precio_neto( $iva=FALSE )
    {
        return round( $this->precio / ( 1 + ( $this->compra->tasa_iva / 100 ) ), 2 );
    }


    /**
     * Precio
     **/
    public function precio( $iva=FALSE )
    {
        return round( $this->precio * ( 1 - ( $this->descuento / 100 ) ), 2 );
    }




}
