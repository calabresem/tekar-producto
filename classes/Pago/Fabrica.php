<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Fabrica de metodo de pagos. Instancia el metodo pasado por parametro, si existe.
 *
 * @package tekar-producto
 * @author Marcos Calabrese <marcosc@tekar.net>
 * @since 20131023
 * @license {uri}
 */
class Pago_Fabrica {



    /**
     * Devuelve una instancia del metodo de pago.
     **/
    public static function instancia( $metodo )
    {

        // Define el nombre de la clase
        $clase = 'Pago_'.ucfirst( $metodo );

        return( new $clase() );

    }




}
