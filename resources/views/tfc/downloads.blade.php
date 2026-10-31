@extends('tfc.layouts.app')

@section('title', 'Downloads & Resources | ' . tfc_setting('company_name', 'TFC Sales & Marketing'))
@section('meta_description', 'Download official TFC Sales & Marketing presentations, product catalogues, and direct seller guidelines.')

@section('content')
<section class="tfc-page-header py-5" style="background: linear-gradient(180deg, #FFFFFF 0%, #F8FAFC 100%); border-bottom: 1px solid rgba(0,0,0,0.06);">
    <div class="container text-center py-2">
        <span class="badge bg-warning text-dark fw-bold mb-2 px-3 py-1.5" style="font-size: 0.8rem; letter-spacing: 0.5px;">OFFICIAL RESOURCES</span>
        <h1 class="display-5 fw-bold text-dark mb-2">Downloads &amp; Business Collaterals</h1>
        <p class="text-muted lead mb-0" style="max-width: 600px; margin: 0 auto;">
            Access authentic presentations, product catalogues, and distributor guidelines to help grow your direct selling enterprise.
        </p>
    </div>
</section>

<section class="py-5" style="background-color: #FFFFFF;">
    <div class="container py-3">
        <div class="row g-4">
            {{-- Item 1: Business Presentation --}}
            <div class="col-12 col-md-6 col-lg-4">
                <div class="p-4 rounded-4 h-100 border shadow-sm d-flex flex-column" style="background: #FAFAFA; border-radius: 18px;">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="p-2.5 rounded-3" style="background: rgba(37, 99, 235, 0.1); color: #2563EB;">
                            <i class="ri-file-ppt-2-fill fs-4"></i>
                        </span>
                        <span class="badge bg-primary bg-opacity-10 text-primary">PDF</span>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">TFC Business Presentation</h5>
                    <p class="text-muted small mb-4 flex-grow-1" style="line-height: 1.6;">
                        Complete overview of the TFC direct selling business model, dual-team synergy, ranks, and compensation structure.
                    </p>
                    <a href="{{ tfc_route('business-plan') }}" class="btn btn-outline-dark w-100 fw-semibold d-flex align-items-center justify-content-center gap-2">
                        <i class="ri-download-2-line"></i>
                        <span>Download Presentation</span>
                    </a>
                </div>
            </div>

            {{-- Item 2: Product Catalogue --}}
            <div class="col-12 col-md-6 col-lg-4">
                <div class="p-4 rounded-4 h-100 border shadow-sm d-flex flex-column" style="background: #FAFAFA; border-radius: 18px;">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="p-2.5 rounded-3" style="background: rgba(16, 185, 129, 0.1); color: #10B981;">
                            <i class="ri-file-list-3-fill fs-4"></i>
                        </span>
                        <span class="badge bg-success bg-opacity-10 text-success">PDF</span>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Product Catalogue</h5>
                    <p class="text-muted small mb-4 flex-grow-1" style="line-height: 1.6;">
                        Detailed product catalogue containing ingredients, benefits, MRP, and dosage recommendations for our complete formulations line.
                    </p>
                    <a href="{{ tfc_route('products') }}" class="btn btn-outline-dark w-100 fw-semibold d-flex align-items-center justify-content-center gap-2">
                        <i class="ri-download-2-line"></i>
                        <span>View &amp; Download</span>
                    </a>
                </div>
            </div>

            {{-- Item 3: Direct Seller Code of Ethics --}}
            <div class="col-12 col-md-6 col-lg-4">
                <div class="p-4 rounded-4 h-100 border shadow-sm d-flex flex-column" style="background: #FAFAFA; border-radius: 18px;">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <span class="p-2.5 rounded-3" style="background: rgba(245, 158, 11, 0.15); color: #D49B24;">
                            <i class="ri-shield-check-fill fs-4"></i>
                        </span>
                        <span class="badge bg-warning bg-opacity-20 text-dark">PDF</span>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Direct Seller Code of Conduct</h5>
                    <p class="text-muted small mb-4 flex-grow-1" style="line-height: 1.6;">
                        Official guidelines, ethical compliance rules, and best practices for independent direct sellers representing TFC Sales &amp; Marketing.
                    </p>
                    <a href="{{ tfc_route('policy', 'direct-seller-code-of-ethics') }}" class="btn btn-outline-dark w-100 fw-semibold d-flex align-items-center justify-content-center gap-2">
                        <i class="ri-file-text-line"></i>
                        <span>Read Code of Ethics</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
