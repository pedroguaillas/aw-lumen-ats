<?php

namespace App\Http\Controllers;

use App\Proveedor;
use App\Archivo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProveedorController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            $file = Archivo::where([
                'user_id' => 1,
                'mes' => $request->get('mes'),
                'anio' => $request->get('anio')
            ])->get('filecompra');
            if ($file) {
                //$cedulas = $this->extraerCedulas($file[0]->filecompra);
                $insertados = DB::table('proveedors')->whereIn('id', $this->extraerCedulas($file[0]->filecompra))->get();

                var_dump($insertados);
            }
        } catch (\Exception $ex) {
            return $ex;
        }
    }
    private function extraerCedulas($filecompras)
    {
        $filecompra = base64_decode($filecompras);

        $document = new \DOMDocument();
        $document->loadXML($filecompra);

        $compras =  $document->getElementsByTagName('compra');

        $cedulas = array();

        foreach ($compras as $compra) {
            array_push($cedulas, $compra->getElementsByTagName('RUC')->item(0)->textContent);
        }
        return $cedulas;
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
        $proveedores = json_encode($request->get('proveedores'));
        $proveedores = json_decode($proveedores);

        $arrayinsertados = json_decode(DB::table('proveedors')->whereIn('id', $this->toArrayIds($proveedores))->get());

        $output = array_udiff(
            $proveedores,
            $arrayinsertados,
            function ($obj_a, $obj_b) {
                return $obj_a->id - $obj_b->id;
            }
        );

        $provs = array();
        foreach ($output as $proveedor) {

            $prov = [
                'id' => $proveedor->id,
                'denoProv' => $proveedor->denoProv,
                'tpIdProv' => '01', //Especial solo se ejecuta despues de importar facturas electrónicas y el 01 corresponde al ruc
                'tipoProv' => substr($proveedor->id, 2, 1) == '6' || substr($proveedor->id, 2, 1) == '9' ? '02' : '01',
                'contabilidad' => $proveedor->contabilidad //Si llega null guarda null
            ];

            array_push($provs, $prov);
        }

        DB::table('proveedors')->insert($provs);

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
}
