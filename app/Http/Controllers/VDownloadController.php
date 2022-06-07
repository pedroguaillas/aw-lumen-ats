<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\ContactoController;

class VDownloadController extends Controller
{
    public function index()
    {
        return 'Sin utilizar';
    }

    public function downloading(Request $request)
    {
        $clave_accs = $request->get('clave_accs');
        //$clave_accs = array("0110201901099000419600121150350003605870036058710", "0110201901069000051200120010410016534824929197819");

        $url = "https://cel.sri.gob.ec/comprobantes-electronicos-ws/AutorizacionComprobantesOffline?wsdl";

        $domNew = new \DOMDocument('1.0', 'ISO-8859-1');

        /* create the root element of the xml tree */
        $compras = $domNew->createElement("ventas");
        /* append it to the document created */
        $compras = $domNew->appendChild($compras);

        ini_set("default_socket_timeout", 120);
        $client = new \SoapClient(
            $url,
            array(
                "soap_version" => SOAP_1_1,
                'connection_timeout' => 3,
                // exceptions used for detect error in SOAP is_soap_fault
                'exceptions' => 0
            )
        );

        $proveedors = array();

        foreach ($clave_accs as $clave_acc) {
            // Parametros SOAP
            $user_param = array(
                'claveAccesoComprobante' => $clave_acc
            );

            // Peticion al metodo expuesto
            $response = $client->autorizacionComprobante($user_param);

            if (!is_soap_fault($response) && $response->RespuestaAutorizacionComprobante->autorizaciones->autorizacion->estado === 'AUTORIZADO') {
                $this->rowcompra($domNew, $response->RespuestaAutorizacionComprobante->autorizaciones->autorizacion->comprobante, $compras, $proveedors);
            } else {
                $response = $client->autorizacionComprobante($user_param);
                $this->rowcompra($domNew, $response->RespuestaAutorizacionComprobante->autorizaciones->autorizacion->comprobante, $compras, $proveedors);
            }
        }

        $proveedors = json_encode($proveedors);
        $proveedors = json_decode($proveedors);
        (new ContactoController)->loadMasive($proveedors);

        $xml = str_replace('.', ',', $domNew->saveXML());
        $xml = str_replace('version="1,0"', 'version="1.0"', $xml);
        return base64_encode($xml);
    }

    private function rowcompra($domNew, $comprobante, $compras, &$proveedors)
    {
        $b0R = 0;
        $b12R = 0;

        $dom = new \DOMDocument();
        $dom->loadXML($comprobante);

        $impuestos = $dom->getElementsByTagName('totalImpuesto');

        foreach ($impuestos as $impuesto) {
            switch ((int) $impuesto->getElementsByTagName('codigoPorcentaje')->item(0)->textContent) {
                case 0:
                    $b0R = round($impuesto->getElementsByTagName('baseImponible')->item(0)->textContent, 2);
                    break;
                case 2:
                    $b12R = round($impuesto->getElementsByTagName('baseImponible')->item(0)->textContent, 2);
                    break;
                case 3:
                    $b12R = round($impuesto->getElementsByTagName('baseImponible')->item(0)->textContent, 2);
                    break;
            }
        }

        /* create the venta element of the xml tree */
        $compra = $domNew->createElement("venta");
        /* append it to the document created */
        $compra = $compras->appendChild($compra);

        $tpIdc = $dom->getElementsByTagName('tipoIdentificacionComprador')->item(0)->textContent;

        $rucc = $dom->getElementsByTagName('identificacionComprador')->item(0)->textContent;
        $ruc = $domNew->createElement("ruc", $rucc);
        $ruc = $compra->appendChild($ruc);    //ruc

        $rasons = $tpIdc == '06' ? $this->removeOtherCharacter($dom->getElementsByTagName('razonSocialComprador')->item(0)->textContent) : null;

        $rz = $domNew->createElement("rz", $rasons);
        $rz = $compra->appendChild($rz);    //Rason Social

        $tpId = $domNew->createElement("tpId", $tpIdc);
        $tpId = $compra->appendChild($tpId);    //tpId

        $f = $domNew->createElement("f", $dom->getElementsByTagName('fechaEmision')->item(0)->textContent);
        $f = $compra->appendChild($f);    //Fecha

        $codDoc = $dom->getElementsByTagName('codDoc')->item(0)->textContent;

        $tc = $domNew->createElement("tc", $codDoc == '01' ? 'F' : ($codDoc == '04' ? 'N/C' : '')); //Tipo Comprobante
        $tc = $compra->appendChild($tc);

        $serial = $domNew->createElement("serial", $dom->getElementsByTagName('estab')->item(0)->textContent . '-' . $dom->getElementsByTagName('ptoEmi')->item(0)->textContent . '-' . $dom->getElementsByTagName('secuencial')->item(0)->textContent);
        $serial = $compra->appendChild($serial);    //secuencial

        $bi = $domNew->createElement("bi", $b0R + $b12R);
        $bi = $compra->appendChild($bi);

        $b0 = $domNew->createElement("b0", $b0R);
        $b0 = $compra->appendChild($b0);

        $b12 = $domNew->createElement("b12", $b12R);
        $b12 = $compra->appendChild($b12);

        $mi = $domNew->createElement("mi", 0);
        $mi = $compra->appendChild($mi);

        $genIva = round($b12R * .12, 2);
        $iva = $domNew->createElement("iva", $genIva);
        $iva = $compra->appendChild($iva);

        $tt = $domNew->createElement("tt", $b0R + $b12R + $genIva);
        $tt = $compra->appendChild($tt);
        //porcentaje
        $pr = $domNew->createElement("pr", 0);
        $pr = $compra->appendChild($pr);
        $pi = $domNew->createElement("pi", 0);
        $pi = $compra->appendChild($pi);
        //retenciones
        $vrr = $domNew->createElement("vrr", $dom->getElementsByTagName('valorRetRenta')->length != 0 ? round($dom->getElementsByTagName('valorRetRenta')->item(0)->textContent, 2) : 0);
        $vrr = $compra->appendChild($vrr);
        $vri = $domNew->createElement("vri", $dom->getElementsByTagName('valorRetIva')->length != 0 ? round($dom->getElementsByTagName('valorRetIva')->item(0)->textContent, 2) : 0);
        $vri = $compra->appendChild($vri);
        //campo extra para codigo de retencion

        //Load proveedors
        if ($tpIdc == '06') {
            $found_key = array_search($rucc, array_column($proveedors, 'id'));
            if (!is_int($found_key)) {
                $proveedor = [
                    'id' => $rucc,
                    'denominacion' => $rasons,
                    'tpId' => '03',
                    'tpContacto' => null,
                    'contabilidad' => null
                ];

                array_push($proveedors, $proveedor);
            }
        }
    }

    private function removeOtherCharacter($deno)
    {
        $permit = array("á", "é", "í", "ó", "ú", "ñ", "&");
        $replace = array("a", "e", "i", "o", "u", "n", "y");
        $deno = str_replace($permit, $replace, $deno);

        $permit = array("Á", "É", "Í", "Ó", "Ú", "Ñ", "&");
        $deno = str_replace($permit, $replace, $deno);

        $deno = strtoupper($deno);

        $count  = strlen($deno);
        $newc = str_split($deno);

        for ($i = 0; $i < $count; $i++) {
            if (($newc[$i] < 'A' || $newc[$i] > 'Z') && $newc[$i] != ' ') {
                $deno = str_replace($newc[$i], '', $deno);
            }
        }

        return $deno;
    }
}
