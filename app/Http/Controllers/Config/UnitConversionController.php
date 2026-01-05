<?php

namespace App\Http\Controllers\Config;

use App\Http\Controllers\Controller;
use App\Models\Config\UnitConversion;
use Illuminate\Http\Request;

class UnitConversionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $unit_id = $request->get("unit_id");

        $unit_conversions = UnitConversion::where("unit_id",$unit_id)->orderBy("id","desc")->get();

        return response()->json([
            "unit_conversion" => $unit_conversions->map(function($unit_conversion) {
                return [
                    "id" => $unit_conversion->id,
                    "unit_id" => $unit_conversion->unit_id,
                    "unit" => [
                        "id" => $unit_conversion->unit->id,
                        "name" => $unit_conversion->unit->name,
                    ],
                    "unit_to_id" => $unit_conversion->unit_to_id,
                    "unit_to" => [
                        "id" => $unit_conversion->unit_to->id,
                        "name" => $unit_conversion->unit_to->name,
                    ]
                ];
            })
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $exist_unit_conversion  = UnitConversion::where("unit_id",$request->unit_id)->where("unit_to_id",$request->unit_to_id)->first();

        if($exist_unit_conversion){
            return response()->json([
                "message" => 403,
                "message_text" => "LA UNIDAD A CONVERTIR YA EXISTE"
            ]);
        }

        $unit_conversion = UnitConversion::create([
            "unit_id" => $request->unit_id,// ALA UNIDAD RELACIONADA
            "unit_to_id" => $request->unit_to_id, // A LA UNIDAD QUE SE PUEDE CONVERTIR
        ]);

        return response()->json([
            "unit_conversion" => [
                "id" => $unit_conversion->id,
                "unit_id" => $unit_conversion->unit_id,
                "unit" => [
                    "id" => $unit_conversion->unit->id,
                    "name" => $unit_conversion->unit->name,
                ],
                "unit_to_id" => $unit_conversion->unit_to_id,
                "unit_to" => [
                    "id" => $unit_conversion->unit_to->id,
                    "name" => $unit_conversion->unit_to->name,
                ]
            ],
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $unit_conversion = UnitConversion::findOrFail($id);
        $unit_conversion->delete();

        return response()->json([
            "message" => 200
        ]);
    }
}
