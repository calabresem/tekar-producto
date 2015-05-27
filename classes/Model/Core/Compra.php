<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Modelo de Orden de Compra
 *
 * @package tekar-producto
 * @author Marcos Calabrese <marcosc@tekar.net>
 * @since 20131015
 * @license http://openzula.org/license-bsd-3c BSD 3-Clause License
 */
abstract class Model_Core_Compra extends ORM {

    protected $_primary_key = 'id';
    protected $_table_name = 'compra';

    protected $_created_column = array( 'column' => 'creado', 'format' => TRUE );
    protected $_updated_column = array( 'column' => 'modificado', 'format' => TRUE );


    /**
     * Definicion de tablas manualmente. Evitamos lectura extra y podemos usar PDO.
     **/
    protected $_table_columns = array(
        'id' => NULL,
        'codigo'     => NULL,
        'cliente_id'   => NULL,
        'estado_id'   => NULL,
        'creado'   => NULL,
        'modificado'   => NULL,
        'borrado'   => NULL,
        'tasa_iva'   => NULL,
        'forma_pago_id'   => NULL,
        'email'   => NULL,
        'nombre'   => NULL,
        'codigo_fiscal' => NULL,
        'direccion_calle'     => NULL,
        'direccion_numero'   => NULL,
        'direccion_piso'   => NULL,
        'direccion_dpto'   => NULL,
        'ciudad'   => NULL,
        'telefono'   => NULL,
        'codigo_postal'   => NULL,
        'movil'   => NULL,
        'pais_id'   => NULL,
        'observaciones'   => NULL,
        'ip'   => NULL,
        'vencimiento'   => NULL,
        'comision_retener'   => NULL,
        'pagada_vendedor'   => NULL,
    );


    /**
     * Relaciones
     **/
    protected $_has_many = array(
        'items' => array(
            'model'   => 'Compra_Item',
            'foreign_key' => 'compra_id',
        ),
        'extra' => array(
            'model'   => 'Compra_Extra',
            'foreign_key' => 'compra_id',
        ),
    );


