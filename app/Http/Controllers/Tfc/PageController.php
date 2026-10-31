<?php

namespace App\Http\Controllers\Tfc;

use App\Http\Controllers\Controller;
use App\Models\Tfc\TfcPage;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PageController extends Controller
{
    /**
     * Display a corporate direct selling policy.
     */
    public function show(string $slug, Request $request): View
    {
        $slug = strtolower(trim($slug));
        $page = TfcPage::where('slug', $slug)->first();

        if (!$page) {
            $page = new TfcPage();
            $page->slug = $slug;
            $defaults = $this->getDefaultPolicy($slug);
            $page->title = $defaults['title'];
            $page->content = $defaults['content'];
            $page->updated_at = now();
        }

        return view('tfc.pages.show', compact('page'));
    }

    /**
     * Professional Standard Direct Selling Policy Templates.
     */
    protected function getDefaultPolicy(string $slug): array
    {
        return match ($slug) {
            'terms-of-service', 'terms-and-conditions' => [
                'title' => 'Terms & Conditions',
                'content' => '
                    <h4>1. Introduction</h4>
                    <p>Welcome to TFC Sales & Marketing. By accessing our website, browsing our product offerings, or registering as an Independent Direct Seller, you agree to comply with and be bound by the following terms and conditions.</p>
                    <h4>2. Direct Selling Model</h4>
                    <p>TFC Sales & Marketing is a bona fide direct selling enterprise. Enrollment as an Independent Direct Seller is free of charge with zero mandatory purchases or enrollment fees. Earnings are generated strictly through the sale of genuine consumer products to consumers.</p>
                    <h4>3. Product Orders & Deliveries</h4>
                    <p>All products ordered through TFC Sales & Marketing undergo rigorous batch quality inspection and are delivered across India via trackable logistics partners.</p>
                    <h4>4. Code of Conduct</h4>
                    <p>Independent Direct Sellers must accurately represent products, present the binary compensation plan truthfully without exaggerations, and adhere to ethical business practices.</p>
                ',
            ],
            'privacy-policy' => [
                'title' => 'Privacy Policy',
                'content' => '
                    <h4>1. Information Collection</h4>
                    <p>TFC Sales & Marketing respects your privacy. We collect personal identification details such as name, phone number, address, and email strictly for account administration, order fulfillment, KYC compliance, and team communication.</p>
                    <h4>2. Use of Information</h4>
                    <p>Your details are utilized to process orders, facilitate doorstep delivery, compute distributor incentives, and provide customer support. We do not sell or trade your personal information to unauthorized third parties.</p>
                    <h4>3. Data Security</h4>
                    <p>We maintain strict administrative, technical, and physical safeguards to protect confidential personal and financial data against unauthorized access, alteration, or disclosure.</p>
                ',
            ],
            'return-and-refund-policy', 'refund-policy' => [
                'title' => 'Return & Refund Policy',
                'content' => '
                    <h4>1. Customer Satisfaction Guarantee</h4>
                    <p>TFC Sales & Marketing is committed to superior product quality. If a consumer is not satisfied with any product purchased through an authorized channel, they may request an exchange or refund under our cooling-off period policy.</p>
                    <h4>2. Eligibility for Return</h4>
                    <p>Products must be returned within 30 days of purchase in their original packaging, accompanied by the original invoice or purchase receipt.</p>
                    <h4>3. Refund Processing</h4>
                    <p>Approved refunds are processed within 7 business days to the customer\'s original payment method or designated bank account.</p>
                ',
            ],
            'direct-seller-code-of-ethics', 'code-of-ethics', 'code-of-conduct' => [
                'title' => 'Direct Seller Code of Ethics',
                'content' => '
                    <h4>1. Truth in Promotion</h4>
                    <p>Independent Direct Sellers must present products truthfully, without misleading therapeutic claims or unverified guarantees.</p>
                    <h4>2. Transparent Presentation</h4>
                    <p>When sharing the TFC business opportunity, direct sellers must clearly explain that rewards are derived from retail sales and team volume, not mere recruitment.</p>
                    <h4>3. Mutual Respect & Leadership</h4>
                    <p>Direct sellers are expected to conduct themselves professionally, respect consumer preferences, and support their team members through mentorship and constructive training.</p>
                ',
            ],
            default => [
                'title' => ucwords(str_replace('-', ' ', $slug)),
                'content' => '
                    <p>For more information regarding this policy or official documentation, please reach out to TFC Corporate Relations at <strong>contact@tfcsalesmarketing.com</strong>.</p>
                ',
            ],
        };
    }
}
