<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\ContactoController;

class TestController extends Controller
{
    public function index()
    {
        return 'Sin utilizar';
    }

    public function downloading(Request $request)
    {
        $clave_accs = $request->get('clave_accs');
        //$clave_accs = array("0110201901099000419600121150350003605870036058710", "0110201901179198472200125150030006028837139172115");

        $url = "https://cel.sri.gob.ec/comprobantes-electronicos-ws/AutorizacionComprobantesOffline?wsdl";

        $domNew = new \DOMDocument('1.0', 'ISO-8859-1');

        /* create the root element of the xml tree */
        $compras = $domNew->createElement("compras");
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
            // Parameters SOAP
            $user_param = array(
                'claveAccesoComprobante' => $clave_acc
            );
            //Request to server SRI
            $response = $client->autorizacionComprobante($user_param);

            if (!is_soap_fault($response) && $response->RespuestaAutorizacionComprobante->autorizaciones->autorizacion->estado === 'AUTORIZADO') {
                $this->rowcompra($domNew, $response->RespuestaAutorizacionComprobante->autorizaciones->autorizacion, $compras, $proveedors);
            } else {
                $response = $client->autorizacionComprobante($user_param);
                $this->rowcompra($domNew, $response->RespuestaAutorizacionComprobante->autorizaciones->autorizacion, $compras, $proveedors);
            }
        }

        $proveedors = json_encode($proveedors);
        $proveedors = json_decode($proveedors);
        (new ContactoController)->loadMasive($proveedors);