    protected $_belongs_to = array(
        'estado' => array(
            'model'   => 'Compra_Estado',
            'foreign_key' => 'estado_id',
        ),
        'cliente' => array(
            'model'   => 'Cliente',
            'foreign_key' => 'cliente_id',
        ),
        'pais' => array(
            'model'   => 'Pais',
            'foreign_key' => 'pais_id',
        ),
        'forma_pago' => array(
            'model'   => 'Compra_Forma_Pago',
            'foreign_key' => 'forma_pago_id',
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
     * Contiene el obj para las reglas de descuento
     **/
    protected $_regla_descuento;


    /**
     * Inicializa la compra.
     *
     *
     * @return this
     **/
    public function inicializa( array $params=null )
    {

        $this->codigo = $this->codigo_unico();
        $this->estado_id = 1;

        // asigna IVA
        if( !empty( $params['tasa_iva'] ) )
        {
            $this->tasa_iva = $params['tasa_iva'];
        }

        $this->save();

    }



    /**
     * Devuelve la validacion del modelo.
     *
     * @param array $user_post an array of user parameters
     *
     * @return Validation
     **/
    public static function validador( $datos )
    {
/*
        return Validation::factory( $datos )
                            ->rule( 'codigo', 'not_empty' )
                            ->rule( 'codigo', 'max_length', array( ':value', '30' ) )
                            ->rule( 'codigo', 'Model_Core_Producto::codigo_unico' )
                            ->rule( 'nombre', 'not_empty' );
*/
    }



    /**
     * Validation callback to ensure shipping/billing value has a first
     * name and surname.
     *
     * @param   string  $value
     * @return  bool
     */
    public function nombre_completo( $value )
    {
        return strpos(trim($value), ' ') !== FALSE;
    }


    /**
     * Calculates the total price of all products within the order and
     * the shipping cost, rounded to 2 decimal places.
     *
     * If $apply_discount is true, then the value of the 'discount' property
     * shall be deducted from the above result.
     *
     * @param   bool  $apply_discount
     * @param   bool  $include_vat
     * @return  float
     */
    public function total( $aplica_descuento = TRUE, $incluye_impuestos = FALSE )
    {
        $total = 0;
        foreach( $this->items->find_all() as $producto )
        {
            //$total += $producto->cantidad * $producto->precio() * ( 1 - ( $producto->descuento / 100 ) );
            $total += $producto->cantidad * $producto->precio();
        }

/*
        if( $aplica_descuento )
        {
            $amount -= $this->discount;
        }
        if( $incluye_impuestos AND $this->tasa_iva > 0 )
        {
            $total *= 1 + ($this->vat_rate / 100);
        }
*/

        return round(max(0, $total), 2);
    }

    /**
     * Calculate how much VAT/tax is being paid on this order
     *
     * @return  float
     */
    public function vat_amount()
    {
        return ($this->amount(TRUE) / 100) * $this->vat_rate;
    }

    /**
     * Actualiza el estado de la compra
     *
     * @param int
     * @return  mixed
     **/
    public function actualiza_estado( $estado_id )
    {
        $this->estado_id = $estado_id;
        return parent::save();
    }

    /**
     * Actualiza el metodo de pago de la compra.
     *
     * @param int [$forma_pago_id]
     * @return  mixed
     */
    public function actualiza_forma_pago( $forma_pago_id )
    {
        $this->forma_pago_id = $forma_pago_id;
        return parent::save();
    }

    /**
     * Actualiza el campo observaciones
     *
     * @param   string  $observaciones
     * @return  mixed
     */
    public function actualiza_observaciones( $observaciones )
    {
        $this->observaciones = $observaciones;
        return parent::save();
    }

    /**
     * Asignamos el cliente a la COMPRA.
     *
     * @param Model_Cliente
     * @return  mixed
     */
    public function actualiza_cliente( Model_Cliente $cliente )
    {

        // actualiza el ID
        $this->cliente_id = $cliente->id;

        // pero tambien actualizamos los datos
        $this->nombre = $cliente->nombre;
        $this->email = $cliente->email;
        $this->pais_id = $cliente->pais_id;
        $this->codigo_fiscal = $cliente->codigo_fiscal;

        $this->direccion_calle = $cliente->direccion_calle;
        $this->ciudad = $cliente->ciudad;
        $this->codigo_postal = $cliente->codigo_postal;
        $this->telefono = $cliente->telefono;


        return parent::save();
    }


    /**
     * Override the save() method to provide some default value for columns
     *
     * @return  mixed
     */
    public function save( Validation $validation = NULL )
    {
        if ( ! $this->loaded())
        {
            //$this->date = DB::expr('UTC_TIMESTAMP()');
            //$this->tasa_iva = (float) Kohana::$config->load( 'tekar-producto' )->tasa_iva;
        }

        // guardamos la IP
        $this->ip = ip2long( $_SERVER['REMOTE_ADDR'] );


        return parent::save($validation);
    }

    /**
     * Override the delete() method to prevent existing orders being deleted
     *
     * @return  mixed
     */
    public function delete()
    {
        if ($this->loaded())
            throw new Kohana_Exception('existing orders can not be deleted');

        return parent::delete();
    }



    /**
     * Genera un codigo de identificacion unico para una compra.
     *
     * @since 20131015
     *
     * @return string
     */
    public function codigo_unico()
    {
        return Text::random( 'alnum', 32 );
    }


    /**
     * Agrega un item a la compra
     *
     * @since 20131015
     *
     * @return string
     */
    public function agrega_item( Model_Producto $producto, array $parametros=NULL )
    {

        if( !$this->loaded() )
            throw new Exception( 'Compra invalida.' );

        // revisar si el producto si ya esta cargado
        $item = $this->items->where( 'producto_id', '=', $producto->id )->limit( 1 )->find();

        // ya esta cargado?
        if( $item->loaded() )
        {

            $item->cantidad += empty( $parametros['cantidad'] ) ? 1 : $parametros['cantidad'];
            $item->save();


        } else
        {

            // inicializa el item
            $compra_item = ORM::factory( 'Compra_Item' );
            $compra_item->compra_id = $this->id;
            $compra_item->producto_id = $producto->id;
            $compra_item->precio = $producto->precio();
            $compra_item->cantidad = empty( $parametros['cantidad'] ) ? 1 : $parametros['cantidad'];
            $compra_item->descuento = empty( $parametros['descuento'] ) ? 0 : $parametros['descuento'];
            $compra_item->save();
            unset( $compra_item );

        }

        unset( $item );


        return;

    }


    /**
     * Devuelve los items de la compra resumidos.
     *
     * @since 20131015
     *
     * @return string
     */
    public function items()
    {

        if( empty( $this->id ) )
            return( array() );


        $detalle = array();

        $items = $this->detalle_items();
        foreach( $items as $item )
        {
            $detalle[] = array( 'id' => $item->id, 'codigo' => $item->producto->codigo, 'precio' => $item->precio() );
        }

/*
        $sql = "SELECT compra_item.id, producto.codigo, compra_item.precio
                FROM compra_item
                INNER JOIN producto ON producto.id = compra_item.producto_id
                WHERE compra_item.compra_id = ".$this->id;

        $consulta = DB::query( Database::SELECT, $sql )->execute();
*/


        return( $detalle );

    }


    /**
     * Devuelve los items de la compra con mas informacion.
     *
     * @since 20131015
     *
     * @return string
     */
    public function detalle_items()
    {

        // por si es una compra inicializada
        if( empty( $this->id ) )
            return( array() );


        $this->evalua_descuento();

        return( $this->items->find_all() );

    }



    /**
     * Establece o pide un dato extra de la COMPRA.
     *
     * @since 20131017
     **/
    public function extra( $dato, $valor=NULL )
    {

        if( is_null( $valor ) )
        {

            // pide el valor
            return( $this->extra
                            ->where( 'dato', '=', $dato )
                            ->find()->get( 'valor' ) );

        } else
        {

            // lo establece
            $extra = $this->extra
                            ->where( 'dato', '=', $dato )
                            ->find();

            if( !$extra->loaded() )
            {
                $extra->compra_id = $this->id;
                $extra->dato = $dato;
            }

            $extra->valor = $valor;
            $extra->save();

            unset( $extra );

        }

        return;

    }





    /**
     * Establece la orden como pagada.
     *
     * @param int
     * @return  mixed
     **/
    public function cambia_estado____( Compra_Estado $estado )
    {


        // graba el estado de la orden
        $this->estado_id = Model_Compra_Estado::estado_pagada();
        parent::save();

        // ahora graba en el historico
        $historico = ORM::factory( 'Compra_Pago_Historico' );
        $historico->compra_id = $this->id;
        $historico->forma_pago_id = $parametros['forma_pago_id'];
        $historico->estado_id = $this->estado_id;
        $historico->codigo = $parametros['codigo'];
        $historico->observaciones = $parametros['observaciones'];
        $historico->save();

        return;
    }



    /**
     * Establece la orden como confirmada.
     *
     * @version 20131025
     **/
    public function confirmada()
    {

        // graba el estado de la orden
        $this->estado_id = Model_Compra_Estado::estado_confirmada();
        parent::save();

        return;
    }


    /**
     * Establece la orden como pagada.
     *
     * @param int
     * @return  mixed
     **/
    public function pagada( array $parametros )
    {
        // graba el estado de la orden
        $this->actualiza_estado( Model_Compra_Estado::estado_pagada() );

    }



    /**
     * Marca como rechazada la orden.
     *
     * @param int
     * @return  mixed
     **/
    public function rechazada( array $parametros )
    {
        // graba el estado de la orden
        $this->actualiza_estado( Model_Compra_Estado::estado_rechazada() );

    }



    /**
     * Validacion de paso: PAGO
     *
     * @version 20131029
     **/
    public function valida_paso_pago()
    {

        if( empty( $this->email ) )
            return( false );

        return( true );

    }

    /**
     * Validacion de paso: PAGO
     *
     * @version 20131029
     **/
    public function valida_paso_confirmacion()
    {

        if( $this->estado_id < Model_Compra_Estado::estado_confirmada() )
            return( false );

        return( true );

    }


    /*
     *
     * @version 20131208
     **/
    public function evalua_descuento()
    {

        // si hay una regla cargada, evalualar.
        if( !empty( $this->_regla_descuento ) AND is_object( $this->_regla_descuento ) )
        {
            $items = $this->items->find_all();
            $this->_regla_descuento->aplica_descuento( $items );
        }

        return( true );

    }



}
