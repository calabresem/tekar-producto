<?php defined('SYSPATH') or die('No direct script access.');

/**
 * Clase para administrar la notificacion del TPV.
 *
 * @author Marcos Calabrese <marcosc@tekar.net>
 **/
class Pago_Tpv implements Interface_Metodo_Pago {


    /**
     * ID de la forma de pago
     **/
    public $id = 1;

    /**
     * Guarda el codigo de ORDEN
     **/
    public $compra_codigo;

    /**
     * Guarda el codigo de autorizacion dado por el TPV
     **/
    public $autorizacion;


    /**
     * Indica si la orden fue PAGADO o RECHAZADO.
     **/
    protected $pagado = FALSE;


    /**
     * Guarda el mensaje de la respuesta
     **/
    protected $mensaje = '';

    /**
     * Indica el codigo de respuesta del TPV
     **/
    protected $respuesta_codigo = '';



    /**
     * Procesar la informacion enviada. El array seguramente sea el POST del pedido.
     **/
    public function procesar( array $datos )
    {

        // validamos el input
        $validacion = $this->validador( $datos );


        // verificamos la forma de los datos
        if( $validacion->check() === FALSE )
        {
            //echo( Debug::vars( $validacion ) );
            throw new Exception( 'La informacion recibida por el sistema de pago no es consistente.' );
        }


        // verificamos la validez del contenido
        $firma = Arr::get( $datos, 'Ds_Signature', NULL );
        $this->compra_codigo = Arr::get( $datos, 'Ds_Order', NULL );
        $this->respuesta_codigo = Arr::get( $datos, 'Ds_Response', NULL );


        // buscamos los datos para recalcular la firma
        $parametros = array(
                'monto' => Arr::get( $datos, 'Ds_Amount', NULL ),
                'compra_codigo' => $this->compra_codigo,
                'moneda' => Arr::get( $datos, 'Ds_Currency', NULL ),
                'codigo_respuesta' => $this->respuesta_codigo
        );


        $firma_calculada = $this->arma_firma_para_recibido( $parametros );

        // compara la firma generada con la que nos devolvieron
        if( $firma_calculada != $firma )
        {
            throw new Exception( 'No hay integridad en los datos.' );
        }


        // guardamos el codigo de autorizacion
        $this->autorizacion = Arr::get( $datos, 'Ds_AuthorisationCode', NULL );


        $resultado = $this->respuesta_mensaje( $this->respuesta_codigo );


        return( $resultado );

    }


    /**
     * Informa si el metodo confirmo o rechazo la orden.
     * @return bool
     **/
    public function pagado()
    {
        return( $this->pagado );
    }


    /**
     * Informa si el metodo confirmo o rechazo la orden.
     * @return bool
     **/
    public function estado()
    {

    }



    /**
     * Devuelve el validador para la informacion enviada por el sistema de pago.
     * @return Validation
     **/
    protected static function validador( array $datos )
    {

        return Validation::factory( $datos )
                            ->rule( 'Ds_Date', 'not_empty' )
                            ->rule( 'Ds_Date', 'regex', array( ':value', '/^[0-9]{2}\/[0-9]{2}\/[0-9]{4}/' ) )
                            ->rule( 'Ds_Hour', 'regex', array( ':value', '/^[0-9]{2}\:[0-9]{2}/' ) )
                            ->rule( 'Ds_Amount', 'numeric' )
                            ->rule( 'Ds_Currency', 'numeric' )
                            ->rule( 'Ds_Order', 'regex', array( ':value', '/^[0-9a-z]{1,12}$/i' ) )
                            ->rule( 'Ds_MerchantCode', 'regex', array( ':value', '/^[0-9]{9}$/' ) )
                            ->rule( 'Ds_Terminal', 'numeric' )
                            ->rule( 'Ds_Signature', 'regex', array( ':value', '/^[0-9a-f]{40}$/i' ) )
                            ->rule( 'Ds_Response', 'regex', array( ':value', '/^[0-9]{3,4}$/' ) )
                            ->rule( 'Ds_MerchantData', 'regex', array( ':value', '/^[0-9a-z]{0,1024}$/i' ) )
                            ->rule( 'Ds_SecurePayment', 'numeric' )
                            ->rule( 'Ds_TransactionType', 'numeric' )
                            ->rule( 'Ds_Card_Country', 'regex', array( ':value', '/^[0-9]{3}$/' ) )
                            ->rule( 'Ds_AuthorisationCode', 'regex', array( ':value', '/^[0-9a-z]{6}$/i' ) )
                            ->rule( 'Ds_ConsumerLanguage', 'numeric' )
                            ->rule( 'Ds_Card_Type', 'regex', array( ':value', '/^[C|D]{1}$/i' ) );

    }


