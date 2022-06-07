<?php

namespace App\Http\Controllers;

use App\Contacto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ContactoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return Contacto::all();
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $cliente = json_encode($request->get('contacto'));
        $cliente = json_decode($cliente);

        if ($cliente->id !== '9999999999999') {
            $tpContacto = null;
            if ($cliente->tpId == '01') {
                $contec = substr($cliente->id, 2, 1);
                if ($contec == 6 || $contec == 9) {
                    $tpContacto = '02';
                } else {
                    $tpContacto = '01';
                }
            }

            DB::table('contactos')
                ->updateOrInsert(
                    ['id' => $cliente->id],
                    [
                        'denominacion' => $cliente->denominacion,
                        'tpId' => $cliente->tpId,
                        'tpContacto' => $cliente->tpId === '03' ? '01' : $tpContacto,
                        'contabilidad' => $cliente->contabilidad
                    ]
                );
        }

        return response()->json(['OK', 201]);
    }

    function toArrayIds($objs)
    {
        $cedulas = array();
        foreach ($objs as $obj) {
            array_push($cedulas, $obj->id);
        }
        return $cedulas;
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Cliente  $cliente
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return Contacto::find($id);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Cliente  $cliente
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $clientes = json_encode($request->get('contactos'));
        $clientes = json_decode($clientes);

        $this->loadMasive($clientes);

        return response()->json(['OK', 201]);
    }

    public function getmasive(Request $request)
    {
        return DB::table('contactos')->select('id', 'denominacion')->whereIn('id', $request->providers)->get();
    }

    public function loadMasive($clientes)
    {
        $arrayinsertados = json_decode(DB::table('contactos')->whereIn('id', $this->toArrayIds($clientes))->get());

        $output = array_udiff(
            $clientes,
            $arrayinsertados,
            function ($obj_a, $obj_b) {
                return $obj_a->id - $obj_b->id;
            }
        );

        $clies = array();
        foreach ($output as $cliente) {
            $tpContacto = null;
            if ($cliente->tpId == '01') {
                $contec = substr($cliente->id, 2, 1);
                if ($contec == 6 || $contec == 9) {
                    $tpContacto = '02';
                } else {
                    $tpContacto = '01';
                }
            }
            $clie = [
                'id' => $cliente->id,
                'denominacion' => $this->removeOtherCharacter($cliente->denominacion),
                'tpId' => $cliente->tpId,
                'tpContacto' => $tpContacto,
                'contabilidad' => $cliente->contabilidad,
                //'tpContacto' => $cliente->tpId == '01' || $cliente->tpId == '02' ? (substr($cliente->tpId, 2, 1) == 6 || substr($cliente->tpId, 2, 1) == 9 ? '02' : '01') : '',
                // 'tpContribuyente' => NULL, //Preguntar (ESPECIAL-OLLC-NO/OLLC)
                // 'retencionrenta' => NULL, //Este campo se llena al importar las retenciones retenciones
                // 'retencioniva' => NULL//Este campo se llena al importar las retenciones retenciones
            ];

            array_push($clies, $clie);
        }

        DB::table('contactos')->insert($clies);
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
