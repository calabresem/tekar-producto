<?php defined('SYSPATH') or die('No direct script access.');

class Request extends Kohana_Request {


    /**
     * Devuelve el ->body ya parseado. Para usar en metodos: PUT y DELETE.
     *
     * @return  Request
     */
    public static function data()
    {
        $datos = array();
        parse_str( Request::current()->body(), $datos );
        return( $datos );
    }




}