    /**
     * Genera un hash en base a datos del pedido:
     * SHA-1(Ds_ Amount + Ds_ Order + Ds_MerchantCode + Ds_ Currency + Ds _Response + CLAVE SECRETA)
     * @return bool
     **/
    public function firmar( $datos )
    {

        if( is_array( $datos ) )
        {
            $mensaje = implode( '', $datos );

        } else
        {
            $mensaje = $datos;
        }

        return( strtoupper( sha1( $mensaje ) ) );

    }


    /**
     * Genera un hash en base a datos del pedido:
     * @return bool
     **/
    public function arma_firma_para_enviar( array $parametros )
    {

        // formato: {monto}{pedido}{cliente}{moneda}{tipo_transaccion}{url}{clave}
        $firma_datos = array(
                0 => $parametros['monto'],
                1 => $parametros['compra_codigo'],
                2 => Kohana::$config->load( 'tekar-producto' )->pagos['tpv']['cliente'],
                3 => '978',                                     // moneda
                4 => '0',
                5 => Kohana::$config->load( 'tekar-producto' )->pagos['url_notificacion'],
                6 => Kohana::$config->load( 'tekar-producto' )->pagos['tpv']['clave']
        );

        return( $this->firmar( $firma_datos ) );

    }


    /**
     * Genera un hash en base a datos del pedido:
     * @return bool
     **/
    public function arma_firma_para_recibido( array $parametros )
    {

        // formato: {monto} + {nro_pedido} + {codigo_cliente} + {moneda} + {respuesta} + {clave}

        $firma_datos = array(
                0 => $parametros['monto'],
                1 => $parametros['compra_codigo'],
                2 => Kohana::$config->load( 'tekar-producto' )->pagos['tpv']['cliente'],
                3 => $parametros['moneda'],
                4 => $parametros['codigo_respuesta'],
                5 => Kohana::$config->load( 'tekar-producto' )->pagos['tpv']['clave']
        );

        return( $this->firmar( $firma_datos ) );

    }



    /**
     * Devuelve el mensaje asociado al codigo de respuesta
     * @return string
     **/
    public function respuesta_mensaje( $codigo )
    {
        $codigo = (int) $codigo;

        //Kohana::$log->add( Log::DEBUG, 'api/compra/notificacionpago: Codigo: ' . var_export( $codigo, true ) );

        switch( $codigo )
        {

            case 900:
                $this->mensaje = 'Transacción autorizada para devoluciones y confirmaciones';
                $this->pagado = TRUE;
                break;

            case 101:
                $this->mensaje = 'Tarjeta caducada';
                $this->pagado = FALSE;
                break;

            case 102:
                $this->mensaje = 'Tarjeta en excepción transitoria o bajo sospecha de fraude';
                $this->pagado = FALSE;
                break;

            case 104:
            case 9104:
                $this->mensaje = 'Operación no permitida para esa tarjeta o terminal';
                $this->pagado = FALSE;
                break;

            case 116:
                $this->mensaje = 'Disponible insuficiente';
                $this->pagado = FALSE;
                break;

            case 118:
                $this->mensaje = 'Tarjeta no registrada';
                $this->pagado = FALSE;
                break;

            case 129:
                $this->mensaje = 'Código de seguridad (CVV2/CVC2) incorrecto';
                $this->pagado = FALSE;
                break;

            case 180:
                $this->mensaje = 'Tarjeta ajena al servicio';
                $this->pagado = FALSE;
                break;

            case 184:
                $this->mensaje = 'Error en la autenticación del titular';
                $this->pagado = FALSE;
                break;

            case 190:
                $this->mensaje = 'Denegación sin especificar Motivo';
                $this->pagado = FALSE;
                break;

            case 191:
                $this->mensaje = 'Fecha de caducidad errónea';
                $this->pagado = FALSE;
                break;

            case 202:
                $this->mensaje = 'Tarjeta en excepción transitoria o bajo sospecha de fraude con retirada de tarjeta';
                $this->pagado = FALSE;
                break;

            case 912:
            case 9912;
                $this->mensaje = 'Emisor no disponible';
                $this->pagado = FALSE;
                break;

            case 0:
            case ( $codigo > 0 AND $codigo < 100 ):
                $this->mensaje = 'Transacción autorizada para pagos y preautorizaciones';
                $this->pagado = TRUE;
                break;


            default:
                $this->mensaje = 'Transacción denegada';
                $this->pagado = FALSE;
                break;

        }

        return( $this->pagado );
    }


    /**
     * Genera un codigo de orden con el formato requerido por el TPV:
     * Formato: [0-9]{4}[A-Za-z0-9]{8}
     **/
    public function genera_codigo_orden()
    {
        //return( rand( 1000, 9999 ) . Text::random( 'alpha', 8 ) );
        return( date('is') . Text::random( 'alpha', 8 ) );
    }


}

