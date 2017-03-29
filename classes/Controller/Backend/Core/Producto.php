<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Controladora del Administrador de Productos
 *
 * @author Marcos Calabrese <marcosc@tekar.net>
 **/
abstract class Controller_Backend_Core_Producto extends Controller
{


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
     * Inicializa
     **/
    public function before()
    {
        parent::before();

        // levanta informacion del usuario
        $this->usuario = Auth::instance()->get_user();

        // si no hay usuario, entonces mostramos el login
        if (!$this->usuario) {
            $this->redirect($this->base_uri.'login', 302);
        }
    }




    /**
     * Lista los grupos disponibles del usuario.
     *
     * @author Marcos Calabrese <marcosc@tekar.net>
     **/
    public function action_get_listar()
    {
        $filtros = Request::current()->query();


        // carga la plnatilla de contenidos
        $plantilla = View::factory('backend/productos')
                            ->bind('errors', $errors)
                            ->bind('message', $message);


        // carga la lista
        $plantilla->productos = ORM::factory('Producto')
                                    ->where('borrado', 'IS', null)
                                    ->find_all();


        // titulo
        $plantilla->token = Security::token();
        // asigna el titulo
        $plantilla->h3 = $plantilla->titulo = 'Productos';
        $plantilla->filtros = $filtros;
        $plantilla->usuario = $this->usuario;


        $this->response->body($plantilla->render());
    }



    /**
     * Prepara la pantalla para editar/agregar un producto
     *
     * @author Marcos Calabrese <marcosc@tekar.net>
     * @version 20130626
     **/
    public function action_get_editar()
    {

        // busca el codigo pasado por parametro
        $id = Request::current()->param('id');


        // cambia el template
        $plantilla = View::factory('backend/producto')
                            ->bind('errors', $errors)
                            ->bind('message', $message);


        $producto = ORM::factory('Producto', $id);



        // revisamos que el codigo exista
        if ($producto->loaded() === true) {
            $plantilla->h1 = 'Edici&oacute;n de Producto: ' . $producto->nombre;
        } else {
            $plantilla->h1 = 'Nuevo producto';
            $producto->estado_id = 1;
        }


        // plantilla
        $plantilla->token = Security::token();
        $plantilla->producto = $producto;
        $plantilla->titulo = $plantilla->h1;
        $plantilla->usuario = $this->usuario;

        $this->response->body($plantilla->render());
    }



