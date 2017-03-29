<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Controladora del Administrador de contenido
 *
 * @author Marcos Calabrese <marcosc@tekar.net>
 **/
abstract class Controller_Backend_Core_Categorias extends Controller {


    /**
     * @name base_uri
     **/
    protected $base_uri = 'panel/';

    /**
     * Contiene el usuario de sesion.
     * @name usuario
     **/
    protected $usuario;


    /**
     * Acciones varias al iniciar el controlador
     *
     * @author Marcos Calabrese <marcosc@tekar.net>
     **/
    public function before()
    {

        parent::before();


        // levanta informacion del usuario
        $this->usuario = Auth::instance()->get_user();

        // si no hay usuario, entonces mostramos el login
        if( !$this->usuario )
            $this->redirect( $this->base_uri.'login', 302 );


    }



    /**
     * Lista las categorias.
     *
     * @author Marcos Calabrese <marcosc@tekar.net>
     * @since 20130820
     **/
     public function action_get_listar()
    {


        //$padre = Request::current()->param( 'id' );
        $format = Request::current()->param( 'format' );


        if( empty( $padre ) )
        {
            $padre = 0;
        } else
        {
            $categoria = ORM::factory( 'Categoria', $padre );
        }



        // revisa si hay una busqueda
        $buscado = Request::current()->query( 'buscado' );



        // carga la plnatilla de contenidos
        $plantilla = View::factory( 'backend/categorias' )
                            ->bind( 'errors', $errors )
                            ->bind( 'message', $message );



        // -------------------------------------------------------

        $sql = "SELECT padre.id AS padre_id, padre.id, padre.descripcion, '0' AS es_hijo,
                padre.activo
                FROM categoria AS padre
                WHERE padre.tipo_categoria_id IN (1,2)
                AND padre.categoria_id IS NULL
                AND 1=1

                UNION

                SELECT hijos.categoria_id AS padre_id,  hijos.id, hijos.descripcion, '1' AS es_hijo,
                hijos.activo
                FROM categoria AS hijos
                WHERE hijos.tipo_categoria_id IN (1,2)
                AND hijos.categoria_id IS NOT NULL
                AND 2=2

                ORDER BY padre_id ASC, es_hijo ASC, descripcion ASC";

        if (!empty($buscado)) {
            $sql = str_replace("1=1", "padre.descripcion LIKE '%".$buscado."%'", $sql);
            $sql = str_replace("2=2", "hijos.descripcion LIKE '%".$buscado."%'", $sql);
        }


        $plantilla->categorias = DB::query( Database::SELECT, $sql )->execute();


        // -------------------------------------------------------


        // pasa la accion actual, para marcar el menu activo
        $plantilla->buscado = $buscado;


        // pasa el combo con categorias
        $plantilla->categoria_combo_padre = 0;
        $plantilla->categorias_tipo = Model_Categoria_Tipo::tipo_categorias_desplegable( array( 'ubicacion' => 1 ) );
        $plantilla->usuario = $this->usuario;


        if( $format == 'json' )
        {

            $this->response->body( json_encode( $plantilla->categorias->as_array( 'descripcion' ) ) );

            unset( $plantilla );

        } else
        {

            $this->response->body( $plantilla->render() );

        }


    }




    /**
     * Administra las categorias
     *
     * @author Marcos Calabrese <marcosc@tekar.net>
     * @version 20131104
     **/
    public function action_get_editar()
    {


        // busca el codigo pasado por parametro
        $id = Request::current()->param( 'id' );


        // cambia el template
        $plantilla = View::factory( 'backend/categoria' )
                            ->bind( 'errors', $errors )
                            ->bind( 'message', $message );


        // cargamos la categoria
        $categoria = ORM::factory( 'Categoria', $id );



        // -------------------------------------------

        // revisamos que el codigo exista
        if( $categoria->loaded() === true )
        {
            $plantilla->titulo = 'Edici&oacute;n de filtro: ' . $categoria->descripcion;

        } else
        {
            $plantilla->titulo = 'Nuevo filtro';

        }


        $plantilla->categoria = $categoria;
        $plantilla->token = Security::token();
        $plantilla->usuario = $this->usuario;


        // pasa el combo con tipos de categorias
        $plantilla->categorias_tipo = Model_Categoria_Tipo::tipo_categorias_desplegable();

        // pasa el combo con categorias padre
        $plantilla->categorias_padre = Model_Categoria::categorias_padre_desplegable();


        $this->response->body( $plantilla->render() );


    }





