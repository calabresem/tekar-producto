<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Modelo de datos extras de Compras. Sirve para agregar informacion del tipo: clave-valor.
 * La clave primaria es doble: compra_id / dato
 *
 * @package tekar-producto
 * @author Marcos Calabrese <marcosc@tekar.net>
 * @since 20131017
 * @license http://openzula.org/license-bsd-3c BSD 3-Clause License
 */
abstract class Model_Core_Compra_Extra extends ORM {

    protected $_primary_key = 'id';
    protected $_table_name = 'compra_extra';

    protected $_created_column = array( 'column' => 'creado', 'format' => TRUE );


    /**
     * Definicion de tablas manualmente. Evitamos lectura extra y podemos usar PDO.
     **/
    protected $_table_columns = array(
        'id' => NULL,
        'compra_id' => NULL,
        'dato'     => NULL,
        'valor'   => NULL,
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
    );


}
