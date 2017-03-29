<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Modelo de Orden de Compra
 *
 * @package tekar-producto
 * @author Marcos Calabrese <marcosc@tekar.net>
 * @since 20131015
 * @license http://openzula.org/license-bsd-3c BSD 3-Clause License
 */
abstract class Model_Core_Contacto extends ORM {

    protected $_primary_key = 'id';
    protected $_table_name = 'contacto';


    protected $_created_column = array('column' => 'creado', 'format' => TRUE);
    protected $_updated_column = array('column' => 'modificado', 'format' => TRUE);


    /**
     * Definicion de tablas manualmente. Evitamos lectura extra y podemos usar PDO.
     **/
    protected $_table_columns = array(
        'id' => NULL,
        'creado'     => NULL,
        'ip'   => NULL,
        'pagina_categoria_id'   => NULL,
        'email'   => NULL,
        'nombre'   => NULL,
        'pais'   => NULL,
        'mensaje'   => NULL,
        'direccion'   => NULL,
        'telefono'   => NULL,
        'movil'   => NULL,
        'otros_datos'   => NULL,
    );




    /**
     * Extiende el metodo original de guardado
     **/
    public function save( Validation $validation = NULL )
    {

        if (empty($this->pais)) {
            $this->pais = $this->obtiene_pais();
        }

        // guarda la IP, pero en formato numerico
        $this->ip = Helper_MyUrl::ip_long();

        parent::save($validation);

        return $this;
    }



    /**
     * Busca el pais del usuario.
     **/
    protected function obtiene_pais()
    {

        $file = "http://freegeoip.net/json/" . Helper_MyUrl::obtiene_ip();
        //$file_data = file_get_contents($file);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $file);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $file_data = curl_exec($ch);
        curl_close($ch);

        $json = json_decode( $file_data );
        return (empty($json->country_name)) ? 'N/A' : $json->country_name;

    }


}