    /**
     * Administra las categorias.
     * Este metodo se asume que viene solicitado via AJAX.
     *
     * @author Marcos Calabrese <marcosc@tekar.net>
     * @version 20130710
     *
     * @returns JSON
     **/
    public function action_post_editar()
    {

        $json = new JSend();

        $DB = Database::instance( 'default' );

        $DB->begin();

        try
        {

            /**
             * Procesamos la categoria
             **/

            $post_categoria = Request::current()->post( 'categoria' );

            // reglas de validacion para la categoria
            $validacion = Validation::factory( $post_categoria )
                            ->rule( 'tipo_categoria_id', 'not_empty' )
                            ->rule( 'tipo_categoria_id', 'numeric' )
                            ->rule( 'categoria_id', 'numeric' )
                            ->rule( 'url', 'alpha_dash' )
                            ->rule( 'descripcion', 'not_empty' );

            // validamos
            if( $validacion->check() === FALSE )
                throw new ORM_Validation_Exception( '', $validacion );



            $categoria = ORM::factory( 'Categoria' );

            // asigna los valores al modelo
            $categoria->values( $post_categoria );

            // creador
            $categoria->creado_por = $this->usuario->id;


            // prepara el TAG y la RUTA. Solo si el tag esta vacio
            if( empty( $categoria->url ) )
            {
                $categoria->url = URL::title( Helper_MyUrl::normaliza( $categoria->descripcion ) );
            }


            // graba
            $categoria->save();

            $DB->commit();


            // respuesta
            $json->uri = '/panel/categorias/editar/'.$categoria->id;
            $json->mensaje = 'Grabado.';



        } catch ( ORM_Validation_Exception $e )
        {

            $DB->rollback();

            // carga los errores de validacion
            $respuesta = $e->errors( 'model/categoria' );

            $json->status(JSend::FAIL)
                ->data( 'errors', $respuesta );



        }
        catch ( Exception $e )
        {

            $DB->rollback();

            $json->status( JSend::FAIL )
                ->data( 'errors', array( $e->getMessage() ) );

        }


        unset( $DB, $categoria, $validacion, $post_categoria );

        $json->render_into( $this->response );


    }




    /**
     * Actualiza una categoria.
     *
     * @author Marcos Calabrese <marcosc@tekar.net>
     * @version 20131104
     *
     * @returns JSON
     **/
    public function action_put_editar()
    {

        // busca el codigo pasado por parametro
        $id = Request::current()->param( 'id' );


        $json = new JSend();

        $DB = Database::instance( 'default' );

        $DB->begin();

        try
        {

            /**
             * Procesamos
             **/

            // buscamos los datos del form
            $put_crudo = Request::data();
            $put_categoria = Arr::get( $put_crudo, 'categoria' );


            // reglas de validacion para la categoria
            $validacion = Validation::factory( $put_categoria )
                            ->rule( 'tipo_categoria_id', 'not_empty' )
                            ->rule( 'tipo_categoria_id', 'numeric' )
                            ->rule( 'categoria_id', 'numeric' )
                            ->rule( 'url', 'alpha_dash' )
                            ->rule( 'descripcion', 'not_empty' );


            // validamos
            if( $validacion->check() === FALSE )
                throw new ORM_Validation_Exception( '', $validacion );


            $obj = ORM::factory( 'Categoria', $id );


            /**
             * Procesamos la categoria
             **/

            // asigna los valores al modelo
            $obj->values( $put_categoria );


            // prepara el TAG y la RUTA. Solo si el tag esta vacio
            if( empty( $obj->url ) )
            {
                $obj->url = URL::title( Helper_MyUrl::normaliza( $obj->descripcion ) );
            }


            // graba
            $obj->save();

            $DB->commit();


            // respuesta
            $json->uri = '/panel/categorias/editar/'.$obj->id;
            $json->mensaje = 'Grabado.';



        } catch ( ORM_Validation_Exception $e )
        {

            $DB->rollback();

            // carga los errores de validacion
            $respuesta = $e->errors( 'model/categoria' );

            $json->status(JSend::FAIL)
                ->data( 'errors', $respuesta );



        }
        catch ( Exception $e )
        {

            $DB->rollback();

            $json->status( JSend::FAIL )
                ->data( 'errors', array( $e->getMessage() ) );

        }


        unset( $DB, $categoria, $validacion, $post_categoria );

        $json->render_into( $this->response );


    }


