<?php

return array(

    'email' => array(
        'not_empty' => 'Falta el campo email',
        'unique' => 'El e-mail ya se encuentra registrado.',
    ),

    'username' => array(
        'unique' => '',
    ),


    'password' => array(
        'min_length' => 'La clave debe ser de por lo menos 6 caracteres',
    ),
    'password' => array(
        'matches' => 'Las claves no coinciden',
    ),
    'clave' => array(
        'min_length' => 'La clave debe ser de por lo menos 6 caracteres',
    ),
    'clave_confirmacion' => array(
        'matches' => 'Las claves no coinciden',
    ),

    'codigo' => array(
        'not_empty' => 'Falta el campo Codigo',
    ),

    'borrado' => array(
        'not_empty' => 'Borrado no confirmado',
    ),

);

