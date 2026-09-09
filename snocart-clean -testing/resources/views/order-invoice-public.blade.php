<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Invoice #{{ $order->id }}</title>
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/theme.minc619.css') }}">
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('public/assets/admin/css/custom.css') }}">
    <style>
        body { background: #f5f5f5; padding: 16px; }
        .non-printable { display: none !important; }
        .print--invoice { background: #fff; border-radius: 8px; box-shadow: 0 2px 12px rgba(0,0,0,.1); padding: 16px; }
    </style>
</head>
<body>
@include('admin-views.order.partials._invoice')
</body>
</html>
