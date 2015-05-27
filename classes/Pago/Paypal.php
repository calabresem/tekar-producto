<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Controladora de Busqueda
 *
 * @author Marcos Calabrese <marcosc@tekar.net>
 **/
class Buscador_Codigo {


    /**
     *
     **/
    public $filtros;


    /**
     * Inicia. Devuelve los filtros de busqueda disponibles.
     *
     * @author Marcos Calabrese <marcosc@tekar.net>
     * @version 20130809
     **/
    public function __construct()
    {

        $this->filtros = new Buscador_Filtro();




    }


}

