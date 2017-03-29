<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Modelo de Categoria
 *
 * @package    tekar/tekar-producto
 * @author     Marcos Calabrese <marcosc@tekar.net>
 * @copyright  Copyright (c) 2013 TEKAR
 * @license    ?
 **/
abstract class Model_Core_Categoria extends ORM {


    protected $_table_name = 'categoria';

    protected $_created_column = array('column' => 'creado', 'format' => TRUE);
    protected $_updated_column = array('column' => 'modificado', 'format' => TRUE);


    /**
     * Aca va a ir como array el campo INFO. Ese campo info se graba como JSON.
     */
    protected $_opciones = array();


    /**
     * Definicion de tablas manualmente. Evitamos lectura extra y podemos usar PDO.
     **/
    protected $_table_columns = array(
        'id' => NULL,
        'tipo_categoria_id'     => NULL,
        'descripcion'   => NULL,
        'categoria_id'   => NULL,
        'activo'   => NULL,
        'url'   => NULL,
        'orden'   => NULL,
        'creado_por'   => NULL,
        'creado'   => NULL,
        'modificado'   => NULL,
        'en_menu'   => NULL,
        'info'   => NULL,
    );



    /**
     * Relaciones
     **/
    protected $_has_many = array(
        'categorias_hijas' => array(
            'model'   => 'Categoria',
            'foreign_key' => 'categoria_id',
        ),
        'productos' => array(
            'model' => 'Producto_Categoria',
            'through' => 'producto_categoria',
            'far_key' => 'producto_id',
            'foreign_key' => 'categoria_id',
        ),
        'categorias' => array(
            'model'   => 'Categoria',
            'foreign_key' => 'categoria_id',
        ),
    );


    protected $_belongs_to = array(
        'padre' => array(
            'model' => 'Categoria',
            'foreign_key' => 'categoria_id',
        ),
        'tipo' => array(
            'model' => 'Categoria_Tipo',
            'foreign_key' => 'tipo_categoria_id',
        ),
    );



    protected $_sub_categorias = NULL;


    /**
     * Filtros varios:
     * - pais_id: para evitar que se intente guardar como 0, que da error de FK.
     **/
    public function filters()
    {
        return array(
            'categoria_id' => array(
                array( function( $value ) {
                    return( empty( $value ) ? NULL : $value );
                }),
            ),
        );
    }



    /**
     * Borra la categoria y todo lo asociado.
     *
     * @author Marcos Calabrese <marcosc@tekar.net>
     * @since 20130625
     * @version 20130625
     **/
    public function delete()
    {

        // borra las subcategorias asociadas
        foreach( $this->categorias_hijas->find_all() as $sub_categoria )
        {
            $sub_categoria->delete();
        }

        // finalmente borra la categoria en si
        parent::delete();

    }



    /**
     * Devuelve la categoria padre
     *
     * @author Marcos Calabrese <marcosc@tekar.net>
     * @since 20130802
     * @version 20130802
     **/
    public function cat_padre()
    {
        return( ( $this->categoria_id != 0 ) ? ORM::factory( 'Categoria', $this->categoria_id ) : FALSE );
    }



    /**
     * Devuelve un listado de categorias padre para el menu desplegable.
     *
     * @author Marcos Calabrese <marcosc@tekar.net>
     * @version 20131101
     **/
    static public function categorias_padre_desplegable()
    {

        // prepara el ARRAY de secciones que va a servir para armar el combo de secciones PADRES
        $sql = "SELECT categoria.id, CONCAT( categoria_tipo.descripcion, ' - ', categoria.descripcion ) AS descripcion
                FROM categoria
                INNER JOIN categoria_tipo ON categoria_tipo.id = categoria.tipo_categoria_id
                WHERE categoria.activo = 1
                AND categoria.categoria_id IS NULL";

        $categorias = DB::query( Database::SELECT, $sql )
                        ->execute()
                        ->as_array( 'id', 'descripcion' );

        // agrega el primer item
        $categorias = array( '' => '-- es filtro padre' ) + $categorias;

        return $categorias;

    }


    /**
     * Devuelve o setea valores en la configuracion que esta en el campo JSON
     **/
    public function opciones( $clave, $valor=NULL )
    {

        // inicializa (lazy)
        if (! empty ($this->info) AND count($this->_opciones) === 0) {
            $this->_opciones = json_decode($this->info, true);
        }


        // hay que devolver el valor
        if (is_null($valor)) {
            return (! isset ($this->_opciones[$clave])) ? NULL : $this->_opciones[$clave];

        } else {

            // hay que setearlo
            $this->_opciones[$clave] = $valor;
        }

    }


}
