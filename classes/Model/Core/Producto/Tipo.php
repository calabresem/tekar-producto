<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Clase modelo para el Tipo de Uso que se le va a dar
 *
 **/
abstract class Model_Core_Producto_Tipo extends ORM {

    protected $_primary_key = 'id';
    protected $_table_name = 'producto_tipo';

    protected $_caracteristicas;


    /**
     * Definicion de tablas manualmente. Evitamos lectura extra y podemos usar PDO.
     **/
    protected $_table_columns = array(
        'id' => NULL,
        'descripcion'     => NULL,
        'estilo'   => NULL,
        'defecto'   => NULL,
        'activo'   => NULL,
    );


    /**
     * Relaciones
     **/
    protected $_has_many = array(
        'items' => array(
            'model'   => 'Compra_Item',
            'foreign_key' => 'compra_id',
        ),
        'productos' => array(
            'model'   => 'Producto',
            'foreign_key' => 'compra_id',
        ),
        'caracteristicas' => array(
            'model'   => 'Producto_Tipo_Caracteristica',
            'foreign_key' => 'producto_tipo_id',
            'far_key' => 'id',
        ),
    );



    /**
     * Devuelve el id por defecto.
     *
     * @return int
     */
    public static function defecto()
    {
        $tipo = ORM::factory( 'Producto_Tipo' )->where( 'defecto', '=', '1' )->find();
        return $tipo->id;
    }



    /**
     * Devuelve el listado de caracteristicas definidas para el tipo de producto.
     *
     * @return int
     */
    public function caracteristicas()
    {
        if( !is_object( $this->_caracteristicas ) )
        {
            $this->_caracteristicas = $this->caracteristicas->where( 'activo', '=', 1 )->find_all();

        }

        return $this->_caracteristicas;
    }


    /**
     * Devuelve el listado de caracteristicas definidas para el tipo de producto.
     *
     * @return int
     */
    public function caracteristicas_html()
    {
        $caract = '';
        foreach( $this->caracteristicas() as $item )
        {
            $caract .= $item->descripcion . "\r\n";
        }

        return $caract;
    }



}