    /**
     * Adminsitra el agregado de un producto.
     *
     * @author Marcos Calabrese <marcosc@tekar.net>
     * @version 20131011
     *
     * @returns JSON
     **/
    public function action_post_editar()
    {
        $json = new JSend();


        $DB = Database::instance('default');

        $DB->begin();

        try {


            /**
             * Procesamos
             **/

            $datos_producto = Request::current()->post('producto');


            // reglas de validacion + validacion de token
            $validacion = Model_Producto::validador($datos_producto)
                                    ->rule('codigo', 'Model_Core_Producto::codigo_unico')
                                    ->rule('csrf', 'not_empty')
                                    ->rule('csrf', 'Security::check');

            // validamos
            if ($validacion->check() === false) {
                throw new ORM_Validation_Exception('', $validacion);
            }


            $producto = ORM::factory('Producto');


            // asigna los valores al modelo
            $producto->values($datos_producto);

            // graba
            $producto->save();


            // reinicia asignacion de Tipos de Producto
            $producto_tipo = Arr::get($datos_producto, 'producto_tipo_id');
            $producto->remove('tipos');

            if (!empty($producto_tipo)) {
                if (is_array($producto_tipo)) {
                    foreach ($producto_tipo as $producto_tipo_id) {
                        $producto->add('tipos', $producto_tipo_id);
                    }
                } else {
                    $producto->add('tipos', $producto_tipo);
                }
            }



            // ---------------------------------  CATEGORIAS
            $datos_categoria = Request::current()->post('categoria');

            if (!is_null($datos_categoria)) {
                $categorias = Arr::get($datos_categoria, 'id', null);

                $producto->remove('categorias');

                if (count($categorias) > 0) {
                    foreach ($categorias as $categoria_id) {
                        $producto->add('categorias', $categoria_id);
                    }
                }
            }



            $DB->commit();

            // respuesta
            $json->uri = '/'.$this->base_uri.'producto/editar/'.$producto->id;
            $json->mensaje = 'Grabado.';
        } catch (ORM_Validation_Exception $e) {
            $DB->rollback();

            // carga los errores de validacion
            $respuesta = $e->errors('model/producto');

            $json->status(JSend::FAIL)
                ->data('errors', $respuesta);
        } catch (Exception $e) {
            $DB->rollback();

            $json->status(JSend::FAIL)
                ->data('errors', array( $e->getMessage() ));
        }


        unset($DB, $producto, $validacion, $datos_producto);

        $json->render_into($this->response);
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


        $DB = Database::instance('default');

        $DB->begin();

        try {


            /**
             * Procesamos
             **/
            $put_crudo = Request::data();
            $datos_producto = Arr::get($put_crudo, 'producto');


            // reglas de validacion + validacion de token
            $validacion = Model_Producto::validador($datos_producto)
                                    ->rule('csrf', 'not_empty')
                                    ->rule('csrf', 'Security::check');

            // validamos
            if ($validacion->check() === false) {
                throw new ORM_Validation_Exception('', $validacion);
            }



            $producto = ORM::factory('producto', $datos_producto['id']);


            // asigna los valores al modelo
            $producto->values($datos_producto);

            // graba
            $producto->save();



            // ---------------------------------  TIPO DE PRODUCTO
            $producto_tipo = Arr::get($datos_producto, 'producto_tipo_id');
            $producto->remove('tipos');

            if (!empty($producto_tipo)) {
                if (is_array($producto_tipo)) {
                    foreach ($producto_tipo as $producto_tipo_id) {
                        $producto->add('tipos', $producto_tipo_id);
                    }
                } else {
                    $producto->add('tipos', $producto_tipo);
                }
            }


            // ---------------------------------  CATEGORIAS
            $put_categoria = Arr::get($put_crudo, 'categoria', null);

            if (!is_null($put_categoria)) {
                $categorias = Arr::get($put_categoria, 'id', null);

                $producto->remove('categorias');

                if (count($categorias) > 0) {
                    foreach ($categorias as $categoria_id) {
                        $producto->add('categorias', $categoria_id);
                    }
                }
            }



            // --------------------------------- CARACTERISTICAS
            $caracteristicas = Arr::get($put_crudo, 'caracteristicas', null);

            if (!is_null($caracteristicas)) {
                foreach ($caracteristicas as $caract_id => $caract_valor) {

                    // carga la categoria
                    $producto_tipo_caract = ORM::factory('Producto_Tipo_Caracteristica', $caract_id);


                    // buscamos si el producto tiene cargada la caracteristica
                    $caracteristica = $producto->caracteristica($caract_id);


                    // si el valor esta vacio y hay caracteristica, la borra
                    if (empty($caract_valor) and $caracteristica->loaded()) {
                        $caracteristica->delete();
                    } elseif (!empty($caract_valor)) {
                        $caracteristica->producto_id = $producto->id;
                        $caracteristica->producto_tipo_caracteristica_id = $caract_id;
                        $caracteristica->valor = $caract_valor;
                        $caracteristica->descripcion = $producto_tipo_caract->descripcion;
                        $caracteristica->save();
                    }
                }
            }



            $DB->commit();

            // respuesta
            $json->uri = '/'.$this->base_uri.'producto/editar/'.$producto->id;
            $json->mensaje = 'Grabado.';
        } catch (ORM_Validation_Exception $e) {
            $DB->rollback();

            // carga los errores de validacion
            $respuesta = $e->errors('backend/errors/categoria');

            $json->status(JSend::FAIL)
                ->data('errors', $respuesta);
        } catch (Exception $e) {
            $DB->rollback();

            $json->status(JSend::FAIL)
                ->data('errors', array( $e->getMessage() ));
        }


        unset($DB, $producto, $validacion, $put_crudo, $datos_producto, $producto_tipo);

        $json->render_into($this->response);
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


        $DB = Database::instance('default');

        $DB->begin();


        try {


            /**
             * Procesamos las fotos
             **/

/*
            $post_foto = Request::current()->post( 'foto' );


            // reglas de validacion para la categoria
            $validacion = Validation::factory( $post_foto )
                            ->rule( 'nombre', 'not_empty' );

            // validamos
            if( $validacion->check() === FALSE )
                throw new ORM_Validation_Exception( 'backend/errors/slide', $validacion );
*/


            /**
             * Generamos el grupo
             **/

            $grupo = ORM::factory('Grupo');

            $grupo->nombre = '** nuevo del ' . date('d-m-Y') . ' **';


            // graba
            $grupo->save();


            // asocia las fotos
            /*
            $post = Request::current()->post();

            if( !empty( $post['multimedia_id'] ) )
            {
                $slide->remove( 'multimedia' );
                $slide->add( 'multimedia', $post['multimedia_id'] );
            }
            */

            $DB->commit();


            // respuesta
            $json->uri = '/'.$this->base_uri.'grupo/editar/'.$grupo->id;
            $json->mensaje = 'Grabado.';
        } catch (ORM_Validation_Exception $e) {
            $DB->rollback();

            // carga los errores de validacion
            $respuesta = $e->errors('backend/model/grupo');

            $json->status(JSend::FAIL)
                ->data('errors', $respuesta);
        } catch (Exception $e) {
            $DB->rollback();

            $json->status(JSend::FAIL)
                ->data('errors', 'Error ['.$e->getMessage().']');
        }


        unset($DB, $grupo, $validacion, $post, $post_slide);

        $json->render_into($this->response);
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
        $id = Request::current()->param('id');


        $json = new JSend();


        $DB = Database::instance('default');

        $DB->begin();


        try {


            /**
             * Procesamos las fotos
             **/

            $put_repositorio = array();
            parse_str(Request::current()->body(), $put_repositorio);
            $put_repositorio = Arr::get($put_repositorio, 'foto');


            $productos = array();

            if (is_array($put_repositorio)) {
                $helper_repo = new Helper_Repositorio();

                foreach ($put_repositorio as $ruta) {
                    $archivo = new SplFileInfo($helper_repo::repositorio_usuario($ruta));

                    // genera una foto en base al archivo
                    $foto = ORM::factory('Foto');
                    $foto->genera_de_archivo($archivo);

                    // genera un producto en base a la foto
                    $producto = ORM::factory('Producto');
                    $producto->genera_de_foto($foto);

                    $productos[] = $producto;
                }

                unset($helper_repo);
            }



/*
            $post_foto = Request::current()->post( 'foto' );


            // reglas de validacion para la categoria
            $validacion = Validation::factory( $post_foto )
                            ->rule( 'nombre', 'not_empty' );

            // validamos
            if( $validacion->check() === FALSE )
                throw new ORM_Validation_Exception( 'backend/errors/slide', $validacion );
*/


            /**
             * Generamos el grupo
             **/

            $grupo = ORM::factory('Grupo', $id);


            foreach ($productos as $producto) {
                $grupo->add('productos', $producto->id);
            }


            $DB->commit();


            // respuesta
            $json->uri = '/'.$this->base_uri.'grupo/editar/'.$grupo->id;
            $json->mensaje = 'Grabado.';
        } catch (ORM_Validation_Exception $e) {
            $DB->rollback();

            // carga los errores de validacion
            $respuesta = $e->errors('backend/model/grupo');

            $json->status(JSend::FAIL)
                ->data('errors', $respuesta);
        } catch (Exception $e) {
            $DB->rollback();

            $json->status(JSend::FAIL)
                ->data('errors', 'Error ['.$e->getMessage().']');
        }


        unset($DB, $grupo, $validacion, $post, $post_slide);

        $json->render_into($this->response);
    }



