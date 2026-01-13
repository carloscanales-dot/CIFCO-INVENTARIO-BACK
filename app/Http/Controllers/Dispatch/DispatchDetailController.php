<?php

namespace App\Http\Controllers\Dispatch;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\Dispatch\Dispatch;
use App\Models\Dispatch\DispatchDetail;
use App\Models\Product\ProductWarehouse;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DispatchDetailController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        /*
            dispatch_id
            product_id
            warehouse_id
            unit_id
            quantity
        */

        try {
            DB::beginTransaction();

            $dispatch = Dispatch::findOrFail($request->dispatch_id);

            // VALIDAR STOCK
            $productWarehouse = ProductWarehouse::where("product_id", $request->product_id)
                ->where("warehouse_id", $request->warehouse_id)
                ->where("unit_id", $request->unit_id)
                ->first();

            if (!$productWarehouse || $productWarehouse->stock < $request->quantity) {
                throw new HttpException(403, "STOCK INSUFICIENTE PARA REALIZAR EL DESPACHO");
            }

            // DESCONTAR STOCK
            $productWarehouse->update([
                "stock" => $productWarehouse->stock - $request->quantity
            ]);

            // CREAR DETALLE
            $dispatchDetail = DispatchDetail::create([
                "dispatch_id" => $request->dispatch_id,
                "product_id" => $request->product_id,
                "warehouse_id" => $request->warehouse_id,
                "unit_id" => $request->unit_id,
                "quantity" => $request->quantity,
            ]);

            DB::commit();

            return response()->json([
                "message" => 200,
                "detail" => $dispatchDetail
            ]);

        } catch (\Throwable $th) {
            DB::rollBack();
            throw new HttpException(500, $th->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            DB::beginTransaction();

            $dispatchDetail = DispatchDetail::findOrFail($id);

            $productWarehouse = ProductWarehouse::where("product_id", $dispatchDetail->product_id)
                ->where("warehouse_id", $dispatchDetail->warehouse_id)
                ->where("unit_id", $dispatchDetail->unit_id)
                ->first();

            if (!$productWarehouse) {
                throw new HttpException(404, "STOCK NO ENCONTRADO");
            }

            // DEVOLVER STOCK ANTERIOR
            $productWarehouse->update([
                "stock" => $productWarehouse->stock + $dispatchDetail->quantity
            ]);

            // VALIDAR NUEVA CANTIDAD
            if ($productWarehouse->stock < $request->quantity) {
                throw new HttpException(403, "STOCK INSUFICIENTE PARA ACTUALIZAR EL DESPACHO");
            }

            // DESCONTAR NUEVO STOCK
            $productWarehouse->update([
                "stock" => $productWarehouse->stock - $request->quantity
            ]);

            // ACTUALIZAR DETALLE
            $dispatchDetail->update([
                "quantity" => $request->quantity
            ]);

            DB::commit();

            return response()->json([
                "message" => 200,
                "detail" => $dispatchDetail
            ]);

        } catch (\Throwable $th) {
            DB::rollBack();
            throw new HttpException(500, $th->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            DB::beginTransaction();

            $dispatchDetail = DispatchDetail::findOrFail($id);

            $productWarehouse = ProductWarehouse::where("product_id", $dispatchDetail->product_id)
                ->where("warehouse_id", $dispatchDetail->warehouse_id)
                ->where("unit_id", $dispatchDetail->unit_id)
                ->first();

            if ($productWarehouse) {
                // DEVOLVER STOCK
                $productWarehouse->update([
                    "stock" => $productWarehouse->stock + $dispatchDetail->quantity
                ]);
            }

            $dispatchDetail->delete();

            DB::commit();

            return response()->json([
                "message" => 200,
                "dispatch_detail_id" => $id
            ]);

        } catch (\Throwable $th) {
            DB::rollBack();
            throw new HttpException(500, $th->getMessage());
        }
    }
}
