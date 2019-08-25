<?php

class ChilexpressOT
{

    public $ot;
    public $fecha;
    public $hora;
    public $rut;
    public $nombre;
    public $fecha_ts;
    public $tipo;


    /**
     * ChilexpressOT constructor.
     * @param $ot string, nº de orden de transporte
     */
    function __construct($ot)
    {

        $this->ot = $ot;
        $this->get_data();

    }

    /**
     * @param $ot string, nº de orden de transporte
     * @return array
     */
    private function get_data(){


        $html = self::get_main_html($this->ot);
        $pos = strpos($html, '<div class="titulo_seccion">Datos de entrega</div>');
        $tiene_datos_entrega = $pos !== FALSE;

        if($tiene_datos_entrega) {
            $res = self::parse_html($html);
            $this->fecha = $res['fecha'];
            $this->hora = $res['hora'];
            $this->rut = $res['rut'];
            $this->nombre = $res['nombre'];
            $this->fecha_ts = $res['fecha_ts'];
            $this->tipo = $res['tipo'];
        }

    }

    /**
     * @param $ot string, nº de orden de transporte
     * @return mixed
     */
    private static function get_main_html($ot){

        $curl = curl_init();
        curl_setopt_array($curl,
            array(
                CURLOPT_URL => 'http://www.chilexpress.cl/Views/ChilexpressCL/Resultado-busqueda.aspx?DATA=' . $ot,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FAILONERROR => true
            )
        );
        $html = curl_exec($curl);
        curl_close($curl);

        return $html;

    }


    /**
     * @param $html
     * @return array
     */
    private static function parse_html($html) {

        $results = array();


        $pos_start = strpos($html, '<div class="entrega_recibido datos_informacion">') + strlen('<div class="entrega_recibido datos_informacion">');
        $pos_end = strpos($html, '</div>', $pos_start);
        $results['nombre'] = substr($html, $pos_start, $pos_end - $pos_start);

        $pos_start = strpos($html, '<div class="entrega_rut datos_informacion">') + strlen('<div class="entrega_rut datos_informacion">');
        $pos_end = strpos($html, '</div>', $pos_start);
        $results['rut'] = substr($html, $pos_start, $pos_end - $pos_start);

        $pos_start = strpos($html, '<div class="entrega_fecha datos_informacion">') + strlen('<div class="entrega_fecha datos_informacion">');
        $pos_end = strpos($html, '</div>', $pos_start);
        $fecha_hora = substr($html, $pos_start, $pos_end - $pos_start);

        // separar fecha de hora. Está en formato 23/08/2019 - 12:00 hrs.
        $fecha_hora_parts = explode(' - ', $fecha_hora);
        $hora_parts = explode(' ', $fecha_hora_parts[1]);
        $results['fecha'] = $fecha_hora_parts[0];
        $results['hora'] = $hora_parts[0];

        $func = function ($valor) {
            $valor = str_replace('&nbsp;', '', $valor);
            $valor = trim($valor);
            return $valor;
        };
        $results = array_map($func, $results);

        // calcular fecha ts
        $fecha_ts = null;
        if ($results['fecha']) {
            $dt = \DateTime::createFromFormat('d/m/Y H:i:s', $results['fecha'] . ' ' . $results['hora'] . ':00');
            $fecha_ts = $dt->getTimestamp();

        }
        $results['fecha_ts'] = $fecha_ts;


        return $results;

    }

    
}
