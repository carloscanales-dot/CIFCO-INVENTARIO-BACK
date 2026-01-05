<?php

namespace App\Exports\Sale;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\FromCollection;

class SaleDownloadExcel implements FromView
{
    protected $sales;
    public function __construct($sales) {
        $this->sales = $sales;
    }
    
    public function view() : View{
        return view("sale.download_excel",[
            "sales" => $this->sales,
        ]);
    }
}
