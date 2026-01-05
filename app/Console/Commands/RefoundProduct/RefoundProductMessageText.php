<?php

namespace App\Console\Commands\RefoundProduct;

use App\Models\Sale\Sale;
use Illuminate\Console\Command;
use App\Models\Sale\RefoundProduct;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class RefoundProductMessageText extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:refound-product-message-text';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Envio de mensaje de texto al cliente, sobre el estado de su producto en reparación';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        date_default_timezone_set('America/Lima');
        
        $refound_products = RefoundProduct::where("type",1)
                                            ->whereColumn("state","<>","state_clone")
                                            ->whereDate("updated_at",now()->format("Y-m-d"))
                                            ->get();
        // dd($refound_products);
        foreach ($refound_products as $key => $refound_product) {
            $state = $refound_product->state;
            $client_full_name = $refound_product->client->full_name;
            $message = "";
            switch ($state) {
                case 1:
                    $message = "Hola ".$client_full_name." , tu proceso de reparación ha iniciado, en unos minutos los tecnicos empezaran a revisar tu equipo, buen dia.";
                    break;
                
                case 2:
                    $message = "Hola ".$client_full_name." , la reparación esta en revisión,  los tecnicos estan evaluando que problema puede presentar para poder solucionarlo, en los proximos dias te estaremos comunicando el resultado";
                    break;

                case 3:
                    $message = "Hola ".$client_full_name." , la reparación fue todo un exito, puedes venir al local a recoger tu producto, recuerda que no hay ningun costo, todo es parte de la garantia.";
                    break;

                case 4:
                    $message = "Hola ".$client_full_name. ", la reparación no fue exitosa, el producto no pudo solucionarse , pero no te preocupes porque puedes venir al local a recoger un producto totalmente nuevo :)";
                    break;
                default:
                    # code...
                    break;
            }
            
            $url = "https://9zab07tsxa.execute-api.sa-east-1.amazonaws.com/sns-1/laravest-sns-inventory";

            try {
                
                $response = Http::withHeaders([
                    'Content-Type' => "application/json"
                ])->post($url,[
                    "message" => $message,
                ]);

                if($response->successful()){
                    Log::info(json_encode($response->json()));
                }else{
                    Log::info($response->status());
                }

            } catch (\Exception $th) {
                //throw $th;
                Log::info(json_encode($th->getMessage()));
            }
        }
    }
}
