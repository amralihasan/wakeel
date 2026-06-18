<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure at least one company exists
        if (Company::count() === 0) {
            Company::factory()->create(['name' => 'Default Real Estate Company']);
        }

        $companies = Company::all();

        // Standard types of properties we now support
        $types = [
            'apartment' => [
                ['title' => 'شقة فاخرة بإطلالة على الحديقة', 'price' => 1200000, 'rooms' => 3, 'area' => 150, 'location' => 'التجمع الخامس، القاهرة الجديدة'],
                ['title' => 'ستوديو مفروش بالكامل للبيع', 'price' => 750000, 'rooms' => 1, 'area' => 60, 'location' => 'الرحاب، القاهرة الجديدة'],
            ],
            'villa' => [
                ['title' => 'فيلا مستقلة مع حمام سباحة خاص', 'price' => 8500000, 'rooms' => 5, 'area' => 420, 'location' => 'مدينتي، القاهرة الجديدة'],
            ],
            'townhouse' => [
                ['title' => 'تاون هاوس للبيع في كمبوند شهير', 'price' => 4500000, 'rooms' => 4, 'area' => 280, 'location' => 'الشيخ زايد، الجيزة'],
            ],
            'twinhouse' => [
                ['title' => 'توين هاوس تشطيب كامل جاهز للاستلام', 'price' => 5200000, 'rooms' => 4, 'area' => 310, 'location' => 'أكتوبر، الجيزة'],
            ],
            'chalet' => [
                ['title' => 'شاليه صف أول على البحر مباشرة', 'price' => 3200000, 'rooms' => 2, 'area' => 110, 'location' => 'الساحل الشمالي'],
            ],
            'compound' => [
                ['title' => 'مشروع كمبوند سكني متكامل الخدمات', 'price' => 150000000, 'rooms' => 200, 'area' => 50000, 'location' => 'العاصمة الإدارية الجديدة'],
            ],
            'office' => [
                ['title' => 'مكتب إداري مرخص في مول تجاري', 'price' => 2100000, 'rooms' => 2, 'area' => 85, 'location' => 'شارع التسعين، التجمع الخامس'],
            ],
            'retail' => [
                ['title' => 'محل تجاري للبيع موقع مميز جداً', 'price' => 6500000, 'rooms' => 1, 'area' => 45, 'location' => 'وسط البلد، القاهرة'],
            ],
            'land' => [
                ['title' => 'أرض فضاء للبناء ترخيص سكني', 'price' => 3800000, 'rooms' => 0, 'area' => 600, 'location' => 'الحزام الأخضر، الشيخ زايد'],
            ],
        ];

        foreach ($companies as $company) {
            foreach ($types as $type => $units) {
                foreach ($units as $unitData) {
                    Unit::create(array_merge($unitData, [
                        'company_id' => $company->id,
                        'type' => $type,
                        'description' => 'هذا العقار يمثل فرصة استثمارية ممتازة بموقع استراتيجي رائع، متكامل الخدمات والمرافق وقريب من الطرق الرئيسية والمناطق التجارية.',
                        'down_payment' => intval($unitData['price'] * 0.1), // 10% down payment
                        'installment_years' => 7,
                        'status' => 'available',
                        'delivery_date' => now()->addYears(2)->toDateString(),
                    ]));
                }
            }
        }
    }
}
