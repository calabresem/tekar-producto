<?php defined('SYSPATH') or die('No direct script access.');

return array(

    'codigo' => array(
        'not_empty' => 'El codigo es requerido.',
        'Model_Core_Producto::codigo_unico' => 'El codigo ya esta utilizado por otro producto y no pueden repetirse.',
    ),
    'nombre' => array(
        'not_empty' => 'El nombre es requerido.',
    ),
    'principal_foto_id' => array(
        'not_empty' => 'La foto principal es requerido.',
    ),

);
