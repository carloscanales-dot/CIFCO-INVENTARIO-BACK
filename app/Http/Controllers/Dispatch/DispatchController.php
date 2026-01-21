<?php

namespace App\Http\Controllers\Dispatch;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use App\Models\Dispatch\Dispatch;
use App\Models\Dispatch\DispatchDetail;
use App\Models\Product\ProductWarehouse;
use App\Http\Resources\Dispatch\DispatchResource;
use Symfony\Component\HttpKernel\Exception\HttpException;

class DispatchController extends Controller
{
    /**
     * Listado de salidas
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Dispatch::class);

        $dispatches = Dispatch::with([
                'warehouse',
                'requester',
                'user',
            ])
            ->orderBy('id', 'desc')
            ->paginate(15);

        return response()->json([
            'total_page' => $dispatches->lastPage(),
            'dispatches' => DispatchResource::collection($dispatches),
        ]);
    }

    /**
     * Configuración inicial
     */
    public function config()
    {
        return response()->json([
            'warehouses' => \App\Models\Config\Warehouse::select('id', 'name')->get(),
            'areas' => [
                ['id' => 1, 'name' => 'Informática'],
                ['id' => 2, 'name' => 'Recursos Humanos'],
                ['id' => 3, 'name' => 'Administración'],
                ['id' => 4, 'name' => 'Finanzas'],
                ['id' => 5, 'name' => 'Logística'],
            ],
        ]);
    }

    /**
     * Registrar salida
     */
    public function store(Request $request)
    {
        Gate::authorize('create', Dispatch::class);

        $request->validate([
            'warehouse_id' => 'required|integer',
            'requester_id' => 'required|integer',
            'date_emision' => 'required|date',
            'details'      => 'required|array|min:1',
        ]);

        DB::beginTransaction();

        try {
            $user = auth('api')->user();

            $dispatch = Dispatch::create([
                'warehouse_id'       => $request->warehouse_id,
                'requester_id'       => $request->requester_id,
                'user_id'            => $user->id,
                'sucursale_id'       => $user->sucursale_id,
                'requisition_number' => $request->requisition_number,
                'area_id'            => $request->area_id,
                'reference'          => $request->reference,
                'date_emision'       => $request->date_emision,
                'date_document'      => $request->date_document,
                'description'        => $request->description,
                'state'              => 1,
            ]);

            foreach ($request->details as $detail) {

                $stock = ProductWarehouse::where('product_id', $detail['product_id'])
                    ->where('warehouse_id', $detail['warehouse_id'])
                    ->where('unit_id', $detail['unit_id'])
                    ->lockForUpdate()
                    ->first();

                if (!$stock || $stock->stock < $detail['quantity']) {
                    throw new HttpException(
                        422,
                        'Stock insuficiente para el producto'
                    );
                }

                $stock->update([
                    'stock' => $stock->stock - $detail['quantity'],
                ]);

                DispatchDetail::create([
                    'dispatch_id'  => $dispatch->id,
                    'product_id'   => $detail['product_id'],
                    'warehouse_id' => $detail['warehouse_id'],
                    'unit_id'      => $detail['unit_id'],
                    'quantity'     => $detail['quantity'],
                ]);
            }

            DB::commit();

            return response()->json([
                'message'  => 'Salida registrada correctamente',
                'dispatch' => DispatchResource::make($dispatch->load(['warehouse','requester','user'])),
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            throw new HttpException(500, $e->getMessage());
        }
    }

    /**
     * Mostrar salida
     */
    public function show(string $id)
    {
        Gate::authorize('view', Dispatch::class);

        $dispatch = Dispatch::with([
            'details.product',
            'details.unit',
            'warehouse',
            'requester',
            'user'
        ])->findOrFail($id);

        return response()->json([
            'dispatch' => DispatchResource::make($dispatch),
        ]);
    }

    /**
     * Anular salida
     */
    public function destroy(string $id)
    {
        Gate::authorize('delete', Dispatch::class);

        $dispatch = Dispatch::findOrFail($id);
        $dispatch->delete();

        return response()->json([
            'message' => 'Salida anulada',
        ]);
    }
}
