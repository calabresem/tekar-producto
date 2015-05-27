<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Modelo de Foto
 *
 * @package tekar-producto
 * @author Marcos Calabrese <marcosc@tekar.net>
 * @license http://openzula.org/license-bsd-3c BSD 3-Clause License
 */
abstract class Model_Core_Foto extends ORM {


    protected $_primary_key = 'id';
    protected $_table_name = 'foto';

    protected $_created_column = array( 'column' => 'creado', 'format' => TRUE );


    /**
     * Definicion de tablas manualmente. Evitamos lectura extra y podemos usar PDO.
     **/
    protected $_table_columns = array(
        'id' => NULL,
        'fecha_hora'     => NULL,
        'orden'   => NULL,
        'extension'   => NULL,
        'tipo'   => NULL,
        'peso'   => NULL,
        'codigo'   => NULL,
        'nombre_original' => NULL,
        'creado'   => NULL,
        'resolucion'   => NULL,
    );


    /**
     * Relaciones
     **/
    protected $_has_many = array(
        'productos' => array(
            'model'   => 'Producto',
            'through' => 'producto_foto',
            'foreign_key' => 'producto_id',
            'far_key' => 'foto_id',
        ),
    );




    /**
     * Extiende la accion de borrado
     *
     * @author Marcos Calabrese <marcosc@tekar.net>
     * @since 2013-01-03
     **/
    public function delete()
    {

        if( $this->loaded() === FALSE )
            throw new Exception( 'No se pudo borrar la foto.' );


        // borra las asignaciones a productos
        //$this->remove( 'productos' );


        // borra el archivo
        $this->borra_archivos_fisicos();


        // ahora si borra
        return( parent::delete() );

    }


    /**
     * Devuelve la ruta absoluta base del repositorio de fotos.
     *
     * @author Marcos Calabrese <marcosc@tekar.net>
     * @version 20131011
     *
     * @return string
     **/
    public function ruta_absoluta_base()
    {
        throw new Exception( 'Definir.' );
    }


    /**
     * Devuelve la ruta absoluta a la foto.
     *
     * @author Marcos Calabrese <marcosc@tekar.net>
     * @version 20131011
     *
     * @return string
     **/
    public function ruta_absoluta()
    {
        throw new Exception( 'Definir.' );
    }



    /**
     * Devuelve la ruta a la imagen para el tag <IMG/>
     *
     * @author Marcos Calabrese <marcosc@tekar.net>
     * @since 20130110
     * @return string
     **/
    public function rutaImageFly()
    {

        // primero vemos a que galeria pertenece
        $galeria = ORM::factory( 'Galeria', $this->galeria );

        return( $galeria->rutaImageFly().$this->imagen );

    }



    /**
     * Borra archivos fisicos
     *
     * @author Marcos Calabrese <marcosc@tekar.net>
     * @since 20131115
     * @return bool
     **/
    public function borra_archivos_fisicos()
    {
        $ruta_archivo = $this->ruta_absoluta();
        if( is_file( $ruta_archivo ) )
        {
            return( unlink( $ruta_archivo ) );
        }
        return true;
    }


    /**
     * Devuelve los datos
     *
     * @author Marcos Calabrese <marcosc@tekar.net>
     * @version 20131015
     *
     * @param string [campo] El campo a buscar. Si no se pasa, devuelve el array entero.
     * @return mixed
     **/
    public function obtiene_meta_info( $archivo=NULL, $campo=NULL )
    {
        $datos = exif_read_data( !is_null( $archivo ) ? $archivo : $this->ruta_absoluta_foto() );
        $valor = isset( $datos[$campo] ) ? $datos[$campo] : '';
        return( is_null( $campo ) ? $datos : $valor );
    }


    /**
     * Genera una Foto en base a un archivo fisico de imagen.
     *
     * @author Marcos Calabrese <marcosc@tekar.net>
     * @since 20130110
     * @return string
     **/
    public function genera_de_archivo( SplFileInfo $archivo, $usa_meta=FALSE )
    {

        // genera la foto
        $this->orden = 0;
        $this->extension = $archivo->getExtension();
        $this->tipo = null;
        $this->nombre_original = $archivo->getBasename( '.'.$archivo->getExtension() );
        $this->codigo = $archivo->getBasename( '.'.$archivo->getExtension() );


        // busca meta-informacion de la imagen
        if( $usa_meta === TRUE )
        {

            $fecha_hora = $this->obtiene_meta_info( $archivo->getPathname(), 'DateTime' );
            if( !empty( $fecha_hora ) )
            {
                $fecha_hora = new DateTime( $fecha_hora );
                $this->fecha_hora = $fecha_hora->format( 'Y-m-d H:i:s' );

            }

            unset( $fecha_hora );

            // meta: resolucion original
            // 3208x2984 / 27.2cm x 25.3cm (300dpi)
            #dpi = pixels / size
            $img_ancho = $this->obtiene_meta_info( $archivo->getPathname(), 'ExifImageWidth' );
            $img_alto = $this->obtiene_meta_info( $archivo->getPathname(), 'ExifImageLength' );
            $img_resolucion_x = $this->obtiene_meta_info( $archivo->getPathname(), 'XResolution' );
            $img_resolucion_y = $this->obtiene_meta_info( $archivo->getPathname(), 'YResolution' );
            $this->resolucion = $img_ancho.'x'.$img_alto . 'px (' . substr( $img_resolucion_x, 0, strpos( $img_resolucion_x, '/' ) ) . 'dpi)';

        }


        // fuerza fecha y hora
        if( empty( $this->fecha_hora ) )
        {
            $this->fecha_hora = date( 'Y-m-d H:i:s' );
        }


        // tamaño
        try
        {
            $this->peso = $archivo->getSize();
        }
        catch( \RuntimeException $e )
        {
            //$this->peso = filesize( $archivo->getPathname() );
            $this->peso = 0;
        }


        // graba
        $this->save();


        // ahora recargamos para obtener los datos frescos: ID y creado.
        //$this->reload();
        //$this->codigo = $this->archivo();
        //$this->save();


        return( $this );

    }



}

