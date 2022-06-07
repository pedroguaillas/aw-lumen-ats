<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DownloadZip extends Controller
{
    public function downloading(Request $request)
    {
        $clave_accs = $request->get('clave_accs');
        $ruc = $request->get('ruc');
        //$clave_accs = array("0110201901099000419600121150350003605870036058710", "0110201901179198472200125150030006028837139172115");

        $url = "https://cel.sri.gob.ec/comprobantes-electronicos-ws/AutorizacionComprobantesOffline?wsdl";

        ini_set("default_socket_timeout", 120);

        $client = new \SoapClient(
            $url,
            array(
                "soap_version" => SOAP_1_1,
                // trace used for __getLastResponse return result in XML
                "trace" => 1,
                'connection_timeout' => 3,
                // exceptions used for detect error in SOAP is_soap_fault
                'exceptions' => 0
            )
        );

        $zip = new \ZipArchive();
        $filename = './' . $ruc . '.zip';

        if ($zip->open($filename, \ZipArchive::CREATE) !== TRUE) {
            exit("cannot open <$filename>\n");
        }

        foreach ($clave_accs as $clave_acc) {

            // Parameters SOAP
            $user_param = array(
                'claveAccesoComprobante' => $clave_acc
            );

            //Request to server SRI
            $response = $client->autorizacionComprobante($user_param);

            if (!is_soap_fault($response) && $response->RespuestaAutorizacionComprobante->autorizaciones->autorizacion->estado === 'AUTORIZADO') {
                $this->loadInZip($zip, $response->RespuestaAutorizacionComprobante->autorizaciones->autorizacion);
            } else {
                //Request to server SRI
                $response = $client->autorizacionComprobante($user_param);
                $this->loadInZip($zip, $response->RespuestaAutorizacionComprobante->autorizaciones->autorizacion);
            }
        }
        // $file = base64_encode($zip);
        // return $file;

        $zip->close();
        $fp = fopen($filename, "rb");
        $binary = fread($fp, filesize($filename));
        unlink($filename);
        return base64_encode($binary);
    }

    private function loadInZip($zip, $comprobante)
    {
        $dom = new \DOMDocument('1.0', 'ISO-8859-1');

        $autorizacion = $dom->createElement('autorizacion');
        $dom->appendChild($autorizacion);

        $estado = $dom->createElement('estado', $comprobante->estado);
        $autorizacion->appendChild($estado);

        $auth = $dom->createElement('numeroAutorizacion', $comprobante->numeroAutorizacion);
        $autorizacion->appendChild($auth);

        $fechaAutorizacion = $dom->createElement('fechaAutorizacion', $comprobante->fechaAutorizacion);
        $autorizacion->appendChild($fechaAutorizacion);

        $ambiente = $dom->createElement('ambiente', $comprobante->ambiente);
        $autorizacion->appendChild($ambiente);

        $elementocomprobante = $dom->createElement('comprobante');
        $autorizacion->appendChild($elementocomprobante);

        // Use createCDATASection() function to create a new cdata node 
        $domElement = $dom->createCDATASection($comprobante->comprobante);

        // Append element in the document 
        $elementocomprobante->appendChild($domElement);

        $zip->addFromString($comprobante->numeroAutorizacion . ".xml", $dom->saveXML());
    }
}
