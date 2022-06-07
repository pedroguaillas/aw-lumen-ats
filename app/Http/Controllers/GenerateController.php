<?php

namespace App\Http\Controllers;

use App\Archivo;
use App\ClienteAuditwhole;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GenerateController extends Controller
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
            $xmlRoot->appendChild($domtree->createElement("TipoIDInformante", 'R'));
            $xmlRoot->appendChild($domtree->createElement("IdInformante", $ruc));

            $file = Archivo::join('cliente_auditwholes', 'cliente_auditwholes.ruc', '=', 'archivos.cliente_auditwhole_ruc')
                ->select('archivos.filecompra', 'archivos.fileventa', 'archivos.fileanulado', 'cliente_auditwholes.razonsocial')
                ->where([
                    'archivos.cliente_auditwhole_ruc' => $ruc,
                    'archivos.mes' => (int) $mes,
                    'archivos.anio' => $anio
                ])->get();
            if (count($file) > 0) {

                $file = $file[0];

                $xmlRoot->appendChild($domtree->createElement("razonSocial", $file->razonsocial));
                $xmlRoot->appendChild($domtree->createElement("Anio", $anio));
                $xmlRoot->appendChild($domtree->createElement("Mes", $mes));
                $xmlRoot->appendChild($domtree->createElement("numEstabRuc", $request->get('establecimiento')));
                $xmlRoot->appendChild($domtree->createElement("codigoOperativo", 'IVA'));

                $totalVentas = $domtree->createElement("totalVentas", number_format(103970.23, 2, '.', ''));
                $totalVentas = $xmlRoot->appendChild($totalVentas);

                if ($file->filecompra != null) {
                    $this->insertcompras($file->filecompra);
                    $this->partFileCompras($domtree, $xmlRoot);
                    $deleted = DB::delete('DELETE FROM compras_temp');
                }

                if ($file->fileventa != null) {
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

        // $array = json_encode($array);
        // $array = json_decode($array, true);

        // // Sort by serie
        // usort($array, function ($epos1, $epos2) {
        //     return $epos1->sec - $epos2->sec;
        // });

        // // Sort by voucher type
        // usort($array, function ($epos1, $epos2) {
        //     return $epos1['TCV'] - $epos2['TCV'];
        // });

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
        $comprasf = DB::select('SELECT p.denominacion, p.tpId, p.tpContacto, cod, RUC, ccu, TCV, fec, Est, pe, sec, aut, bni, bi, b0, b12, be, mi, miv, r10, r20, r30, r50, r70, r100, es1, pe1, se1, au1, cda, por, vra, em, pem, sm, c.tst  FROM contactos AS p RIGHT JOIN compras_temp ON RUC = p.id LEFT JOIN cuentas AS c ON c.code=compras_temp.ccu');

        //Compras/---------------
        $compras = $domtree->createElement("compras");
        $compras = $xmlRoot->appendChild($compras);

        for ($i = 0; $i < count($comprasf); $i++) {

            $compra = $comprasf[$i];

            $detalleCompras = $domtree->createElement("detalleCompras");
            $detalleCompras = $compras->appendChild($detalleCompras);

            $codSustento = $domtree->createElement("codSustento", $compra->TCV == 'N/V' ? '02' : $compra->tst);
            $codSustento = $detalleCompras->appendChild($codSustento);

            $tpId = $domtree->createElement("tpIdProv", $compra->tpId !== NULL ? $compra->tpId : (strlen($compra->tpId) === 13 ? '01' : (strlen($compra->tpId) === 10 ? '02' : '03'))); //Calcular o igual q cuentas
            $tpId = $detalleCompras->appendChild($tpId);

            $detalleCompras->appendChild($domtree->createElement("idProv", $compra->RUC));

            $tipoComprobante = $domtree->createElement("tipoComprobante", $compra->TCV == 'F' ? '01' : ($compra->TCV == 'N/V' ? '02' : ($compra->TCV == 'L/C' ? '03' : ($compra->TCV == 'N/C' ? '04' : ($compra->TCV == 'N/D' ? '05' : 0)))));
            $tipoComprobante = $detalleCompras->appendChild($tipoComprobante);

            $detalleCompras->appendChild($domtree->createElement("tipoProv", $compra->tpContacto));
            $detalleCompras->appendChild($domtree->createElement("denoProv", $compra->denominacion));
            $detalleCompras->appendChild($domtree->createElement("parteRel", $compra->tpId == 3 ? '' : 'NO'));
            $detalleCompras->appendChild($domtree->createElement("fechaRegistro", $compra->fec));
            $detalleCompras->appendChild($domtree->createElement("establecimiento", $compra->Est));
            $detalleCompras->appendChild($domtree->createElement("puntoEmision", $compra->pe));
            $detalleCompras->appendChild($domtree->createElement("secuencial", $compra->sec));
            $detalleCompras->appendChild($domtree->createElement("fechaEmision", $compra->fec));
            $detalleCompras->appendChild($domtree->createElement("autorizacion", $compra->aut));

            if ($i < count($comprasf) - 1 && $compra->sec === $comprasf[$i + 1]->sec && $compra->TCV === $comprasf[$i + 1]->TCV) {
                $detalleCompras->appendChild($domtree->createElement("baseNoGraIva", number_format($compra->bni + $comprasf[$i + 1]->bni, 2, '.', '')));
                $detalleCompras->appendChild($domtree->createElement("baseImponible", number_format($compra->b0 + $comprasf[$i + 1]->b0, 2, '.', '')));
                $detalleCompras->appendChild($domtree->createElement("baseImpGrav", number_format($compra->b12 + $comprasf[$i + 1]->b12, 2, '.', '')));
                $detalleCompras->appendChild($domtree->createElement("baseImpExe", number_format($compra->be + $comprasf[$i + 1]->be, 2, '.', '')));
                $detalleCompras->appendChild($domtree->createElement("montoIce", number_format($compra->mi + $comprasf[$i + 1]->mi, 2, '.', '')));
                $detalleCompras->appendChild($domtree->createElement("montoIva", number_format($compra->miv + $comprasf[$i + 1]->miv, 2, '.', '')));
                $detalleCompras->appendChild($domtree->createElement("valRetBien10", number_format($compra->r10 + $comprasf[$i + 1]->r10, 2, '.', '')));
                $detalleCompras->appendChild($domtree->createElement("valRetServ20", number_format($compra->r20 + $comprasf[$i + 1]->r20, 2, '.', '')));
                $detalleCompras->appendChild($domtree->createElement("valorRetBienes", number_format($compra->r30 + $comprasf[$i + 1]->r30, 2, '.', '')));
                $detalleCompras->appendChild($domtree->createElement("valRetServ50", number_format($compra->r50 + $comprasf[$i + 1]->r50, 2, '.', '')));
                $detalleCompras->appendChild($domtree->createElement("valorRetServicios", number_format($compra->r70 + $comprasf[$i + 1]->r70, 2, '.', '')));
                $detalleCompras->appendChild($domtree->createElement("valRetServ100", number_format($compra->r100 + $comprasf[$i + 1]->r100, 2, '.', '')));

                //Todo multiplicado x 0 = 0
                $detalleCompras->appendChild($domtree->createElement("totBasesImpReemb", number_format(0.0, 2, '.', '')));
            } else {
                $detalleCompras->appendChild($domtree->createElement("baseNoGraIva", number_format($compra->bni, 2, '.', '')));
                $detalleCompras->appendChild($domtree->createElement("baseImponible", number_format($compra->b0, 2, '.', '')));
                $detalleCompras->appendChild($domtree->createElement("baseImpGrav", number_format($compra->b12, 2, '.', '')));
                $detalleCompras->appendChild($domtree->createElement("baseImpExe", number_format($compra->be, 2, '.', '')));
                $detalleCompras->appendChild($domtree->createElement("montoIce", number_format($compra->mi, 2, '.', '')));
                $detalleCompras->appendChild($domtree->createElement("montoIva", number_format($compra->miv, 2, '.', '')));
                $detalleCompras->appendChild($domtree->createElement("valRetBien10", number_format($compra->r10, 2, '.', '')));
                $detalleCompras->appendChild($domtree->createElement("valRetServ20", number_format($compra->r20, 2, '.', '')));
                $detalleCompras->appendChild($domtree->createElement("valorRetBienes", number_format($compra->r30, 2, '.', '')));
                $detalleCompras->appendChild($domtree->createElement("valRetServ50", number_format($compra->r50, 2, '.', '')));
                $detalleCompras->appendChild($domtree->createElement("valorRetServicios", number_format($compra->r70, 2, '.', '')));
                $detalleCompras->appendChild($domtree->createElement("valRetServ100", number_format($compra->r100, 2, '.', '')));

                //Todo multiplicado x 0 = 0
                $detalleCompras->appendChild($domtree->createElement("totBasesImpReemb", number_format(0, 2, '.', '')));
            }

            $pagoExterior = $domtree->createElement("pagoExterior");
            $pagoExterior = $detalleCompras->appendChild($pagoExterior);

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
            $paisEfecPago = $domtree->createElement("paisEfecPago", 'NA');
            $paisEfecPago = $pagoExterior->appendChild($paisEfecPago);
            $aplicConvDobTrib = $domtree->createElement("aplicConvDobTrib", 'NA');
            $aplicConvDobTrib = $pagoExterior->appendChild($aplicConvDobTrib);
            $pagExtSujRetNorLeg = $domtree->createElement("pagExtSujRetNorLeg", 'NA');
            $pagExtSujRetNorLeg = $pagoExterior->appendChild($pagExtSujRetNorLeg);

            //Formas de pago
            $formasDePago = $domtree->createElement("formasDePago");
            $formasDePago = $detalleCompras->appendChild($formasDePago);

            $formaPago = $domtree->createElement("formaPago", (($compra->bni > 0 ? $compra->bni : 0) + ($compra->b0 > 0 ? $compra->b0 : 0) + ($compra->b12 > 0 ? $compra->b12 : 0)) > 999.99 ? '20' : '01');
            $formaPago = $formasDePago->appendChild($formaPago);

            if ($i < count($comprasf) - 1 && $compra->sec === $comprasf[$i + 1]->sec && $compra->TCV === $comprasf[$i + 1]->TCV) {
                $formaPago = $domtree->createElement("formaPago", (($comprasf[$i + 1]->bni > 0 ? $comprasf[$i + 1]->bni : 0) + ($comprasf[$i + 1]->b0 > 0 ? $comprasf[$i + 1]->b0 : 0) + ($comprasf[$i + 1]->b12 > 0 ? $comprasf[$i + 1]->b12 : 0)) > 999.99 ? '20' : '01');
                $formaPago = $formasDePago->appendChild($formaPago);
            }

            //Retenciones
            $air = $domtree->createElement("air");
            $air = $detalleCompras->appendChild($air);
            if ($compra->cda !== '') {

                if (!is_int(strpos($compra->cda, '332'))) {
                    $estabRetencion1 = $domtree->createElement("estabRetencion1", $compra->es1);
                    $estabRetencion1 = $detalleCompras->appendChild($estabRetencion1);
                    $ptoEmiRetencion1 = $domtree->createElement("ptoEmiRetencion1", $compra->pe1);
                    $ptoEmiRetencion1 = $detalleCompras->appendChild($ptoEmiRetencion1);
                    $secRetencion1 = $domtree->createElement("secRetencion1", $compra->se1);
                    $secRetencion1 = $detalleCompras->appendChild($secRetencion1);
                    $autRetencion1 = $domtree->createElement("autRetencion1", $compra->au1);
                    $autRetencion1 = $detalleCompras->appendChild($autRetencion1);
                }

                $fechaEmiRet1 = $domtree->createElement("fechaEmiRet1", $compra->fec);
                $fechaEmiRet1 = $detalleCompras->appendChild($fechaEmiRet1);

                //Retenciones compras
                $detalleAir = $domtree->createElement("detalleAir");
                $detalleAir = $air->appendChild($detalleAir);

                $detalleAir->appendChild($domtree->createElement("codRetAir", $compra->cda));
                $detalleAir->appendChild($domtree->createElement("baseImpAir", number_format($compra->bi, 2, '.', '')));
                $detalleAir->appendChild($domtree->createElement("porcentajeAir", number_format($compra->por, 2, '.', '')));
                $detalleAir->appendChild($domtree->createElement("valRetAir", number_format($compra->vra, 2, '.', '')));

                if ($i < count($comprasf) - 1 && $compra->sec === $comprasf[$i + 1]->sec && $compra->TCV === $comprasf[$i + 1]->TCV) {
                    $detalleAir = $domtree->createElement("detalleAir");
                    $detalleAir = $air->appendChild($detalleAir);

                    $detalleAir->appendChild($domtree->createElement("codRetAir", $comprasf[$i + 1]->cda));
                    $detalleAir->appendChild($domtree->createElement("baseImpAir", number_format($comprasf[$i + 1]->bi, 2, '.', '')));
                    $detalleAir->appendChild($domtree->createElement("porcentajeAir", number_format($comprasf[$i + 1]->por, 2, '.', '')));
                    $detalleAir->appendChild($domtree->createElement("valRetAir", number_format($comprasf[$i + 1]->vra, 2, '.', '')));

                    $i++;
                }
            }

            //Notas
            if ($compra->TCV == 'N/C' || $compra->TCV == 'N/D') {
                $docModificado = $domtree->createElement("docModificado", '01');
                $docModificado = $detalleCompras->appendChild($docModificado);
                $estabModificado = $domtree->createElement("estabModificado", $compra->em);
                $estabModificado = $detalleCompras->appendChild($estabModificado);
                $ptoEmiModificado = $domtree->createElement("ptoEmiModificado", $compra->pem);
                $ptoEmiModificado = $detalleCompras->appendChild($ptoEmiModificado);
                $secModficado = $domtree->createElement("secModificado", $compra->sm);
                $secModficado = $detalleCompras->appendChild($secModficado);
                $autModficado = $domtree->createElement("autModificado", $compra->aut);
                $autModficado = $detalleCompras->appendChild($autModficado);
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

        foreach ($ventasf as $venta) {
            $detalleVentas = $domtree->createElement("detalleVentas");
            $detalleVentas = $ventas->appendChild($detalleVentas);

            $tpIdCliente = $domtree->createElement("tpIdCliente", ((int) $venta->tic) === 0 ? (strlen($venta->ruc) == 10 ? '05' : '04') : (((int) $venta->tic) < 7 ? '0' . (3 + (int) $venta->tic) : $venta->tic));
            $tpIdCliente = $detalleVentas->appendChild($tpIdCliente);

            $detalleVentas->appendChild($domtree->createElement("idCliente", $venta->ruc));

            // $parteRelVtas = $domtree->createElement("parteRelVtas", $venta->tic == '07' ? null : 'NO');
            // $parteRelVtas = $detalleVentas->appendChild($parteRelVtas);
            // $tipoCliente = $domtree->createElement("tipoCliente", $venta->tic == '03' ? '01' : null);
            // $tipoCliente = $detalleVentas->appendChild($tipoCliente);
            // $denoCli = $domtree->createElement("denoCli", $venta->tic == '03' ? 'CLIENTE EXTRAJERO' : null);
            // $denoCli = $detalleVentas->appendChild($denoCli);

            $tipoComprobante = $domtree->createElement("tipoComprobante", $venta->TCV == 'F' ? '18' : ($venta->TCV == 'N/C' ? '04' : ''));
            $tipoComprobante = $detalleVentas->appendChild($tipoComprobante);
            $tipoEmision = $domtree->createElement("tipoEmision", 'F');
            $tipoEmision = $detalleVentas->appendChild($tipoEmision);
            $numeroComprobantes = $domtree->createElement("numeroComprobantes", $venta->numeroComprobantes);
            $numeroComprobantes = $detalleVentas->appendChild($numeroComprobantes);

            $detalleVentas->appendChild($domtree->createElement("baseNoGraIva", number_format(0.0, 2, '.', '')));
            $detalleVentas->appendChild($domtree->createElement("baseImponible", number_format($venta->b0, 2, '.', '')));
            $detalleVentas->appendChild($domtree->createElement("baseImpGrav", number_format($venta->b12, 2, '.', '')));
            $detalleVentas->appendChild($domtree->createElement("montoIva", number_format($venta->miv, 2, '.', '')));
            $detalleVentas->appendChild($domtree->createElement("montoIce", number_format($venta->mi, 2, '.', '')));
            $detalleVentas->appendChild($domtree->createElement("valorRetIva", number_format($venta->vri, 2, '.', '')));
            $detalleVentas->appendChild($domtree->createElement("valorRetRenta", number_format($venta->vrr, 2, '.', '')));

            //Formas de pago
            $formasDePago = $domtree->createElement("formasDePago");
            $formasDePago = $detalleVentas->appendChild($formasDePago);

            $formasDePago->appendChild($domtree->createElement("formaPago", '01'));
        }

        $ventasEstablecimiento = $domtree->createElement("ventasEstablecimiento");
        $ventasEstablecimiento = $xmlRoot->appendChild($ventasEstablecimiento);

        $ventasf = DB::select('SELECT com, SUM(bi) AS bi FROM ventas_temp WHERE bi > 0 GROUP BY com');

        $sumVentas = 0;
        $orden = 1;
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
                        $ventasEstab = $domtree->createElement("ventasEstab", number_format($venta->bi, 2, '.', ''));
                        $ventasEstab = $ventaEst->appendChild($ventasEstab);
                        $ivaComp = $domtree->createElement("ivaComp", number_format(0.0, 2, '.', ''));
                        $ivaComp = $ventaEst->appendChild($ivaComp);
                    }
                    //No existe completar
                    else {
                        $ventaEst = $domtree->createElement("ventaEst");
                        $ventaEst = $ventasEstablecimiento->appendChild($ventaEst);

                        $codEstab = $domtree->createElement("codEstab", str_pad($orden, 3, 0, STR_PAD_LEFT));
                        $codEstab = $ventaEst->appendChild($codEstab);
                        $ventasEstab = $domtree->createElement("ventasEstab", number_format(0.0, 2, '.', ''));
                        $ventasEstab = $ventaEst->appendChild($ventasEstab);
                        $ivaComp = $domtree->createElement("ivaComp", number_format(0, 0, 2, '.', ''));
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
                $ventasEstab = $domtree->createElement("ventasEstab", number_format($venta->bi, 2, '.', ''));
                $ventasEstab = $ventaEst->appendChild($ventasEstab);
                $ivaComp = $domtree->createElement("ivaComp", number_format(0.0, 2, '.', ''));
                $ivaComp = $ventaEst->appendChild($ivaComp);
                $orden++;
            }

            $sumVentas += $venta->bi;
        }

        // $totalVentas = $domtree->createElement("totalVentas", number_format($sumVentas, 2, '.', ''));
        // $totalVentas = $xmlRoot->appendChild($totalVentas);
    }
}
