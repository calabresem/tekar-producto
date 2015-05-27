<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Controladora del Administrador de grupos de fotos
 *
 * @author Marcos Calabrese <marcosc@tekar.net>
 **/
abstract class Controller_Backend_Core_Compra extends Controller {


    /**
     * Se usa para armar las URLs. Es la base de la URL.
     * @name base_uri
     **/
    protected $base_uri = 'panel/';


    /**
     * Contiene el usuario de sesion.
     * @name usuario
     **/
    protected $usuario;



    /**
     * Inicializa
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
     * Listado.
     *
     * @author Marcos Calabrese <marcosc@tekar.net>
     * @since 20131016
     *
     **/
    public function action_get_listar()
    {


        // revisa si hay una busqueda
        $buscado = '';
        if( Request::current()->post() )
        {
            $buscado = Request::current()->post( 'buscado' );
        }


        // carga la plnatilla de contenidos
        $plantilla = View::factory( 'backend/compras' )
                            ->bind( 'errors', $errors )
                            ->bind( 'message', $message );



        $plantilla->listado = ORM::factory( 'Compra' )
                                ->where( 'estado_id', '>', '1' )
                                ->order_by( 'id', 'asc' )
                                ->find_all();




        //
        $plantilla->titulo = 'Compras';
        $plantilla->h3 = $plantilla->titulo;
        $plantilla->usuario = $this->usuario;


        $this->response->body( $plantilla->render() );


    }



    /**
     * Prepara la pantalla para editar/agregar slide
     *
     * @author Marcos Calabrese <marcosc@tekar.net>
     * @version 20130626
     **/
    public function action_get_editar()
    {

        // busca el codigo pasado por parametro
        $id = Request::current()->param( 'id' );


        // cambia el template
        $plantilla = View::factory( 'backend/grupo' )
                            ->bind( 'errors', $errors )
                            ->bind( 'message', $message );


        $grupo = ORM::factory( 'Grupo', $id );



        // revisamos que el codigo exista
        if( $grupo->loaded() === true )
        {

            $plantilla->h1 = 'Edici&oacute;n de Grupo: ' . $grupo->nombre;

        } else
        {
            $plantilla->h1 = 'Nuevo grupo en p&aacute;gina de inicio';

        }


        $plantilla->grupo = $grupo;
        $plantilla->titulo = 'Contenidos';
        $plantilla->usuario = $this->usuario;


        $this->response->body( $plantilla->render() );


    }



    /**
     * Prepara la pantalla para editar/agregar slide
     *
     * @author Marcos Calabrese <marcosc@tekar.net>
     * @version 20130626
     **/
    public function action_get_detalle()
    {

        // busca el codigo pasado por parametro
        $id = Request::current()->param( 'id' );


        // cambia el template
        $plantilla = View::factory( 'backend/compra' );


        $compra = ORM::factory( 'Compra', $id );

        if( $compra->loaded() === FALSE )
            $this->redirect( '/panel/compra/lista/', 302 );



        $plantilla->detalle = $compra;
        $plantilla->h1 = $plantilla->titulo = 'Detalle de compra';
        $plantilla->usuario = $this->usuario;


        $this->response->body( $plantilla->render() );


    }


