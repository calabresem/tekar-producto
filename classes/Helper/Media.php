<?php defined('SYSPATH') or die('No direct script access.');

class Helper_Media extends HTML {


    /**
    * Reforma el HTML
    *
    * @author Marcos Calabrese <marcosc@tekar.net>
    */
    public static function rearmarMultimedia( $fhtml, $aParams=null )
    {

        //Levanta la cantidad de imagenes [-imagen16-]
        //$vcantimg=substr_count(strtolower($fhtml), '[-imagen');

        // busca imagenes
        $aImagenes = array();
        preg_match_all( '/\[\-imagen(\d+)\-\]/', $fhtml, $aImagenes, PREG_PATTERN_ORDER );

        // busca archivos
        $aArchivos = array();
        preg_match_all( '/\[\-archivo(\d+)\-\]/', $fhtml, $aArchivos, PREG_PATTERN_ORDER );

        // Levanta la cantidad de links [-link5-]
        $vcantlink = substr_count(strtolower($fhtml), '[-link');



        // ----------------------------------
        //Recorre todas las imagenes
        if( count( $aImagenes[0] ) > 0 )
        {
            for( $i=0; $i < count( $aImagenes[0] ); $i++ )
            {

                // buscamos la imagen que se esta buscando
                $multimedia = ORM::factory( 'Multimedia' )
                                ->where( 'id', '=', $aImagenes[1][$i] )
                                ->find();

                $sImg = '';
                if( $multimedia->loaded() === true and !empty( $multimedia->ruta ) )
                {
                    $sParam = '';
                    $aOps = array();
                    $aImg = array( 'alt' => '' );

                    if( !empty( $aParams['w'] ) )
                    {
                        $aOps[] = 'w' . $aParams['w'];
                        $aImg['width'] = $aParams['w'];
                    }
                    if( !empty( $aParams['h'] ) )
                    {
                        $aOps[] = 'h' . $aParams['h'];
                        $aImg['height'] = $aParams['h'];
                    }
                    if( !empty( $aParams['id'] ) )
                    {
                        $aImg['id'] = $aParams['id'];
                    }

                    $aOps[] = 'c';

                    if( count( $aOps ) > 1 )
                    {
                        $sParam = 'imagefly/' . implode( '-', $aOps ) . '/';
                    }

                    // arma el tag de imagen
                    $sImg = HTML::image( $sParam . '/recursos/' . $multimedia->ruta, $aImg );

                    unset( $aImg );
                }

                // reemplaza x el tag
                $fhtml = str_replace( $aImagenes[0][$i], $sImg, $fhtml );

                unset( $multimedia );

            }
        }

        unset( $aImagenes );


        // ----------------------------------
        // Recorre todos los archivos
        if( count( $aArchivos[0] ) > 0 )
        {

            for( $i=0; $i < count( $aArchivos[0] ); $i++ )
            {

                // buscamos la imagen que se esta buscando
                $multimedia = ORM::factory( 'Multimedia' )
                                ->where( 'id', '=', $aArchivos[1][$i] )
                                ->find();

                $sArchivo = '';
                if( $multimedia->loaded() === true and !empty( $multimedia->ruta ) )
                {
                    $sArchivo = HTML::anchor( 'media/img/recursos/' . $multimedia->ruta, $multimedia->nombre, array( 'target' => '_blank' ) );
                }

                // reemplaza x el link correcto
                $fhtml = str_replace( $aArchivos[0][$i], $sArchivo, $fhtml );

                unset( $multimedia );

            }

        }

        unset( $aArchivos );


        // ----------------------------------
        //Recorre todos los links
        $posi=0;
        for($j=0;$j<$vcantlink;$j++)
        {
            $posi = strpos($fhtml, "[-link");
            $posf = strpos($fhtml, "-]",$posi);
            $codint = substr($fhtml,$posi+6,$posf-$posi-6);
            $linkcambio = "";

            //Trae el url de la imagen
            if(is_numeric($codint))
            {
                $sqlfile="select nombre, link, nuevaventana from links where codigo = $codint";
                $rfile = mysql_query($sqlfile, $fconn);
                if($rsfile = mysql_fetch_array($rfile))
                {
                    $tnuevaventana = "";
                    $linkcambio = $rsfile["link"];
                    $fnombrelink = $rsfile["nombre"];
                    $nuevaventana = $rsfile["nuevaventana"];
                    if($nuevaventana==1)$tnuevaventana = "target='_blank'";
                }
            }
            if($linkcambio!="")
            {
                $fhtml = str_replace("[-link".$codint."-]","<a href='".$linkcambio."' ".$tnuevaventana.">".$fnombrelink."</a>",$fhtml);
            } else {
                $fhtml = str_replace("[-link".$codint."-]","",$fhtml);
            }
        }

        //$fhtml = str_replace(chr(13),"<br>",stripslashes($fhtml));
        return($fhtml);
    }



    /**
    * Reforma el HTML
    *
    * @author Marcos Calabrese <marcosc@tekar.net>
    */
    public static function obtieneImagenesMultimedia( $fhtml )
    {

        $matches = array();
        preg_match_all( '/\[\-imagen(\d+)\-\]/', $fhtml, $matches, PREG_PATTERN_ORDER );


        if( count( $matches[0] ) > 0 )
        {
            return( $matches[0] );
        } else
        {
            return( false );
        }

    }


    /**
     * Indica si el archivo es una imagen.
     *
     * @author Marcos Calabrese <marcosc@tekar.net>
     * @param string [path] La ruta al archivo
     * @return boolean
     */
    public static function is_image( $path )
    {
        if( empty( $path ) )
            return( FALSE );

        $a = getimagesize($path);
        $image_type = $a[2];

        if( in_array( $image_type , array( IMAGETYPE_GIF , IMAGETYPE_JPEG ,IMAGETYPE_PNG , IMAGETYPE_BMP ) ) )
        {
            return true;
        }

        return false;
    }






}