        $xml = str_replace('.', ',', $domNew->saveXML());
        $xml = str_replace('version="1,0"', 'version="1.0"', $xml);
        return base64_encode($xml);
    }

    private function rowcompra($domNew, $autorizacion, $compras, &$proveedors)
    {
        $noIvaR = 0;
        $b0R = 0;
        $b12R = 0;
        $ice = 0;
        $comprobante = $autorizacion->comprobante;

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
                case 6:
                    $noIvaR = round($impuesto->getElementsByTagName('baseImponible')->item(0)->textContent, 2);
                    break;
                default:
                    if ((int)($impuesto->getElementsByTagName('codigo')->item(0)->textContent) === 3) {
                        $ice = round($impuesto->getElementsByTagName('valor')->item(0)->textContent, 2);
                    }
                    break;
            }
        }

        /* create the compra element of the xml tree */
        $compra = $domNew->createElement("compra");
        /* append it to the document created */
        $compra = $compras->appendChild($compra);

        $cod = $domNew->createElement("cod", $dom->getElementsByTagName('tipoIdentificacionComprador')->item(0)->textContent == '05' ? 'XXX' : ''); //Codigo
        $cod = $compra->appendChild($cod);

        $rucc = $dom->getElementsByTagName('ruc')->item(0)->textContent;
        $ruc = $domNew->createElement("ruc", $rucc);
        $ruc = $compra->appendChild($ruc);    //ruc

        $rasons = $this->removeOtherCharacter($dom->getElementsByTagName('razonSocial')->item(0)->textContent);
        $rz = $domNew->createElement("rz", $rasons);
        $rz = $compra->appendChild($rz);    //Rason Social

        $codC = $domNew->createElement("codC"); //Codigo Cuenta
        $codC = $compra->appendChild($codC);

        $de = $domNew->createElement("de"); //Detalle Cuenta
        $de = $compra->appendChild($de);

        $codDoc = $dom->getElementsByTagName('codDoc')->item(0)->textContent;

        $tc = $domNew->createElement("tc", $codDoc == '01' ? 'F' : ($codDoc == '04' ? 'N/C' : ($codDoc == '05' ? 'N/D' : ''))); //Tipo Comprobante
        $tc = $compra->appendChild($tc);

        $f = $domNew->createElement("f", $dom->getElementsByTagName('fechaEmision')->item(0)->textContent);
        $f = $compra->appendChild($f);    //Fecha

        $estab = $domNew->createElement("estab", $dom->getElementsByTagName('estab')->item(0)->textContent);
        $estab = $compra->appendChild($estab);    //Establecimiento

        $ptoEmi = $domNew->createElement("ptoEmi", $dom->getElementsByTagName('ptoEmi')->item(0)->textContent);
        $ptoEmi = $compra->appendChild($ptoEmi);    //ptoEmi

        $sec = $domNew->createElement("sec", $dom->getElementsByTagName('secuencial')->item(0)->textContent);
        $sec = $compra->appendChild($sec);    //secuencial

        $aut = $domNew->createElement("aut", $autorizacion->numeroAutorizacion);
        $aut = $compra->appendChild($aut);    //numeroAutorizacion

        $noIva = $domNew->createElement("noIva", $noIvaR);
        $noIva = $compra->appendChild($noIva);

        $b0 = $domNew->createElement("b0", $b0R);
        $b0 = $compra->appendChild($b0);

        $b12 = $domNew->createElement("b12", $b12R);
        $b12 = $compra->appendChild($b12);

        $be = $domNew->createElement("be", 0);
        $be = $compra->appendChild($be);

        $mi = $domNew->createElement("mi", $ice);
        $mi = $compra->appendChild($mi);

        $genIva = round($b12R * .12, 2);
        $iva = $domNew->createElement("iva", $genIva);
        $iva = $compra->appendChild($iva);

        $tt = $domNew->createElement("tt", $b0R + $b12R + $genIva);
        $tt = $compra->appendChild($tt);
        //retenciones
        $r10 = $domNew->createElement("r10", 0);
        $r10 = $compra->appendChild($r10);
        $r20 = $domNew->createElement("r20", 0);
        $r20 = $compra->appendChild($r20);
        $r30 = $domNew->createElement("r30", 0);
        $r30 = $compra->appendChild($r30);
        $r50 = $domNew->createElement("r50", 0);
        $r50 = $compra->appendChild($r50);
        $r70 = $domNew->createElement("r70", 0);
        $r70 = $compra->appendChild($r70);
        $r100 = $domNew->createElement("r100", 0);
        $r100 = $compra->appendChild($r100);
        //Info retenciones
        $s1 = $domNew->createElement("s1");
        $s1 = $compra->appendChild($s1);
        $pe1 = $domNew->createElement("pe1");
        $pe1 = $compra->appendChild($pe1);
        $se1 = $domNew->createElement("se1");
        $se1 = $compra->appendChild($se1);
        $a1 = $domNew->createElement("a1");
        $a1 = $compra->appendChild($a1);
        //Retenciones
        $codr = $domNew->createElement("codr");
        $codr = $compra->appendChild($codr);
        $cret = $domNew->createElement("cret");
        $cret = $compra->appendChild($cret);
        $porr = $domNew->createElement("porr");
        $porr = $compra->appendChild($porr);
        $valr = $domNew->createElement("valr", 0);
        $valr = $compra->appendChild($valr);

        //Nota de credito o debito
        if ($codDoc == '04' || $codDoc == '05') {
            $esm = $domNew->createElement("esm", substr($dom->getElementsByTagName('numDocModificado')->item(0)->textContent, 0, 3));
            $esm = $compra->appendChild($esm);
            $ptom = $domNew->createElement("ptom", substr($dom->getElementsByTagName('numDocModificado')->item(0)->textContent, 4, 3));
            $ptom = $compra->appendChild($ptom);
            $secm = $domNew->createElement("secm", substr($dom->getElementsByTagName('numDocModificado')->item(0)->textContent, 8, 9));
            $secm = $compra->appendChild($secm);
        }

        //Load proveedors
        $found_key = array_search($rucc, array_column($proveedors, 'id'));
        if (!is_int($found_key)) {
            $proveedor = [
                'id' => $rucc,
                'denominacion' => $rasons,
                'tpId' => '01',
                'tpContacto' => null,
                'contabilidad' => $dom->getElementsByTagName('obligadoContabilidad')->length != 0 ? $dom->getElementsByTagName('obligadoContabilidad')->item(0)->textContent : null
            ];

            array_push($proveedors, $proveedor);
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
