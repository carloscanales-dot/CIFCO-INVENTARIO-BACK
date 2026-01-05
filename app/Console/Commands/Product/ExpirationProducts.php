<?php

namespace App\Console\Commands\Product;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\ExpirationProductMail;

class ExpirationProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:expiration-products';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $products = $this->getExpirationProducts();

        if ($products->count() > 0) {
            Mail::to('fb21010@ues.edu.sv')->send(new ExpirationProductMail($products));
        } else {
            $this->info("No products are about to expire.");
        }

        return 0;
    }

    /** Get products that are about to expire */
    private function getExpirationProducts()
    {
        return DB::table('v_expiration_products')->get();
    }
}
