<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $faqs = [
            [
                'category' => 'Orders & Delivery',
                'question' => 'How long does delivery take?',
                'answer' => 'Standard orders are delivered within 3-7 working days across India. Metro cities typically receive orders within 2-4 days. Express delivery (2-4 days) is available at checkout for select pincodes.',
                'sort_order' => 1,
            ],
            [
                'category' => 'Orders & Delivery',
                'question' => 'Can I track my order?',
                'answer' => 'Yes. Once your order ships, we send you a tracking number by email and SMS. You can also track the latest status anytime from the "Track Order" page using your order ID and phone number.',
                'sort_order' => 2,
            ],
            [
                'category' => 'Orders & Delivery',
                'question' => 'Is cash on delivery available?',
                'answer' => 'Yes, COD is available across most pincodes in India. The COD amount is payable at delivery to our delivery partner.',
                'sort_order' => 3,
            ],
            [
                'category' => 'Payments',
                'question' => 'Which payment methods do you accept?',
                'answer' => 'We accept all major UPI apps, credit cards, debit cards and net banking through our secure online gateway, in addition to cash on delivery.',
                'sort_order' => 4,
            ],
            [
                'category' => 'Returns & Refunds',
                'question' => 'What is your return policy?',
                'answer' => 'You can raise a return request within 7 days of delivery for damaged, defective or incorrect items. Approved returns are picked up free of cost and refunds are processed within 5-7 working days.',
                'sort_order' => 5,
            ],
            [
                'category' => 'Returns & Refunds',
                'question' => 'How long do refunds take?',
                'answer' => 'Once your returned item is picked up and quality-checked, refunds are initiated within 48 hours. Depending on your bank or UPI provider, the amount reflects in 5-7 working days.',
                'sort_order' => 6,
            ],
            [
                'category' => 'Products',
                'question' => 'Are your products tested on animals?',
                'answer' => 'Never. All VANRITI products are certified cruelty-free and manufactured in GMP-certified facilities. Most of our formulations are vegan and made without parabens or sulphates.',
                'sort_order' => 7,
            ],
            [
                'category' => 'Products',
                'question' => 'Are your products suitable for sensitive skin?',
                'answer' => 'Our formulations are created with gentle botanicals and are dermatologically tested. If you have known sensitivities, we recommend a patch test on the inner arm before use.',
                'sort_order' => 8,
            ],
            [
                'category' => 'Account & Support',
                'question' => 'How do I contact customer support?',
                'answer' => 'You can reach our support team at care@vanriti.com or on WhatsApp at +91-98765-43210, Monday to Saturday, 9 AM to 7 PM IST.',
                'sort_order' => 9,
            ],
        ];

        foreach ($faqs as $index => $faq) {
            Faq::updateOrCreate(
                ['question' => $faq['question']],
                [
                    'answer' => $faq['answer'],
                    'category' => $faq['category'],
                    'sort_order' => $faq['sort_order'],
                    'status' => 'active',
                ]
            );
        }
    }
}