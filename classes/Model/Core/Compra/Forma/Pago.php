<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Modelo de Estado de Orden de Compra
 *
 * @package tekar-producto
 * @author Marcos Calabrese <marcosc@tekar.net>
 * @since 20131023
 * @license http://openzula.org/license-bsd-3c BSD 3-Clause License
 */
abstract class Model_Core_Compra_Forma_Pago extends ORM {

    protected $_primary_key = 'id';
    protected $_table_name = 'compra_forma_pago';



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
            'foreign_key' => 'forma_pago_id',
        ),
    );



    /**
     * Devuelve el ID del TPV
     *
     * @return int
     */
    public static function forma_pago_tpv()
    {
        return( 1 );
    }

    /**
     * Devuelve el ID de PayPal
     *
     * @return int
     */
    public static function forma_pago_paypal()
    {
        return( 2 );
    }



}
