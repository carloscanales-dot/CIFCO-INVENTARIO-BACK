<?php

namespace App\Exports\Product;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class InventoryDownloadExcel implements FromCollection, WithHeadings, WithMapping, WithStyles
{
    protected $products;
    protected $warehouses;
    protected $inventory_stock;

    public function __construct($products, $warehouses, $inventory_stock)
    {
        $this->products = $products;
        $this->warehouses = $warehouses;
        $this->inventory_stock = $inventory_stock;
    }

    /**
     * Retorna la colección de productos
     */
    public function collection()
    {
        return $this->products;
    }

    /**
     * Define los encabezados del Excel
     */
    public function headings(): array
    {
        $headings = ['Producto', 'SKU', 'Categoría'];

        // Agregar columnas por cada almacén
        foreach ($this->warehouses as $warehouse) {
            $headings[] = $warehouse->name;
        }

        $headings[] = 'Stock Total';

        return $headings;
    }

    /**
     * Mapea cada producto a una fila del Excel
     */
    public function map($product): array
    {
        $row = [
            $product->title ?? '',
            $product->sku ?? '',
            optional($product->product_categorie)->title ?? 'Sin categoría',
        ];

        $totalStock = 0;

        // Agregar stock por cada almacén
        foreach ($this->warehouses as $warehouse) {
            $stock = collect($this->inventory_stock)->firstWhere(function ($item) use ($product, $warehouse) {
                return $item['product_id'] == $product->id && $item['warehouse_id'] == $warehouse->id;
            });

            $quantity = $stock ? (int) $stock['quantity'] : 0;
            $row[] = $quantity;
            $totalStock += $quantity;
        }

        // Stock total
        $row[] = $totalStock;

        return $row;
    }

    /**
     * Aplica estilos al Excel
     */
    public function styles(Worksheet $sheet)
    {
        return [
            // Estilo para la primera fila (encabezados)
            1 => [
                'font' => ['bold' => true, 'size' => 12],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E3F2FD']
                ],
            ],
        ];
    }
}
