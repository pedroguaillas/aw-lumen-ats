<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class SendToSriController extends Controller
{

    public function index(Request $request)
    {
        $params = array('xml' => $request->get('xml'));
        $url = 'https://celcer.sri.gob.ec/comprobantes-electronicos-ws/RecepcionComprobantesOffline?wsdl';
        $client = new \SoapClient($url);

        // Peticion al metodo expuesto
        $response = $client->__soapCall(
            "validarComprobante",
            array($params, 'exceptions' => 0)
        );

        if (!is_soap_fault($response)) {
            return response()->json(['result' => $response->RespuestaRecepcionComprobante->estado]);
        }

        return response()->json(['result' => 'Respuesta fallida']);
    }
}
