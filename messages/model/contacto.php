<?php defined('SYSPATH') or die('No direct script access.');

return array(

    'nombre' => array(
        'not_empty' => 'Falta tu nombre.',
    ),
    'telefono' => array(
        'not_empty' => 'Falta tu telefono.',
    ),
    'ubicacion' => array(
        'not_empty' => 'Falta tu ubicación.',
    ),
    'email' => array(
        'not_empty' => 'Falta tu email.',
        'email' => 'Revisa el email, parece invalido.',
    ),
    'consumo' => array(
        'not_empty' => 'No nos indicaste tu nivel de consumo.',
    ),
    'condicion' => array(
        'not_empty' => 'Falta tu condicion impositiva.',
    ),
    'actividad' => array(
        'not_empty' => 'Falta tu actividad.',
    ),

);
