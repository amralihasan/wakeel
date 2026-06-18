<?php

namespace Database\Seeders;

use App\Models\ContactMessage;
use Illuminate\Database\Seeder;

class ContactMessageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (ContactMessage::exists()) {
            return;
        }

        ContactMessage::create([
            'name' => 'أحمد حسن',
            'company' => 'شركة النيل للتطوير العقاري',
            'email' => 'ahmed.hassan@nile-estate.com',
            'phone' => '+201011111111',
            'message' => 'أرغب في الحصول على عرض توضيحي للنظام وكيفية ربطه بصفحات فيسبوك الخاصة بنا لخدمة العملاء تلقائياً.',
            'locale' => 'ar',
            'ip_address' => '127.0.0.1',
        ]);

        ContactMessage::create([
            'name' => 'John Doe',
            'company' => 'Apex Developments',
            'email' => 'john.doe@apex-dev.com',
            'phone' => '+15550199',
            'message' => 'I would like to schedule a call to discuss custom integrations for our real estate sales team.',
            'locale' => 'en',
            'ip_address' => '127.0.0.1',
        ]);
    }
}
