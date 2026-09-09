@extends('layouts.landing.app')

@section('title',translate('messages.terms_and_condition'))

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
    .page-body {
        padding: 60px 0 80px;
        background: #fff;
    }
    .page-body-content {
        max-width: 860px;
        margin: 0 auto;
        font-size: 16px;
        color: #444;
        line-height: 1.8;
    }
    .page-body-content h1, .page-body-content h2, .page-body-content h3, .page-body-content h4 {
        color: #0a0a0a;
        font-weight: 700;
        margin-top: 32px;
        margin-bottom: 12px;
    }
    .page-body-content h2 { font-size: 28px; }
    .page-body-content h3 { font-size: 22px; }
    .page-body-content h4 { font-size: 18px; }
    .page-body-content p { margin-bottom: 16px; }
    .page-body-content ul, .page-body-content ol { padding-left: 24px; margin-bottom: 16px; }
    .page-body-content li { margin-bottom: 8px; }
    .page-body-content a { color: var(--base-1, #d61b66); text-decoration: underline; }
    .page-body-content table { width: 100%; border-collapse: collapse; margin: 20px 0; }
    .page-body-content table th, .page-body-content table td { padding: 12px 16px; border: 1px solid #e5e5e5; text-align: left; }
    .page-body-content table th { background: #fafafa; font-weight: 600; }
    .page-body-content img { max-width: 100%; height: auto; border-radius: 12px; margin: 20px 0; }

    @media (max-width: 768px) {
        .page-hero { padding: 50px 0 40px; }
        .page-hero h1 { font-size: 28px; }
        .page-hero .lead { font-size: 15px; }
        .page-body { padding: 40px 0 60px; }
        .page-body-content { font-size: 15px; }
    }
</style>
@endpush

@section('content')
    <section class="page-hero">
        <div class="container">
            <div class="page-hero-inner">
                <div class="since-badge">
                    <i class="fas fa-file-contract"></i>
                    <span>Trusted Platform — Since 2021</span>
                </div>
                <h1>{{translate("messages.Terms_And")}} {{translate("messages.Conditions")}}</h1>
                <p class="lead">Please read these terms carefully before using our platform. By accessing or using Snocart, you agree to be bound by these terms.</p>
            </div>
        </div>
    </section>

    <section class="page-body">
        <div class="container">
            <div class="page-body-content">
                {!! $data !!}
            </div>
        </div>
    </section>
@endsection
