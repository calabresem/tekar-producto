<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Modelo de producto
 *
 * @package producto
 * @author Marcos Calabrese <marcosc@tekar.net>
 * @license http://openzula.org/license-bsd-3c BSD 3-Clause License
 */
abstract class Model_Core_Producto extends ORM {

    protected $_primary_key = 'id';
    protected $_table_name = 'producto';

    protected $_created_column = array( 'column' => 'creado', 'format' => TRUE );
    protected $_updated_column = array( 'column' => 'modificado', 'format' => TRUE );

    protected $_categorias;

    /**
     * Caracteristicas del producto
     */
    protected $_caracteristicas = array();

    /**
     * Fotos del producto.
     */
    protected $_fotos = array();

    /**
     * Campo INFO (JSON) parseado
     */
    protected $_info = array();


    /**
     * Definicion de tablas manualmente. Evitamos lectura extra y podemos usar PDO.
     **/
    protected $_table_columns = array(
        'id' => NULL,
        'codigo'  => NULL,
        'nombre'     => NULL,
        'descripcion'   => NULL,
        'precio'   => NULL,
        'estado_id'   => NULL,
        'principal_foto_id'   => NULL,
        'creado'   => NULL,
        'modificado'   => NULL,
        'borrado'   => NULL,
        'proveedor_id'   => NULL,
        'usuario_id'   => NULL,
        'permite_descuento'     => NULL,
        'info'     => NULL,
    );


    /**
     * Relaciones
     **/
    protected $_has_many = array(
        'categorias' => array(
            'model'   => 'Categoria',
            'through' => 'producto_categoria',
            'foreign_key' => 'producto_id',
            'far_key' => 'categoria_id',
        ),
        'compras' => array(
            'model'   => 'Compra_Item',
            'foreign_key' => 'producto_id',
        ),
        'fotos' => array(
            'model'   => 'Foto',
            'through' => 'producto_foto',
            'foreign_key' => 'producto_id',
            'far_key' => 'foto_id',
        ),
        'tipos' => array(
            'model'   => 'Producto_Tipo',
            'through' => 'producto_tipo_producto',
            'foreign_key' => 'producto_id',
            'far_key' => 'producto_tipo_id',
        ),
        'caracterisicas' => array(
            'model'   => 'Producto_Caracteristica_Valor',
            'foreign_key' => 'producto_id',
        ),
    );

    protected $_belongs_to = array(
        'proveedor' => array(
            'model'   => 'Proveedor',
            'foreign_key' => 'proveedor_id',
        ),
        'estado' => array(
            'model'   => 'Producto_Estado',
            'foreign_key' => 'estado_id',
        ),
    );

    /**
     * Filtros varios:
     * - pais_id: para evitar que se intente guardar como 0, que da error de FK.
     **/
    public function filters()
    {
        return array(
            'principal_foto_id' => array(
                array( function( $value ) {
                    return( empty( $value ) ? NULL : $value );
                }),
            ),
        );
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
        return Validation::factory( $datos )
                            ->rule( 'codigo', 'not_empty' )
                            ->rule( 'codigo', 'max_length', array( ':value', '30' ) )
                            ->rule( 'nombre', 'not_empty' );
                            //->rule( 'principal_foto_id', 'not_empty' );
                            //->rule( 'codigo', 'Model_Core_Producto::codigo_unico' )
    }




    /**
     * Verifica que el codigo sea unico
     *
     * @return boolean
     **/
    public static function codigo_unico( $codigo )
    {
        return ! DB::select( array( DB::expr('COUNT(id)'), 'cnt' ) )
            ->from( 'producto' )
            ->where( 'codigo', '=', $codigo )
            ->execute()
            ->get( 'cnt' );

    }



    /**
     * Devuelve la foto principal
     *
     * @return Model_OZ_Product_Photo
     */
    public function foto_principal()
    {
        return ORM::factory( 'Foto', $this->principal_foto_id );
    }



    /**
     * Helper method to determin if this product has a category
     *
     * @param int $category_id the category to check
     *
     * @return bool
     **/
    public function tiene_categoria( $category_id )
    {
        return $this->has( 'categorias', $category_id );
    }


    /**
     * Borra un producto y todo lo asociado
     *
     * @return mixed
     */
    public function delete()
    {

        // si hay ventas ya hechas, el borrado es logico.
        if( $this->count_relations( 'compras' ) > 0 )
        {
            $this->borrado = time();
            $this->estado_id = Model_Producto_Estado::no_disponible();
            $this->save();
            return( true );
        }


        // anula la relacion de foto principal
        $this->principal_foto_id = null;
        $this->save();


        // borra las asignaciones a los grupos
        /*
        foreach( $this->grupo->find_all() as $grupo )
        {
            $this->remove( 'grupo', $grupo->id );
        }
        */
        $this->remove( 'grupo' );

        // borra las asignaciones con tipos de productos
        $this->remove( 'tipos' );



        // borra las fotos
        foreach( $this->fotos->find_all() as $foto )
        {
            $this->remove( 'fotos', $foto->id );
            $foto->delete();
        }


        return parent::delete();
    }




