<?php

namespace App\Enums\ExportFileNames\Admin;

enum DeliveryMan
{
    const EXPORT_CSV = 'DeliveryMans.csv';
    const EXPORT_XLSX = 'DeliveryMans.xlsx';
    const REVIEW_EXPORT_CSV = 'DeliveryManReviews.csv';
    const REVIEW_EXPORT_XLSX = 'DeliveryManReviews.xlsx';
    const DAY_CLOSE_EXPORT_CSV = 'DeliveryManDayClose.csv';
    const DAY_CLOSE_EXPORT_XLSX = 'DeliveryManDayClose.xlsx';
}