    /**
     * Actualiza un grupo.
     * Este metodo se asume que viene solicitado via AJAX.
     *
     * @author Marcos Calabrese <marcosc@tekar.net>
     * @version 20130710
     *
     * @returns JSON
     **/
    public function action_put_editar()
    {


        $json = new JSend();


        $DB = Database::instance( 'default' );

        $DB->begin();

        try
        {


            /**
             * Procesamos
             **/

            // arma un array con los parametros pasados por PUT, ya que KOHANA no tiene un array propio :(
            // no entiendo bien porque. tenia uno, y al parecer lo sacaron
            $put_grupo = array();
            parse_str( Request::current()->body(), $put_grupo );
            $put_grupo = Arr::get( $put_grupo, 'grupo' );


            // reglas de validacion para la categoria
            $validacion = Validation::factory( $put_grupo )
                            ->rule( 'nombre', 'not_empty' )
                            ->rule( 'codigo', 'not_empty' );


            // validamos
            if( $validacion->check() === FALSE )
                throw new ORM_Validation_Exception( '', $validacion );


            $grupo = ORM::factory( 'Grupo', $put_grupo['id'] );


            // asigna los valores al modelo
            $grupo->values( $put_grupo );

            // graba
            $grupo->save();


            $DB->commit();

            // respuesta
            $json->uri = '/'.$this->base_uri.'grupo/editar/'.$grupo->id;
            $json->mensaje = 'Grabado.';



        } catch ( ORM_Validation_Exception  $e )
        {

            $DB->rollback();

            // carga los errores de validacion
            $respuesta = $e->errors( 'model/grupo' );

            $json->status(JSend::FAIL)
                ->data( 'errors', $respuesta );



        }
        catch ( Exception $e )
        {

            $DB->rollback();

            $json->status( JSend::FAIL )
                ->data( 'errors', 'Error ['.$e->getMessage().']' );

        }


        unset( $DB, $grupo, $validacion, $put_grupo );

        $json->render_into( $this->response );


    }



    /**
     * Genera un nuevo grupo usando las fotos seleccionadas desde el repositorio.
     * Basicamente lo que hace es genera un GRUPO vacio e importa las fotos.
     *
     * @author Marcos Calabrese <marcosc@tekar.net>
     * @version 20131009
     *
     * @returns JSON
     **/
    public function action_post_importar()
    {

        $json = new JSend();


        $DB = Database::instance( 'default' );

        $DB->begin();


        try
        {


            $fotos_post = Request::current()->post();


            /**
             * Generamos el grupo
             **/

            $grupo = ORM::factory( 'Grupo' );

            $grupo->nombre = '** nuevo del ' . date( 'd-m-Y' ) . ' **';


            // graba
            $grupo->save();



            /**
             * Procesamos las fotos
             **/

            $fotos = $fotos_post['foto'];

            foreach( $fotos as $archivo_foto )
            {

                // generamos una Foto en base al archivo
                $foto = new Model_Foto;
                $foto->genera_de_archivo( new SplFileInfo( Helper_Repositorio::repositorio_usuario( $archivo_foto ) ) );

                // generamos un producto en base a la foto
                $producto = new Model_Producto;
                $producto->usuario_id = $this->usuario->id;
                $producto->genera_de_foto( $foto );


                // gestionamos la asociacion
                $grupo->add( 'productos', $producto->id );


                // mueve el archivo
                Helper_Repositorio::mueve_archivo( $archivo_foto, $producto );


                unset( $foto, $producto );

            }

            unset( $fotos, $archivo_foto );


            $DB->commit();


            // respuesta
            $json->uri = '/'.$this->base_uri.'grupo/editar/'.$grupo->id;
            $json->mensaje = 'Grabado.';



        } catch ( ORM_Validation_Exception  $e )
        {

            $DB->rollback();

            // carga los errores de validacion
            $respuesta = $e->errors( 'backend/model/grupo' );

            $json->status(JSend::FAIL)
                ->data( 'errors', $respuesta );



        }
        catch ( Exception $e )
        {

            $DB->rollback();

            $json->status( JSend::FAIL )
                ->data( 'errors', 'Error ['.$e->getMessage().']' );

        }


        unset( $DB, $grupo, $validacion, $post, $post_slide );

        $json->render_into( $this->response );


    }



