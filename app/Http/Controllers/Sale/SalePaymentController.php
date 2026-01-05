<?php

namespace App\Http\Controllers\Sale;

use App\Models\Sale\Sale;
use Illuminate\Http\Request;
use App\Models\Sale\SalePayment;
use App\Http\Controllers\Controller;

class SalePaymentController extends Controller
{

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // METODO DE PAGO method_payment
        // EL MONTO amount
        // EL ID DE LA VENTA sale_id
        $sale_payment = SalePayment::create([
            "sale_id" => $request->sale_id,
            "method_payment" => $request->method_payment,
            "amount" => $request->amount,
        ]);

        $sale = Sale::findOrFail($request->sale_id);

        $sale->update([
            "debt" => $sale->debt - $sale_payment->amount, // MONTO ADEUDADO
            "paid_out" => $sale->paid_out + $sale_payment->amount, // MONTO PAGADO
        ]);
        date_default_timezone_set('America/Lima');
        $state_payment = $sale->state_payment;
        $date_pay_complete = $sale->date_pay_complete;
        if($sale->debt == 0){
            $state_payment = 3;
            $date_pay_complete = now();
        }
        $sale->update([
            "state_payment" => $state_payment,
            "date_pay_complete" => $date_pay_complete
        ]);

        return response()->json([
            "payment" => [
                "id" => $sale_payment->id,
                "method_payment" =>  $sale_payment->method_payment,
                "amount" =>  $sale_payment->amount,
            ],
            "payment_total" => $sale->paid_out,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
       // METODO DE PAGO method_payment
        // EL MONTO amount
        // EL ID DE LA VENTA sale_id
        $sale_payment = SalePayment::findOrFail($id);
        $amount_old = $sale_payment->amount;
        $sale = Sale::findOrFail($request->sale_id);
        
        if((($sale->paid_out - $sale_payment->amount) + $request->amount) > $sale->total ){
            return response()->json([
                "message" => 403,
                "message_text" => "NO PUEDES INGRESAR UN MONTO, PORQUE SUPERA AL TOTAL DE LA VENTA"
            ]);
        }

        $sale_payment->update([
            "method_payment" => $request->method_payment,
            "amount" => $request->amount,
        ]);


        $sale->update([
            "paid_out" => ($sale->paid_out - $amount_old) + $sale_payment->amount, // MONTO PAGADO
            "debt" => $sale->total - (($sale->paid_out - $amount_old) + $sale_payment->amount), // MONTO ADEUDADO
        ]);
        date_default_timezone_set('America/Lima');
        $state_payment = $sale->state_payment;
        $date_pay_complete = $sale->date_pay_complete;
        if($sale->debt == 0){
            $state_payment = 3;
            $date_pay_complete = now();
        }
        // AÑADIR
        if($sale->debt > 0 && $sale->paid_out > 0){
            $state_payment = 2;
            $date_pay_complete = null;
        }
        // 
        $sale->update([
            "state_payment" => $state_payment,
            "date_pay_complete" => $date_pay_complete
        ]);

        return response()->json([
            "payment" => [
                "id" => $sale_payment->id,
                "method_payment" =>  $sale_payment->method_payment,
                "amount" =>  $sale_payment->amount,
            ],
            "payment_total" => $sale->paid_out,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $sale_payment = SalePayment::findOrFail($id);
        $sale = $sale_payment->sale;
        $sale_payment->delete();

        $sale->update([
            "paid_out" => ($sale->paid_out) - $sale_payment->amount, // MONTO PAGADO
            "debt" => ($sale->paid_out + $sale_payment->amount), // MONTO ADEUDADO
        ]);
        date_default_timezone_set('America/Lima');
        $state_payment = 2;
        $date_pay_complete = null;
        if($sale->paid_out == 0){
            $state_payment = 1;
        }
        $sale->update([
            "state_payment" => $state_payment,
            "date_pay_complete" => $date_pay_complete
        ]);

        return response()->json([
            "payment" => [
                "id" => $sale_payment->id,
                "method_payment" =>  $sale_payment->method_payment,
                "amount" =>  $sale_payment->amount,
            ],
            "payment_total" => $sale->paid_out,
        ]);
    }
}