    /**
     * Borra una categoria.
     *
     * @author Marcos Calabrese <marcosc@tekar.net>
     * @since 20130625
     **/
    public function action_delete_borrar()
    {

        // busca el codigo pasado por parametro
        $id = Request::current()->param( 'id' );


        $json = new JSend();


        $DB = Database::instance( 'default' );

        // inicia transaccion
        $DB->begin();

        try
        {

            $categoria = ORM::factory( 'Categoria', $id );


            if( $categoria->loaded() === FALSE )
                throw new Exception( 'La categoria no existe.' );


            // borra el contenido
            $categoria->delete();

            $DB->commit();


            $json->mensaje = 'Borrado.';
            $json->uri = '/panel/categorias/listar';


        }
        catch ( Exception $e )
        {

            $DB->rollback();

            $json->status( JSend::FAIL )
                ->data( 'errors', array( $e->getMessage() ) );

        }

        unset( $DB, $categoria, $id );


        $json->render_into( $this->response );


    }




    /**
     * Establece el orden de las categorias
     *
     * @author Marcos Calabrese <marcosc@tekar.net>
     * @since 20130625
     **/
    public function action_post_orden()
    {


        $orden = Request::current()->post( 'categoria' );

        $json = new JSend();


        try
        {

            if( count( $orden ) > 0 )
            {

                for( $i=0; $i < count( $orden ); $i++ )
                {

                    $obj = ORM::factory( 'Categoria', $orden[$i] );
                    $obj->orden = $i;
                    $obj->save();
                    unset( $obj );

                }

            }

            $json->mensaje = 'Actualizado.';

        }
        catch ( Exception $e )
        {

            $json->status( JSend::FAIL )
                ->data( 'errors', array( $e->getMessage() ) );

        }

        $json->render_into( $this->response );

    }



    /**
     * Actualiza los datos que se mandan desde la lista de categorias.
     *
     * @author Marcos Calabrese <marcosc@tekar.net>
     * @since 20130707
     * @version 20130708
     **/
    public function action_put_estados()
    {

        $put_crudo = Request::data();
        $id = Arr::get( $put_crudo, 'id' );
        $activos = Arr::get( $put_crudo, 'activo' );


        $json = new JSend();


        try
        {

            if( count( $id ) > 0 )
            {

                for( $i=0; $i < count( $id ); $i++ )
                {

                    $categoria = ORM::factory( 'Categoria', $id[$i] );
                    $categoria->activo = ( in_array( $id[$i], $activos ) ? 1 : 0 );
                    $categoria->save();
                    unset( $categoria );

                }

            }

            $json->mensaje = 'Actualizado.';

        }
        catch ( Exception $e )
        {

            $json->status( JSend::FAIL )
                ->data( 'errors', array( $e->getMessage() ) );

        }

        $json->render_into( $this->response );

    }





    /**
     * Borra categorias y contenidos asociados, a los hijos de la categoria pasada por parametro.
     *
     * @author Marcos Calabrese <marcosc@tekar.net>
     **/
    protected function borraCategoriasRecursivo( $categoria )
    {

        if( empty( $categoria ) ) return( false );


        // levanta los datos de la categoria
        $categorias = ORM::factory( 'Categoria' )
                        ->where( 'padre', '=', $categoria )
                        ->find_all();

        foreach( $categorias as $item )
        {

            // primero borra todos los contenidos asociados
            $contenidos = ORM::factory( 'Contenido' )
                                ->where( 'categoria', '=', $item->codigo )
                                ->find_all();

            foreach( $contenidos as $contenido )
            {
                $contenido->delete();       // borra contenido
            }

            unset( $contenidos, $contenido );


            // despues sigue borrando recursivamente
            if( $this->borraCategoriasRecursivo( $item->codigo ) === false )
            {
                return( false );
            }

            // borra la categoria
            $item->delete();

        }

        unset( $categorias, $item );


        return( true );

    }




} // End Backend

