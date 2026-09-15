<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            [
                'name' => 'Wireless Barcode Scanner',
                'code' => 'INV-BAR-001',
                'price_per_unit' => 89.99,
                'tax_percentage' => 8.25,
                'stock_on_hand' => 24,
            ],
            [
                'name' => 'Thermal Receipt Printer',
                'code' => 'INV-PRN-002',
                'price_per_unit' => 149.50,
                'tax_percentage' => 8.25,
                'stock_on_hand' => 12,
            ],
            [
                'name' => 'Inventory Label Rolls',
                'code' => 'INV-LBL-003',
                'price_per_unit' => 18.75,
                'tax_percentage' => 5.00,
                'stock_on_hand' => 80,
            ],
            [
                'name' => 'Point of Sale Tablet Stand',
                'code' => 'POS-STD-004',
                'price_per_unit' => 42.00,
                'tax_percentage' => 8.25,
                'stock_on_hand' => 4,
            ],
            [
                'name' => 'Cash Drawer',
                'code' => 'POS-DRW-005',
                'price_per_unit' => 119.00,
                'tax_percentage' => 8.25,
                'stock_on_hand' => 7,
            ],
            [
                'name' => 'Shipping Scale',
                'code' => 'SHP-SCL-006',
                'price_per_unit' => 64.95,
                'tax_percentage' => 8.25,
                'stock_on_hand' => 3,
            ],
            [
                'name' => 'Packing Tape Dispenser',
                'code' => 'SHP-TPD-007',
                'price_per_unit' => 13.49,
                'tax_percentage' => 5.00,
                'stock_on_hand' => 45,
            ],
            [
                'name' => 'Corrugated Shipping Boxes',
                'code' => 'SHP-BOX-008',
                'price_per_unit' => 2.25,
                'tax_percentage' => 0.00,
                'stock_on_hand' => 150,
            ],
            [
                'name' => 'USB Card Reader',
                'code' => 'POS-CRD-009',
                'price_per_unit' => 29.99,
                'tax_percentage' => 8.25,
                'stock_on_hand' => 2,
            ],
            [
                'name' => 'Store Counter Display',
                'code' => 'MRK-DSP-010',
                'price_per_unit' => 74.25,
                'tax_percentage' => 10.00,
                'stock_on_hand' => 18,
            ],
        ];

        foreach ($products as $product) {
            Product::updateOrCreate(
                ['code' => $product['code']],
                $product
            );
        }
    }
}
