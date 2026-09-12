<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Seeder;

class DisclaimerPolicySeeder extends Seeder
{
    public function run(): void
    {
        $storeName = setting('store_name', 'VANRITI');
        $storeEmail = setting('store_email', '');
        $storePhone = setting('store_phone', '');

        $content = <<<HTML
<div class="vr-section" style="background:var(--vr-cream);">
    <div class="container">
        <nav class="vr-breadcrumb mb-4" aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="/">Home</a></li>
                <li class="breadcrumb-item active" aria-current="page">Disclaimer</li>
            </ol>
        </nav>
        <div class="vr-kicker mb-3">PURE BY NATURE</div>
        <h1 class="vr-section-title mb-4">Disclaimer</h1>
        <div class="d-grid gap-4 small">
            <section>
                <h5 class="fw-bold mb-2">1. General information</h5>
                <p class="mb-0">The information provided on {$storeName} is for general informational purposes only. While we strive to keep the details on our products accurate and up to date, we make no representations or warranties of any kind, express or implied, about the completeness, accuracy, reliability, suitability or availability of the information, products or services on this site.</p>
            </section>
            <section>
                <h5 class="fw-bold mb-2">2. Not medical advice</h5>
                <p class="mb-0">Our products and content are not intended to diagnose, treat, cure or prevent any disease. The statements on this website have not been evaluated by the FDA or any other medical regulatory authority. Always consult a qualified healthcare professional before starting any new skincare or wellness routine, especially if you are pregnant, nursing, taking medication or have a medical condition.</p>
            </section>
            <section>
                <h5 class="fw-bold mb-2">3. Product use</h5>
                <p class="mb-0">Please read all product labels, ingredients and instructions carefully before use. Conduct a patch test before applying any new product. If irritation or an allergic reaction occurs, discontinue use immediately and consult your physician. Results may vary from person to person.</p>
            </section>
            <section>
                <h5 class="fw-bold mb-2">4. External links</h5>
                <p class="mb-0">Our website may contain links to external websites that are not provided or maintained by us. We do not control and are not responsible for the content, privacy policies or practices of any third-party websites.</p>
            </section>
            <section>
                <h5 class="fw-bold mb-2">5. Limitation of liability</h5>
                <p class="mb-0">In no event shall {$storeName} be liable for any loss or damage, including without limitation, indirect or consequential loss or damage, arising out of or in connection with the use of this website or the products purchased through it.</p>
            </section>
            <section>
                <h5 class="fw-bold mb-2">6. Contact</h5>
                <p class="mb-0">If you have any questions about this disclaimer, reach us at {$storeEmail} or call {$storePhone}.</p>
            </section>
        </div>
    </div>
</div>
HTML;

        Page::updateOrCreate(
            ['slug' => 'disclaimer'],
            [
                'title' => 'Disclaimer',
                'status' => 'published',
                'meta_title' => "Disclaimer - {$storeName}",
                'meta_description' => "Important disclaimers and notices regarding products and information on {$storeName}.",
                'content' => $content,
            ]
        );
    }
}