    /**
     * Asocia fotos a un grupo ya establecido.
     *
     * @author Marcos Calabrese <marcosc@tekar.net>
     * @version 20131009
     *
     * @returns JSON
     **/
    public function action_put_importar()
    {

        // busca el codigo pasado por parametro
        $id = Request::current()->param( 'id' );


        $json = new JSend();


        $DB = Database::instance( 'default' );

        $DB->begin();


        try
        {


            /**
             * Procesamos las fotos
             **/

            $fotos_put = array();
            parse_str( Request::current()->body(), $fotos_put );


            /**
             * Generamos el grupo
             **/

            $grupo = ORM::factory( 'Grupo', $id );


            /**
             * Procesamos las fotos
             **/

            $fotos = Arr::get( $fotos_put, 'foto' );

            foreach( $fotos as $archivo_foto )
            {

                // generamos una Foto en base al archivo
                $foto = new Model_Foto;
                $foto->genera_de_archivo( new SplFileInfo( Helper_Repositorio::repositorio_usuario( $archivo_foto ) ) );

                // generamos un producto en base a la foto
                $producto = new Model_Producto;
                $producto->usuario_id = $this->usuario->id;
                $producto->genera_de_foto( $foto );


                // gestionamos la asociacion
                $grupo->add( 'productos', $producto->id );


                // mueve el archivo
                Helper_Repositorio::mueve_archivo( $archivo_foto, $producto );


                // genera la miniatura
                $foto->genera_miniatura();



                unset( $foto, $producto );

            }

            unset( $fotos, $archivo_foto, $fotos_put );


            $DB->commit();


            // respuesta
            $json->uri = '/'.$this->base_uri.'grupo/editar/'.$grupo->id;
            $json->mensaje = 'Grabado.';





        } catch ( ORM_Validation_Exception  $e )
        {

            $DB->rollback();

            // carga los errores de validacion
            $respuesta = $e->errors( 'backend/model/grupo' );

            $json->status(JSend::FAIL)
                ->data( 'errors', $respuesta );



        }
        catch ( Exception $e )
        {

            $DB->rollback();

            $json->status( JSend::FAIL )
                ->data( 'errors', 'Error ['.$e->getMessage().']' );

        }


        unset( $DB, $grupo, $validacion, $post, $post_slide );

        $json->render_into( $this->response );


    }



    /**
     * Borra un recurso
     *
     * @author Marcos Calabrese <marcosc@tekar.net>
     **/
    public function action_delete_borrar()
    {


        // busca el codigo pasado por parametro
        $id = Request::current()->param( 'id' );


        $obj = ORM::factory( 'Slide', $id );

        $json = new JSend();


        $DB = Database::instance( 'default' );


        try
        {


            // inicia transaccion
            $DB->begin();


            if( $obj->loaded() === FALSE )
                throw new Exception( 'El recurso no existe.' );


            // borra el contenido
            $obj->delete();

            $DB->commit();

            $json->mensaje = 'Borrado.';
            $json->uri = '/'.$this->base_uri.'slide/listar/';


        }
        catch ( Exception $e )
        {

            $DB->rollback();

            $json->status( JSend::FAIL )
                ->data( 'errors', 'Error ['.$e->getMessage().']' );

        }

        unset( $DB, $obj, $id );


        $json->render_into( $this->response );


    }



    /**
     * Actualiza los estados de publicacion de los grupos.
     *
     * @author Marcos Calabrese <marcosc@tekar.net>
     * @since 20131009
     * @version 20131009
     **/
    public function action_post_estados()
    {

        $id = Request::current()->post( 'id' );
        $publicados = Request::current()->post( 'publicado' );

        $json = new JSend();


        $DB = Database::instance( 'default' );

        $DB->begin();


        try
        {

            if( count( $id ) > 0 )
            {

                for( $i=0; $i < count( $id ); $i++ )
                {

                    $grupo = ORM::factory( 'Grupo', $id[$i] );
                    $grupo->publicado = ( ( is_array( $publicados ) AND in_array( $id[$i], $publicados ) ) ? 1 : 0 );
                    $grupo->save();
                    unset( $grupo );

                }

            }

            $DB->commit();

            $json->mensaje = 'Actualizado.';

        }
        catch ( Exception $e )
        {

            $DB->rollback();

            $json->status( JSend::FAIL )
                ->data( 'errors', 'Error ['.$e->getMessage().']' );

        }


        unset( $DB, $publicados );

        $json->render_into( $this->response );

    }



} // End Slide

