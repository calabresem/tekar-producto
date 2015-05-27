<?php defined('SYSPATH') or die('No direct script access.');

return array(

    'tipo_categoria_id' => array(
        'not_empty' => 'Tipo de Categoria es requerido.',
        'numeric' => 'El codigo ya esta utilizado por otro producto y no pueden repetirse.',
    ),
    'descripcion' => array(
        'not_empty' => 'El nombre es requerido.',
    )
    'categoria_id' => array(
        'numeric' => 'El codigo ya esta utilizado por otro producto y no pueden repetirse.',
    ),
    'url' => array(
        'not_empty' => 'El codigo es requerido.',
        'alpha_dash' => 'El codigo ya esta utilizado por otro producto y no pueden repetirse.',
    ),
    'categoria_id' => array(
        'numeric' => 'El codigo ya esta utilizado por otro producto y no pueden repetirse.',
    ),

);
