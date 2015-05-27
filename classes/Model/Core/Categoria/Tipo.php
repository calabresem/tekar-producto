<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Modelo de Categoria Tipo
 *
 * @package    tekar/tekar-producto
 * @author     Marcos Calabrese <marcosc@tekar.net>
 * @copyright  Copyright (c) 2013 Tekar
 * @license    ?
 **/
abstract class Model_Core_Categoria_Tipo extends ORM {


    protected $_table_name = 'categoria_tipo';

    protected $_created_column = array( 'column' => 'creado', 'format' => TRUE );
    protected $_updated_column = array( 'column' => 'modificado', 'format' => TRUE );



    /**
     * Definicion de tablas manualmente. Evitamos lectura extra y podemos usar PDO.
     **/
    protected $_table_columns = array(
        'id' => NULL,
        'descripcion'     => NULL,
        'activo'   => NULL,
        'url'   => NULL,
        'ubicacion'   => NULL,
        'creado'   => NULL,
        'modificado'   => NULL,
    );


    /**
     * Relaciones
     **/
    protected $_has_many = array(
        'categorias' => array(
            'model'   => 'Categoria',
            'foreign_key' => 'tipo_categoria_id',
        ),
    );



    /**
     * Borra la categoria y todo lo asociado.
     *
     * @author Marcos Calabrese <marcosc@tekar.net>
     * @since 20130625
     * @version 20130625
     **/
    public function delete()
    {

        // borra los contenidos asociados
        foreach( $this->contenido->find_all() as $contenido )
        {
            $contenido->delete();
        }

        // borra las subcategorias asociadas
        foreach( $this->sub_categorias->find_all() as $sub_categoria )
        {
            $sub_categoria->delete();
        }

        // finalmente borra la categoria en si
        parent::delete();

    }


    /**
     * Devuelve un listado de categorias tipo para el menu desplegable.
     *
     * @author Marcos Calabrese <marcosc@tekar.net>
     * @since 20130823
     * @version 20130823
     *
     **/
    static public function tipo_categorias_desplegable( $params=NULL )
    {

        // prepara el ARRAY de secciones que va a servir para armar el combo de secciones PADRES
        $categorias = DB::select( 'categoria_tipo.id', 'categoria_tipo.descripcion' )
                        ->from( 'categoria_tipo' )
                        ->where( 'categoria_tipo.activo', '=', '1' )
                        ->order_by( 'categoria_tipo.descripcion' );

        if( !empty( $params['ubicacion'] ) )
        {
            $categorias->where( 'categoria_tipo.ubicacion', '=', $params['ubicacion'] );
        }

        $categorias = $categorias->execute()
                    ->as_array( 'id', 'descripcion' );



        // agrega el primer item
        $categorias = array( '' => '-- todos los tipos de filtro' ) + $categorias;

        return( $categorias );

    }



    /**
     * Devuelve el listado de tipos de filtros
     *
     * @author Marcos Calabrese <marcosc@tekar.net>
     * @since 20130824
     * @version 20130824
     **/
    static public function obtiene_filtros( $tipo )
    {

        return( DB::select( 'categoria_tipo.url', 'categoria_tipo.descripcion' )
                            ->from( 'categoria_tipo' )
                            ->where( 'categoria_tipo.activo', '=', '1' )
                            ->where( 'categoria_tipo.ubicacion', '=', '1' )
                            ->execute() );

    }


    /**
     * Devuelve el listado de categorias principales que sean del tipo.
     *
     * @author Marcos Calabrese <marcosc@tekar.net>
     * @version 20140113
     **/
    public function categorias()
    {
        return( $this->categorias->where( 'categoria_id', 'IS', NULL )->where( 'activo', '=', '1' )->order_by( 'descripcion' )->find_all() );
    }



}
