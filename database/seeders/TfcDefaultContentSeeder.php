<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Tfc\TfcSetting;
use App\Models\Tfc\TfcPage;
use App\Models\Tfc\TfcMenu;
use App\Models\Tfc\TfcBanner;

class TfcDefaultContentSeeder extends Seeder
{
    /**
     * Run the database seeds for TFC Sales & Marketing.
     */
    public function run(): void
    {
        // 1. Permission for TFC Admin
        $permissionId = DB::table('permissions')->where('slug', 'manage-tfc')->value('id');
        if (!$permissionId) {
            $permissionId = DB::table('permissions')->insertGetId([
                'name' => 'Manage TFC Website',
                'group' => 'content',
                'slug' => 'manage-tfc',
                'description' => 'Manage TFC Sales & Marketing website content, pages, blogs, banners, menus, inquiries and grievance redressal.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Attach to Super Admin (role id 1) and Manager (role id 2)
        foreach ([1, 2] as $roleId) {
            $roleExists = DB::table('roles')->where('id', $roleId)->exists();
            if ($roleExists) {
                $alreadyAttached = DB::table('permission_role')
                    ->where('role_id', $roleId)
                    ->where('permission_id', $permissionId)
                    ->exists();

                if (!$alreadyAttached) {
                    DB::table('permission_role')->insert([
                        'role_id' => $roleId,
                        'permission_id' => $permissionId,
                    ]);
                }
            }
        }

        // 2. Default Settings
        $defaultSettings = [
            // General Corporate Info
            'company_name' => ['TFC Sales & Marketing', 'general'],
            'company_tagline' => ['CONNECTING PRODUCTS. CREATING POSSIBILITIES.', 'general'],
            'logo_url' => ['https://eduse-uploadedfiles.s3.ap-south-1.amazonaws.com/media/Ti0GRLXRJGYEbWLLfsuTPg7dcEhK04S2HIxxerIH.png', 'general'],
            'contact_email' => ['contact@tfcsalesmarketing.com', 'contact'],
            'support_phone' => ['+91 98765 43210', 'contact'],
            'office_address' => ['TFC Sales & Marketing Corporate Office, India', 'contact'],
            
            // Social Links
            'facebook_url' => ['https://facebook.com', 'social'],
            'instagram_url' => ['https://instagram.com', 'social'],
            'linkedin_url' => ['https://linkedin.com', 'social'],
            'youtube_url' => ['https://youtube.com', 'social'],

            // Statutory Disclosures (Flagged as UNVERIFIED DRAFT until provided by entity owner)
            'statutory_status' => ['DRAFT_PENDING_CONFIRMATION', 'statutory'],
            'cin_or_llpin' => ['[PENDING CORPORATE REGISTRATION CONFIRMATION]', 'statutory'],
            'pan_number' => ['[PENDING VERIFICATION]', 'statutory'],
            'gstin_number' => ['[PENDING VERIFICATION]', 'statutory'],
            'registered_office' => ['[CONFIRMATION REQUIRED - OFFICIAL REGISTERED OFFICE ADDRESS]', 'statutory'],
            
            // Nodal & Grievance Officers (Rule 5 & Rule 7, Direct Selling Rules 2021)
            // Left strictly empty/unassigned per prompt instruction #1: Do not publish placeholder information
            'nodal_officer_name' => ['', 'statutory'],
            'nodal_officer_designation' => ['Nodal Officer (Rule 5(6), Direct Selling Rules 2021)', 'statutory'],
            'nodal_officer_email' => ['', 'statutory'],
            'nodal_officer_phone' => ['', 'statutory'],
            
            'grievance_officer_name' => ['', 'statutory'],
            'grievance_officer_designation' => ['Grievance Redressal Officer (Rule 7, Direct Selling Rules 2021)', 'statutory'],
            'grievance_officer_email' => ['', 'statutory'],
            'grievance_officer_phone' => ['', 'statutory'],
            'grievance_officer_address' => ['', 'statutory'],

            'direct_selling_helpline' => ['+91 (Helpline pending corporate confirmation)', 'statutory'],
            'statutory_notice' => ['Direct selling involves sale of genuine products. TFC prohibits pyramid/money circulation schemes and does not charge any registration or recruitment fee.', 'statutory'],
        ];

        foreach ($defaultSettings as $key => [$value, $group]) {
            TfcSetting::firstOrCreate(
                ['key' => $key],
                ['value' => $value, 'group' => $group]
            );
        }

        // 3. Draft Statutory Disclosures and Legal Policy Pages (Kept in 'draft' per safeguards #1, #2, #8)
        $statutoryPages = [
            [
                'title' => 'Statutory Compliance & Direct Selling Disclosures',
                'slug' => 'statutory-disclosures',
                'excerpt' => 'Mandatory statutory declarations under the Consumer Protection (Direct Selling) Rules, 2021 and 2023 Amendment.',
                'content' => '<h2>Statutory Disclosures under Consumer Protection (Direct Selling) Rules, 2021</h2>
<div class="alert alert-warning">
    <strong>Notice:</strong> This document is in official DRAFT state pending formal corporate filing and officer notification verification.
</div>
<h3>1. Direct Selling Entity Status</h3>
<p>TFC Sales & Marketing is established to market and distribute high quality consumer wellness and personal care products through genuine direct seller partnerships in compliance with Rule 3(1)(f) and Rule 5 of the Consumer Protection (Direct Selling) Rules, 2021 (as amended 2023).</p>

<h3>2. Prohibition of Pyramid and Money Circulation Schemes</h3>
<p>In accordance with Rule 5(1)(c) and Rule 10, TFC strictly prohibits any scheme where benefits are derived from mere recruitment or enrolment of other participants. Compensation and commissions are earned strictly through the sale of legitimate consumer goods.</p>

<h3>3. No Enrolment or Joining Fees</h3>
<p>In adherence to statutory mandates, TFC does not charge any registration fee, training fee, or compulsory starter-kit purchase requirement for individuals wishing to become independent direct sellers.</p>

<h3>4. Nodal Officer & 24x7 Government Liaison</h3>
<p>Pursuant to Rule 5(6), TFC designates a Nodal Officer for coordination with law enforcement authorities and official regulatory bodies.</p>
<p><em>[Officer details pending formal notification — to be published upon administrative verification]</em></p>

<h3>5. Consumer Grievance Redressal Mechanism</h3>
<p>Pursuant to Rule 7, TFC maintains an active online grievance redressal portal. Every complaint is formally acknowledged with a unique ticket reference number within forty-eight (48) hours of receipt and addressed within thirty (30) days.</p>',
                'status' => 'draft',
                'sort_order' => 1,
            ],
            [
                'title' => 'Direct Seller Agreement & Code of Conduct',
                'slug' => 'direct-seller-agreement',
                'excerpt' => 'Terms governing independent direct sellers, code of ethics, honest representation, and marketing guidelines.',
                'content' => '<h2>Direct Seller Agreement & Ethical Code of Conduct</h2>
<div class="alert alert-warning">
    <strong>Draft State:</strong> This agreement is currently in draft format and subject to final legal counsel review.
</div>
<h3>1. Independent Contractor Status</h3>
<p>Direct Sellers associated with TFC operate as independent contractors and not as employees, franchisees, or legal partners. All business activities must be carried out ethically in accordance with statutory consumer protection laws.</p>

<h3>2. Fair Presentation and Honest Claims</h3>
<p>Direct Sellers shall make only truthful representations regarding products, pricing, and potential earnings. Exaggerated therapeutic claims or promises of guaranteed fixed incomes are strictly prohibited.</p>

<h3>3. Cooling-Off Period and Buy-Back Terms</h3>
<p>In compliance with statutory direct selling principles, direct sellers and retail customers are entitled to a cooling-off period to return unopened, marketable products. Details regarding return procedures and buy-back terms are subject to corporate confirmation.</p>',
                'status' => 'draft',
                'sort_order' => 2,
            ],
            [
                'title' => 'Grievance Redressal Policy',
                'slug' => 'grievance-redressal-policy',
                'excerpt' => 'Consumer grievance procedure per Rule 7 of Consumer Protection (Direct Selling) Rules, 2021.',
                'content' => '<h2>Grievance Redressal Policy</h2>
<div class="alert alert-warning">
    <strong>Draft State:</strong> Grievance Officer appointment details are currently under confirmation.
</div>
<h3>Statutory Timelines</h3>
<ul>
    <li><strong>Acknowledgment:</strong> Within 48 hours of ticket submission, with a unique tracking ID.</li>
    <li><strong>Resolution:</strong> Formal redressal within 30 days of receipt.</li>
</ul>
<h3>How to Lodge a Complaint</h3>
<p>Consumers may submit their grievance through our online Grievance Portal or write directly to our designated Grievance Officer.</p>',
                'status' => 'draft',
                'sort_order' => 3,
            ],
            [
                'title' => 'Cooling-Off & Buy-Back Policy',
                'slug' => 'cooling-off-buyback-policy',
                'excerpt' => 'Product return, cancellation rights, and inventory buy-back provisions.',
                'content' => '<h2>Cooling-Off & Buy-Back Policy</h2>
<div class="alert alert-warning">
    <strong>Draft State:</strong> Awaiting confirmation of commercial buy-back deduction thresholds.
</div>
<p>TFC provides a fair cooling-off period allowing direct sellers and consumers to cancel purchase orders and return goods in merchantable condition per applicable direct-selling regulations.</p>',
                'status' => 'draft',
                'sort_order' => 4,
            ],
            [
                'title' => 'Privacy Policy',
                'slug' => 'privacy-policy',
                'excerpt' => 'Data handling, privacy practices, and compliance with the Digital Personal Data Protection Act.',
                'content' => '<h2>TFC Sales & Marketing Privacy Policy</h2>
<div class="alert alert-warning">
    <strong>Draft State:</strong> Under legal verification for DPDP compliance.
</div>
<p>TFC Sales & Marketing respects user privacy and complies with applicable Indian data protection principles.</p>',
                'status' => 'draft',
                'sort_order' => 5,
            ],
            [
                'title' => 'Terms & Conditions',
                'slug' => 'terms-and-conditions',
                'excerpt' => 'Website terms of use, intellectual property, and governing jurisdiction.',
                'content' => '<h2>Terms and Conditions of Website Use</h2>
<div class="alert alert-warning">
    <strong>Draft State:</strong> Awaiting company registration and jurisdiction confirmation.
</div>
<p>By accessing the TFC Sales & Marketing website, you agree to comply with and be bound by the terms and conditions outlined herein.</p>',
                'status' => 'draft',
                'sort_order' => 6,
            ],
        ];

        foreach ($statutoryPages as $page) {
            TfcPage::firstOrCreate(
                ['slug' => $page['slug']],
                $page
            );
        }

        // 4. Default Navigation Menus
        $headerMenus = [
            ['title' => 'Home', 'url' => '/', 'sort_order' => 1],
            ['title' => 'About TFC', 'url' => '/about-us', 'sort_order' => 2],
            ['title' => 'VANRITI Brand', 'url' => '/vanriti-brand', 'sort_order' => 3],
            ['title' => 'Products', 'url' => '/products', 'sort_order' => 4],
            ['title' => 'The Opportunity', 'url' => '/opportunity', 'sort_order' => 5],
            ['title' => 'How It Works', 'url' => '/how-it-works', 'sort_order' => 6],
            ['title' => 'Grievance', 'url' => '/grievance', 'sort_order' => 7],
            ['title' => 'Contact', 'url' => '/contact-us', 'sort_order' => 8],
        ];

        foreach ($headerMenus as $item) {
            TfcMenu::firstOrCreate(
                ['location' => 'header', 'title' => $item['title']],
                [
                    'url' => $item['url'],
                    'sort_order' => $item['sort_order'],
                    'is_active' => true,
                    'open_new_tab' => false,
                ]
            );
        }

        // Footer Quick Links
        $footerCol1 = [
            ['title' => 'About Us', 'url' => '/about-us', 'sort_order' => 1],
            ['title' => 'VANRITI Brand Story', 'url' => '/vanriti-brand', 'sort_order' => 2],
            ['title' => 'Product Catalogue', 'url' => '/products', 'sort_order' => 3],
            ['title' => 'Direct Selling Opportunity', 'url' => '/opportunity', 'sort_order' => 4],
            ['title' => 'How It Works', 'url' => '/how-it-works', 'sort_order' => 5],
        ];
        foreach ($footerCol1 as $item) {
            TfcMenu::firstOrCreate(
                ['location' => 'footer_col1', 'title' => $item['title']],
                [
                    'url' => $item['url'],
                    'sort_order' => $item['sort_order'],
                    'is_active' => true,
                    'open_new_tab' => false,
                ]
            );
        }

        // Footer Support & Redressal Links
        $footerCol2 = [
            ['title' => 'Consumer Grievance Redressal', 'url' => '/grievance', 'sort_order' => 1],
            ['title' => 'Track Grievance Status', 'url' => '/grievance/track', 'sort_order' => 2],
            ['title' => 'Frequently Asked Questions', 'url' => '/faqs', 'sort_order' => 3],
            ['title' => 'Contact Support', 'url' => '/contact-us', 'sort_order' => 4],
            ['title' => 'Official VANRITI Store', 'url' => 'https://vanriti.com', 'sort_order' => 5, 'open_new_tab' => true],
        ];
        foreach ($footerCol2 as $item) {
            TfcMenu::firstOrCreate(
                ['location' => 'footer_col2', 'title' => $item['title']],
                [
                    'url' => $item['url'],
                    'sort_order' => $item['sort_order'],
                    'is_active' => true,
                    'open_new_tab' => $item['open_new_tab'] ?? false,
                ]
            );
        }

        // Footer Legal & Compliance Links
        $footerLegal = [
            ['title' => 'Statutory Disclosures (Draft)', 'url' => '/policy/statutory-disclosures', 'sort_order' => 1],
            ['title' => 'Code of Conduct (Draft)', 'url' => '/policy/direct-seller-agreement', 'sort_order' => 2],
            ['title' => 'Grievance Policy (Draft)', 'url' => '/policy/grievance-redressal-policy', 'sort_order' => 3],
            ['title' => 'Cooling-off & Buy-back (Draft)', 'url' => '/policy/cooling-off-buyback-policy', 'sort_order' => 4],
            ['title' => 'Privacy Policy (Draft)', 'url' => '/policy/privacy-policy', 'sort_order' => 5],
            ['title' => 'Terms & Conditions (Draft)', 'url' => '/policy/terms-and-conditions', 'sort_order' => 6],
        ];
        foreach ($footerLegal as $item) {
            TfcMenu::firstOrCreate(
                ['location' => 'footer_legal', 'title' => $item['title']],
                [
                    'url' => $item['url'],
                    'sort_order' => $item['sort_order'],
                    'is_active' => true,
                    'open_new_tab' => false,
                ]
            );
        }

        // 5. Default Banners
        TfcBanner::firstOrCreate(
            ['title' => 'Empowering Lives Through Quality Products'],
            [
                'subtitle' => 'Connecting high quality Ayurvedic, Herbal & Wellness products with passionate entrepreneurs across India.',
                'image' => 'https://eduse-uploadedfiles.s3.ap-south-1.amazonaws.com/media/Ti0GRLXRJGYEbWLLfsuTPg7dcEhK04S2HIxxerIH.png',
                'link' => '/opportunity',
                'link_text' => 'Explore The Opportunity',
                'position' => 'hero',
                'sort_order' => 1,
                'status' => 'active',
            ]
        );
    }
}
