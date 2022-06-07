<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Archivo;
use App\StaticClasses\DBStatics;

class ReportVentasController extends Controller
{
    public function report(Request $request)
    {
        $datestart = new \DateTime($request->get('datestart'));
        $dateend = new \DateTime($request->get('dateend'));

        $datestart->setTimezone(new \DateTimeZone('America/Guayaquil'));
        $dateend->setTimezone(new \DateTimeZone('America/Guayaquil'));

        $monthstart = $datestart->format('m');
        $monthend = $dateend->format('m');
        $year = $dateend->format('Y');

        $db = null;
        switch ((int)$year) {
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

        // $fileventas = Archivo::on($year < 2021 ? DBStatics::DB : DBStatics::DB21)
        $fileventas = Archivo::on($db)
            ->select('fileventa')
            ->where([
                'cliente_auditwhole_ruc' => $request->get('ruc'),
                'anio' => $year
            ])
            ->whereBetween('mes', [$monthstart, $monthend])
            ->get();

        $newVentas = new \DOMDocument('1.0', 'ISO-8859-1');
        /* create the root element of the xml tree */
        $xmlRoot = $newVentas->createElement("ventas");
        /* append it to the document created */
        $xmlRoot = $newVentas->appendChild($xmlRoot);

        foreach ($fileventas as $fileventa) {
            if ($fileventa->fileventa !== null) {
                $xml = base64_decode($fileventa->fileventa);
                $document = new \DOMDocument();
                $document->loadXML($xml);

                $ventas = $document->getElementsByTagName('venta');

                foreach ($ventas as $venta) {
                    $tag_ruc = $venta->getElementsByTagName('ruc');
                    $ruc = $tag_ruc->length > 0 ? $tag_ruc->item(0)->textContent : null;

                    $tag_fec = $venta->getElementsByTagName('fec');
                    $fec = $tag_fec->length > 0 ? $tag_fec->item(0)->textContent : null;

                    if ($ruc !== null && strlen($ruc) > 2 && $fec !== null && strlen($fec) > 7) {
                        // $fec = new \DateTime(str_replace('/', '-', $fec));

                        $f_explode = explode(is_int(strpos($fec, '-')) ? '-' : '/', $fec);
                        $date = new \DateTime();
                        $date->setDate($f_explode[2], $f_explode[1], $f_explode[0]);
                        $date->setTimezone(new \DateTimeZone('America/Guayaquil'));

                        /* Determinate gange fec */
                        if ($date >= $datestart && $date <= $dateend) {
                            $node = $newVentas->importNode($venta, true);
                            $xmlRoot->appendChild($node);
                        }
                    }
                }
            }
        }

        return base64_encode($newVentas->saveXML());
    }
}
