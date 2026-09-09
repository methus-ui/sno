<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Models\Item;
use App\Models\Store;
use App\Models\Category;
use App\Models\Translation;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProductGalleryController extends Controller
{
    /**
     * Browse product gallery with filters
     * GET /api/v1/vendor/product-gallery/browse
     */
    public function browse(Request $request)
    {
        $vendor = $request->vendor;
        $vendorStoreId = $vendor->stores[0]->id;
        $vendorStore = Store::find($vendorStoreId);

        $search = $request->input('search');
        $categoryId = $request->input('category_id');
        $moduleId = $request->input('module_id');
        $type = $request->input('type', 'all');
        $storeId = $request->input('store_id');
        $excludeMyProducts = $request->input('exclude_my_products', true);
        $page = $request->input('page', 1);
        $limit = $request->input('limit', 20);

        // Default to vendor's module if not specified
        $filterModuleId = $moduleId && $moduleId !== 'all' ? $moduleId : $vendorStore->module_id;

        // Build query
        $query = Item::with([
            'store:id,name,logo',
            'category:id,name',
            'translations'
        ])
        ->where('status', 1)
        ->where('is_approved', 1)
        ->where('module_id', $filterModuleId);

        // Exclude vendor's own products
        if ($excludeMyProducts) {
            $query->where('store_id', '!=', $vendorStoreId);
        }

        // Search filter
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%");
            });
        }

        // Category filter (includes subcategories)
        if ($categoryId && $categoryId !== 'all') {
            $query->whereHas('category', function($q) use ($categoryId) {
                $q->where('id', $categoryId)->orWhere('parent_id', $categoryId);
            });
        }

        // Type filter (veg/non-veg)
        if ($type && $type !== 'all') {
            if ($type === 'veg') {
                $query->where('veg', 1);
            } elseif ($type === 'non_veg') {
                $query->where('veg', 0);
            }
        }

        // Store filter
        if ($storeId) {
            $query->where('store_id', $storeId);
        }

        // Get total count for pagination
        $totalProducts = $query->count();
        $totalPages = ceil($totalProducts / $limit);

        // Paginate
        $products = $query->orderBy('order_count', 'desc')
            ->orderBy('avg_rating', 'desc')
            ->skip(($page - 1) * $limit)
            ->take($limit)
            ->get();

        // Check if products already exist in vendor's store
        $vendorProductNames = Item::where('store_id', $vendorStoreId)
            ->pluck('name')
            ->toArray();

        $productsData = $products->map(function($item) use ($vendorProductNames) {
            return [
                'id' => $item->id,
                'name' => $item->name,
                'description' => $item->description,
                'image' => $item->image_full_url,
                'images' => $item->images_full_url ?? [],
                'price' => (float) $item->price,
                'discount' => (float) $item->discount,
                'discount_type' => $item->discount_type,
                'category_id' => $item->category_id,
                'category_name' => $item->category?->name ?? '',
                'unit' => $item->unit_type ?? $item->unit,
                'stock' => $item->stock ?? 0,
                'source_store_id' => $item->store_id,
                'source_store_name' => $item->store?->name ?? '',
                'source_store_logo' => $item->store?->logo_full_url ?? asset('public/assets/admin/img/100x100/food-default-image.png'),
                'avg_rating' => (float) $item->avg_rating,
                'total_reviews' => $item->rating_count ?? 0,
                'order_count' => $item->order_count ?? 0,
                'veg' => $item->veg,
                'recommended' => $item->recommended,
                'organic' => $item->organic,
                'barcode' => $item->barcode,
                'can_replicate' => true,
                'already_in_my_store' => in_array($item->name, $vendorProductNames)
            ];
        });

        // Get filter options
        // Get modules
        $modules = \App\Models\Module::where('status', 1)
            ->select('id', 'module_name', 'icon')
            ->get();

        // Only show categories that have products (for the selected module)
        $categories = Category::where('status', 1)
            ->where('module_id', $filterModuleId)
            ->whereHas('products', function($q) {
                $q->where('status', 1)->where('is_approved', 1);
            })
            ->withCount(['products' => function($q) {
                $q->where('status', 1)->where('is_approved', 1);
            }])
            ->select('id', 'name', 'image', 'parent_id', 'module_id')
            ->orderBy('position')
            ->get();

        $stores = Store::where('status', 1)
            ->where('id', '!=', $vendorStoreId)
            ->select('id', 'name', 'logo')
            ->take(50)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'products' => $productsData,
                'pagination' => [
                    'current_page' => (int) $page,
                    'total_pages' => (int) $totalPages,
                    'total_products' => $totalProducts,
                    'per_page' => (int) $limit,
                    'has_more' => $page < $totalPages
                ],
                'filters' => [
                    'modules' => $modules->map(function($module) use ($filterModuleId) {
                        return [
                            'id' => $module->id,
                            'name' => $module->module_name,
                            'icon' => $module->icon_full_url ?? null,
                            'selected' => $module->id == $filterModuleId
                        ];
                    }),
                    'categories' => $categories->map(function($category) {
                        return [
                            'id' => $category->id,
                            'name' => $category->name,
                            'image' => $category->image_full_url ?? null,
                            'parent_id' => $category->parent_id,
                            'is_subcategory' => $category->parent_id !== null,
                            'products_count' => $category->products_count ?? 0
                        ];
                    }),
                    'stores' => $stores->map(function($store) {
                        return [
                            'id' => $store->id,
                            'name' => $store->name,
                            'logo' => $store->logo_full_url
                        ];
                    }),
                    'types' => [
                        [
                            'id' => 'all',
                            'name' => 'All Types',
                            'selected' => $type === 'all'
                        ],
                        [
                            'id' => 'veg',
                            'name' => 'Veg',
                            'selected' => $type === 'veg'
                        ],
                        [
                            'id' => 'non_veg',
                            'name' => 'Non-Veg',
                            'selected' => $type === 'non_veg'
                        ]
                    ]
                ]
            ]
        ]);
    }

    /**
     * Get detailed product information
     * GET /api/v1/vendor/product-gallery/details/{id}
     */
    public function details($id)
    {
        $item = Item::with([
            'store:id,name,logo,rating',
            'category',
            'translations',
            'tags',
            'reviews' => function($query) {
                $query->latest()->take(5);
            }
        ])->find($id);

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found'
            ], 404);
        }

        // Check if store exists (might be deleted)
        if (!$item->store) {
            return response()->json([
                'success' => false,
                'message' => 'Product store not found or has been deleted'
            ], 404);
        }

        // Get category path
        $categoryPath = $this->getCategoryPath($item->category);

        $data = [
            'id' => $item->id,
            'name' => $item->name,
            'description' => $item->description,
            'image' => $item->image_full_url,
            'images' => $item->images_full_url ?? [],
            'price' => (float) $item->price,
            'discount' => (float) $item->discount,
            'discount_type' => $item->discount_type,
            'category_id' => $item->category_id,
            'category_name' => $item->category?->name ?? '',
            'category_path' => $categoryPath,
            'unit' => $item->unit_type ?? $item->unit,
            'stock' => $item->stock ?? 0,
            'available_time_starts' => $item->available_time_starts,
            'available_time_ends' => $item->available_time_ends,
            'veg' => $item->veg,
            'recommended' => $item->recommended,
            'organic' => $item->organic,
            'is_halal' => $item->is_halal,
            'barcode' => $item->barcode,
            'variations' => $item->variations ? json_decode($item->variations, true) : [],
            'add_ons' => $item->add_ons ? json_decode($item->add_ons, true) : [],
            'attributes' => $item->attributes ? json_decode($item->attributes, true) : [],
            'choice_options' => $item->choice_options ? json_decode($item->choice_options, true) : [],
            'tags' => $item->tags->pluck('tag')->toArray(),
            'source_store' => [
                'id' => $item->store->id,
                'name' => $item->store->name,
                'logo' => $item->store->logo_full_url,
                'rating' => (float) $item->store->rating
            ],
            'reviews' => [
                'avg_rating' => (float) $item->avg_rating,
                'total_reviews' => $item->rating_count ?? 0,
                'recent_reviews' => $item->reviews->map(function($review) {
                    return [
                        'id' => $review->id,
                        'customer_name' => $review->customer?->f_name . ' ' . $review->customer?->l_name,
                        'rating' => $review->rating,
                        'comment' => $review->comment,
                        'created_at' => $review->created_at->format('Y-m-d H:i:s')
                    ];
                })
            ]
        ];

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Replicate product to vendor's store
     * POST /api/v1/vendor/product-gallery/replicate
     */
    public function replicate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'source_product_id' => 'required|exists:items,id',
            'customize.price' => 'nullable|numeric|min:0.01',
            'customize.discount' => 'nullable|numeric|min:0',
            'customize.stock' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => Helpers::error_processor($validator)
            ], 422);
        }

        $vendor = $request->vendor;
        $vendorStore = $vendor->stores[0];

        // Check if store has item section enabled
        if (!$vendorStore->item_section) {
            return response()->json([
                'success' => false,
                'message' => 'Your store does not have permission to add items'
            ], 403);
        }

        // Check subscription limits
        if ($vendorStore->store_business_model == 'subscription') {
            $store_sub = $vendorStore->store_sub;
            if (isset($store_sub)) {
                if ($store_sub->max_product != "unlimited" && $store_sub->max_product > 0) {
                    $total_item = Item::where('store_id', $vendorStore->id)->count();
                    if ($total_item >= $store_sub->max_product) {
                        return response()->json([
                            'success' => false,
                            'message' => 'You have reached your product limit. Please upgrade your subscription.'
                        ], 403);
                    }
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not subscribed to any package'
                ], 403);
            }
        }

        // Get source product
        $sourceProduct = Item::with(['translations', 'tags'])->find($request->source_product_id);

        if (!$sourceProduct) {
            return response()->json([
                'success' => false,
                'message' => 'Source product not found'
            ], 404);
        }

        // Check if product already exists in vendor's store
        $existingProduct = Item::where('store_id', $vendorStore->id)
            ->where('name', $sourceProduct->name)
            ->first();

        if ($existingProduct) {
            return response()->json([
                'success' => false,
                'message' => 'A product with this name already exists in your store'
            ], 409);
        }

        DB::beginTransaction();
        try {
            // Get customizations - support both nested and flat structure
            $customize = $request->input('customize', []);

            // If customize is empty, check for root-level customizations (mobile app format)
            if (empty($customize)) {
                $customize = [
                    'price' => $request->input('price'),
                    'discount' => $request->input('discount'),
                    'stock' => $request->input('stock'),
                    'barcode' => $request->input('barcode'),
                    'veg' => $request->input('veg'),
                    'available_time_starts' => $request->input('available_time_starts'),
                    'available_time_ends' => $request->input('available_time_ends'),
                    'is_recommended' => $request->input('is_recommended'),
                    'copy_variations' => $request->input('copy_variations', true),
                    'copy_addons' => $request->input('copy_addons', true),
                    'copy_attributes' => $request->input('copy_attributes', true),
                    'copy_images' => $request->input('copy_images', true),
                ];
            }

            // Create new product
            $newProduct = new Item();
            $newProduct->name = $sourceProduct->name;
            $newProduct->description = $sourceProduct->description;
            $newProduct->category_id = $sourceProduct->category_id;
            $newProduct->store_id = $vendorStore->id;
            $newProduct->module_id = $vendorStore->module_id;
            $newProduct->unit_id = $sourceProduct->unit_id;

            // Price and discount (allow customization)
            $newProduct->price = $customize['price'] ?? $sourceProduct->price;
            $newProduct->discount = $customize['discount'] ?? $sourceProduct->discount;
            $newProduct->discount_type = $sourceProduct->discount_type;

            // Stock (allow customization)
            $newProduct->stock = $customize['stock'] ?? $sourceProduct->stock;

            // Times (allow customization)
            $newProduct->available_time_starts = $customize['available_time_starts'] ?? $sourceProduct->available_time_starts;
            $newProduct->available_time_ends = $customize['available_time_ends'] ?? $sourceProduct->available_time_ends;

            // Copy other attributes
            $newProduct->tax = $sourceProduct->tax;
            $newProduct->tax_type = $sourceProduct->tax_type;
            $newProduct->veg = $customize['veg'] ?? $sourceProduct->veg;
            $newProduct->recommended = $customize['is_recommended'] ?? 0;
            $newProduct->organic = $sourceProduct->organic;
            $newProduct->is_halal = $sourceProduct->is_halal;
            $newProduct->barcode = $customize['barcode'] ?? $sourceProduct->barcode;

            // Copy variations, addons, attributes if requested
            if ($customize['copy_variations'] ?? true) {
                $newProduct->variations = $sourceProduct->variations;
            }
            if ($customize['copy_addons'] ?? true) {
                $newProduct->add_ons = $sourceProduct->add_ons;
            }
            if ($customize['copy_attributes'] ?? true) {
                $newProduct->attributes = $sourceProduct->attributes;
                $newProduct->choice_options = $sourceProduct->choice_options;
            }

            // Copy images if requested
            if ($customize['copy_images'] ?? true) {
                // Copy main image
                if ($sourceProduct->image) {
                    $newProduct->image = $this->copyImage($sourceProduct->image, 'product');
                }

                // Copy additional images
                if ($sourceProduct->images && is_array($sourceProduct->images)) {
                    $copiedImages = [];
                    foreach ($sourceProduct->images as $image) {
                        $copiedImage = $this->copyImage($image, 'product');
                        if ($copiedImage) {
                            $copiedImages[] = $copiedImage;
                        }
                    }
                    $newProduct->images = $copiedImages;
                }
            }

            $newProduct->status = 1;
            $newProduct->is_approved = 1; // Auto-approve replicated products
            $newProduct->save();

            // Copy translations
            foreach ($sourceProduct->translations as $translation) {
                Translation::create([
                    'translationable_type' => 'App\Models\Item',
                    'translationable_id' => $newProduct->id,
                    'locale' => $translation->locale,
                    'key' => $translation->key,
                    'value' => $translation->value
                ]);
            }

            // Copy tags
            foreach ($sourceProduct->tags as $tag) {
                $newProduct->tags()->create([
                    'tag' => $tag->tag
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Product replicated successfully',
                'data' => [
                    'new_product_id' => $newProduct->id,
                    'name' => $newProduct->name,
                    'status' => 'active',
                    'items_copied' => [
                        'images' => ($customize['copy_images'] ?? true) ? count(is_array($sourceProduct->images) ? $sourceProduct->images : json_decode($sourceProduct->images ?? '[]', true)) + 1 : 0,
                        'variations' => ($customize['copy_variations'] ?? true) ? count(is_array($sourceProduct->variations) ? $sourceProduct->variations : json_decode($sourceProduct->variations ?? '[]', true)) : 0,
                        'addons' => ($customize['copy_addons'] ?? true) ? count(is_array($sourceProduct->add_ons) ? $sourceProduct->add_ons : json_decode($sourceProduct->add_ons ?? '[]', true)) : 0,
                        'translations' => $sourceProduct->translations->count(),
                        'tags' => $sourceProduct->tags->count()
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Product replication failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to replicate product: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Batch replicate multiple products
     * POST /api/v1/vendor/product-gallery/batch-replicate
     */
    public function batchReplicate(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_ids' => 'required|array|min:1',
            'product_ids.*' => 'required|exists:items,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => Helpers::error_processor($validator)
            ], 422);
        }

        $vendor = $request->vendor;
        $vendorStore = $vendor->stores[0];
        $productIds = $request->input('product_ids');
        $defaultSettings = $request->input('default_settings', []);

        $results = [];
        $successful = 0;
        $failed = 0;

        foreach ($productIds as $productId) {
            // Create a new request for each product
            $replicateRequest = new Request([
                'source_product_id' => $productId,
                'customize' => $defaultSettings
            ]);
            $replicateRequest->setUserResolver(function () use ($request) {
                return $request->user();
            });
            $replicateRequest->merge(['vendor' => $vendor]);

            $response = $this->replicate($replicateRequest);
            $responseData = json_decode($response->getContent(), true);

            if ($responseData['success']) {
                $successful++;
                $results[] = [
                    'source_id' => $productId,
                    'new_id' => $responseData['data']['new_product_id'],
                    'status' => 'success'
                ];
            } else {
                $failed++;
                $results[] = [
                    'source_id' => $productId,
                    'new_id' => null,
                    'status' => 'failed',
                    'error' => $responseData['message']
                ];
            }
        }

        return response()->json([
            'success' => true,
            'message' => "{$successful} products replicated successfully" . ($failed > 0 ? ", {$failed} failed" : ""),
            'data' => [
                'successful' => $successful,
                'failed' => $failed,
                'results' => $results
            ]
        ]);
    }

    /**
     * Get vendor's replication history
     * GET /api/v1/vendor/product-gallery/my-replications
     */
    public function myReplications(Request $request)
    {
        $vendor = $request->vendor;
        $vendorStoreId = $vendor->stores[0]->id;

        // For now, we'll show recently added products
        // In future, you can add a product_replications table to track source
        $replications = Item::where('store_id', $vendorStoreId)
            ->orderBy('created_at', 'desc')
            ->take(50)
            ->get()
            ->map(function($item) {
                return [
                    'my_product_id' => $item->id,
                    'product_name' => $item->name,
                    'added_at' => $item->created_at->format('Y-m-d H:i:s'),
                    'price' => (float) $item->price,
                    'stock' => $item->stock,
                    'status' => $item->status ? 'active' : 'inactive'
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'replications' => $replications,
                'total_products' => Item::where('store_id', $vendorStoreId)->count()
            ]
        ]);
    }

    /**
     * Get trending/popular products in gallery
     * GET /api/v1/vendor/product-gallery/trending
     */
    public function trending(Request $request)
    {
        $vendor = $request->vendor;
        $vendorStoreId = $vendor->stores[0]->id;

        // Most ordered products (excluding vendor's own)
        $mostOrdered = Item::with(['store:id,name,logo'])
            ->where('store_id', '!=', $vendorStoreId)
            ->where('status', 1)
            ->where('is_approved', 1)
            ->orderBy('order_count', 'desc')
            ->take(10)
            ->get()
            ->map(function($item) {
                return $this->formatProductForGallery($item);
            });

        // Highest rated products
        $highestRated = Item::with(['store:id,name,logo'])
            ->where('store_id', '!=', $vendorStoreId)
            ->where('status', 1)
            ->where('is_approved', 1)
            ->where('avg_rating', '>=', 4.5)
            ->orderBy('avg_rating', 'desc')
            ->orderBy('rating_count', 'desc')
            ->take(10)
            ->get()
            ->map(function($item) {
                return $this->formatProductForGallery($item);
            });

        // Newest additions
        $newest = Item::with(['store:id,name,logo'])
            ->where('store_id', '!=', $vendorStoreId)
            ->where('status', 1)
            ->where('is_approved', 1)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get()
            ->map(function($item) {
                return $this->formatProductForGallery($item);
            });

        return response()->json([
            'success' => true,
            'data' => [
                'most_ordered' => $mostOrdered,
                'highest_rated' => $highestRated,
                'newest_additions' => $newest
            ]
        ]);
    }

    // Helper methods

    /**
     * Get category path (breadcrumb)
     */
    private function getCategoryPath($category)
    {
        if (!$category) return '';

        $path = [$category->name];
        $current = $category;

        while ($current->parent_id) {
            $parent = Category::find($current->parent_id);
            if ($parent) {
                array_unshift($path, $parent->name);
                $current = $parent;
            } else {
                break;
            }
        }

        return implode(' > ', $path);
    }

    /**
     * Copy image file
     */
    private function copyImage($originalImage, $folder)
    {
        try {
            if (!$originalImage) return null;
            if (is_array($originalImage)) $originalImage = $originalImage['img'] ?? null;
            if (!$originalImage || !is_string($originalImage)) return null;

            $originalPath = "public/{$folder}/{$originalImage}";

            if (!Storage::exists($originalPath)) {
                return null;
            }

            $extension = pathinfo($originalImage, PATHINFO_EXTENSION);
            $newName = \Carbon\Carbon::now()->toDateString() . "-" . uniqid() . ".{$extension}";
            $newPath = "public/{$folder}/{$newName}";

            Storage::copy($originalPath, $newPath);

            return $newName;
        } catch (\Exception $e) {
            \Log::error("Image copy failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Format product for gallery display
     */
    private function formatProductForGallery($item)
    {
        return [
            'id' => $item->id,
            'name' => $item->name,
            'description' => $item->description,
            'image' => $item->image_full_url,
            'price' => (float) $item->price,
            'discount' => (float) $item->discount,
            'discount_type' => $item->discount_type,
            'barcode' => $item->barcode,
            'source_store_name' => $item->store?->name ?? '',
            'source_store_logo' => $item->store?->logo_full_url,
            'avg_rating' => (float) $item->avg_rating,
            'order_count' => $item->order_count ?? 0
        ];
    }
}
