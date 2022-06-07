<?php

namespace App\Http\Controllers;

use App\Archivo;
use App\StaticClasses\DBStatics;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ArchivoController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $info = $request->get('info');
        $ruc = substr($info, 0, 13);
        $anio = substr($info, 13, 4);
        $mes = substr($info, 17);

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

        // DB::connection($anio < 2021 ? DBStatics::DB : DBStatics::DB21)
        DB::connection($db)
            ->table('archivos')
            ->updateOrInsert(
                ['cliente_auditwhole_ruc' => $ruc, 'mes' => (int) $mes, 'anio' => $anio],
                [
                    $request->get('tipo') => $request->get('file'),
                    'updated_at' => date('Y-m-d H:i:s', strtotime('+5 hours'))
                ]
            );

        return response()->json(['OK', 201]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Archivo  $archivo
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request)
    {
        $info = $request->get('info');
        $ruc = substr($info, 0, 13);
        $anio = substr($info, 13, 4);
        $mes = substr($info, 17);

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

        try {
            $file = Archivo::on($db)
                ->where([
                    'cliente_auditwhole_ruc' => $ruc,
                    'mes' => (int) $mes,
                    'anio' => $anio
                ])->get();
            if ($file)
                return $file[0];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Display the specified file.
     *
     * @param  \App\Archivo  $archivo
     * @return \Illuminate\Http\Response
     */
    public function showtype(Request $request)
    {
        $info = $request->get('info');
        $ruc = substr($info, 0, 13);
        $anio = substr($info, 13, 4);
        $mes = substr($info, 17);
        try {
            $file = DB::table('archivos')
                ->select($request->get('tipo'))
                ->where([
                    'cliente_auditwhole_ruc' => $ruc,
                    'mes' => (int) $mes,
                    'anio' => $anio
                ])->get();
            if ($file)
                return base64_decode($file[0]->{$request->get('tipo')});
        } catch (\Exception $e) {
            return null;
        }
    }
}
