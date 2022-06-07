<?php

namespace App\Http\Controllers;

use App\ClienteAuditwhole;
use App\StaticClasses\DBStatics;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AtsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $info = $request->get('info');
        $ruc = substr($info, 0, 13);
        $anio = substr($info, 13, 4);
        $mes = substr($info, 17);

        try {
            //ATS/---------------------
            $domtree = new \DOMDocument('1.0', 'ISO-8859-1');

            /* create the root element of the xml tree */
            $xmlRoot = $domtree->createElement("iva");
            /* append it to the document created */
            $xmlRoot = $domtree->appendChild($xmlRoot);

            //Informante/---------------
            $IdInformante = $domtree->createElement("IdInformante", $ruc);
            $IdInformante = $xmlRoot->appendChild($IdInformante);
            $Anio = $domtree->createElement("Anio", $anio);
            $Anio = $xmlRoot->appendChild($Anio);
            $Mes = $domtree->createElement("Mes", $mes);
            $Mes = $xmlRoot->appendChild($Mes);
            $numEstabRuc = $domtree->createElement("numEstabRuc", $request->get('establecimiento'));
            $numEstabRuc = $xmlRoot->appendChild($numEstabRuc);

            $db = null;
            switch ((int)$anio) {
                case 2021:
                    $db = DBStatics::DB21;
                    break;
                case 2022:
                    $db = DBStatics::DB22;
                    break;
                default:
                    $db = DBStatics::DB;
                    break;
            }
            // $file = DB::connection($anio < 2021 ? DBStatics::DB : DBStatics::DB21)
            $file = DB::connection($db)
                ->table('archivos')
                ->select('filecompra', 'fileventa', 'fileanulado')
                ->where([
                    'cliente_auditwhole_ruc' => $ruc,
                    'mes' => (int) $mes,
                    'anio' => $anio
                ])->get();

            // $file = Archivo::join('cliente_auditwholes', 'cliente_auditwholes.ruc', '=', 'archivos.cliente_auditwhole_ruc')
            //     ->select('archivos.filecompra', 'archivos.fileventa', 'archivos.fileanulado', 'cliente_auditwholes.razonsocial')
            //     ->where([
            //         'archivos.cliente_auditwhole_ruc' => $ruc,
            //         'archivos.mes' => (int) $mes,
            //         'archivos.anio' => $anio
            //     ])->get();

            if (count($file) > 0) {

                $file = $file[0];
                $empresa = ClienteAuditwhole::where('ruc', $ruc)->first()->razonsocial;

                $razonSocial = $domtree->createElement("razonSocial", $empresa);
                // $razonSocial = $domtree->createElement("razonSocial", $file->razonsocial);
                $razonSocial = $xmlRoot->appendChild($razonSocial);

                if ($file->filecompra != null) {
                    $this->insertcompras($file->filecompra);
                    $this->partFileCompras($domtree, $xmlRoot);
                    $deleted = DB::delete('DELETE FROM compras_temp');
                }

                if ($file->fileventa != null) {
                    // $this->insertventas2($file->fileventa, $domtree, $xmlRoot);
                    $this->insertventas($file->fileventa);
                    $this->partFileVentas($domtree, $xmlRoot);
                    $deleted = DB::delete('DELETE FROM ventas_temp');
                }

                if ($file->fileanulado != null) {

                    $fileanulado = base64_decode($file->fileanulado);

                    $document = new \DOMDocument();
                    $document->loadXML($fileanulado);

                    $fanulados =  $document->getElementsByTagName('anulado');

                    $anulados = $domtree->createElement("anulados");
                    $anulados = $xmlRoot->appendChild($anulados);

                    foreach ($fanulados as $fanulado) {

                        $detalleAnulados = $domtree->createElement("detalleAnulados");
                        $detalleAnulados = $anulados->appendChild($detalleAnulados);

                        $tipoComprobante = $domtree->createElement("tipoComprobante", $fanulado->getElementsByTagName('tipoComprobante')->item(0)->textContent);
                        $tipoComprobante = $detalleAnulados->appendChild($tipoComprobante);
                        $establecimiento = $domtree->createElement("establecimiento", $fanulado->getElementsByTagName('establecimiento')->item(0)->textContent);
                        $establecimiento = $detalleAnulados->appendChild($establecimiento);
                        $puntoEmision = $domtree->createElement("puntoEmision", $fanulado->getElementsByTagName('puntoEmision')->item(0)->textContent);
                        $puntoEmision = $detalleAnulados->appendChild($puntoEmision);
                        $secuencialInicio = $domtree->createElement("secuencialInicio", $fanulado->getElementsByTagName('secuencialInicio')->item(0)->textContent);
                        $secuencialInicio = $detalleAnulados->appendChild($secuencialInicio);
                        $secuencialFin = $domtree->createElement("secuencialFin", $fanulado->getElementsByTagName('secuencialFin')->item(0)->textContent);
                        $secuencialFin = $detalleAnulados->appendChild($secuencialFin);
                        $autorizacion = $domtree->createElement("autorizacion", $fanulado->getElementsByTagName('autorizacion')->item(0)->textContent);
                        $autorizacion = $detalleAnulados->appendChild($autorizacion);
                    }
                }
            } else {

                $clienteAuditwhole = ClienteAuditwhole::where('ruc', $ruc)->get();
                $clienteAuditwhole = $clienteAuditwhole[0];
                $razonSocial = $domtree->createElement("razonSocial", $clienteAuditwhole->razonsocial);
                $razonSocial = $xmlRoot->appendChild($razonSocial);

                $totalVentas = $domtree->createElement("totalVentas", 0);
                $totalVentas = $xmlRoot->appendChild($totalVentas);
            }

            return base64_encode($domtree->saveXML());
        } catch (\Exception $e) {
            return $e;
        }
    }

    function insertcompras($file)
    {
        $filecompra = base64_decode($file);
        $filecompra = str_replace(',', '.', $filecompra);
        $array = new \SimpleXMLElement($filecompra);
        //Start verify a insert providers
        $contats = array();
        foreach ($array->compra as $compra) {
            $found_key = array_search((string) $compra->RUC, array_column($contats, 'id'));
            if (!is_int($found_key) && strlen($compra->RUC) > 2 && strlen($compra->rs) > 2) {
                array_push($contats, [
                    'id' => (string) $compra->RUC,
                    'denominacion' => (string)$compra->rs,
                    'tpId' => '01',
                    'tpContacto' => null,
                    'contabilidad' => null
                ]);
            }
        }

        $contats = json_encode($contats);
        $contats = json_decode($contats);

        (new ContactoController)->loadMasive($contats);
        //End verify a insert providers

        $comprasa = array();
        foreach ($array->compra as $compra) {
            $tcv = (string) $compra->TCV;
            $base0 = (float) $compra->b0;
            $base12 = (float) $compra->b12;
            $codATS = (string) $compra->cda;
            $mi = (float) $compra->mi;
            $compraa = [
                'cod' => (string) $compra->cod,
                'RUC' => (string) $compra->RUC,
                'ccu' => (string) $compra->ccu,
                'TCV' => $tcv,
                'fec' => (string) $compra->fec,
                'Est' => (string) $compra->Est,
                'pe' => (string) $compra->pe,
                'sec' => (string) $compra->sec,
                'aut' => (string) $compra->aut,
                'bi' => $base0 + $base12 - $mi,
                'bni' => (float) $compra->bni,
                'b0' => $base0,
                'b12' => $base12,
                'be' => (float) $compra->be,
                'mi' => $mi,
                'miv' => (float) $compra->miv,
                'r10' => (float) $compra->r10,
                'r20' => (float) $compra->r20,
                'r30' => (float) $compra->r30,
                'r50' => (float) $compra->r50,
                'r70' => (float) $compra->r70,
                'r100' => (float) $compra->r100,

                //Factura o Liquidacion en compra
                'es1' => (string) $compra->es1,
                'pe1' => (string) $compra->pe1,
                'se1' => (string) $compra->se1,
                'au1' => (string) $compra->au1,

                //........................
                'cda' => $codATS,
                'por' => (float) $compra->por,
                'vra' => (float) $compra->vra,

                //Nota de credito o debito
                'em' => (string) $compra->em,
                'pem' => (string) $compra->pem,
                'sm' => (string) $compra->sm,
            ];

            array_push($comprasa, $compraa);
        }

        DB::table('compras_temp')->insert($comprasa);
    }

    function insertventas($file)
    {
        $fileventa = base64_decode($file);
        $fileventa = str_replace(',', '.', $fileventa);

        $array = new \SimpleXMLElement($fileventa);

        $ventasa = array();
        foreach ($array->venta as $venta) {

            $idCliente = (string) $venta->ruc;

            if ($idCliente != '') {
                $base0 = (float) $venta->b0;
                $base12 = (float) $venta->b12;
                $tcv = (string) $venta->TCV;
                $comprobante = (string) $venta->com;
                $ventaa = [
                    'ruc' => $idCliente,
                    'TCV' => $tcv,
                    'com' => ($tcv == 'F' || $tcv == 'N/C') && strlen($comprobante) > 2 ? substr($comprobante, 0, 3) : null,
                    'bi' => $base0 + $base12,
                    'b0' => $base0,
                    'b12' => $base12,
                    'mi' => (float) $venta->mi,
                    'miv' => (float) $venta->miv,
                    'vri' => (float) $venta->vri,
                    'vrr' => (float) $venta->vrr,
                ];

                array_push($ventasa, $ventaa);
            }
        }

        DB::table('ventas_temp')->insert($ventasa);
    }

    function partFileCompras($domtree, $xmlRoot)
    {
        $comprasf = DB::select('SELECT p.denominacion, p.tpId, p.tpContacto, cod, RUC, ccu, TCV, fec, Est, pe, sec, aut, bni, bi, b0, b12, be, mi, miv, r10, r20, r30, r50, r70, r100, es1, pe1, se1, au1, cda, por, vra, em, pem, sm, c.tst  FROM contactos AS p RIGHT JOIN compras_temp ON RUC = p.id LEFT JOIN cuentas AS c ON c.code=compras_temp.ccu ORDER BY sec, TCV');

        //Compras/---------------
        $compras = $domtree->createElement("compras");
        $compras = $xmlRoot->appendChild($compras);

        $cabecerasCompras = $domtree->createElement("cabecerasCompras");
        $cabecerasCompras = $compras->appendChild($cabecerasCompras);

        $formasDePago = $domtree->createElement("formasDePago");
        $formasDePago = $compras->appendChild($formasDePago);

        $retencionesCompras = $domtree->createElement("retencionesCompras");
        $retencionesCompras = $compras->appendChild($retencionesCompras);

        for ($i = 0; $i < count($comprasf); $i++) {

            $compra = $comprasf[$i];
            $cod = 'C' . ($i + 1);
            $cabeceraCompra = $domtree->createElement("cabeceraCompra");
            $cabeceraCompra = $cabecerasCompras->appendChild($cabeceraCompra);

            //$fecha = new \DateTime(str_replace('/', '-', $compra->fec), new \DateTimeZone('America/Guayaquil'));
            //$fecha = $fecha->format('d/m/Y');
            $fecha = $compra->fec;

            //--------------------
            $cabeceraCompra->appendChild($domtree->createElement("codCompra", $cod));

            $codSustento = $domtree->createElement("codSustento", $compra->TCV == 'N/V' ? '02' : $compra->tst);
            $codSustento = $cabeceraCompra->appendChild($codSustento);

            $tpId = $domtree->createElement("tpIdProv", $compra->tpId !== NULL ? $compra->tpId : (strlen($compra->tpId) === 13 ? '01' : (strlen($compra->tpId) === 10 ? '02' : '03'))); //Calcular o igual q cuentas
            $tpId = $cabeceraCompra->appendChild($tpId);

            $cabeceraCompra->appendChild($domtree->createElement("idProv", $compra->RUC));

            $tipoComprobante = $domtree->createElement("tipoComprobante", $compra->TCV == 'F' ? '01' : ($compra->TCV == 'N/V' ? '02' : ($compra->TCV == 'L/C' ? '03' : ($compra->TCV == 'N/C' ? '04' : ($compra->TCV == 'N/D' ? '05' : 0)))));
            $tipoComprobante = $cabeceraCompra->appendChild($tipoComprobante);

            $cabeceraCompra->appendChild($domtree->createElement("tipoProv", $compra->tpContacto));
            $cabeceraCompra->appendChild($domtree->createElement("denoProv", $compra->denominacion));
            $cabeceraCompra->appendChild($domtree->createElement("parteRel", $compra->tpId == 3 ? '' : 'NO'));
            $cabeceraCompra->appendChild($domtree->createElement("fechaRegistro", $fecha));
            $cabeceraCompra->appendChild($domtree->createElement("establecimiento", $compra->Est));
            $cabeceraCompra->appendChild($domtree->createElement("puntoEmision", $compra->pe));
            $cabeceraCompra->appendChild($domtree->createElement("secuencial", $compra->sec));
            $cabeceraCompra->appendChild($domtree->createElement("autorizacion", $compra->aut));
            $cabeceraCompra->appendChild($domtree->createElement("fechaEmision", $fecha));

            $bni = $compra->bni;
            $b0 = $compra->b0;
            $b12 = $compra->b12;
            $be = $compra->be;
            $mi = $compra->mi;
            $miv = $compra->miv;
            $r10 = $compra->r10;
            $r20 = $compra->r20;
            $r30 = $compra->r30;
            $r50 = $compra->r50;
            $r70 = $compra->r70;
            $r100 = $compra->r100;

            $j = $i + 1;

            while ($j < count($comprasf) && $compra->sec === $comprasf[$j]->sec && $compra->TCV === $comprasf[$j]->TCV && $compra->RUC === $comprasf[$j]->RUC) {
                $bni += $comprasf[$j]->bni;
                $b0 += $comprasf[$j]->b0;
                $b12 += $comprasf[$j]->b12;
                $be += $comprasf[$j]->be;
                $mi += $comprasf[$j]->mi;
                $miv += $comprasf[$j]->miv;
                $r10 += $comprasf[$j]->r10;
                $r20 += $comprasf[$j]->r20;
                $r30 += $comprasf[$j]->r30;
                $r50 += $comprasf[$j]->r50;
                $r70 += $comprasf[$j]->r70;
                $r100 += $comprasf[$j]->r100;
                $j++;
            }

            $cabeceraCompra->appendChild($domtree->createElement("baseNoGraIva", $bni));
            $cabeceraCompra->appendChild($domtree->createElement("baseImponible", $b0));
            $cabeceraCompra->appendChild($domtree->createElement("baseImpGrav", $b12));
            $cabeceraCompra->appendChild($domtree->createElement("baseImpExe", $be));
            $cabeceraCompra->appendChild($domtree->createElement("montoIce", $mi));
            $cabeceraCompra->appendChild($domtree->createElement("montoIva", $miv));
            $cabeceraCompra->appendChild($domtree->createElement("valRetBien10", $r10));
            $cabeceraCompra->appendChild($domtree->createElement("valRetServ20", $r20));
            $cabeceraCompra->appendChild($domtree->createElement("valorRetBienes", $r30));
            $cabeceraCompra->appendChild($domtree->createElement("valRetServ50", $r50));
            $cabeceraCompra->appendChild($domtree->createElement("valorRetServicios", $r70));
            $cabeceraCompra->appendChild($domtree->createElement("valRetServ100", $r100));
            $cabeceraCompra->appendChild($domtree->createElement("totBasesImpReemb")); //Todo multiplicado x 0 = 0

            $pagoExterior = $domtree->createElement("pagoExterior");
            $pagoExterior = $cabeceraCompra->appendChild($pagoExterior);

            //Pago Exterior
            $pagoLocExt = $domtree->createElement("pagoLocExt", $compra->tpId == 3 ? '02' : '01');
            $pagoLocExt = $pagoExterior->appendChild($pagoLocExt);
            // $tipoRegi = $domtree->createElement("tipoRegi", 'N/A');
            // $tipoRegi = $pagoExterior->appendChild($tipoRegi);
            // $paisEfecPagoGen = $domtree->createElement("paisEfecPagoGen", 'N/A');
            // $paisEfecPagoGen = $pagoExterior->appendChild($paisEfecPagoGen);
            // $paisEfecPagoParFis = $domtree->createElement("paisEfecPagoParFis", 'N/A');
            // $paisEfecPagoParFis = $pagoExterior->appendChild($paisEfecPagoParFis);
            // $denopagoRegFis = $domtree->createElement("denopagoRegFis", 'N/A');
            // $denopagoRegFis = $pagoExterior->appendChild($denopagoRegFis);
            $paisEfecPago = $domtree->createElement("paisEfecPago");
            $paisEfecPago = $pagoExterior->appendChild($paisEfecPago);
            $aplicConvDobTrib = $domtree->createElement("aplicConvDobTrib");
            $aplicConvDobTrib = $pagoExterior->appendChild($aplicConvDobTrib);
            $pagExtSujRetNorLeg = $domtree->createElement("pagExtSujRetNorLeg");
            $pagExtSujRetNorLeg = $pagoExterior->appendChild($pagExtSujRetNorLeg);

            //Retenciones
            // if ($compra->TCV == 'F' || $compra->TCV == 'L/C' || $compra->TCV == 'N/V') {
            if ($compra->cda !== '') {

                if (!is_int(strpos($compra->cda, '332'))) {
                    $cabeceraCompra->appendChild($domtree->createElement("estabRetencion1", $compra->es1));
                    $cabeceraCompra->appendChild($domtree->createElement("ptoEmiRetencion1", $compra->pe1));
                    $cabeceraCompra->appendChild($domtree->createElement("secRetencion1", $compra->se1));
                    $cabeceraCompra->appendChild($domtree->createElement("autRetencion1", $compra->au1));
                }

                $cabeceraCompra->appendChild($domtree->createElement("fechaEmiRet1", $fecha));

                //Retenciones compras
                $detalleAir = $domtree->createElement("detalleAir");
                $detalleAir = $retencionesCompras->appendChild($detalleAir);

                $detalleAir->appendChild($domtree->createElement("codCompra", $cod));
                $detalleAir->appendChild($domtree->createElement("codRetAir", $compra->cda));
                $detalleAir->appendChild($domtree->createElement("baseImpAir", $compra->bi));
                $detalleAir->appendChild($domtree->createElement("porcentajeAir", $compra->por));
                $detalleAir->appendChild($domtree->createElement("valRetAir", $compra->vra));

                $j = $i + 1;

                while ($j < count($comprasf) && $compra->sec === $comprasf[$j]->sec && $compra->TCV === $comprasf[$j]->TCV && $compra->RUC === $comprasf[$j]->RUC) {

                    $detalleAir = $domtree->createElement("detalleAir");
                    $detalleAir = $retencionesCompras->appendChild($detalleAir);

                    $detalleAir->appendChild($domtree->createElement("codCompra", $cod));
                    $detalleAir->appendChild($domtree->createElement("codRetAir", $comprasf[$j]->cda));
                    $detalleAir->appendChild($domtree->createElement("baseImpAir", $comprasf[$j]->bi));
                    $detalleAir->appendChild($domtree->createElement("porcentajeAir", $comprasf[$j]->por));
                    $detalleAir->appendChild($domtree->createElement("valRetAir", $comprasf[$j]->vra));
                    $j++;
                }
            }

            //Notas
            if ($compra->TCV == 'N/C' || $compra->TCV == 'N/D') {
                $cabeceraCompra->appendChild($domtree->createElement("docModificado", '01'));
                $cabeceraCompra->appendChild($domtree->createElement("estabModificado", $compra->em));
                $cabeceraCompra->appendChild($domtree->createElement("ptoEmiModificado", $compra->pem));
                $cabeceraCompra->appendChild($domtree->createElement("secModificado", $compra->sm));
                $cabeceraCompra->appendChild($domtree->createElement("autModificado", $compra->aut));
            }

            //Formas de pago
            $formaPago = $domtree->createElement("formaPago");
            $formaPago = $formasDePago->appendChild($formaPago);

            $formaPago->appendChild($domtree->createElement("codModulo", $cod));
            $forma = $domtree->createElement("forma", (($compra->bni > 0 ? $compra->bni : 0) + ($compra->b0 > 0 ? $compra->b0 : 0) + ($compra->b12 > 0 ? $compra->b12 : 0)) > 999.99 ? '20' : '01');
            $forma = $formaPago->appendChild($forma);

            $j = $i + 1;

            while ($j < count($comprasf) && $compra->sec === $comprasf[$j]->sec && $compra->TCV === $comprasf[$j]->TCV && $compra->RUC === $comprasf[$j]->RUC) {

                $formaPago = $domtree->createElement("formaPago");
                $formaPago = $formasDePago->appendChild($formaPago);

                $formaPago->appendChild($domtree->createElement("codModulo", $cod));
                $forma = $domtree->createElement("forma", (($comprasf[$j]->bni > 0 ? $comprasf[$j]->bni : 0) + ($comprasf[$j]->b0 > 0 ? $comprasf[$j]->b0 : 0) + ($comprasf[$j]->b12 > 0 ? $comprasf[$j]->b12 : 0)) > 999.99 ? '20' : '01');
                $forma = $formaPago->appendChild($forma);

                $j++;
                $i++;
            }
        }
    }

    function partFileVentas($domtree, $xmlRoot)
    {
        $ventasf = DB::select('SELECT ruc, TCV, COUNT(TCV) AS numeroComprobantes, SUM(b0) AS b0, SUM(b12) AS b12, SUM(miv) AS miv, SUM(mi) AS mi, SUM(vri) AS vri, SUM(vrr) AS vrr, c.tpId AS tic
        FROM ventas_temp LEFT JOIN contactos AS c ON c.id=ventas_temp.ruc
        GROUP BY TCV, c.tpId, ruc');

        //Compras/---------------
        $ventas = $domtree->createElement("ventas");
        $ventas = $xmlRoot->appendChild($ventas);

        $cont = 1;

        $formasDePago = $domtree->createElement("formasDePago");

        foreach ($ventasf as $venta) {
            $detalleVentas = $domtree->createElement("detalleVentas");
            $detalleVentas = $ventas->appendChild($detalleVentas);

            $codigo = 'V' . $cont;

            //--------------------
            $codVenta = $domtree->createElement("codVenta", $codigo);
            $codVenta = $detalleVentas->appendChild($codVenta);
            $tpIdCliente = $domtree->createElement("tpIdCliente", ((int) $venta->tic) === 0 ? (strlen($venta->ruc) == 10 ? '05' : '04') : (((int) $venta->tic) < 7 ? '0' . (3 + (int) $venta->tic) : $venta->tic));
            $tpIdCliente = $detalleVentas->appendChild($tpIdCliente);
            $idCliente = $domtree->createElement("idCliente", $venta->ruc);
            $idCliente = $detalleVentas->appendChild($idCliente);
            $parteRelVtas = $domtree->createElement("parteRelVtas", $venta->tic == '07' ? null : 'NO');
            $parteRelVtas = $detalleVentas->appendChild($parteRelVtas);
            $tipoCliente = $domtree->createElement("tipoCliente", $venta->tic == '03' ? '01' : null);
            $tipoCliente = $detalleVentas->appendChild($tipoCliente);
            $denoCli = $domtree->createElement("denoCli", $venta->tic == '03' ? 'CLIENTE EXTRAJERO' : null);
            $denoCli = $detalleVentas->appendChild($denoCli);
            $tipoComprobante = $domtree->createElement("tipoComprobante", $venta->TCV == 'F' ? '18' : ($venta->TCV == 'N/C' ? '04' : ''));
            $tipoComprobante = $detalleVentas->appendChild($tipoComprobante);
            $tipoEmision = $domtree->createElement("tipoEmision", 'F');
            $tipoEmision = $detalleVentas->appendChild($tipoEmision);
            $numeroComprobantes = $domtree->createElement("numeroComprobantes", $venta->numeroComprobantes);
            $numeroComprobantes = $detalleVentas->appendChild($numeroComprobantes);
            $baseNoGraIva = $domtree->createElement("baseNoGraIva");
            $baseNoGraIva = $detalleVentas->appendChild($baseNoGraIva);
            $baseImponible = $domtree->createElement("baseImponible", $venta->b0);
            $baseImponible = $detalleVentas->appendChild($baseImponible);
            $baseImpGrav = $domtree->createElement("baseImpGrav", $venta->b12);
            $baseImpGrav = $detalleVentas->appendChild($baseImpGrav);
            $montoIva = $domtree->createElement("montoIva", $venta->miv);
            $montoIva = $detalleVentas->appendChild($montoIva);
            $montoIce = $domtree->createElement("montoIce", $venta->mi);
            $montoIce = $detalleVentas->appendChild($montoIce);
            $valorRetIva = $domtree->createElement("valorRetIva", $venta->vri);
            $valorRetIva = $detalleVentas->appendChild($valorRetIva);
            $valorRetRenta = $domtree->createElement("valorRetRenta", $venta->vrr);
            $valorRetRenta = $detalleVentas->appendChild($valorRetRenta);

            //Formas de pago
            if ($venta->TCV === 'F') {
                $formaPago = $domtree->createElement("formaPago");
                $formaPago = $formasDePago->appendChild($formaPago);

                $codModulo = $domtree->createElement("codModulo", $codigo);
                $codModulo = $formaPago->appendChild($codModulo);
                $forma = $domtree->createElement("forma", '01');
                $forma = $formaPago->appendChild($forma);
            }

            if ($cont == sizeof($ventasf)) {
                $formasDePago = $ventas->appendChild($formasDePago);
            }
            $cont++;
        }

        $ventasEstablecimiento = $domtree->createElement("ventasEstablecimiento");
        $ventasEstablecimiento = $xmlRoot->appendChild($ventasEstablecimiento);

        $ventasf = DB::select("SELECT com, SUM(CASE WHEN TCV LIKE 'F' THEN bi ELSE bi * (-1) END) AS bi FROM ventas_temp WHERE bi > 0 GROUP BY com");

        $sumVentas = 0;
        $orden = 1;
        if (count($ventasf) > 0) {
            foreach ($ventasf as $venta) {
                if ($orden != (int) $venta->com) {
                    $auxmax = (int) $venta->com;
                    for (; $orden <= $auxmax; $orden++) {
                        //Si existe
                        if ($orden == $auxmax) {

                            $ventaEst = $domtree->createElement("ventaEst");
                            $ventaEst = $ventasEstablecimiento->appendChild($ventaEst);

                            $codEstab = $domtree->createElement("codEstab", $venta->com);
                            $codEstab = $ventaEst->appendChild($codEstab);
                            $ventasEstab = $domtree->createElement("ventasEstab", $venta->bi);
                            $ventasEstab = $ventaEst->appendChild($ventasEstab);
                            $ivaComp = $domtree->createElement("ivaComp", 0);
                            $ivaComp = $ventaEst->appendChild($ivaComp);
                        }
                        //No existe completar
                        else {
                            $ventaEst = $domtree->createElement("ventaEst");
                            $ventaEst = $ventasEstablecimiento->appendChild($ventaEst);

                            $codEstab = $domtree->createElement("codEstab", str_pad($orden, 3, 0, STR_PAD_LEFT));
                            $codEstab = $ventaEst->appendChild($codEstab);
                            $ventasEstab = $domtree->createElement("ventasEstab", 0);
                            $ventasEstab = $ventaEst->appendChild($ventasEstab);
                            $ivaComp = $domtree->createElement("ivaComp", 0);
                            $ivaComp = $ventaEst->appendChild($ivaComp);
                        }
                    }
                }
                //Si existe
                else {
                    $ventaEst = $domtree->createElement("ventaEst");
                    $ventaEst = $ventasEstablecimiento->appendChild($ventaEst);

                    $codEstab = $domtree->createElement("codEstab", $venta->com);
                    $codEstab = $ventaEst->appendChild($codEstab);
                    $ventasEstab = $domtree->createElement("ventasEstab", $venta->bi);
                    $ventasEstab = $ventaEst->appendChild($ventasEstab);
                    $ivaComp = $domtree->createElement("ivaComp", 0);
                    $ivaComp = $ventaEst->appendChild($ivaComp);
                    $orden++;
                }
                $sumVentas += $venta->bi;
            }
        } else {
            $ventaEst = $domtree->createElement("ventaEst");
            $ventaEst = $ventasEstablecimiento->appendChild($ventaEst);

            $codEstab = $domtree->createElement("codEstab", '001');
            $codEstab = $ventaEst->appendChild($codEstab);
            $ventasEstab = $domtree->createElement("ventasEstab", 0);
            $ventasEstab = $ventaEst->appendChild($ventasEstab);
            $ivaComp = $domtree->createElement("ivaComp", 0);
            $ivaComp = $ventaEst->appendChild($ivaComp);
        }

        $totalVentas = $domtree->createElement("totalVentas", $sumVentas);
        $totalVentas = $xmlRoot->appendChild($totalVentas);
    }


    function insertventas2($file, $domtree, $xmlRoot)
    {
        $fileventa = base64_decode($file);
        $fileventa = str_replace(',', '.', $fileventa);

        $array = new \SimpleXMLElement($fileventa);

        $ventasa = array();
        foreach ($array->venta as $venta) {

            $idCliente = (string) $venta->ruc;

            if ($idCliente != '') {
                $base0 = (float) $venta->b0;
                $base12 = (float) $venta->b12;
                $tcv = (string) $venta->TCV;
                $comprobante = (string) $venta->com;
                $ventaa = [
                    'ruc' => trim($idCliente),
                    'TCV' => trim($tcv),
                    'establecimiento' => ($tcv == 'F' || $tcv == 'N/C') && strlen($comprobante) > 2 ? substr($comprobante, 0, 3) : null,
                    'bi' => $base0 + $base12,
                    'b0' => $base0,
                    'b12' => $base12,
                    'mi' => (float) $venta->mi,
                    'miv' => (float) $venta->miv,
                    'vri' => (float) $venta->vri,
                    'vrr' => (float) $venta->vrr
                ];

                array_push($ventasa, $ventaa);
            }
        }

        $ventasa = json_decode(json_encode($ventasa));

        usort($ventasa, $this->object_sorter('TCV'));
        usort($ventasa, $this->object_sorter('ruc'));

        //Ventas/---------------
        $ventas = $domtree->createElement("ventas");
        $ventas = $xmlRoot->appendChild($ventas);

        $formasDePago = $domtree->createElement("formasDePago");

        for ($i = 0; $i < count($ventasa);) {
            $detalleVentas = $domtree->createElement("detalleVentas");
            $detalleVentas = $ventas->appendChild($detalleVentas);

            $venta = $ventasa[$i];
            $codigo = 'V' . $i;

            $tic = strlen($venta->ruc) === 10 ? '05' : (strlen($venta->ruc) === 13 ? ($venta->ruc === '9999999999999' ? '07' : '04') : '06');

            //--------------------
            $codVenta = $domtree->createElement("codVenta", $codigo);
            $codVenta = $detalleVentas->appendChild($codVenta);
            $tpIdCliente = $domtree->createElement("tpIdCliente", $tic);
            $tpIdCliente = $detalleVentas->appendChild($tpIdCliente);
            $idCliente = $domtree->createElement("idCliente", $venta->ruc);
            $idCliente = $detalleVentas->appendChild($idCliente);
            $parteRelVtas = $domtree->createElement("parteRelVtas", $tic == '07' ? null : 'NO');
            $parteRelVtas = $detalleVentas->appendChild($parteRelVtas);
            $tipoCliente = $domtree->createElement("tipoCliente", $tic == '06' ? '01' : null);
            $tipoCliente = $detalleVentas->appendChild($tipoCliente);
            $denoCli = $domtree->createElement("denoCli", $tic == '06' ? 'CLIENTE EXTRAJERO' : null);
            $denoCli = $detalleVentas->appendChild($denoCli);
            $tipoComprobante = $domtree->createElement("tipoComprobante", $venta->TCV == 'F' ? '18' : ($venta->TCV == 'N/C' ? '04' : ''));
            $tipoComprobante = $detalleVentas->appendChild($tipoComprobante);
            $tipoEmision = $domtree->createElement("tipoEmision", 'F');
            $tipoEmision = $detalleVentas->appendChild($tipoEmision);

            $j = $i + 1;

            $numeroComprobantes = 1;
            $b0 = $venta->b0;
            $b12 = $venta->b12;
            $iva = $venta->miv;
            $ice = $venta->mi;
            // Valor retenido en iva y renta
            $vri = $venta->vri;
            $vrr = $venta->vrr;

            while ($j < count($ventasa) && $venta->ruc === $ventasa[$j]->ruc && $venta->TCV === $ventasa[$j]->TCV) {
                $numeroComprobantes++;
                $b0 += $ventasa[$j]->b0;
                $b12 += $ventasa[$j]->b12;
                $iva += $ventasa[$j]->miv;
                $ice += $ventasa[$j]->mi;
                // Valor retenido en iva y renta
                $vri += $ventasa[$j]->vri;
                $vrr += $ventasa[$j]->vrr;
                $j++;
            }

            $numeroComprobantes = $domtree->createElement("numeroComprobantes", $numeroComprobantes);
            $numeroComprobantes = $detalleVentas->appendChild($numeroComprobantes);
            $baseNoGraIva = $domtree->createElement("baseNoGraIva");
            $baseNoGraIva = $detalleVentas->appendChild($baseNoGraIva);
            $baseImponible = $domtree->createElement("baseImponible", $b0);
            $baseImponible = $detalleVentas->appendChild($baseImponible);
            $baseImpGrav = $domtree->createElement("baseImpGrav", $b12);
            $baseImpGrav = $detalleVentas->appendChild($baseImpGrav);
            $montoIva = $domtree->createElement("montoIva", $iva);
            $montoIva = $detalleVentas->appendChild($montoIva);
            $montoIce = $domtree->createElement("montoIce", $ice);
            $montoIce = $detalleVentas->appendChild($montoIce);
            $valorRetIva = $domtree->createElement("valorRetIva", $vri);
            $valorRetIva = $detalleVentas->appendChild($valorRetIva);
            $valorRetRenta = $domtree->createElement("valorRetRenta", $vrr);
            $valorRetRenta = $detalleVentas->appendChild($valorRetRenta);

            //Formas de pago
            if ($venta->TCV === 'F') {
                $formaPago = $domtree->createElement("formaPago");
                $formaPago = $formasDePago->appendChild($formaPago);

                $codModulo = $domtree->createElement("codModulo", $codigo);
                $codModulo = $formaPago->appendChild($codModulo);
                $forma = $domtree->createElement("forma", '01');
                $forma = $formaPago->appendChild($forma);
            }

            $i = $j;
            if ($i == sizeof($ventasa)) {
                $formasDePago = $ventas->appendChild($formasDePago);
            }
        }

        // Ordenar por establecimiento
        usort($ventasa, $this->object_sorter('establecimiento'));

        // Array para guardar las bases imponibles por establicimientos
        $establecimientos = [];

        // Agrupar y sumar las base imponibles por establecimientos
        for ($i = 0; $i < count($ventasa);) {

            // Suma las bases imponibles de un establecimiento
            $sum = $ventasa[$i]->bi * ($ventasa[$i]->TCV === 'F' ? 1 : -1);

            $j = $i + 1;

            while ($j < count($ventasa) && $ventasa[$i]->establecimiento === $ventasa[$j]->establecimiento) {
                $sum += $ventasa[$j]->bi * ($ventasa[$j]->TCV === 'F' ? 1 : -1);
                $j++;
            }

            array_push($establecimientos, [
                'establecimiento' => $ventasa[$i]->establecimiento,
                'bi' => $sum
            ]);

            $i = $j;
        }

        $ventasEstablecimiento = $domtree->createElement("ventasEstablecimiento");
        $ventasEstablecimiento = $xmlRoot->appendChild($ventasEstablecimiento);

        $sumVentas = 0;
        $orden = 1;

        $establecimientos = json_decode(json_encode($establecimientos));

        if (count($establecimientos) > 0) {
            foreach ($establecimientos as $venta) {
                if ($orden != (int) $venta->establecimiento) {
                    $auxmax = (int) $venta->establecimiento;
                    for (; $orden <= $auxmax; $orden++) {

                        $ventaEst = $domtree->createElement("ventaEst");
                        $ventaEst = $ventasEstablecimiento->appendChild($ventaEst);

                        //Si existe
                        if ($orden == $auxmax) {
                            $codEstab = $domtree->createElement("codEstab", $venta->establecimiento);
                            $ventasEstab = $domtree->createElement("ventasEstab", $venta->bi);
                        }
                        //No existe completar
                        else {
                            $codEstab = $domtree->createElement("codEstab", str_pad($orden, 3, 0, STR_PAD_LEFT));
                            $ventasEstab = $domtree->createElement("ventasEstab", 0);
                        }

                        $codEstab = $ventaEst->appendChild($codEstab);
                        $ventasEstab = $ventaEst->appendChild($ventasEstab);
                        $ivaComp = $domtree->createElement("ivaComp", 0);
                        $ivaComp = $ventaEst->appendChild($ivaComp);
                    }
                }
                //Si existe
                else {
                    $ventaEst = $domtree->createElement("ventaEst");
                    $ventaEst = $ventasEstablecimiento->appendChild($ventaEst);

                    $codEstab = $domtree->createElement("codEstab", $venta->establecimiento);
                    $codEstab = $ventaEst->appendChild($codEstab);
                    $ventasEstab = $domtree->createElement("ventasEstab", $venta->bi);
                    $ventasEstab = $ventaEst->appendChild($ventasEstab);
                    $ivaComp = $domtree->createElement("ivaComp", 0);
                    $ivaComp = $ventaEst->appendChild($ivaComp);
                    $orden++;
                }
                $sumVentas += $venta->bi;
            }
        } else {
            $ventaEst = $domtree->createElement("ventaEst");
            $ventaEst = $ventasEstablecimiento->appendChild($ventaEst);

            $codEstab = $domtree->createElement("codEstab", '001');
            $codEstab = $ventaEst->appendChild($codEstab);
            $ventasEstab = $domtree->createElement("ventasEstab", 0);
            $ventasEstab = $ventaEst->appendChild($ventasEstab);
            $ivaComp = $domtree->createElement("ivaComp", 0);
            $ivaComp = $ventaEst->appendChild($ivaComp);
        }

        $totalVentas = $domtree->createElement("totalVentas", $sumVentas);
        $totalVentas = $xmlRoot->appendChild($totalVentas);
    }

    // Ordena array de objetos por un atributo
    function object_sorter($clave, $orden = null)
    {
        return function ($a, $b) use ($clave, $orden) {
            $result =  ($orden == "DESC") ? strnatcmp($b->$clave, $a->$clave) :  strnatcmp($a->$clave, $b->$clave);
            return $result;
        };
    }
}
