@extends('layouts.landing.app')

@section('title',translate('messages.about_us'))

@push('css_or_js')
<style>
    .page-hero {
        padding: 80px 0 60px;
        background: linear-gradient(135deg, #f8f9fa 0%, #fff 50%, #fef2f6 100%);
        position: relative;
        overflow: hidden;
    }
    .page-hero::before {
        content: '';
        position: absolute;
        top: -100px;
        right: -100px;
        width: 300px;
        height: 300px;
        background: radial-gradient(circle, rgba(214,27,102,0.06) 0%, transparent 70%);
        border-radius: 50%;
    }
    .page-hero-inner {
        max-width: 800px;
        margin: 0 auto;
        text-align: center;
    }
    .page-hero h1 {
        font-size: 42px;
        font-weight: 800;
        color: #0a0a0a;
        margin-bottom: 20px;
        line-height: 1.2;
        letter-spacing: -0.02em;
    }
    .page-hero .lead {
        font-size: 18px;
        color: #666;
        line-height: 1.7;
        max-width: 650px;
        margin: 0 auto;
    }
    .since-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 16px;
        background: linear-gradient(135deg, rgba(214,27,102,0.1), rgba(233,74,127,0.05));
        border: 1px solid rgba(214,27,102,0.2);
        border-radius: 999px;
        color: var(--base-1, #d61b66);
        font-size: 14px;
        font-weight: 600;
        margin-bottom: 20px;
    }
    .about-body {
        padding: 60px 0 80px;
        background: #fff;
    }
    .about-body-content {
        max-width: 860px;
        margin: 0 auto;
        font-size: 16px;
        color: #444;
        line-height: 1.8;
    }
    .about-body-content h2, .about-body-content h3, .about-body-content h4 {
        color: #0a0a0a;
        font-weight: 700;
        margin-top: 32px;
        margin-bottom: 12px;
    }
    .about-body-content h2 { font-size: 28px; }
    .about-body-content h3 { font-size: 22px; }
    .about-body-content h4 { font-size: 18px; }
    .about-body-content p { margin-bottom: 16px; }
    .about-body-content ul, .about-body-content ol { padding-left: 24px; margin-bottom: 16px; }
    .about-body-content li { margin-bottom: 8px; }
    .about-body-content img { max-width: 100%; height: auto; border-radius: 12px; margin: 20px 0; }
    .about-body-content a { color: var(--base-1, #d61b66); text-decoration: underline; }

    .about-timeline {
        padding: 60px 0;
        background: #fff;
    }
    .timeline-grid {
        max-width: 800px;
        margin: 0 auto;
        position: relative;
    }
    .timeline-grid::before {
        content: '';
        position: absolute;
        left: 24px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: linear-gradient(180deg, var(--base-1, #d61b66), rgba(214,27,102,0.1));
    }
    .timeline-entry {
        display: flex;
        gap: 24px;
        margin-bottom: 32px;
        position: relative;
    }
    .timeline-dot {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--base-1, #d61b66), var(--base-2, #e94a7f));
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 800;
        font-size: 14px;
        flex-shrink: 0;
        z-index: 1;
    }
    .timeline-entry-content {
        background: #fafafa;
        border-radius: 16px;
        padding: 24px;
        flex: 1;
        border: 1px solid #e5e5e5;
    }
    .timeline-entry-content h3 {
        font-size: 18px;
        font-weight: 700;
        color: #0a0a0a;
        margin-bottom: 6px;
    }
    .timeline-entry-content p {
        font-size: 14px;
        color: #666;
        margin: 0;
        line-height: 1.6;
    }

    .about-values {
        padding: 60px 0;
        background: #fafafa;
    }
    .about-values-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 24px;
        max-width: 960px;
        margin: 40px auto 0;
    }
    .about-value-card {
        background: #fff;
        border-radius: 16px;
        padding: 32px 24px;
        text-align: center;
        border: 1px solid #e5e5e5;
        transition: all 0.3s ease;
    }
    .about-value-card:hover {
        box-shadow: 0 8px 24px rgba(0,0,0,0.08);
        transform: translateY(-4px);
    }
    .about-value-icon {
        width: 56px;
        height: 56px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        margin-bottom: 16px;
        background: linear-gradient(135deg, rgba(214,27,102,0.1), rgba(233,74,127,0.05));
        color: var(--base-1, #d61b66);
    }
    .about-value-card h3 { font-size: 18px; font-weight: 700; color: #0a0a0a; margin-bottom: 8px; }
    .about-value-card p { font-size: 14px; color: #666; line-height: 1.6; margin: 0; }
    .about-values .section-title { text-align: center; font-size: 32px; font-weight: 800; color: #0a0a0a; margin-bottom: 0; }

    @media (max-width: 768px) {
        .page-hero { padding: 50px 0 40px; }
        .page-hero h1 { font-size: 28px; }
        .page-hero .lead { font-size: 15px; }
        .about-values-grid { grid-template-columns: 1fr; max-width: 400px; }
        .about-body { padding: 40px 0 60px; }
        .about-body-content { font-size: 15px; }
        .timeline-grid::before { left: 24px; }
    }
</style>
@endpush

@section('content')
    <!-- Hero -->
    <section class="page-hero">
        <div class="container">
            <div class="page-hero-inner">
                <div class="since-badge">
                    <i class="fas fa-calendar-check"></i>
                    <span>In the Market Since 2021</span>
                </div>
                <h1>{{ $data_title ?? translate('About Us') }}</h1>
                <p class="lead">From a small idea in Srinagar to Kashmir's leading grocery delivery platform — learn about the journey, mission, and people behind Snocart.</p>
            </div>
        </div>
    </section>

    <!-- Dynamic Content from Admin -->
    <section class="about-body">
        <div class="container">
            <div class="about-body-content">
                {!! $data ?? '' !!}
            </div>
        </div>
    </section>

    <!-- Our Journey Timeline -->
    <section class="about-timeline">
        <div class="container">
            <h2 class="section-title" style="text-align:center; font-size:32px; font-weight:800; color:#0a0a0a; margin-bottom:40px;">Our Journey</h2>
            <div class="timeline-grid">
                <div class="timeline-entry">
                    <div class="timeline-dot">2021</div>
                    <div class="timeline-entry-content">
                        <h3>Founded in Srinagar</h3>
                        <p>Snocart was born with a simple mission — connect local kirana stores to customers through technology and enable fast, reliable grocery delivery in Kashmir.</p>
                    </div>
                </div>
                <div class="timeline-entry">
                    <div class="timeline-dot">2022</div>
                    <div class="timeline-entry-content">
                        <h3>Expanding Across Srinagar</h3>
                        <p>Onboarded hundreds of local store partners and delivery riders, establishing a strong hyperlocal network across key areas of Srinagar.</p>
                    </div>
                </div>
                <div class="timeline-entry">
                    <div class="timeline-dot">2023</div>
                    <div class="timeline-entry-content">
                        <h3>AI-Powered Operations</h3>
                        <p>Launched AI-driven vendor matching, smart routing, and demand prediction — cutting average delivery time to 25 minutes.</p>
                    </div>
                </div>
                <div class="timeline-entry">
                    <div class="timeline-dot">2024</div>
                    <div class="timeline-entry-content">
                        <h3>5,000+ Store Partners</h3>
                        <p>Reached a major milestone with 5,000+ kirana store partners and expanded into pharmacy, e-commerce, and multi-category delivery.</p>
                    </div>
                </div>
                <div class="timeline-entry">
                    <div class="timeline-dot">Now</div>
                    <div class="timeline-entry-content">
                        <h3>Kashmir's Leading Platform</h3>
                        <p>Serving thousands of daily orders, empowering local businesses, and building the future of hyperlocal commerce in Jammu & Kashmir.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Values -->
    <section class="about-values">
        <div class="container">
            <h2 class="section-title">Our Values</h2>
            <div class="about-values-grid">
                <div class="about-value-card">
                    <div class="about-value-icon"><i class="fas fa-heart"></i></div>
                    <h3>Community First</h3>
                    <p>We empower local businesses and serve our neighborhoods with care and commitment.</p>
                </div>
                <div class="about-value-card">
                    <div class="about-value-icon"><i class="fas fa-bolt"></i></div>
                    <h3>Speed & Reliability</h3>
                    <p>Fast deliveries you can count on, powered by smart technology and dedicated partners.</p>
                </div>
                <div class="about-value-card">
                    <div class="about-value-icon"><i class="fas fa-shield-alt"></i></div>
                    <h3>Trust & Quality</h3>
                    <p>Every product is sourced from verified local stores, ensuring freshness and quality.</p>
                </div>
            </div>
        </div>
    </section>
@endsection