    /**
     * Borra todas las fotos asociadas
     *
     * @return mixed
     */
    public static function buscar( array $parametros=NULL )
    {

        $DB = Database::instance( 'default' );

        $productos = $DB->query( Database::SELECT, 'SELECT buscar_productos.id FROM buscar_productos;' );



/*
        $productos = ORM::factory( 'Producto' );
        $productos->where( '' )

        if( !empty( $parametros['buscado'] ) )
        {
            $productos->where( 'nombre', 'like', '%' . $parametros['buscado'] . '%' );
        }

        $productos->order_by( 'nombre', 'asc' );
*/

        return( $productos );



    }


    /**
     * Tipo de producto
     *
     * @return mixed
     */
    public function producto_tipo()
    {
        return( $this->tipos->limit( 1 )->find() );
    }


    /**
     * Devuelve el valor de una caracteristica buscada por tipo de ID de caract
     *
     * @return mixed
     */
    /*
    public function caracteristica( $producto_tipo_caracteristica_id=NULL )
    {
        return( $this->caracterisicas->where( 'producto_tipo_caracteristica_id', '=', $producto_tipo_caracteristica_id )->find() );
    }
    */
    public function caracteristica($caract)
    {
        if (empty($this->_caracteristicas)) {
            $this->cargaCaracteristicas();
        }

        return isset($this->_caracteristicas[$caract]) ? $this->_caracteristicas[$caract] : null;
    }

    public function caracteristicas()
    {
        if (empty($this->_caracteristicas)) {
            $this->cargaCaracteristicas();
        }

        return $this->_caracteristicas;
    }


    /**
     * Devuelve el valor de una caracteristica buscada por nombre de tipo de caract
     *
     * @return mixed
     */
    public function caracteristica_desc( $producto_tipo_caracteristica=NULL )
    {
        return( $this->caracterisicas->where( 'descripcion', '=', $producto_tipo_caracteristica )->find() );
    }


    /**
     * Carga las caracteristicas del producto
     */
    protected function cargaCaracteristicas()
    {

        $sql = "select pcv.descripcion, pcv.valor
                from producto_caracteristica_valor as pcv
                inner join producto on pcv.producto_id = producto.id
                where producto.id = ".$this->id;

        $this->_caracteristicas = DB::query(Database::SELECT, $sql)
                                        ->execute()
                                        ->as_array('descripcion', 'valor');

    }



    /**
     * Devuelve las categorias asociadas al producto.
     *
     * @tipo_id
     * @return mixed
     */
    public function categorias($tipo_id=NULL)
    {

        if (is_null($tipo_id)) {
            $this->_categorias = $this->categorias->where( 'activo', '=', 1 )->find_all();

        } else {

            $this->_categorias = $this->categorias
                            ->where( 'tipo_categoria_id', 'IN', is_array($tipo_id) ? $tipo_id : [$tipo_id] )
                            ->where( 'activo', '=', 1 )->find_all();

        }

        return $this->_categorias;

    }


    /**
     * Busca fotos del producto. No las que salen de uso; sino fotos del producto en si.
     */
    protected function cargaFotos()
    {
        throw new Exception ('Sin definir.');
    }


    /**
     * Devuelve la lista de fotos disponibles del producto
     */
    public function fotos()
    {
        if (empty($this->_fotos)) {
            $this->cargaFotos();
        }

        return $this->_fotos;
    }

    public function imagenDefecto()
    {
        if (empty($this->_fotos)) {
            $this->cargaFotos();
        }

        return isset($this->_fotos[0]) ? $this->_fotos[0] : null;

    }

    /**
     * Devuelve un dato del campo INFO (JSON)
     */
    public function info($clave=null)
    {
        if (empty($this->_info)) {
            $this->_info = json_decode($this->info, true);
        }

        if (is_null($clave)) {
            return $this->_info;

        } else {
            return isset ($this->_info[$clave]) ? $this->_info[$clave] : null;

        }

    }

    /**
     * Override del SAVE para ajustar algunos valores x defecto.
     * Reconsideración: Voy a meterlo como una regla en FILTERS
     * Igualmente lo dejo x los dudas.
     */
    public function save( Validation $validation = NULL )
    {
        return parent::save($validation);
    }


}
