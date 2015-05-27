<?php defined('SYSPATH') or die('No direct script access.');

return array(

    'nombre' => array(
        'not_empty' => 'Falta el nombre.',
    ),
    'email' => array(
        'not_empty' => 'Falta el e-mail.',
        'email' => 'E-mail inválido.',
    )

);
