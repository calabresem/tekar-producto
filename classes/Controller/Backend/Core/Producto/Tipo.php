<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Controladora del Administrador de contenido
 *
 * @author Marcos Calabrese <marcosc@tekar.net>
 **/
abstract class Controller_Backend_Core_Producto_Tipo extends Controller {


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
     * Lista los tipos de productos.
     *
     * @author Marcos Calabrese <marcosc@tekar.net>
     * @since 20140106
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
            $categoria = ORM::factory( 'Producto_Tipo', $padre );
        }



        // revisa si hay una busqueda
        $buscado = Request::current()->query( 'buscado' );



        // carga la plnatilla de contenidos
        $plantilla = View::factory( 'backend/tipos' )
                            ->bind( 'errors', $errors )
                            ->bind( 'message', $message );



        // -------------------------------------------------------

        $plantilla->lista = ORM::factory( 'Producto_Tipo' )->find_all();


        // -------------------------------------------------------

        // pasa la accion actual, para marcar el menu activo
        $plantilla->buscado = $buscado;
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
     * Administra los tipos
     *
     * @author Marcos Calabrese <marcosc@tekar.net>
     * @version 20140106
     **/
    public function action_get_editar()
    {


        // busca el codigo pasado por parametro
        $id = Request::current()->param( 'id' );


        // cambia el template
        $plantilla = View::factory( 'backend/tipo' )
                            ->bind( 'errors', $errors )
                            ->bind( 'message', $message );


        // cargamos la categoria
        $obj = ORM::factory( 'Producto_Tipo', $id );



        // -------------------------------------------

        // revisamos que el codigo exista
        if( $obj->loaded() === true )
        {
            $plantilla->titulo = 'Edici&oacute;n de tipo de producto: ' . $obj->descripcion;

        } else
        {
            $plantilla->titulo = 'Nuevo tipo de producto';

        }


        $plantilla->obj = $obj;
        $plantilla->token = Security::token();
        $plantilla->usuario = $this->usuario;


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

            $post_obj = Request::current()->post( 'producto_tipo' );

            // reglas de validacion para la categoria
            $validacion = Validation::factory( $post_obj )
                            ->rule( 'descripcion', 'not_empty' );


            // validamos
            if( $validacion->check() === FALSE )
                throw new ORM_Validation_Exception( '', $validacion );



            $obj = ORM::factory( 'Producto_Tipo' );

            // asigna los valores al modelo
            $obj->values( $post_obj );

            // graba
            $obj->save();



            /**
             * Procesamos las caracteristicas
             **/

            // primero buscamos las que ya existen
            $sql = "SELECT producto_tipo_caracteristica.id, producto_tipo_caracteristica.descripcion
                    FROM producto_tipo_caracteristica";

            $caracts = DB::query( Database::SELECT, $sql )->execute()->as_array( 'id', 'descripcion' );

            // ahora procesamos
            $caracteristicas = Arr::get( $post_obj, 'producto_tipo_caracteristica', '' );

            // PHP_EOL
            $items = explode( "\r\n", Arr::get( $caracteristicas, 'caracteristicas', '' ) );

            foreach( $items as $item )
            {

                $item_limpio = str_replace( "*", "", $item );


                if( empty( $item_limpio ) )
                    continue;

                // si no existe...
                if( !( $index = array_search( $item_limpio, $caracts ) ) )
                {

                    // lo crea
                    // el "*" en el nombre, indica la caracteristica por defecto. si tiene 2, le da orden 2
                    // que tiene que ver con el renglon en el listado.
                    $item_nuevo = ORM::factory( 'Producto_Tipo_Caracteristica' );
                    $item_nuevo->descripcion = $item_limpio;
                    $item_nuevo->activo = 1;
                    $item_nuevo->producto_tipo_id = $obj->id;
                    $item_nuevo->defecto = substr_count( $item, '*' );
                    $item_nuevo->save();
                    unset( $item_nuevo );

                } else
                {

                    // si existe, lo borra del array
                    unset( $caracts[$index] );


                }

            }


            if( count( $caracts ) > 0 )
            {
                //$obj->remove( 'caracteristicas', array_keys( $caracts ) );
                                // primero buscamos las que ya existen
                $sql = "DELETE producto_tipo_caracteristica
                        FROM producto_tipo_caracteristica
                        WHERE producto_tipo_caracteristica.producto_tipo_id = " . $obj->id . "
                        AND producto_tipo_caracteristica.id IN ( " . implode( ",", array_keys( $caracts ) ) . ")";

                DB::query( Database::DELETE, $sql )->execute();

            }




            $DB->commit();


            // respuesta
            $json->uri = '/panel/producto_tipo/editar/'.$obj->id;
            $json->mensaje = 'Grabado.';



        } catch ( ORM_Validation_Exception $e )
        {

            $DB->rollback();

            // carga los errores de validacion
            $respuesta = $e->errors( 'model/producto_tipo' );

            $json->status(JSend::FAIL)
                ->data( 'errors', $respuesta );



        }
        catch ( Exception $e )
        {

            $DB->rollback();

            $json->status( JSend::FAIL )
                ->data( 'errors', array( $e->getMessage() ) );

        }


        unset( $DB, $obj, $validacion, $post_obj );

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
            $put_producto_tipo = Arr::get( $put_crudo, 'producto_tipo' );


            // reglas de validacion para la categoria
            $validacion = Validation::factory( $put_producto_tipo )
                            ->rule( 'descripcion', 'not_empty' );


            // validamos
            if( $validacion->check() === FALSE )
                throw new ORM_Validation_Exception( '', $validacion );


            $obj = ORM::factory( 'Producto_Tipo', $id );


            /**
             * Procesamos
             **/

            // asigna los valores al modelo
            $obj->values( $put_producto_tipo );

            // graba
            $obj->save();



            /**
             * Procesamos las caracteristicas
             **/

            if( $obj->loaded() )
            {

                // primero buscamos las que ya existen
                $sql = "SELECT producto_tipo_caracteristica.id, producto_tipo_caracteristica.descripcion
                        FROM producto_tipo_caracteristica
                        WHERE producto_tipo_caracteristica.producto_tipo_id = " . $obj->id;

                $caracts = DB::query( Database::SELECT, $sql )->execute()->as_array( 'id', 'descripcion' );

                // ahora procesamos
                $put_caracteristicas = Arr::get( $put_crudo, 'producto_tipo_caracteristica', '' );

                // PHP_EOL
                $items = explode( "\r\n", Arr::get( $put_caracteristicas, 'caracteristicas', '' ) );

                //$obj->remove( 'caracteristicas' );
                foreach( $items as $item )
                {

                    $item_limpio = str_replace( "*", "", $item );

                    if( empty( $item_limpio ) )
                        continue;

                    // si no existe...
                    if( !( $index = array_search( $item_limpio, $caracts ) ) )
                    {

                        // lo crea
                        // el "*" en el nombre, indica la caracteristica por defecto. si tiene 2, le da orden 2
                        // que tiene que ver con el renglon en el listado.
                        $item_nuevo = ORM::factory( 'Producto_Tipo_Caracteristica' );
                        $item_nuevo->descripcion = $item_limpio;
                        $item_nuevo->activo = 1;
                        $item_nuevo->producto_tipo_id = $obj->id;
                        $item_nuevo->defecto = substr_count( $item, '*' );
                        $item_nuevo->save();
                        unset( $item_nuevo );

                    } else
                    {

                        // si existe, lo borra del array
                        unset( $caracts[$index] );


                    }

                }

                // si quedo alguno, hay que intentar borrarlo
                if( count( $caracts ) > 0 )
                {
                    //$obj->remove( 'caracteristicas', array_keys( $caracts ) );
                                    // primero buscamos las que ya existen
                    $sql = "DELETE producto_tipo_caracteristica
                            FROM producto_tipo_caracteristica
                            WHERE producto_tipo_caracteristica.producto_tipo_id = " . $obj->id . "
                            AND producto_tipo_caracteristica.id IN ( " . implode( ",", array_keys( $caracts ) ) . ")";

                    DB::query( Database::DELETE, $sql )->execute();

                }

            }


            $DB->commit();


            // respuesta
            $json->uri = '/panel/producto_tipo/editar/'.$obj->id;
            $json->mensaje = 'Grabado.';



        } catch ( ORM_Validation_Exception $e )
        {

            $DB->rollback();

            // carga los errores de validacion
            $respuesta = $e->errors( 'model/producto_tipo' );

            $json->status(JSend::FAIL)
                ->data( 'errors', $respuesta );



        }
        catch ( Exception $e )
        {

            $DB->rollback();

            $json->status( JSend::FAIL )
                ->data( 'errors', array( $e->getMessage() ) );

        }


        unset( $DB, $obj, $validacion, $put_crudo, $put_producto_tipo );

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

