<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Archivo;
use App\StaticClasses\DBStatics;

class ReportComprasController extends Controller
{
    public function report(Request $request)
    {
        $datestart = new \DateTime($request->get('datestart'));
        $dateend = new \DateTime($request->get('dateend'));
        
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

        // $filecompras = Archivo::on($year < 2021 ? DBStatics::DB : DBStatics::DB21)
        $filecompras = Archivo::on($db)
            ->select('filecompra')
            ->where([
                'cliente_auditwhole_ruc' => $request->get('ruc'),
                'anio' => $year
            ])
            ->whereBetween('mes', [$monthstart, $monthend])
            ->get();

        $newCompras = new \DOMDocument('1.0', 'ISO-8859-1');
        /* create the root element of the xml tree */
        $xmlRoot = $newCompras->createElement("compras");
        /* append it to the document created */
        $xmlRoot = $newCompras->appendChild($xmlRoot);

        foreach ($filecompras as $filecompra) {
            if ($filecompra->filecompra !== null) {
                $xml = base64_decode($filecompra->filecompra);
                $document = new \DOMDocument();
                $document->loadXML($xml);

                $compras = $document->getElementsByTagName('compra');

                foreach ($compras as $compra) {
                    $fec = trim($compra->getElementsByTagName('fec')->item(0)->textContent);
                    if (strlen($fec) > 7) {
                        $fec = new \DateTime(str_replace('/', '-', $fec));
                        if ($fec >= $datestart && $fec <= $dateend) {
                            $node = $newCompras->importNode($compra, true);
                            $xmlRoot->appendChild($node);
                        }
                    }
                }
            }
        }

        return base64_encode($newCompras->saveXML());
    }
}
