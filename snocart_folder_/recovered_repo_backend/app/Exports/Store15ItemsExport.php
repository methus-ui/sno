<?php

namespace App\Exports;

use App\Models\Item;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class Store15ItemsExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return Item::where('store_id', 15)
            ->orderByDesc('id')
            ->get([
                'id',
                'name',
                'description',
                'image',
                'category_id',
                'category_ids',
                'variations',
                'add_ons',
                'attributes',
                'choice_options',
                'price',
                'tax',
                'tax_type',
                'discount',
                'discount_type',
                'available_time_starts',
                'available_time_ends',
                'veg',
                'status',
                'store_id',
                'created_at',
                'updated_at',
                'order_count',
                'avg_rating',
                'rating_count',
                'rating',
                'module_id',
                'stock',
                'unit_id',
                'images',
                'food_variations',
                'slug',
                'recommended',
                'organic',
                'maximum_cart_quantity',
                'barcode',
                'rack',
                'row',
                'is_approved',
                'is_halal'
            ]);
    }

    public function map($item): array
    {
        return [
            $item->id,
            $item->name,
            $item->description,
            $item->image,
            $item->category_id,
            $item->category_ids,
            $item->variations,
            $item->add_ons,
            $item->attributes,
            $item->choice_options,
            $item->price,
            $item->tax,
            $item->tax_type,
            $item->discount,
            $item->discount_type,
            $item->available_time_starts,
            $item->available_time_ends,
            $item->veg,
            $item->status,
            $item->store_id,
            $item->created_at,
            $item->updated_at,
            $item->order_count,
            $item->avg_rating,
            $item->rating_count,
            $item->rating,
            $item->module_id,
            $item->stock,
            $item->unit_id,
            $item->images,
            $item->food_variations,
            $item->slug,
            $item->recommended,
            $item->organic,
            $item->maximum_cart_quantity,
            $item->barcode,
            $item->rack,
            $item->row,
            $item->is_approved,
            $item->is_halal,
        ];
    }

    public function headings(): array
    {
        return [
            'ID',
            'Name',
            'Description',
            'Image',
            'Category ID',
            'Category IDs',
            'Variations',
            'Add Ons',
            'Attributes',
            'Choice Options',
            'Price',
            'Tax',
            'Tax Type',
            'Discount',
            'Discount Type',
            'Available Time Starts',
            'Available Time Ends',
            'Veg',
            'Status',
            'Store ID',
            'Created At',
            'Updated At',
            'Order Count',
            'Avg Rating',
            'Rating Count',
            'Rating',
            'Module ID',
            'Stock',
            'Unit ID',
            'Images',
            'Food Variations',
            'Slug',
            'Recommended',
            'Organic',
            'Maximum Cart Quantity',
            'Barcode',
            'Storage Rack Location',
            'Storage Row Location',
            'Is Approved',
            'Is Halal',
        ];
    }
}