    /**
     * Borra
     *
     * @author Marcos Calabrese <marcosc@tekar.net>
     * @version 20131011
     **/
    public function action_delete_borrar()
    {


        // busca el codigo pasado por parametro
        $id = Request::current()->param('id');


        $json = new JSend();


        // inicia transaccion
        $DB = Database::instance('default');
        $DB->begin();


        try {
            $obj = ORM::factory('Producto', $id);


            if ($obj->loaded() === false) {
                throw new Exception('El recurso no existe.');
            }


            // borra el contenido
            $obj->delete();

            $DB->commit();

            $json->mensaje = 'Borrado.';
            $json->uri = '/'.$this->base_uri.'producto/listar/';
        } catch (Exception $e) {
            $DB->rollback();

            $json->status(JSend::FAIL)
                ->data('errors', array( $e->getMessage() ));
        }

        unset($DB, $obj, $id);


        $json->render_into($this->response);
    }



    /**
     * Actualiza los estados de publicacion de los productos.
     *
     * @author Marcos Calabrese <marcosc@tekar.net>
     * @since 20131010
     * @version 20131010
     **/
    public function action_post_estados()
    {
        $id = Request::current()->post('id');
        $estado = Request::current()->post('estado');

        $json = new JSend();


        $DB = Database::instance('default');

        $DB->begin();


        try {
            if (count($id) > 0) {
                for ($i=0; $i < count($id); $i++) {
                    $producto = ORM::factory('Producto', $id[$i]);
                    $producto->estado_id = ((is_array($estado) and in_array($id[$i], $estado)) ? 1 : 0);
                    $producto->save();
                    unset($grupo);
                }
            }

            $DB->commit();

            $json->mensaje = 'Actualizado.';
        } catch (Exception $e) {
            $DB->rollback();

            $json->status(JSend::FAIL)
                ->data('errors', 'Error ['.$e->getMessage().']');
        }


        unset($DB, $publicados);

        $json->render_into($this->response);
    }
} // End
