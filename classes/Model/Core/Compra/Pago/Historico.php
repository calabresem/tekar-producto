<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Modelo de Historico de Pagos.
 *
 * @package tekar-producto
 * @author Marcos Calabrese <marcosc@tekar.net>
 * @since 20131023
 * @license http://openzula.org/license-bsd-3c BSD 3-Clause License
 */
abstract class Model_Core_Compra_Pago_Historico extends ORM {

    protected $_primary_key = 'id';
    protected $_table_name = 'compra_pago_historico';

    protected $_created_column = array( 'column' => 'creado', 'format' => TRUE );


    /**
     * Definicion de tablas manualmente. Evitamos lectura extra y podemos usar PDO.
     **/
    protected $_table_columns = array(
        'id' => NULL,
        'compra_id'     => NULL,
        'forma_pago_id'   => NULL,
        'transaccion'   => NULL,
        'estado_id'   => NULL,
        'codigo' => NULL,
        'observaciones'     => NULL,
        'creado'   => NULL,
    );


    /**
     * Relaciones
     **/
    protected $_belongs_to = array(
        'compra' => array(
            'model' => 'Compra',
            'foreign_key' => 'compra_id',
        ),
        'forma_pago' => array(
            'model'   => 'FormaPago',
            'foreign_key' => 'forma_pago_id',
        ),
    );


}
