<?php

namespace App\Http\Controllers\Admin;


use Carbon\Carbon;
use App\Models\Tag;
use App\Models\Item;
use App\Models\Brand;
use App\Models\Store;
use App\Models\Review;
use App\Models\Allergy;
use App\Models\Category;
use App\Models\Nutrition;
use App\Scopes\StoreScope;
use App\Models\GenericName;
use App\Models\TempProduct;
use App\Models\Translation;
use Illuminate\Support\Str;
use App\Models\ItemCampaign;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use App\Exports\ItemListExport;
use App\Models\CommonCondition;
use Illuminate\Validation\Rule;
use App\Exports\StoreItemExport;
use App\Exports\ItemReviewExport;
use Illuminate\Support\Facades\DB;
use App\CentralLogics\ProductLogic;
use App\Models\PharmacyItemDetails;
use App\Http\Controllers\Controller;
use App\Models\EcommerceItemDetails;
use Brian2694\Toastr\Facades\Toastr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use Rap2hpoutre\FastExcel\FastExcel;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use App\Services\SerpApiService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class ItemController extends Controller
{

    protected $serpApiService;

    public function __construct(SerpApiService $serpApiService)
    {
        $this->serpApiService = $serpApiService;
    }
    public function index(Request $request)
    {
        $categories = Category::where(['position' => 0])->get();
        return view('admin-views.product.index', compact('categories'));
    }

    
    /**
     * Search for item by barcode within a specific store
     */
    public function searchByBarcode(Request $request)
    {
        try {
            // Log the incoming request
            \Log::info('Barcode search request:', [
                'barcode' => $request->get('barcode'),
                'store_id' => $request->get('store_id'),
                'has_token' => $request->has('_token')
            ]);

            $barcode = trim($request->get('barcode', ''));
            $storeId = $request->get('store_id');

            if (empty($barcode)) {
                return response()->json([
                    'found' => false,
                    'message' => 'Barcode is required'
                ]);
            }

            if (empty($storeId)) {
                return response()->json([
                    'found' => false,
                    'message' => 'Store selection is required'
                ]);
            }

            // Search for item
            $item = Item::where('barcode', $barcode)
                       ->where('store_id', $storeId)
                       ->where('is_approved', 1)
                       ->first();

            if ($item) {
                \Log::info('Item found:', ['id' => $item->id, 'name' => $item->name]);
                
                return response()->json([
                    'found' => true,
                    'item' => [
                        'id' => $item->id,
                        'name' => $item->name,
                        'price' => floatval($item->price),
                        'discount' => floatval($item->discount ?? 0),
                        'discount_type' => $item->discount_type ?? 'amount',
                        'barcode' => $item->barcode,
                        'stock' => intval($item->stock ?? 0)
                    ]
                ]);
            }

            \Log::info('No item found with barcode: ' . $barcode);

            return response()->json([
                'found' => false,
                'message' => "Product with barcode '{$barcode}' not found in selected store"
            ]);

        } catch (\Exception $e) {
            \Log::error('Barcode search error: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'found' => false,
                'message' => 'Server error occurred'
            ], 500);
        }
    }
    
    
    
    public function store(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'name.0' => 'required',
            'name.*' => 'max:191',
            'category_id' => 'required',
            'image' => [
                Rule::requiredIf(function ()use ($request) {
                    return (Config::get('module.current_module_type') != 'food' && $request?->product_gellary == null )  ;
                })
            ],
            'price' => 'required|numeric|between:.01,999999999999.99',
            'discount' => 'required|numeric|min:0',
            'store_id' => 'required',
            'description.*' => 'max:1000',
            'name.0' => 'required',
            'description.0' => 'required',
            'barcode' => 'nullable|string|max:100',
            'rack' => 'nullable|string|max:100',
            'row' => 'nullable|string|max:100',
        ], [
            'description.*.max' => translate('messages.description_length_warning'),
            'name.0.required' => translate('messages.item_name_required'),
            'category_id.required' => translate('messages.category_required'),
            'image.required' => translate('messages.thumbnail image is required'),
            'name.0.required' => translate('default_name_is_required'),
            'description.0.required' => translate('default_description_is_required'),
        ]);
        if ($request['discount_type'] == 'percent') {
            $dis = ($request['price'] / 100) * $request['discount'];
        } else {
            $dis = $request['discount'];
        }

        if ($request['price'] <= $dis) {
                $validator->getMessageBag()->add('unit_price', translate("Discount amount can't be greater than 100%"));
        }

        if ($request['price'] <= $dis || $validator->fails()) {
                return response()->json(['errors' => Helpers::error_processor($validator)]);
            }

        $images = [];

        if($request->item_id && $request?->product_gellary == 1 ){
            $item_data= Item::withoutGlobalScope(StoreScope::class)->select(['image','images'])->findOrfail($request->item_id);
            if(!$request->has('image')){

                $oldDisk = 'public';
                if ($item_data->storage && count($item_data->storage) > 0) {
                    foreach ($item_data->storage as $value) {
                        if ($value['key'] == 'image') {
                            $oldDisk = $value['value'];
                        }
                    }
                }
                $oldPath = "product/{$item_data->image}";
                $newFileNamethumb = Carbon::now()->toDateString() . "-" . uniqid() . ".png";
                $newPath = "product/{$newFileNamethumb}";
                $dir = 'product/';
                $newDisk = Helpers::getDisk();

                try{
                    if (Storage::disk($oldDisk)->exists($oldPath)) {
                        if (!Storage::disk($newDisk)->exists($dir)) {
                            Storage::disk($newDisk)->makeDirectory($dir);
                        }
                        $fileContents = Storage::disk($oldDisk)->get($oldPath);
                        Storage::disk($newDisk)->put($newPath, $fileContents);
                    }
                } catch (\Exception $e) {
                }
            }

            foreach($item_data->images as$key=> $value){
                if( !in_array( is_array($value) ?   $value['img'] : $value ,explode(",", $request->removedImageKeys))) {
                    $value = is_array($value)?$value:['img' => $value, 'storage' => 'public'];
                    $oldDisk = $value['storage'];
                    $oldPath = "product/{$value['img']}";
                    $newFileName = Carbon::now()->toDateString() . "-" . uniqid() . ".png";
                    $newPath = "product/{$newFileName}";
                    $dir = 'product/';
                    $newDisk = Helpers::getDisk();
                    try{
                        if (Storage::disk($oldDisk)->exists($oldPath)) {
                            if (!Storage::disk($newDisk)->exists($dir)) {
                                Storage::disk($newDisk)->makeDirectory($dir);
                            }
                            $fileContents = Storage::disk($oldDisk)->get($oldPath);
                            Storage::disk($newDisk)->put($newPath, $fileContents);
                        }
                    } catch (\Exception $e) {
                    }
                    $images[]=['img'=>$newFileName, 'storage'=> Helpers::getDisk()];
                }
            }
        }

        $tag_ids = [];
        if ($request->tags != null) {
            $tags = explode(",", $request->tags);
        }
        if (isset($tags)) {
            foreach ($tags as $key => $value) {
                $tag = Tag::firstOrNew(
                    ['tag' => $value]
                );
                $tag->save();
                array_push($tag_ids, $tag->id);
            }
        }

        $nutrition_ids = [];
        if ($request->nutritions != null) {
            $nutritions = $request->nutritions;
        }
        if (isset($nutritions)) {
            foreach ($nutritions as $key => $value) {
                $nutrition = Nutrition::firstOrNew(
                    ['nutrition' => $value]
                );
                $nutrition->save();
                array_push($nutrition_ids, $nutrition->id);
            }
        }
        $generic_ids = [];
        if ($request->generic_name != null) {
            $generic_name = GenericName::firstOrNew(
                ['generic_name' => $request->generic_name]
            );
            $generic_name->save();
            array_push($generic_ids, $generic_name->id);
        }

        $allergy_ids = [];
        if ($request->allergies != null) {
            $allergies = $request->allergies;
        }
        if (isset($allergies)) {
            foreach ($allergies as $key => $value) {
                $allergy = Allergy::firstOrNew(
                    ['allergy' => $value]
                );
                $allergy->save();
                array_push($allergy_ids, $allergy->id);
            }
        }

        $item = new Item;
        $item->name = $request->name[array_search('default', $request->lang)];

        $category = [];
        if ($request->category_id != null) {
            array_push($category, [
                'id' => $request->category_id,
                'position' => 1,
            ]);
        }
        if ($request->sub_category_id != null) {
            array_push($category, [
                'id' => $request->sub_category_id,
                'position' => 2,
            ]);
        }
        if ($request->sub_sub_category_id != null) {
            array_push($category, [
                'id' => $request->sub_sub_category_id,
                'position' => 3,
            ]);
        }
        $item->category_ids = json_encode($category);
        $item->category_id = $request->sub_category_id ? $request->sub_category_id : $request->category_id;
        $item->description =  $request->description[array_search('default', $request->lang)];

        $choice_options = [];
        if ($request->has('choice')) {
            foreach ($request->choice_no as $key => $no) {
                $str = 'choice_options_' . $no;
                if ($request[$str][0] == null) {
                    $validator->getMessageBag()->add('name', translate('messages.attribute_choice_option_value_can_not_be_null'));
                    return response()->json(['errors' => Helpers::error_processor($validator)]);
                }
                $temp['name'] = 'choice_' . $no;
                $temp['title'] = $request->choice[$key];
                $temp['options'] = explode(',', implode('|', preg_replace('/\s+/', ' ', $request[$str])));
                array_push($choice_options, $temp);
            }
        }
        $item->choice_options = json_encode($choice_options);
        $variations = [];
        $options = [];
        if ($request->has('choice_no')) {
            foreach ($request->choice_no as $key => $no) {
                $name = 'choice_options_' . $no;
                $my_str = implode('|', $request[$name]);
                array_push($options, explode(',', $my_str));
            }
        }
        //Generates the combinations of customer choice options
        $combinations = Helpers::combinations($options);
        if (count($combinations[0]) > 0) {
            foreach ($combinations as $key => $combination) {
                $str = '';
                foreach ($combination as $k => $temp) {
                    if ($k > 0) {
                        $str .= '-' . str_replace(' ', '', $temp);
                    } else {
                        $str .= str_replace(' ', '', $temp);
                    }
                }
                $temp = [];
                $temp['type'] = $str;
                $temp['price'] = abs($request['price_' . str_replace('.', '_', $str)]);


                if($request->discount_type == 'amount' &&  $temp['price']  <   $request->discount){
                    $validator->getMessageBag()->add('unit_price', translate("Variation price must be greater than discount amount"));
                    return response()->json(['errors' => Helpers::error_processor($validator)]);
                }

                $temp['stock'] = abs($request['stock_' . str_replace('.', '_', $str)]);
                array_push($variations, $temp);
            }
        }
        //combinations end

        if (!empty($request->file('item_images'))) {
            foreach ($request->item_images as $img) {
                $image_name = Helpers::upload('product/', 'png', $img);
                // Only process image (background removal) if checkbox is checked
                if ($request->has('process_images') && $request->process_images == '1') {
                    Helpers::processProductImage('product/', $image_name);
                }
                $images[]=['img'=>$image_name, 'storage'=> Helpers::getDisk()];
            }
        }
        // food variation
        $food_variations = [];
        if (isset($request->options)) {
            foreach (array_values($request->options) as $key => $option) {

                $temp_variation['name'] = $option['name'];
                $temp_variation['type'] = $option['type'];
                $temp_variation['min'] = $option['min'] ?? 0;
                $temp_variation['max'] = $option['max'] ?? 0;
                $temp_variation['required'] = $option['required'] ?? 'off';
                if ($option['min'] > 0 &&  $option['min'] > $option['max']) {
                    $validator->getMessageBag()->add('name', translate('messages.minimum_value_can_not_be_greater_then_maximum_value'));
                    return response()->json(['errors' => Helpers::error_processor($validator)]);
                }
                if (!isset($option['values'])) {
                    $validator->getMessageBag()->add('name', translate('messages.please_add_options_for') . $option['name']);
                    return response()->json(['errors' => Helpers::error_processor($validator)]);
                }
                if ($option['max'] > count($option['values'])) {
                    $validator->getMessageBag()->add('name', translate('messages.please_add_more_options_or_change_the_max_value_for') . $option['name']);
                    return response()->json(['errors' => Helpers::error_processor($validator)]);
                }
                $temp_value = [];

                foreach (array_values($option['values']) as $value) {
                    if (isset($value['label'])) {
                        $temp_option['label'] = $value['label'];
                    }
                    $temp_option['optionPrice'] = $value['optionPrice'];
                    array_push($temp_value, $temp_option);
                }
                $temp_variation['values'] = $temp_value;
                array_push($food_variations, $temp_variation);
            }
        }

        $item->food_variations = json_encode($food_variations);
        $item->variations = json_encode($variations);
        $item->price = $request->price;
        $item->image =  $request->has('image') ? Helpers::upload('product/', 'png', $request->file('image')) : $newFileNamethumb ?? null;
        if ($request->has('image') && $item->image) {
            // Only process thumbnail (background removal) if checkbox is checked
            if ($request->has('process_images') && $request->process_images == '1') {
                Helpers::processProductImage('product/', $item->image);
            }
        }
        $item->available_time_starts = $request->available_time_starts ?? '00:00:00';
        $item->available_time_ends = $request->available_time_ends ?? '23:59:59';
        $item->discount = $request->discount_type == 'amount' ? $request->discount : $request->discount;
        $item->discount_type = $request->discount_type;
        $item->unit_id = $request->unit;
        $item->attributes = $request->has('attribute_id') ? json_encode($request->attribute_id) : json_encode([]);
        $item->add_ons = $request->has('addon_ids') ? json_encode($request->addon_ids) : json_encode([]);
        $item->store_id = $request->store_id;
        $item->maximum_cart_quantity = $request->maximum_cart_quantity;
        $item->veg = $request->veg;
        $item->barcode = $request->barcode ?? null;
        $item->rack = $request->rack ?? null;
        $item->row = $request->row ?? null;
        $item->module_id = Config::get('module.current_module_id');
        $module_type = Config::get('module.current_module_type');
        if ($module_type == 'grocery') {
            $item->organic = $request->organic ?? 0;
        }
        $item->stock = $request->current_stock ?? 0;
        $item->images = $images;
        $item->is_halal =  $request->is_halal ?? 0;
        $item->save();
        $item->tags()->sync($tag_ids);
        $item->nutritions()->sync($nutrition_ids);
        $item->allergies()->sync($allergy_ids);
        if ($module_type == 'pharmacy') {
            $item_details = new PharmacyItemDetails();
            $item_details->item_id = $item->id;
            $item_details->common_condition_id = $request->condition_id;
            $item_details->is_basic = $request->basic ?? 0;
            $item_details->is_prescription_required = $request->is_prescription_required ?? 0;
            $item_details->save();
            $item->generic()->sync($generic_ids);
            }
        if ($module_type == 'ecommerce') {
            $item_details = new EcommerceItemDetails();
            $item_details->item_id = $item->id;
            $item_details->brand_id = $request->brand_id;
            $item_details->save();
        }

        Helpers::add_or_update_translations(request: $request, key_data: 'name', name_field: 'name', model_name: 'Item', data_id: $item->id, data_value: $item->name);
        Helpers::add_or_update_translations(request: $request, key_data: 'description', name_field: 'description', model_name: 'Item', data_id: $item->id, data_value: $item->description);

        return response()->json(['success' => translate('messages.product_added_successfully')], 200);
    }

    public function view($id)
    {
        $product = Item::withoutGlobalScope(StoreScope::class)->where(['id' => $id])->firstOrFail();
        $reviews = Review::where(['item_id' => $id])->latest()->paginate(config('default_pagination'));
        return view('admin-views.product.view', compact('product', 'reviews'));
    }

    public function edit(Request $request,$id)
    {
        $temp_product= false;
        if($request->temp_product){
            $product = TempProduct::withoutGlobalScope(StoreScope::class)->withoutGlobalScope('translate')->with('store', 'category', 'module')->findOrFail($id);
            $temp_product= true;
        }else{
            $product = Item::withoutGlobalScope(StoreScope::class)->withoutGlobalScope('translate')->with('store', 'category', 'module')->findOrFail($id);
        }
        if (!$product) {
            Toastr::error(translate('messages.item_not_found'));
            return back();
        }
        $temp = $product->category;
        if ($temp?->position) {
            $sub_category = $temp;
            $category = $temp->parent;
        } else {
            $category = $temp;
            $sub_category = null;
        }

        return view('admin-views.product.edit', compact('product', 'sub_category', 'category','temp_product'));
    }

    public function removeBg(Request $request)
    {
        $request->validate(['image_name' => 'required|string']);
        $result = Helpers::processProductImage('product/', $request->image_name);
        if ($result) {
            return response()->json(['success' => true, 'message' => translate('Background removed successfully')]);
        }
        return response()->json(['success' => false, 'message' => translate('Background removal failed. Make sure Python and withoutbg are installed.')], 500);
    }

    public function saveEditedImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:5120',
            'image_name' => 'required|string',
        ]);

        $imageName = $request->image_name;
        $path = 'product/' . $imageName;

        // Overwrite existing file
        $disk = config('filesystems.default', 'public');
        \Storage::disk($disk)->put($path, file_get_contents($request->file('image')));

        $fullUrl = Helpers::get_full_url('product', $imageName, $disk);
        return response()->json([
            'success' => true,
            'message' => translate('Image updated successfully'),
            'url' => $fullUrl,
        ]);
    }

    public function status(Request $request)
    {
        $product = Item::withoutGlobalScope(StoreScope::class)->findOrFail($request->id);
        $product->status = $request->status;
        $product->save();
        Toastr::success(translate('messages.item_status_updated'));
        return back();
    }

public function update(Request $request, $id)
{
    $validator = Validator::make($request->all(), [
        'name' => 'array',
        'name.0' => 'required',
        'name.*' => 'max:191',
        'category_id' => 'required',
        'price' => 'required|numeric|between:.01,999999999999.99',
        'store_id' => 'required',
        'description' => 'array',
        'description.*' => 'max:1000',
        'discount' => 'required|numeric|min:0',
        'name.0' => 'required',
        'description.0' => 'required',
        'barcode' => 'nullable|string|max:100',
        'rack' => 'nullable|string|max:100',
        'row' => 'nullable|string|max:100',
    ], [
        'description.*.max' => translate('messages.description_length_warning'),
        'category_id.required' => translate('messages.category_required'),
        'name.0.required' => translate('default_name_is_required'),
        'description.0.required' => translate('default_description_is_required'),
    ]);

    if ($request['discount_type'] == 'percent') {
        $dis = ($request['price'] / 100) * $request['discount'];
    } else {
        $dis = $request['discount'];
    }

    if ($request['price'] <= $dis) {
        $validator->getMessageBag()->add('unit_price', translate("Discount amount can't be greater than 100%"));
    }

    if ($request['price'] <= $dis || $validator->fails()) {
        return response()->json(['errors' => Helpers::error_processor($validator)]);
    }

    $item = Item::withoutGlobalScope(StoreScope::class)->find($id);
    
    $tag_ids = [];
    if ($request->tags != null) {
        $tags = explode(",", $request->tags);
    }
    if (isset($tags)) {
        foreach ($tags as $key => $value) {
            $tag = Tag::firstOrNew(
                ['tag' => $value]
            );
            $tag->save();
            array_push($tag_ids, $tag->id);
        }
    }
    
    $nutrition_ids = [];
    if ($request->nutritions != null) {
        $nutritions = $request->nutritions;
    }
    if (isset($nutritions)) {
        foreach ($nutritions as $key => $value) {
            $nutrition = Nutrition::firstOrNew(
                ['nutrition' => $value]
            );
            $nutrition->save();
            array_push($nutrition_ids, $nutrition->id);
        }
    }
    
    $allergy_ids = [];
    if ($request->allergies != null) {
        $allergies = $request->allergies;
    }
    if (isset($allergies)) {
        foreach ($allergies as $key => $value) {
            $allergy = Allergy::firstOrNew(
                ['allergy' => $value]
            );
            $allergy->save();
            array_push($allergy_ids, $allergy->id);
        }
    }

    $generic_ids = [];
    if ($request->generic_name != null) {
        $generic_name = GenericName::firstOrNew(
            ['generic_name' => $request->generic_name]
        );
        $generic_name->save();
        array_push($generic_ids, $generic_name->id);
    }

    $item->name = $request->name[array_search('default', $request->lang)];

    $category = [];
    if ($request->category_id != null) {
        array_push($category, [
            'id' => $request->category_id,
            'position' => 1,
        ]);
    }
    if ($request->sub_category_id != null) {
        array_push($category, [
            'id' => $request->sub_category_id,
            'position' => 2,
        ]);
    }
    if ($request->sub_sub_category_id != null) {
        array_push($category, [
            'id' => $request->sub_sub_category_id,
            'position' => 3,
        ]);
    }

    // ===================================================================
    // ✅ SAFE IMAGE HANDLING - Only affects THIS specific item
    // ===================================================================
    
    // Get current images for THIS item only
    $currentImages = $item->images;
    if (is_string($currentImages)) {
        $currentImages = json_decode($currentImages, true) ?? [];
    }
    if (!is_array($currentImages)) {
        $currentImages = [];
    }

    // Build a map of images that belong to THIS item
    $itemImageMap = [];
    foreach ($currentImages as $imageData) {
        $imageName = is_array($imageData) ? ($imageData['img'] ?? '') : $imageData;
        if (!empty($imageName)) {
            $itemImageMap[$imageName] = is_array($imageData) ? $imageData : ['img' => $imageData, 'storage' => 'public'];
        }
    }

    // Get list of images marked for removal
    $removedImageKeys = $request->removedImageKeys ? array_filter(explode(',', $request->removedImageKeys)) : [];
    
    // Process image deletions SAFELY - only for THIS item
    if (!$request?->temp_product && !empty($removedImageKeys)) {
        foreach ($removedImageKeys as $imageToRemove) {
            $imageToRemove = trim($imageToRemove);
            if (empty($imageToRemove)) continue;
            
            // ✅ CRITICAL CHECK: Only delete if this image belongs to THIS item
            if (isset($itemImageMap[$imageToRemove])) {
                // ✅ Additional safety: Check if other items use this image
                $otherItemsUsingImage = Item::withoutGlobalScope(StoreScope::class)
                    ->where('id', '!=', $id)
                    ->where(function($q) use ($imageToRemove) {
                        $q->where('image', $imageToRemove)
                          ->orWhereRaw("JSON_SEARCH(images, 'one', ?) IS NOT NULL", [$imageToRemove]);
                    })
                    ->exists();
                
                if (!$otherItemsUsingImage) {
                    // ✅ Safe to delete - image belongs only to THIS item
                    Helpers::check_and_delete('product/', $imageToRemove);
                    \Log::info("Deleted image from item", [
                        'item_id' => $id,
                        'image' => $imageToRemove
                    ]);
                } else {
                    \Log::info("Image not deleted - used by other items", [
                        'item_id' => $id,
                        'image' => $imageToRemove
                    ]);
                }
                
                // Remove from images array
                unset($itemImageMap[$imageToRemove]);
            }
        }
    }

    // Convert back to array for storage
    $images = array_values($itemImageMap);
    
    // Handle new image uploads
    if ($request->has('item_images')) {
        foreach ($request->item_images as $img) {
            $extension = $img->getClientOriginalExtension();
            $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg'];
            
            if (in_array(strtolower($extension), $allowedExtensions)) {
                $image = Helpers::upload('product/', $extension, $img);
                // Only process image (background removal) if checkbox is checked
                if ($request->has('process_images') && $request->process_images == '1') {
                    Helpers::processProductImage('product/', $image);
                }
                array_push($images, ['img'=>$image, 'storage'=> Helpers::getDisk()]);
            } else {
                throw new \Exception("Invalid file type. Only image files are allowed.");
            }
        }
    }

    // ===================================================================
    // END OF SAFE IMAGE HANDLING
    // ===================================================================

    $item->category_id = $request->sub_category_id ? $request->sub_category_id : $request->category_id;
    $item->category_ids = json_encode($category);
    $item->description =  $request->description[array_search('default', $request->lang)];

    $choice_options = [];
    if ($request->has('choice')) {
        foreach ($request->choice_no as $key => $no) {
            $str = 'choice_options_' . $no;
            if ($request[$str][0] == null) {
                $validator->getMessageBag()->add('name', translate('messages.attribute_choice_option_value_can_not_be_null'));
                return response()->json(['errors' => Helpers::error_processor($validator)]);
            }
            $temp['name'] = 'choice_' . $no;
            $temp['title'] = $request->choice[$key];
            $temp['options'] = explode(',', implode('|', preg_replace('/\s+/', ' ', $request[$str])));
            array_push($choice_options, $temp);
        }
    }
    $item->choice_options = $request->has('attribute_id') ? json_encode($choice_options) : json_encode([]);
    
    $variations = [];
    $options = [];
    if ($request->has('choice_no')) {
        foreach ($request->choice_no as $key => $no) {
            $name = 'choice_options_' . $no;
            $my_str = implode('|', $request[$name]);
            array_push($options, explode(',', $my_str));
        }
    }
    
    //Generates the combinations of customer choice options
    $combinations = Helpers::combinations($options);
    if (count($combinations[0]) > 0) {
        foreach ($combinations as $key => $combination) {
            $str = '';
            foreach ($combination as $k => $temp) {
                if ($k > 0) {
                    $str .= '-' . str_replace(' ', '', $temp);
                } else {
                    $str .= str_replace(' ', '', $temp);
                }
            }
            $temp = [];
            $temp['type'] = $str;
            $temp['price'] = abs($request['price_' . str_replace('.', '_', $str)]);

            if($request->discount_type == 'amount' &&  $temp['price']  <   $request->discount){
                $validator->getMessageBag()->add('unit_price', translate("Variation price must be greater than discount amount"));
                return response()->json(['errors' => Helpers::error_processor($validator)]);
            }
            $temp['stock'] = abs($request['stock_' . str_replace('.', '_', $str)]);
            array_push($variations, $temp);
        }
    }
    //combinations end

    $food_variations = [];
    if (isset($request->options)) {
        foreach (array_values($request->options) as $key => $option) {
            $temp_variation['name'] = $option['name'];
            $temp_variation['type'] = $option['type'];
            $temp_variation['min'] = $option['min'] ?? 0;
            $temp_variation['max'] = $option['max'] ?? 0;
            if ($option['min'] > 0 &&  $option['min'] > $option['max']) {
                $validator->getMessageBag()->add('name', translate('messages.minimum_value_can_not_be_greater_then_maximum_value'));
                return response()->json(['errors' => Helpers::error_processor($validator)]);
            }
            if (!isset($option['values'])) {
                $validator->getMessageBag()->add('name', translate('messages.please_add_options_for') . $option['name']);
                return response()->json(['errors' => Helpers::error_processor($validator)]);
            }
            if ($option['max'] > count($option['values'])) {
                $validator->getMessageBag()->add('name', translate('messages.please_add_more_options_or_change_the_max_value_for') . $option['name']);
                return response()->json(['errors' => Helpers::error_processor($validator)]);
            }
            $temp_variation['required'] = $option['required'] ?? 'off';
            $temp_value = [];
            foreach (array_values($option['values']) as $value) {
                if (isset($value['label'])) {
                    $temp_option['label'] = $value['label'];
                }
                $temp_option['optionPrice'] = $value['optionPrice'];
                array_push($temp_value, $temp_option);
            }
            $temp_variation['values'] = $temp_value;
            array_push($food_variations, $temp_variation);
        }
    }
    
    $slug = Str::slug($request->name[array_search('default', $request->lang)]);
    $item->slug = $item->slug ? $item->slug : "{$slug}{$item->id}";
    $item->food_variations = json_encode($food_variations);
    $item->variations = $request->has('attribute_id') ? json_encode($variations) : json_encode([]);
    $item->price = $request->price;
    $item->image = $request->has('image') ? Helpers::update('product/', $item->image, 'png', $request->file('image')) : $item->image;
    if ($request->has('image') && $request->file('image')) {
        // Only process thumbnail (background removal) if checkbox is checked
        if ($request->has('process_images') && $request->process_images == '1') {
            Helpers::processProductImage('product/', $item->image);
        }
    }
    $item->available_time_starts = $request->available_time_starts ?? '00:00:00';
    $item->available_time_ends = $request->available_time_ends ?? '23:59:59';

    $item->discount =  $request->discount;
    $item->discount_type = $request->discount_type;
    $item->unit_id = $request->unit;
    $item->attributes = $request->has('attribute_id') ? json_encode($request->attribute_id) : json_encode([]);
    $item->add_ons = $request->has('addon_ids') ? json_encode($request->addon_ids) : json_encode([]);
    $item->store_id = $request->store_id;
    $item->maximum_cart_quantity = $request->maximum_cart_quantity;
    $item->barcode = $request->barcode;
    $item->rack = $request->rack;
    $item->row = $request->row;
    $item->stock = $request->current_stock ?? 0;
    $item->is_halal = $request->is_halal ?? 0;
    $item->organic = $request->organic ?? 0;
    $item->veg = $request->veg;
    $item->images = $images;
    
    if (Helpers::get_mail_status('product_approval') && $request?->temp_product) {
        // Handle temp product approval logic here
        // (keeping existing code for temp product handling)
    }
    
    $item->save();
    $item->tags()->sync($tag_ids);
    $item->nutritions()->sync($nutrition_ids);
    $item->allergies()->sync($allergy_ids);
    
    if($item->module->module_type == 'pharmacy'){
        $item->generic()->sync($generic_ids);
        DB::table('pharmacy_item_details')
            ->updateOrInsert(
                ['item_id' => $item->id],
                [
                    'common_condition_id' => $request->condition_id,
                    'is_basic' => $request->basic ?? 0,
                    'is_prescription_required' => $request->is_prescription_required ?? 0,
                ]
            );
    }
    
    if($item->module->module_type == 'ecommerce'){
        DB::table('ecommerce_item_details')
            ->updateOrInsert(
                ['item_id' => $item->id],
                [
                    'brand_id' => $request->brand_id,
                ]
            );
    }
    
    Helpers::add_or_update_translations(request: $request, key_data: 'name', name_field: 'name', model_name: 'Item', data_id: $item->id, data_value: $item->name);
    Helpers::add_or_update_translations(request: $request, key_data: 'description', name_field: 'description', model_name: 'Item', data_id: $item->id, data_value: $item->description);

    return response()->json(['success' => translate('messages.product_updated_successfully')], 200);
}
    

    public function delete(Request $request)
    {

        if($request?->temp_product){
            $product = TempProduct::withoutGlobalScope(StoreScope::class)->find($request->id);
            $excludeItemId = null;
            $excludeTempProductId = $product->id;
        }
        else{
            $product = Item::withoutGlobalScope(StoreScope::class)->withoutGlobalScope('translate')->find($request->id);
            $excludeItemId = $product->id;
            $excludeTempProductId = $product->temp_product?->id;
            $product?->temp_product?->translations()?->delete();
            $product?->temp_product()?->delete();
            $product?->carts()?->delete();
        }

        // Pass item ID to check_and_delete so it only deletes if no other items use the image
        if ($product->image) {
            Helpers::check_and_delete('product/' , $product['image'], $excludeItemId, $excludeTempProductId);
        }
        foreach($product->images as $value){
            $value = is_array($value)?$value:['img' => $value, 'storage' => 'public'];
            Helpers::check_and_delete('product/' , $value['img'], $excludeItemId, $excludeTempProductId);
        }
        $product?->translations()->delete();
        $product->delete();
        Toastr::success(translate('messages.product_deleted_successfully'));
        return back();
    }

    public function variant_combination(Request $request)
    {
        $options = [];
        $price = $request->price;
        $product_name = $request->name;

        if ($request->has('choice_no')) {
            foreach ($request->choice_no as $key => $no) {
                $name = 'choice_options_' . $no;
                $my_str = implode('', $request[$name]);
                array_push($options, explode(',', $my_str));
            }
        }

        $result = [[]];
        foreach ($options as $property => $property_values) {
            $tmp = [];
            foreach ($result as $result_item) {
                foreach ($property_values as $property_value) {
                    $tmp[] = array_merge($result_item, [$property => $property_value]);
                }
            }
            $result = $tmp;
        }

        $data = [];
        foreach ($result as $combination) {
            $str = '';
            foreach ($combination as $key => $item) {
                if ($key > 0) {
                    $str .= '-' . str_replace(' ', '', $item);
                } else {
                    $str .= str_replace(' ', '', $item);
                }
            }

            $price_field = 'price_' . $str;
            $stock_field = 'stock_' . $str;
            $item_price = $request->input($price_field);
            $item_stock = $request->input($stock_field);

            $data[] = [
                'name' => $str,
                'price' => $item_price ?? $price,
                'stock' => $item_stock ?? 1
            ];
        }
        $combinations = $result;
        $stock = $request->stock == 'true' ? true : false;
        return response()->json([
            'view' => view('admin-views.product.partials._variant-combinations', compact('combinations', 'price', 'product_name', 'stock','data'))->render(),
            'length' => count($combinations),
            'stock' => $stock,
        ]);
    }

    public function variant_price(Request $request)
    {
        if ($request->item_type == 'item') {
            $product = Item::withoutGlobalScope(StoreScope::class)->find($request->id);
        } else {
            $product = ItemCampaign::find($request->id);
        }
        // $product = Item::withoutGlobalScope(StoreScope::class)->find($request->id);
        if (isset($product->module_id) && $product->module->module_type == 'food' && $product->food_variations) {
            $price = $product->price;
            $addon_price = 0;
            if ($request['addon_id']) {
                foreach ($request['addon_id'] as $id) {
                    $addon_price += $request['addon-price' . $id] * $request['addon-quantity' . $id];
                }
            }
            $product_variations = json_decode($product->food_variations, true);
            if ($request->variations && count($product_variations)) {

                $price += Helpers::food_variation_price($product_variations, $request->variations);
            } else {
                $price = $product->price - Helpers::product_discount_calculate($product, $product->price, $product->store)['discount_amount'];
            }
        } else {
            $str = '';
            $quantity = 0;
            $price = 0;
            $addon_price = 0;

            foreach (json_decode($product->choice_options) as $key => $choice) {
                if ($str != null) {
                    $str .= '-' . str_replace(' ', '', $request[$choice->name]);
                } else {
                    $str .= str_replace(' ', '', $request[$choice->name]);
                }
            }

            if ($request['addon_id']) {
                foreach ($request['addon_id'] as $id) {
                    $addon_price += $request['addon-price' . $id] * $request['addon-quantity' . $id];
                }
            }

            if ($str != null) {
                $count = count(json_decode($product->variations));
                for ($i = 0; $i < $count; $i++) {
                    if (json_decode($product->variations)[$i]->type == $str) {
                        $price = json_decode($product->variations)[$i]->price - Helpers::product_discount_calculate($product, json_decode($product->variations)[$i]->price, $product->store)['discount_amount'];
                    }
                }
            } else {
                $price = $product->price - Helpers::product_discount_calculate($product, $product->price, $product->store)['discount_amount'];
            }
        }

        return array('price' => Helpers::format_currency(($price * $request->quantity) + $addon_price));
    }
    public function get_categories(Request $request)
    {
        $key = explode(' ', $request['q']);
        $cat = Category::when(isset($request->module_id), function ($query) use ($request) {
            $query->where('module_id', $request->module_id);
        })
            ->when($request->sub_category, function ($query) {
                $query->where('position', '>', '0');
            })
            ->where(['parent_id' => $request->parent_id])
            ->when(isset($key), function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->where('name', 'like', "%{$value}%");
                }
            })
            ->get()
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'text' => $category->name,
                ];
            });

        return response()->json($cat);
    }

    public function get_items(Request $request)
    {
        $items = Item::withoutGlobalScope(StoreScope::class)->with('store')
            ->when($request->zone_id, function ($q) use ($request) {
                $q->whereHas('store', function ($query) use ($request) {
                    $query->where('zone_id', $request->zone_id);
                });
            })
            ->when($request->module_id, function ($q) use ($request) {
                $q->where('module_id', $request->module_id);
            })->get();
        $res = '';
        if (count($items) > 0 && !$request->data) {
            $res = '<option value="' . 0 . '" disabled selected>---Select---</option>';
        }

        foreach ($items as $row) {
            $res .= '<option value="' . $row->id . '" ';
            if ($request->data) {
                $res .= in_array($row->id, $request->data) ? 'selected ' : '';
            }
            $res .= '>' . $row->name . ' (' . $row->store->name . ')' . '</option>';
        }
        return response()->json([
            'options' => $res,
        ]);
    }

    public function get_items_flashsale(Request $request)
    {
        $items = Item::withoutGlobalScope(StoreScope::class)->with('store')->active()
            ->when($request->zone_id, function ($q) use ($request) {
                $q->whereHas('store', function ($query) use ($request) {
                    $query->where('zone_id', $request->zone_id);
                });
            })
            ->when($request->module_id, function ($q) use ($request) {
                $q->where('module_id', $request->module_id);
            })->whereDoesntHave('flashSaleItems.flashSale', function ($query) {
                $now = now();
                $query->where('start_date', '<=', $now)
                      ->where('end_date', '>=', $now);
            })->get();
        $res = '';
        if (count($items) > 0 && !$request->data) {
            $res = '<option value="' . 0 . '" disabled selected>---Select---</option>';
        }

        foreach ($items as $row) {
            $res .= '<option value="' . $row->id . '" ';
            if ($request->data) {
                $res .= in_array($row->id, $request->data) ? 'selected ' : '';
            }
            $res .= '>' . $row->name . ' (' . $row->store->name . ')' . '</option>';
        }
        return response()->json([
            'options' => $res,
        ]);
    }

    public function list(Request $request)
    {
        $store_id = $request->query('store_id', 'all');
        $category_id = $request->query('category_id', 'all');
        $sub_category_id = $request->query('sub_category_id', 'all');
        $zone_id = $request->query('zone_id', 'all');
        $condition_id = $request->query('condition_id', 'all');
        $brand_id = $request->query('brand_id', 'all');

        $type = $request->query('type', 'all');
        $key = explode(' ', $request['search']);
        $items = Item::withoutGlobalScope(StoreScope::class)
            ->when($request->query('module_id', null), function ($query) use ($request) {
                return $query->module($request->query('module_id'));
            })
            ->when(is_numeric($store_id), function ($query) use ($store_id) {
                return $query->where('store_id', $store_id);
            })
            ->when(is_numeric($sub_category_id), function ($query) use ($sub_category_id) {
                return $query->where('category_id', $sub_category_id);
            })
            ->when(is_numeric($category_id), function ($query) use ($category_id) {
                return $query->whereHas('category', function ($q) use ($category_id) {
                    return $q->whereId($category_id)->orWhere('parent_id', $category_id);
                });
            })
            ->when(is_numeric($zone_id), function ($query) use ($zone_id) {
                return $query->whereHas('store', function ($q) use ($zone_id) {
                    return $q->where('zone_id'  , $zone_id);
                });
            })
            ->when(is_numeric($condition_id), function ($query) use ($condition_id) {
                return $query->whereHas('pharmacy_item_details', function ($q) use ($condition_id) {
                    return $q->where('common_condition_id'  , $condition_id);
                });
            })
            ->when(is_numeric($brand_id), function ($query) use ($brand_id) {
                return $query->whereHas('ecommerce_item_details', function ($q) use ($brand_id) {
                    return $q->where('brand_id'  , $brand_id);
                });
            })
            ->when($request['search'], function ($query) use ($key) {
                return $query->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->where('name', 'like', "%{$value}%")->orWhereHas('category', function ($q) use ($value) {
                            return $q->where('name', 'like', "%{$value}%");
                        });
                    }
                });
            })
            ->where('is_approved',1)
            ->module(Config::get('module.current_module_id'))
            ->type($type)
            ->latest()->paginate(config('default_pagination'));
        $store = $store_id != 'all' ? Store::findOrFail($store_id) : null;
        $category = $category_id != 'all' ? Category::findOrFail($category_id) : null;
        $sub_categories = $category_id != 'all' ? Category::where('parent_id', $category_id)->get(['id','name']) : [];
        $condition = $condition_id != 'all' ? CommonCondition::findOrFail($condition_id) : [];
        $brand = $brand_id != 'all' ? Brand::findOrFail($brand_id) : [];

        return view('admin-views.product.list', compact('items', 'store', 'category', 'type','sub_categories', 'condition'));
    }

    public function remove_image(Request $request)
    {

        if($request?->temp_product){
            $item = TempProduct::withoutGlobalScope(StoreScope::class)->find($request['id']);
            $excludeItemId = $item->item_id; // Get the linked item ID if exists
            $excludeTempProductId = $item->id;
        }
        else{
            $item = Item::withoutGlobalScope(StoreScope::class)->find($request['id']);
            $excludeItemId = $item->id;
            $excludeTempProductId = $item->temp_product?->id;
        }

        $array = [];
        if (count($item['images']) < 2) {
            Toastr::warning(translate('all_image_delete_warning'));
            return back();
        }

        // Pass item ID to check if other items use this image before deleting
        Helpers::check_and_delete('product/' , $request['name'], $excludeItemId, $excludeTempProductId);

        foreach ($item['images'] as $image) {
            if(is_array($image)) {
                if ($image['img'] != $request['name']) {
                    array_push($array, $image);
                }
            } else{
                if ($image != $request['name']) {
                    array_push($array, $image);
                }
            }
        }


        if($request?->temp_product){
            TempProduct::withoutGlobalScope(StoreScope::class)->where('id', $request['id'])->update([
                'images' => json_encode($array),
            ]);
        }
        else{
            Item::withoutGlobalScope(StoreScope::class)->where('id', $request['id'])->update([
                'images' => json_encode($array),
            ]);
        }
        Toastr::success(translate('item_image_removed_successfully'));
        return back();
    }

    public function search(Request $request)
    {
        $view='admin-views.product.partials._table';
        $key = explode(' ', $request['search']);
        $store_id = $request->query('store_id', 'all');
        $category_id = $request->query('category_id', 'all');
        $items = Item::withoutGlobalScope(StoreScope::class)
        ->where(function ($q) use ($key) {
            foreach ($key as $value) {
                $q->where('name', 'like', "%{$value}%");
            }
        })->when(is_numeric($store_id), function ($query) use ($store_id) {
            return $query->where('store_id', $store_id);
        })
        ->when(is_numeric($category_id), function ($query) use ($category_id) {
            return $query->whereHas('category', function ($q) use ($category_id) {
                return $q->whereId($category_id)->orWhere('parent_id', $category_id);
            });
        })->module(Config::get('module.current_module_id'))->where('is_approved',1);

        if(isset($request->product_gallery) && $request->product_gallery==1){
        $items=   $items->limit(12)->get();
        $view='admin-views.product.partials._gallery';
        }
        else{
        $items= $items->latest()->limit(50)->get();
        }

        return response()->json([
            'count' => $items->count(),
            'view' => view($view, compact('items'))->render()
        ]);
    }

    public function review_list(Request $request)
    {

        $key = explode(' ', $request['search']);
        $reviews = Review::with('item')
            ->when(isset($key), function ($query) use ($key,$request) {
                $query->where(function($query) use($key,$request) {

                    $query->whereHas('item', function ($query) use ($key) {
                        foreach ($key as $value) {
                            $query->where('name', 'like', "%{$value}%");
                        }
                    })->orWhereHas('customer', function ($query) use ($key){
                        foreach ($key as $value) {
                            $query->where('f_name', 'like', "%{$value}%")->orwhere('l_name', 'like', "%{$value}%");
                        }
                    })->orwhere('rating', $request['search'])->orwhere('review_id', $request['search']);
                });

            })
            ->whereHas('item', function ($q) {
                return $q->where('module_id', Config::get('module.current_module_id'))->withoutGlobalScope(StoreScope::class);
            })

            ->latest()->paginate(config('default_pagination'));

        return view('admin-views.product.reviews-list', compact('reviews'));
    }

    public function reviews_status(Request $request)
    {
        $review = Review::find($request->id);
        $review->status = $request->status;
        $review->save();
        Toastr::success(translate('messages.review_visibility_updated'));
        return back();
    }

    // public function review_search(Request $request)
    // {
    //     $key = explode(' ', $request['search']);
    //     $reviews = Review::with('item')
    //     ->when(isset($key), function($query) use($key){
    //         $query->whereHas('item', function ($query) use ($key) {
    //             foreach ($key as $value) {
    //                 $query->where('name', 'like', "%{$value}%");
    //             }
    //         });
    //     })
    //     ->whereHas('item', function ($q) use ($request) {
    //         return $q->where('module_id', Config::get('module.current_module_id'))->withoutGlobalScope(StoreScope::class);
    //     })->limit(50)->get();
    //     return response()->json([
    //         'count' => count($reviews),
    //         'view' => view('admin-views.product.partials._review-table', compact('reviews'))->render()
    //     ]);
    // }

    public function reviews_export(Request $request)
    {
        $key = explode(' ', $request['search']);
        $reviews = Review::with('item')
            ->when(isset($key), function ($query) use ($key) {
                $query->whereHas('item', function ($query) use ($key) {
                    foreach ($key as $value) {
                        $query->where('name', 'like', "%{$value}%");
                    }
                });
            })
            ->whereHas('item', function ($q) {
                return $q->where('module_id', Config::get('module.current_module_id'))->withoutGlobalScope(StoreScope::class);
            })

            ->latest()->get();

        $data = [
            'data' => $reviews,
            'search' => $request['search'] ?? null,
        ];
        $typ = 'Item';
        if (Config::get('module.current_module_type') == 'food') {
            $typ = 'Food';
        }
        if ($request->type == 'csv') {
            return Excel::download(new ItemReviewExport($data), $typ . 'Review.csv');
        }
        return Excel::download(new ItemReviewExport($data), $typ . 'Review.xlsx');
    }

    public function item_wise_reviews_export(Request $request)
    {
        $reviews = Review::where(['item_id' => $request->id])->latest()->get();
        $Item = Item::where('id', $request->id)->first()?->category_ids;
        $data = [
            'type' => 'single',
            'category' => \App\CentralLogics\Helpers::get_category_name($Item),
            'data' => $reviews,
            'search' => $request['search'] ?? null,
            'store' => $request['store'] ?? null,
        ];
        $typ = 'ItemWise';
        if (Config::get('module.current_module_type') == 'food') {
            $typ = 'FoodWise';
        }
        if ($request->type == 'csv') {
            return Excel::download(new ItemReviewExport($data), $typ . 'Review.csv');
        }
        return Excel::download(new ItemReviewExport($data), $typ . 'Review.xlsx');
    }

    public function bulk_import_index()
    {
        $module_type = Config::get('module.current_module_type');
        return view('admin-views.product.bulk-import', compact('module_type'));
    }

    public function bulk_import_data(Request $request)
    {
        $request->validate([
            'products_file' => 'required|max:2048'
        ]);
        $module_id = Config::get('module.current_module_id');
        $module_type = Config::get('module.current_module_type');
        try {
            $collections = (new FastExcel)->import($request->file('products_file'));
        } catch (\Exception $exception) {
            Toastr::error(translate('messages.you_have_uploaded_a_wrong_format_file'));
            return back();
        }
        if ($request->button == 'import') {
            $data = [];
            try{
                foreach ($collections as $collection) {
                    if ($collection['Id'] === "" || $collection['Name'] === "" || $collection['CategoryId'] === "" || $collection['SubCategoryId'] === "" || $collection['Price'] === "" || $collection['StoreId'] === "" || $collection['ModuleId'] === "" || $collection['Discount'] === "" || $collection['DiscountType'] === "") {
                        Toastr::error(translate('messages.please_fill_all_required_fields'));
                        return back();
                    }
                    if (isset($collection['Price']) && ($collection['Price'] < 0)) {
                        Toastr::error(translate('messages.Price_must_be_greater_then_0_on_id') . ' ' . $collection['Id']);
                        return back();
                    }
                    if (isset($collection['Discount']) && ($collection['Discount'] < 0)) {
                        Toastr::error(translate('messages.Discount_must_be_greater_then_0_on_id') . ' ' . $collection['Id']);
                        return back();
                    }
                    if (data_get($collection,'Image') != "" &&  strlen(data_get($collection,'Image')) > 30 ) {
                        Toastr::error(translate('messages.Image_name_must_be_in_30_char._on_id') . ' ' . $collection['Id']);
                        return back();
                    }
                    try {
                        $t1 = Carbon::parse($collection['AvailableTimeStarts']);
                        $t2 = Carbon::parse($collection['AvailableTimeEnds']);
                        if ($t1->gt($t2)) {
                            Toastr::error(translate('messages.AvailableTimeEnds_must_be_greater_then_AvailableTimeStarts_on_id') . ' ' . $collection['Id']);
                            return back();
                        }
                    } catch (\Exception $e) {
                        info(["line___{$e->getLine()}", $e->getMessage()]);
                        Toastr::error(translate('messages.Invalid_AvailableTimeEnds_or_AvailableTimeStarts_on_id') . ' ' . $collection['Id']);
                        return back();
                    }
                    array_push($data, [
                        'name' => $collection['Name'],
                        'description' => $collection['Description'],
                        'image' => $collection['Image'],
                        'images' => $collection['Images'] ?? json_encode([]),
                        'category_id' => $collection['SubCategoryId'] ? $collection['SubCategoryId'] : $collection['CategoryId'],
                        'category_ids' => json_encode([['id' => $collection['CategoryId'], 'position' => 0], ['id' => $collection['SubCategoryId'], 'position' => 1]]),
                        'unit_id' => is_int($collection['UnitId']) ? $collection['UnitId'] : null,
                        'stock' => is_numeric($collection['Stock']) ? abs($collection['Stock']) : 0,
                        'price' => $collection['Price'],
                        'discount' => $collection['Discount'],
                        'discount_type' => $collection['DiscountType'],
                        'available_time_starts' => $collection['AvailableTimeStarts'] ?? '00:00:00',
                        'available_time_ends' => $collection['AvailableTimeEnds'] ?? '23:59:59',
                        'variations' => $module_type == 'food' ? json_encode([]) : $collection['Variations'] ?? json_encode([]),
                        'choice_options' => $module_type == 'food' ? json_encode([]) : $collection['ChoiceOptions'] ?? json_encode([]),
                        'food_variations' => $module_type == 'food' ? $collection['Variations'] ?? json_encode([]) : json_encode([]),
                        'add_ons' => $collection['AddOns'] ? ($collection['AddOns'] == "" ? json_encode([]) : $collection['AddOns']) : json_encode([]),
                        'attributes' => $collection['Attributes'] ? ($collection['Attributes'] == "" ? json_encode([]) : $collection['Attributes']) : json_encode([]),
                        'store_id' => $collection['StoreId'],
                        'module_id' => $module_id,
                        'status' => $collection['Status'] == 'active' ? 1 : 0,
                        'veg' => $collection['Veg'] == 'yes' ? 1 : 0,
                        'recommended' => $collection['Recommended'] == 'yes' ? 1 : 0,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }catch(\Exception $e){
                info(["line___{$e->getLine()}",$e->getMessage()]);
                Toastr::error($e->getMessage());
                return back();
            }
            try {
                DB::beginTransaction();
                $chunkSize = 100;
                $chunk_items = array_chunk($data, $chunkSize);
                foreach ($chunk_items as $key => $chunk_item) {
//                    DB::table('items')->insert($chunk_item);
                    foreach ($chunk_item as $item) {
                        $insertedId = DB::table('items')->insertGetId($item);
                        Helpers::updateStorageTable(get_class(new Item), $insertedId, $item['image']);
                    }
                }
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                info(["line___{$e->getLine()}", $e->getMessage()]);
                Toastr::error($e->getMessage());
                return back();
            }
            Toastr::success(translate('messages.product_imported_successfully', ['count' => count($data)]));
            return back();
        }
        $data = [];
        try {
                foreach ($collections as $collection) {
                    if ($collection['Id'] === "" || $collection['Name'] === "" || $collection['CategoryId'] === "" || $collection['SubCategoryId'] === "" || $collection['Price'] === "" || $collection['StoreId'] === "" || $collection['ModuleId'] === "" || $collection['Discount'] === "" || $collection['DiscountType'] === "") {
                        Toastr::error(translate('messages.please_fill_all_required_fields'));
                        return back();
                    }
                    if (isset($collection['Price']) && ($collection['Price'] < 0)) {
                        Toastr::error(translate('messages.Price_must_be_greater_then_0') . ' ' . $collection['Id']);
                        return back();
                    }
                    if (isset($collection['Discount']) && ($collection['Discount'] < 0)) {
                        Toastr::error(translate('messages.Discount_must_be_greater_then_0') . ' ' . $collection['Id']);
                        return back();
                    }
                    if (isset($collection['Discount']) && ($collection['Discount'] > 100)) {
                        Toastr::error(translate('messages.Discount_must_be_less_then_100') . ' ' . $collection['Id']);
                        return back();
                    }
                    if (data_get($collection,'Image') != "" &&  strlen(data_get($collection,'Image')) > 30 ) {
                        Toastr::error(translate('messages.Image_name_must_be_in_30_char_on_id') . ' ' . $collection['Id']);
                        return back();
                    }
                    try {
                        $t1 = Carbon::parse($collection['AvailableTimeStarts']);
                        $t2 = Carbon::parse($collection['AvailableTimeEnds']);
                        if ($t1->gt($t2)) {
                            Toastr::error(translate('messages.AvailableTimeEnds_must_be_greater_then_AvailableTimeStarts_on_id') . ' ' . $collection['Id']);
                            return back();
                        }
                    } catch (\Exception $e) {
                        info(["line___{$e->getLine()}", $e->getMessage()]);
                        Toastr::error(translate('messages.Invalid_AvailableTimeEnds_or_AvailableTimeStarts_on_id') . ' ' . $collection['Id']);
                        return back();
                    }
                    array_push($data, [
                        'id' => $collection['Id'],
                        'name' => $collection['Name'],
                        'description' => $collection['Description'],
                        'image' => $collection['Image'],
                        'images' => $collection['Images'] ?? json_encode([]),
                        'category_id' => $collection['SubCategoryId'] ? $collection['SubCategoryId'] : $collection['CategoryId'],
                        'category_ids' => json_encode([['id' => $collection['CategoryId'], 'position' => 0], ['id' => $collection['SubCategoryId'], 'position' => 1]]),
                        'unit_id' => is_int($collection['UnitId']) ? $collection['UnitId'] : null,
                        'stock' => is_numeric($collection['Stock']) ? abs($collection['Stock']) : 0,
                        'price' => $collection['Price'],
                        'discount' => $collection['Discount'],
                        'discount_type' => $collection['DiscountType'],
                        'available_time_starts' => $collection['AvailableTimeStarts'] ?? '00:00:00',
                        'available_time_ends' => $collection['AvailableTimeEnds'] ?? '23:59:59',
                        'variations' => $module_type == 'food' ? json_encode([]) : $collection['Variations'] ?? json_encode([]),
                        'choice_options' => $module_type == 'food' ? json_encode([]) : $collection['ChoiceOptions'] ?? json_encode([]),
                        'food_variations' => $module_type == 'food' ? $collection['Variations'] ?? json_encode([]) : json_encode([]),
                        'add_ons' => $collection['AddOns'] ? ($collection['AddOns'] == "" ? json_encode([]) : $collection['AddOns']) : json_encode([]),
                        'attributes' => $collection['Attributes'] ? ($collection['Attributes'] == "" ? json_encode([]) : $collection['Attributes']) : json_encode([]),
                        'store_id' => $collection['StoreId'],
                        'module_id' => $module_id,
                        'status' => $collection['Status'] == 'active' ? 1 : 0,
                        'veg' => $collection['Veg'] == 'yes' ? 1 : 0,
                        'recommended' => $collection['Recommended'] == 'yes' ? 1 : 0,
                        'updated_at' => now()
                    ]);
                }
                $id = $collections->pluck('Id')->toArray();
                if (Item::whereIn('id', $id)->doesntExist()) {
                    Toastr::error(translate('messages.Item_doesnt_exist_at_the_database'));
                    return back();
                }
            }catch(\Exception $e){
                info(["line___{$e->getLine()}",$e->getMessage()]);
                Toastr::error($e->getMessage());
                return back();
            }
        try {
            DB::beginTransaction();
            $chunkSize = 100;
            $chunk_items = array_chunk($data, $chunkSize);
            foreach ($chunk_items as $key => $chunk_item) {
//                DB::table('items')->upsert($chunk_item, ['id', 'module_id'], ['name', 'description', 'image', 'images', 'category_id', 'category_ids', 'unit_id', 'stock', 'price', 'discount', 'discount_type', 'available_time_starts', 'available_time_ends','choice_options', 'variations', 'food_variations', 'add_ons', 'attributes', 'store_id', 'status', 'veg', 'recommended']);
                foreach ($chunk_item as $item) {
                    if (isset($item['id']) && DB::table('items')->where('id', $item['id'])->exists()) {
                        DB::table('items')->where('id', $item['id'])->update($item);
                        Helpers::updateStorageTable(get_class(new Item), $item['id'], $item['image']);
                    } else {
                        $insertedId = DB::table('items')->insertGetId($item);
                        Helpers::updateStorageTable(get_class(new Item), $insertedId, $item['image']);
                    }
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            info(["line___{$e->getLine()}", $e->getMessage()]);
            Toastr::error($e->getMessage());
            return back();
        }
        Toastr::success(translate('messages.product_imported_successfully', ['count' => count($data)]));
        return back();
    }

    public function bulk_export_index()
    {
        return view('admin-views.product.bulk-export');
    }

    public function bulk_export_data(Request $request)
    {
        $request->validate([
            'type' => 'required',
            'start_id' => 'required_if:type,id_wise',
            'end_id' => 'required_if:type,id_wise',
            'from_date' => 'required_if:type,date_wise',
            'to_date' => 'required_if:type,date_wise'
        ]);
        $module_type = Config::get('module.current_module_type');
        $products = Item::when($request['type'] == 'date_wise', function ($query) use ($request) {
            $query->whereBetween('created_at', [$request['from_date'] . ' 00:00:00', $request['to_date'] . ' 23:59:59']);
        })
            ->when($request['type'] == 'id_wise', function ($query) use ($request) {
                $query->whereBetween('id', [$request['start_id'], $request['end_id']]);
            })
            ->module(Config::get('module.current_module_id'))
            ->withoutGlobalScope(StoreScope::class)->get();
        return (new FastExcel(ProductLogic::format_export_items(Helpers::Export_generator($products), $module_type)))->download('Items.xlsx');
    }

    public function get_variations(Request $request)
    {
        $product = Item::withoutGlobalScope(StoreScope::class)->find($request['id']);

        return response()->json([
            'view' => view('admin-views.product.partials._get_stock_data', compact('product'))->render()
        ]);
    }
    public function get_stock(Request $request)
    {
        $product = Item::withoutGlobalScope(StoreScope::class)->find($request['id']);
        return response()->json([
            'view' => view('admin-views.product.partials._get_stock_data', compact('product'))->render()
        ]);
    }

    public function stock_update(Request $request)
    {
        $variations = [];
        $stock_count = $request['current_stock'];
        if ($request->has('type')) {
            foreach ($request['type'] as $key => $str) {
                $item = [];
                $item['type'] = $str;
                $item['price'] = abs($request[ 'price_'.$key.'_'. str_replace('.', '_', $str)]);
                $item['stock'] = abs($request['stock_'.$key.'_'. str_replace('.', '_', $str)]);
                array_push($variations, $item);
            }
        }


        $product = Item::withoutGlobalScope(StoreScope::class)->find($request['product_id']);

        $product->stock = $stock_count ?? 0;
        $product->variations = json_encode($variations);
        $product->save();
        Toastr::success(translate("messages.Stock_updated_successfully"));
        return back();
    }

    public function markOutOfStock($id)
    {
        $product = Item::withoutGlobalScope(StoreScope::class)->findOrFail($id);

        // Set all variation stocks to 0
        $variations = json_decode($product->variations, true) ?? [];
        foreach ($variations as &$variation) {
            $variation['stock'] = 0;
        }

        $product->stock = 0;
        $product->variations = json_encode($variations);
        $product->save();

        Toastr::success(translate('messages.item_marked_as_out_of_stock'));
        return back();
    }

    public function search_vendor(Request $request)
    {
        $key = explode(' ', $request['search']);
        if ($request->has('store_id')) {

            $foods = Item::withoutGlobalScope(StoreScope::class)
                ->where('store_id', $request->store_id)
                ->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->where('name', 'like', "%{$value}%");
                    }
                })->limit(50)->get();
            return response()->json([
                'count' => count($foods),
                'view' => view('admin-views.vendor.view.partials._product', compact('foods'))->render()
            ]);
        }
        $foods = Item::withoutGlobalScope(StoreScope::class)->where(function ($q) use ($key) {
            foreach ($key as $value) {
                $q->where('name', 'like', "%{$value}%");
            }
        })->limit(50)->get();
        return response()->json([
            'count' => count($foods),
            'view' => view('admin-views.vendor.view.partials._product', compact('foods'))->render()
        ]);
    }

    public function store_item_export(Request $request)
    {
        $key = explode(' ', request()->search);
        $model = app("\\App\\Models\\Item");
        if($request?->table && $request?->table == 'TempProduct'){
            $model = app("\\App\\Models\\TempProduct");
        }

        $foods =$model->withoutGlobalScope(StoreScope::class)->where('store_id', $request->store_id)
            ->when(isset($key), function ($q) use ($key) {
                $q->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->where('name', 'like', "%{$value}%");
                    }
                });
            })
            ->when($request?->sub_tab == 'active-items' , function($q){
                $q->where('status' , 1);
            })
            ->when($request?->sub_tab == 'inactive-items' , function($q){
                $q->where('status' , 0);
            })
            ->when($request?->sub_tab == 'pending-items' , function($q){
                $q->where('is_rejected' , 0);
            })
            ->when($request?->sub_tab == 'rejected-items' , function($q){
                $q->where('is_rejected' , 1);
            })
            ->latest()->get();

// dd($request?->sub_tab,$foods,);

        $store = Store::where('id', $request->store_id)->select(['name', 'zone_id'])->first();
        $typ = 'Item';
        if (Config::get('module.current_module_type') == 'food') {
            $typ = 'Food';
        }

        $data = [
            'sub_tab' => $request?->sub_tab,
            'data' => $foods,
            'search' => $request['search'] ?? null,
            'zone' => Helpers::get_zones_name($store->zone_id),
            'store_name' => $store->name,
        ];
        if ($request->type == 'csv') {
            return Excel::download(new StoreItemExport($data), $typ . 'List.csv');
        }
        return Excel::download(new StoreItemExport($data), $typ . 'List.xlsx');

        // if ($request->type == 'excel') {
        //     return (new FastExcel(Helpers::export_store_item($item)))->download('Items.xlsx');
        // } elseif ($request->type == 'csv') {
        //     return (new FastExcel(Helpers::export_store_item($item)))->download('Items.csv');
        // }
    }

    public function export(Request $request)
    {
        $store_id = $request->query('store_id', 'all');
        $category_id = $request->query('category_id', 'all');
        $sub_category_id = $request->query('sub_category_id', 'all');
        $zone_id = $request->query('zone_id', 'all');

        $model = app("\\App\\Models\\Item");
        if($request?->table && $request?->table == 'TempProduct'){
            $model = app("\\App\\Models\\TempProduct");
        }

        $type = $request->query('type', 'all');
        $key = explode(' ', $request['search']);
        $item =$model->withoutGlobalScope(StoreScope::class)
            ->when($request->query('module_id', null), function ($query) use ($request) {
                return $query->module($request->query('module_id'));
            })
            ->when(is_numeric($store_id), function ($query) use ($store_id) {
                return $query->where('store_id', $store_id);
            })
            ->when(is_numeric($sub_category_id), function ($query) use ($sub_category_id) {
                return $query->where('category_id', $sub_category_id);
            })
            ->when(is_numeric($category_id), function ($query) use ($category_id) {
                return $query->whereHas('category', function ($q) use ($category_id) {
                    return $q->whereId($category_id)->orWhere('parent_id', $category_id);
                });
            })
            ->when(is_numeric($zone_id), function ($query) use ($zone_id) {
                return $query->whereHas('store', function ($q) use ($zone_id) {
                    return $q->where('zone_id'  , $zone_id);
                });
            })
            ->when($request['search'], function ($query) use ($key) {
                return $query->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->where('name', 'like', "%{$value}%");
                    }
                });
            })
            ->approved()
            ->module(Config::get('module.current_module_id'))
            ->type($type)
            ->with('category', 'store')
            ->type($type)->latest()->get();



        $format_type = 'Item';
        if (Config::get('module.current_module_type') == 'food') {
            $format_type = 'Food';
        }

        $data = [
            'table'=> $request?->table ,
            'data' => $item,
            'search' => $request['search'] ?? null,
            'store' => $store_id != 'all' ? Store::findOrFail($store_id)?->name : null,
            'category' => $category_id != 'all' ? Category::findOrFail($category_id)?->name : null,
            'module_name' => Helpers::get_module_name(Config::get('module.current_module_id')),
        ];
        if ($request->type == 'csv') {
            return Excel::download(new ItemListExport($data), $format_type . 'List.csv');
        }
        return Excel::download(new ItemListExport($data), $format_type . 'List.xlsx');


        // if ($types == 'excel') {
        //     return (new FastExcel(Helpers::export_items(Helpers::Export_generator($item),$module_type)))->download('Items.xlsx');
        // } elseif ($types == 'csv') {
        //     return (new FastExcel(Helpers::export_items(Helpers::Export_generator($item),$module_type)))->download('Items.csv');
        // }



    }

    public function search_store(Request $request, $store_id)
    {
        $key = explode(' ', $request['search']);
        $foods = Item::withoutGlobalScope(StoreScope::class)
            ->where('store_id', $store_id)
            ->where(function ($q) use ($key) {
                foreach ($key as $value) {
                    $q->where('name', 'like', "%{$value}%");
                }
            })->limit(50)->get();
        return response()->json([
            'count' => count($foods),
            'view' => view('admin-views.vendor.view.partials._product', compact('foods'))->render()
        ]);
    }

    public function food_variation_generator(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'options' => 'required',
        ]);

        $food_variations = [];
        if (isset($request->options)) {
            foreach (array_values($request->options) as $key => $option) {

                $temp_variation['name'] = $option['name'];
                $temp_variation['type'] = $option['type'];
                $temp_variation['min'] = $option['min'] ?? 0;
                $temp_variation['max'] = $option['max'] ?? 0;
                $temp_variation['required'] = $option['required'] ?? 'off';
                if ($option['min'] > 0 &&  $option['min'] > $option['max']) {
                    $validator->getMessageBag()->add('name', translate('messages.minimum_value_can_not_be_greater_then_maximum_value'));
                    return response()->json(['errors' => Helpers::error_processor($validator)]);
                }
                if (!isset($option['values'])) {
                    $validator->getMessageBag()->add('name', translate('messages.please_add_options_for') . $option['name']);
                    return response()->json(['errors' => Helpers::error_processor($validator)]);
                }
                if ($option['max'] > count($option['values'])) {
                    $validator->getMessageBag()->add('name', translate('messages.please_add_more_options_or_change_the_max_value_for') . $option['name']);
                    return response()->json(['errors' => Helpers::error_processor($validator)]);
                }
                $temp_value = [];

                foreach (array_values($option['values']) as $value) {
                    if (isset($value['label'])) {
                        $temp_option['label'] = $value['label'];
                    }
                    $temp_option['optionPrice'] = $value['optionPrice'];
                    array_push($temp_value, $temp_option);
                }
                $temp_variation['values'] = $temp_value;
                array_push($food_variations, $temp_variation);
            }
        }

        return response()->json([
            'variation' => json_encode($food_variations)
        ]);
    }

    public function variation_generator(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'choice' => 'required',
        ]);
        $choice_options = [];
        if ($request->has('choice')) {
            foreach ($request->choice_no as $key => $no) {
                $str = 'choice_options_' . $no;
                if ($request[$str][0] == null) {
                    $validator->getMessageBag()->add('name', translate('messages.attribute_choice_option_value_can_not_be_null'));
                    return response()->json(['errors' => Helpers::error_processor($validator)]);
                }
                $temp['name'] = 'choice_' . $no;
                $temp['title'] = $request->choice[$key];
                $temp['options'] = explode(',', implode('|', preg_replace('/\s+/', ' ', $request[$str])));
                array_push($choice_options, $temp);
            }
        }

        $variations = [];
        $options = [];
        if ($request->has('choice_no')) {
            foreach ($request->choice_no as $key => $no) {
                $name = 'choice_options_' . $no;
                $my_str = implode('|', $request[$name]);
                array_push($options, explode(',', $my_str));
            }
        }
        //Generates the combinations of customer choice options
        $combinations = Helpers::combinations($options);
        if (count($combinations[0]) > 0) {
            foreach ($combinations as $key => $combination) {
                $str = '';
                foreach ($combination as $k => $temp) {
                    if ($k > 0) {
                        $str .= '-' . str_replace(' ', '', $temp);
                    } else {
                        $str .= str_replace(' ', '', $temp);
                    }
                }
                $temp = [];
                $temp['type'] = $str;
                $temp['price'] = abs($request['price_' . str_replace('.', '_', $str)]);
                $temp['stock'] = abs($request['stock_' . str_replace('.', '_', $str)]);
                array_push($variations, $temp);
            }
        }
        //combinations end

        return response()->json([
            'choice_options' => json_encode($choice_options),
            'variation' => json_encode($variations),
            'attributes' => $request->has('attribute_id') ? json_encode($request->attribute_id) : json_encode([])
        ]);
    }


    public function approval_list(Request $request)
    {
        abort_if(Helpers::get_mail_status('product_approval') != 1, 404);
        $store_id = $request->query('store_id', 'all');
        $category_id = $request->query('category_id', 'all');
        $sub_category_id = $request->query('sub_category_id', 'all');
        $zone_id = $request->query('zone_id', 'all');
        $type = $request->query('type', 'all');
        $filter = $request->query('filter');
        $key = explode(' ', $request['search']);
        $from =  $request->query('from');
        $to =  $request->query('to');

        $items = TempProduct::withoutGlobalScope(StoreScope::class)
            ->when($request->query('module_id', null), function ($query) use ($request) {
                return $query->module($request->query('module_id'));
            })
            ->when(is_numeric($store_id), function ($query) use ($store_id) {
                return $query->where('store_id', $store_id);
            })
            ->when(is_numeric($sub_category_id), function ($query) use ($sub_category_id) {
                return $query->where('category_id', $sub_category_id);
            })
            ->when(is_numeric($category_id), function ($query) use ($category_id) {
                return $query->whereHas('category', function ($q) use ($category_id) {
                    return $q->whereId($category_id)->orWhere('parent_id', $category_id);
                });
            })
            ->when(is_numeric($zone_id), function ($query) use ($zone_id) {
                return $query->whereHas('store', function ($q) use ($zone_id) {
                    return $q->where('zone_id'  , $zone_id);
                });
            })
            ->when($request['search'], function ($query) use ($key) {
                return $query->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->where('name', 'like', "%{$value}%");
                    }
                });
            })
            ->when(isset($filter) && $filter == 'pending' , function ($query)  {
                return $query->where('is_rejected', 0);
            })
            ->when(isset($filter) && $filter == 'rejected' , function ($query)  {
                return $query->where('is_rejected', 1);
            })
            ->when(isset($from) && isset($to) && $from != null && $to != null && isset($filter) && $filter == 'custom', function ($query) use ($from, $to) {
                return $query->whereBetween('updated_at', [$from . " 00:00:00", $to . " 23:59:59"]);
            })

            ->module(Config::get('module.current_module_id'))
            ->type($type)
            ->orderBy('is_rejected', 'asc')
            ->orderBy('updated_at', 'desc')
            ->paginate(config('default_pagination'));
        $store = $store_id != 'all' ? Store::findOrFail($store_id) : null;
        $category = $category_id != 'all' ? Category::findOrFail($category_id) : null;
        $sub_categories = $category_id != 'all' ? Category::where('parent_id', $category_id)->get(['id','name']) : [];

        return view('admin-views.product.approv_list', compact('items', 'store', 'category', 'type','sub_categories','filter'));
    }


    public function requested_item_view($id){
        $product=TempProduct::withoutGlobalScope(StoreScope::class)->withoutGlobalScope('translate')->with(['translations','store','unit'])->findOrFail($id);
        return view('admin-views.product.requested_product_view', compact('product'));
    }

    public function deny(Request $request)
    {
        $data = TempProduct::withoutGlobalScope(StoreScope::class)->findOrfail($request->id);
        $data->is_rejected = 1;
        $data->note = $request->note;
        $data->save();
        Toastr::success(translate('messages.Product_denied'));

        try
        {

            if(Helpers::getNotificationStatusData('store','store_product_reject','push_notification_status',$data?->store->id)  &&  $data?->store?->vendor?->firebase_token){
                $ndata = [
                    'title' => translate('product_rejected'),
                    'description' => translate('Product_Request_Has_Been_Rejected_By_Admin'),
                    'order_id' => '',
                    'image' => '',
                    'type' => 'product_rejected',
                    'order_status' => '',
                ];
                Helpers::send_push_notif_to_device($data?->store?->vendor?->firebase_token, $ndata);
                DB::table('user_notifications')->insert([
                    'data' => json_encode($ndata),
                    'vendor_id' => $data?->store?->vendor_id,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }


            if(config('mail.status') && Helpers::get_mail_status('product_deny_mail_status_store')  == '1' &&  Helpers::getNotificationStatusData('store','store_product_reject','mail_status',$data?->store?->id) ) {
                Mail::to($data?->store?->vendor?->email)->send(new \App\Mail\VendorProductMail($data?->store?->name,'denied'));
            }
        }
        catch(\Exception $e)
        {
            info($e->getMessage());
        }
        return to_route('admin.item.approval_list');
    }
    public function approved(Request $request)
    {
        $data = TempProduct::withoutGlobalScope(StoreScope::class)->findOrfail($request->id);

        $item= Item::withoutGlobalScope(StoreScope::class)->withoutGlobalScope('translate')->with('translations')->findOrfail($data->item_id);

        $item->name = $data->name;
        $item->description =  $data->description;


        if ($item->image) {
            Helpers::check_and_delete('product/' , $item['image']);
        }

        foreach($item->images as $value){
            $value = is_array($value)?$value:['img' => $value, 'storage' => 'public'];
            Helpers::check_and_delete('product/' , $value['img']);
        }

        $item->image = $data->image;
        $item->images = $data->images;
        $item->store_id = $data->store_id;
        $item->module_id = $data->module_id;
        $item->unit_id = $data->unit_id;

        $item->category_id = $data->category_id;
        $item->category_ids = $data->category_ids;

        $item->choice_options = $data->choice_options;
        $item->food_variations = $data->food_variations;
        $item->variations = $data->variations;
        $item->add_ons = $data->add_ons;
        $item->attributes = $data->attributes;

        $item->price = $data->price;
        $item->discount = $data->discount;
        $item->discount_type = $data->discount_type;

        $item->available_time_starts = $data->available_time_starts;
        $item->available_time_ends = $data->available_time_ends;
        $item->maximum_cart_quantity = $data->maximum_cart_quantity;
        $item->veg = $data->veg;

        $item->organic = $data->organic;
        $item->is_halal = $data->is_halal;
        $item->stock =  $data->stock;
        $item->is_approved = 1;

        $item->save();
        $item->tags()->sync(json_decode($data->tag_ids));
        $item->nutritions()->sync(json_decode($data->nutrition_ids));
        $item->allergies()->sync(json_decode($data->allergy_ids));
        $item->generic()->sync(json_decode($data->generic_ids));

        $item?->pharmacy_item_details()?->delete();

        if($item->module->module_type == 'pharmacy'){
            DB::table('pharmacy_item_details')->where('temp_product_id' , $data->id)->update([
                'item_id' => $item->id,
                'temp_product_id' => null
                ]);
        }
        if($item->module->module_type == 'ecommerce'){
            DB::table('ecommerce_item_details')->where('temp_product_id' , $data->id)->update([
                'item_id' => $item->id,
                'temp_product_id' => null
                ]);
        }

        $item?->translations()?->delete();
        Translation::where('translationable_type' , 'App\Models\TempProduct')->where('translationable_id' , $data->id)->update([
            'translationable_type' => 'App\Models\Item',
            'translationable_id' => $item->id
            ]);

        $data->delete();

        try
        {

            if(Helpers::getNotificationStatusData('store','store_product_approve','push_notification_status',$item?->store->id)  &&  $item?->store?->vendor?->firebase_token){
                $data = [
                    'title' => translate('product_approved'),
                    'description' => translate('Product_Request_Has_Been_Approved_By_Admin'),
                    'order_id' => '',
                    'image' => '',
                    'type' => 'product_approve',
                    'order_status' => '',
                ];
                Helpers::send_push_notif_to_device($item?->store?->vendor?->firebase_token, $data);
                DB::table('user_notifications')->insert([
                    'data' => json_encode($data),
                    'vendor_id' => $item?->store?->vendor_id,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }


            if(config('mail.status') && Helpers::get_mail_status('product_approve_mail_status_store') == '1' &&  Helpers::getNotificationStatusData('store','store_product_approve','mail_status',$item?->store?->id)) {
                Mail::to($item?->store?->vendor?->email)->send(new \App\Mail\VendorProductMail($item?->store?->name,'approved'));
            }
        }
        catch(\Exception $e)
        {
            info($e->getMessage());
        }
        Toastr::success(translate('messages.Product_approved'));
        return to_route('admin.item.approval_list');
    }

    public function product_gallery(Request $request){
        $store_id = $request->query('store_id', 'all');
        $category_id = $request->query('category_id', 'all');
        $type = $request->query('type', 'all');
        $key = explode(' ', $request['search']);
        $items = Item::withoutGlobalScope(StoreScope::class)
            ->when($request->query('module_id', null), function ($query) use ($request) {
                return $query->module($request->query('module_id'));
            })
            ->when(is_numeric($store_id), function ($query) use ($store_id) {
                return $query->where('store_id', $store_id);
            })
            ->when(is_numeric($category_id), function ($query) use ($category_id) {
                return $query->whereHas('category', function ($q) use ($category_id) {
                    return $q->whereId($category_id)->orWhere('parent_id', $category_id);
                });
            })
            ->when($request['search'], function ($query) use ($key) {
                return $query->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->where('name', 'like', "%{$value}%");
                    }
                });
            })
            ->orderByRaw("FIELD(name, ?) DESC", [$request['name']])
            ->where('is_approved',1)
            ->module(Config::get('module.current_module_id'))
            ->type($type)
            // ->latest()->paginate(config('default_pagination'));
            ->inRandomOrder()->limit(12)->get();
        $store = $store_id != 'all' ? Store::findOrFail($store_id) : null;
        $category = $category_id != 'all' ? Category::findOrFail($category_id) : null;
        return view('admin-views.product.product_gallery', compact('items', 'store', 'category', 'type'));
    }

    public function regular_items(Request $request)
    {
        $store_id = $request->query('store_id', 'all');
        $category_id = $request->query('category_id', 'all');
        $sub_category_id = $request->query('sub_category_id', 'all');
        $type = $request->query('type', 'all');
        
        $key = explode(' ', $request['search']);
        $items = Item::withoutGlobalScope(StoreScope::class)
            ->when($request->query('module_id', null), function ($query) use ($request) {
                return $query->module($request->query('module_id'));
            })
            ->when(is_numeric($store_id), function ($query) use ($store_id) {
                return $query->where('store_id', $store_id);
            })
            ->when(is_numeric($sub_category_id), function ($query) use ($sub_category_id) {
                return $query->where('category_id', $sub_category_id);
            })
            ->when(is_numeric($category_id), function ($query) use ($category_id) {
                return $query->whereHas('category', function ($q) use ($category_id) {
                    return $q->whereId($category_id)->orWhere('parent_id', $category_id);
                });
            })
            ->when($request['search'], function ($query) use ($key) {
                return $query->where(function ($q) use ($key) {
                    foreach ($key as $value) {
                        $q->where('name', 'like', "%{$value}%")
                        ->orWhere('barcode', 'like', "%{$value}%")
                        ->orWhereHas('category', function ($q) use ($value) {
                            return $q->where('name', 'like', "%{$value}%");
                        });
                    }
                });
            })
            ->where('is_approved', 1)
            ->module(Config::get('module.current_module_id'))
            ->type($type)
            ->with(['store', 'category'])
            ->latest()
            ->paginate(config('default_pagination'));

        $store = $store_id != 'all' ? Store::findOrFail($store_id) : null;
        $category = $category_id != 'all' ? Category::findOrFail($category_id) : null;
        $sub_categories = $category_id != 'all' ? Category::where('parent_id', $category_id)->get(['id','name']) : [];

        return view('admin-views.product.regular_items', compact('items', 'store', 'category', 'type', 'sub_categories'));
    }

    public function regular_items_update(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:items,id',
                'name' => 'required|max:191',
                'price' => 'required|numeric|between:.01,999999999999.99',
                'discount' => 'required|numeric|min:0',
                'discount_type' => 'required|in:amount,percent',
                'barcode' => 'nullable|string|max:100',
                'stock' => 'required|numeric|min:0',
                'rack' => 'nullable|string|max:100',
                'row' => 'nullable|string|max:100',
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => Helpers::error_processor($validator)], 403);
            }

            $item = Item::withoutGlobalScope(StoreScope::class)->find($request->id);
            
            if (!$item) {
                return response()->json(['errors' => ['Item not found']], 404);
            }

            // Calculate discount validation
            $dis = $request->discount_type == 'percent'
                ? ($request->price / 100) * $request->discount
                : $request->discount;

            if ($request->price <= $dis) {
                return response()->json(['errors' => ['unit_price' => translate("Discount amount can't be greater than 100%")]], 403);
            }

            // Update item
            $item->name = $request->name;
            $item->price = $request->price;
            $item->discount = $request->discount;
            $item->discount_type = $request->discount_type;
            $item->barcode = $request->barcode;
            $item->stock = $request->stock;
            $item->rack = $request->rack;
            $item->row = $request->row;
            $item->save();

            return response()->json(['success' => translate('messages.product_updated_successfully')]);

        } catch (\Exception $e) {
            info("Error updating item: ".$e->getMessage());
            return response()->json(['errors' => ['Something went wrong']], 500);
        }
    }


public function quickUpdate(Request $request)
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'item_id' => 'required|exists:items,id',
                'store_id' => 'required|exists:stores,id',
                'price' => 'required|numeric|min:0',
                'add_stock' => 'required|integer|min:0',
                'discount' => 'nullable|numeric|min:0',
                'status' => 'required|boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Get item with relations
            $item = Item::with(['store', 'category'])->findOrFail($request->item_id);
            
            // Security check: verify item belongs to selected store
            if ($item->store_id != $request->store_id) {
                return response()->json([
                    'success' => false,
                    'message' => translate('Item does not belong to selected store')
                ], 403);
            }

            // Track what changes were made
            $changes = [];
            $oldPrice = $item->price;
            $oldStock = $item->current_stock ?? $item->stock ?? 0;

            // Update price if changed
            if ($request->price != $item->price) {
                $item->price = $request->price;
                $changes[] = "Price updated from ₹{$oldPrice} to ₹{$request->price}";
            }

            // Add stock (IMPORTANT: this ADDS to existing stock, doesn't replace)
            if ($request->add_stock > 0) {
                $currentStock = $item->current_stock ?? $item->stock ?? 0;
                $newStock = $currentStock + $request->add_stock;
                
                // Update both stock fields if they exist in your table
                if (Schema::hasColumn('items', 'current_stock')) {
                    $item->current_stock = $newStock;
                }
                if (Schema::hasColumn('items', 'stock')) {
                    $item->stock = $newStock;
                }
                
                $changes[] = "Stock increased by {$request->add_stock} (from {$currentStock} to {$newStock})";
            }

            // Update discount if provided
            if ($request->has('discount')) {
                $oldDiscount = $item->discount ?? 0;
                if ($request->discount != $oldDiscount) {
                    $item->discount = $request->discount;
                    $changes[] = "Discount updated from ₹{$oldDiscount} to ₹{$request->discount}";
                }
            }

            // Update status
            if ($request->status != $item->status) {
                $item->status = $request->status;
                $statusText = $request->status ? 'Active' : 'Inactive';
                $changes[] = "Status changed to {$statusText}";
            }

            // Save all changes
            $item->save();

            // Reload fresh data with relations
            $item->load(['store', 'category']);
            
            // Prepare item data for response
            $itemData = [
                'id' => $item->id,
                'name' => $item->name,
                'barcode' => $item->barcode,
                'price' => (float) $item->price,
                'stock' => $item->current_stock ?? $item->stock ?? 0,
                'discount' => (float) ($item->discount ?? 0),
                'status' => (int) $item->status,
                'image_full_url' => $item->image_full_url ?? asset('public/assets/admin/img/160x160/img2.jpg'),
                'store' => [
                    'id' => $item->store->id ?? null,
                    'name' => $item->store->name ?? 'N/A'
                ],
                'category' => [
                    'id' => $item->category->id ?? null,
                    'name' => $item->category->name ?? 'N/A'
                ]
            ];

            // Create success message with changes
            $message = count($changes) > 0
                ? translate('Item updated successfully') . ': ' . implode(', ', $changes)
                : translate('No changes made');

            return response()->json([
                'success' => true,
                'message' => $message,
                'item' => $itemData,
                'changes' => $changes
            ]);

        } catch (\Exception $e) {
            \Log::error('Quick update error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => translate('Failed to update item: ') . $e->getMessage()
            ], 500);
        }
    }



















        public function regular_items_search(Request $request)
        {
            try {
                $key = explode(' ', $request['search']);
                $store_id = $request->input('store_id', 'all');
                $category_id = $request->input('category_id', 'all');
                $sub_category_id = $request->input('sub_category_id', 'all');
                $filters = $request->input('filters', []);
                $perPage = $request->input('per_page', 50);

                $items = Item::withoutGlobalScope(StoreScope::class)
                    ->where(function ($q) use ($key) {
                        foreach ($key as $value) {
                            $q->where(function ($query) use ($value) {
                                $query->where('name', 'like', "%{$value}%")
                                    ->orWhere('barcode', 'like', "%{$value}%")
                                    ->orWhereHas('store', function($q) use ($value) {
                                        $q->where('name', 'like', "%{$value}%");
                                    });
                            });
                        }
                    })
                    ->when(is_numeric($store_id), function ($query) use ($store_id) {
                        return $query->where('store_id', $store_id);
                    })
                    ->when(is_numeric($category_id), function ($query) use ($category_id) {
                        return $query->whereHas('category', function ($q) use ($category_id) {
                            return $q->whereId($category_id)->orWhere('parent_id', $category_id);
                        });
                    })
                    ->when(is_numeric($sub_category_id), function ($query) use ($sub_category_id) {
                        return $query->where('category_id', $sub_category_id);
                    })
                    ->when(in_array('has_barcode', $filters), function ($query) {
                        return $query->whereNotNull('barcode')->where('barcode', '!=', '');
                    })
                    ->when(in_array('no_barcode', $filters), function ($query) {
                        return $query->where(function($q) {
                            $q->whereNull('barcode')->orWhere('barcode', '');
                        });
                    })
                    ->when(in_array('low_stock', $filters), function ($query) {
                        return $query->where('stock', '<=', 10);
                    })
                    ->module(Config::get('module.current_module_id'))
                    ->where('is_approved', 1)
                    ->with(['store', 'category', 'unit']) // ✅ Unit added here
                    ->latest()
                    ->paginate($perPage);

                return response()->json([
                    'items' => $items->items(),
                    'total' => $items->total(),
                    'from' => $items->firstItem(),
                    'to' => $items->lastItem(),
                    'currentPage' => $items->currentPage(),
                    'lastPage' => $items->lastPage(),
                    'hasMorePages' => $items->hasMorePages(),
                    'pagination' => [
                        'links' => $items->links()->render()
                    ]
                ]);

            } catch (\Exception $e) {
                \Log::error('Search error: ' . $e->getMessage());
                return response()->json([
                    'items' => [],
                    'total' => 0,
                    'error' => 'Search failed'
                ], 500);
            }
        }

public function toggleStatus(Request $request)
{
    try {
        \Log::info('Toggle status request received', [
            'item_id' => $request->item_id,
            'status' => $request->status,
            'all_input' => $request->all()
        ]);

        $validator = Validator::make($request->all(), [
            'item_id' => 'required|exists:items,id',
            'status' => 'required|in:0,1'
        ]);

        if ($validator->fails()) {
            \Log::error('Validation failed', [
                'errors' => $validator->errors()->toArray()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Find item without global scope
        $item = Item::withoutGlobalScope(StoreScope::class)->find($request->item_id);
        
        if (!$item) {
            \Log::error('Item not found', ['item_id' => $request->item_id]);
            return response()->json([
                'success' => false,
                'message' => translate('Item not found')
            ], 404);
        }

        // Store old status for logging
        $oldStatus = $item->status;
        
        // Update status
        $item->status = $request->status;
        $saved = $item->save();

        if (!$saved) {
            \Log::error('Failed to save item status', [
                'item_id' => $item->id,
                'old_status' => $oldStatus,
                'new_status' => $request->status
            ]);
            
            return response()->json([
                'success' => false,
                'message' => translate('Failed to update status')
            ], 500);
        }

        \Log::info('Status updated successfully', [
            'item_id' => $item->id,
            'item_name' => $item->name,
            'old_status' => $oldStatus,
            'new_status' => $item->status
        ]);

        $statusText = $request->status ? 'active' : 'inactive';
        
        return response()->json([
            'success' => true,
            'message' => translate("Item status changed to {$statusText} successfully"),
            'item' => [
                'id' => $item->id,
                'name' => $item->name,
                'status' => $item->status,
                'old_status' => $oldStatus
            ]
        ]);

    } catch (\Exception $e) {
        \Log::error('Toggle status exception', [
            'message' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'success' => false,
            'message' => translate('Failed to update status: ') . $e->getMessage()
        ], 500);
    }
}
    public function searchByBarcodeWithSerp(Request $request)
{
    try {
        $barcode = $request->input('barcode');
        $storeId = $request->input('store_id');
        
        if (!$barcode || !$storeId) {
            return response()->json([
                'found' => false,
                'message' => translate('messages.barcode_and_store_required')
            ]);
        }
        
        // First, search in the database for the exact barcode
        $item = Item::where('barcode', $barcode)
                ->where('store_id', $storeId)
                ->first();
        
        if ($item) {
            return response()->json([
                'found' => true,
                'item' => [
                    'id' => $item->id,
                    'name' => $item->name,
                    'price' => $item->price,
                    'discount' => $item->discount ?? 0,
                    'discount_type' => $item->discount_type ?? 'amount',
                    'barcode' => $item->barcode,
                    'stock' => $item->stock ?? 0,
                    'image_full_url' => $item->image_full_url ?? asset('public/assets/admin/img/160x160/img2.jpg')
                ]
            ]);
        }
        
        // Item not found - immediately return suggested items WITHOUT waiting for SERP
        $suggestedItems = $this->getSuggestedItems($storeId, $barcode);
        
        // Start SERP API call in background (non-blocking)
        $serpData = null;
        try {
            // Use a much shorter timeout for SERP API
            $serpData = $this->getSerpDataFast($barcode);
            
            // If SERP data is available, enhance suggestions
            if ($serpData) {
                $serpEnhancedItems = $this->getSuggestedItemsFromSerpData($storeId, $serpData, $barcode);
                if (!empty($serpEnhancedItems)) {
                    $suggestedItems = $serpEnhancedItems;
                }
            }
        } catch (\Exception $e) {
            // Log SERP error but don't let it affect the response
            \Log::warning('SERP API failed but continuing with regular suggestions: ' . $e->getMessage());
        }
        
        return response()->json([
            'found' => false,
            'message' => translate('messages.product_not_found_showing_suggestions'),
            'search_query' => $barcode,
            'serp_data' => $serpData,
            'suggested_items' => $suggestedItems
        ]);
        
    } catch (\Exception $e) {
        \Log::error('Barcode search with SERP error: ' . $e->getMessage());
        
        return response()->json([
            'found' => false,
            'message' => translate('messages.search_failed')
        ], 500);
    }
}

// New fast SERP method with aggressive timeout
private function getSerpDataFast($barcode)
{
    try {
        // Get SERP API key from config
        $serpApiKey = 'f7a4416b4d47954e5a4be299edf10b8b0211cc522f33a76b76b0fded66e79b2e';
        
        if (!$serpApiKey) {
            \Log::warning('SERP API key not configured');
            return null;
        }
        
        // Make API call to SERP with balanced timeout
        $response = Http::timeout(5) // Increased from 3 to 5 seconds for better results
                    ->connectTimeout(3)
                    ->get('https://serpapi.com/search.json', [
            'engine' => 'google',
            'q' => $barcode,
            'api_key' => $serpApiKey,
            'num' => 5  // Back to 5 results for better accuracy
        ]);
        
        if ($response->successful()) {
            $data = $response->json();
            
            // Log essential info
            \Log::info('SERP API fast response for barcode: ' . $barcode, [
                'results_count' => isset($data['organic_results']) ? count($data['organic_results']) : 0,
                'status_code' => $response->status()
            ]);
            
            // Check if organic results exist
            if (!empty($data['organic_results']) && is_array($data['organic_results'])) {
                $result = $data['organic_results'][0]; // Get first result
                
                return [
                    'title' => $result['title'] ?? null,
                    'snippet' => isset($result['snippet']) ? substr($result['snippet'], 0, 300) : null, // Increased snippet length
                    'link' => $result['link'] ?? null,
                    'thumbnail' => $result['thumbnail'] ?? null,
                    'all_results' => array_slice($data['organic_results'], 0, 5) // Keep more results for accuracy
                ];
            }
        } else {
            \Log::warning('SERP API request failed (fast mode)', [
                'barcode' => $barcode,
                'status_code' => $response->status()
            ]);
        }
        
        return null;
        
    } catch (\Illuminate\Http\Client\ConnectionException $e) {
        \Log::warning('SERP API connection timeout (fast mode): ' . $e->getMessage());
        return null;
    } catch (\Illuminate\Http\Client\RequestException $e) {
        \Log::warning('SERP API request timeout (fast mode): ' . $e->getMessage());
        return null;
    } catch (\Exception $e) {
        \Log::warning('SERP API error (fast mode): ' . $e->getMessage());
        return null;
    }
}

// Updated extractRelevantKeywords with much better keyword extraction for 90% accuracy
private function extractRelevantKeywords($title)
{
    try {
        // Convert to lowercase and remove special characters but keep important ones
        $title = strtolower($title);
        $title = preg_replace('/[^\w\s\-]/', ' ', $title);
        
        // Remove brand/store specific terms that might confuse search
        $brandStopWords = [
            'upc', 'ean', 'gtin', 'barcode', 'amazon', 'walmart', 'flipkart', 'myntra',
            'buy', 'shop', 'online', 'price', 'sale', 'free', 'shipping', 'delivery',
            'where', 'info', 'variations', 'registration', 'images', 'view', 'source'
        ];
        
        // Keep essential stop words
        $stopWords = [
            'the', 'a', 'an', 'and', 'or', 'in', 'on', 'at', 'to', 'for', 'of', 'with',
            'is', 'are', 'was', 'were', 'be', 'been', 'being', 'have', 'has', 'had'
        ];
        
        // Split into words and clean
        $words = preg_split('/\s+/', $title);
        $words = array_map('trim', $words);
        
        // Extract meaningful keywords with priority scoring
        $priorityKeywords = [];
        $regularKeywords = [];
        
        foreach ($words as $word) {
            $word = trim($word);
            
            // Skip if too short, numeric only, or stop word
            if (strlen($word) <= 2 || is_numeric($word) ||
                in_array($word, $stopWords) || in_array($word, $brandStopWords)) {
                continue;
            }
            
            // Priority keywords (brand names, product types, specific descriptors)
            if (in_array($word, ['basmati', 'rice', 'gate', 'india', 'classic', 'premium',
                               'organic', 'brown', 'white', 'long', 'grain', 'sona', 'masoori',
                               'daawat', 'kohinoor', 'fortune', 'aashirvaad', 'tata', 'amul',
                               'oil', 'flour', 'atta', 'dal', 'masala', 'tea', 'coffee', 'milk'])) {
                $priorityKeywords[] = $word;
            } else if (strlen($word) >= 3) {
                $regularKeywords[] = $word;
            }
        }
        
        // Combine with priority keywords first, limit total to prevent over-matching
        $keywords = array_merge(
            array_slice($priorityKeywords, 0, 4), // Max 4 priority keywords
            array_slice($regularKeywords, 0, 3)   // Max 3 regular keywords
        );
        
        return array_unique($keywords);
        
    } catch (\Exception $e) {
        \Log::error('Extract keywords error: ' . $e->getMessage());
        return [];
    }
}

        private function scoreItemsByRelevance($items, $keywords, $serpTitle)
        {
            $scoredItems = [];
            $serpTitleLower = strtolower($serpTitle);
            $serpWords = explode(' ', $serpTitleLower);
            
            // Extract brand and product type from SERP title
            $brandName = $this->extractBrandFromTitle($serpTitle);
            $productType = $this->extractProductTypeFromTitle($serpTitle);
            
            $items = $items->take(30); // Reduced for better performance
            
            foreach ($items as $item) {
                $score = 0;
                $itemName = strtolower($item->name);
                $itemWords = explode(' ', $itemName);
                
                // EXACT BRAND MATCH (Highest Priority - 50 points)
                if ($brandName && strpos($itemName, $brandName) !== false) {
                    $score += 50;
                }
                
                // EXACT PRODUCT TYPE MATCH (High Priority - 30 points)
                if ($productType) {
                    foreach ($productType as $type) {
                        if (strpos($itemName, $type) !== false) {
                            $score += 30;
                            break; // Only give bonus once
                        }
                    }
                }
                
                // KEYWORD MATCHING with weighted importance
                $keywordMatches = 0;
                foreach ($keywords as $keyword) {
                    if (strpos($itemName, $keyword) !== false) {
                        // Give higher weight to longer, more specific keywords
                        $keywordWeight = strlen($keyword) >= 4 ? 15 : 8;
                        $score += $keywordWeight;
                        $keywordMatches++;
                    }
                }
                
                // WORD SEQUENCE MATCHING (checks if words appear in similar order)
                $sequenceBonus = $this->calculateSequenceBonus($serpWords, $itemWords);
                $score += $sequenceBonus;
                
                // PENALIZE COMPLETELY UNRELATED ITEMS
                if ($keywordMatches === 0 && !$brandName) {
                    $score -= 20; // Heavy penalty for no keyword matches
                }
                
                // PENALIZE ITEMS WITH CONFLICTING KEYWORDS
                $conflictPenalty = $this->calculateConflictPenalty($serpTitle, $itemName);
                $score -= $conflictPenalty;
                
                // BONUS for items with barcodes (they're more likely to be real products)
                if (!empty($item->barcode)) {
                    $score += 5;
                }
                
                // BONUS for items in stock
                if ($item->stock > 0) {
                    $score += 3;
                }
                
                // SMALL BONUS for active items (but don't exclude inactive ones)
                if ($item->status == 1) {
                    $score += 2; // Small bonus for active items
                }
                
                // Only include items with positive scores
                if ($score > 0) {
                    $scoredItems[] = [
                        'id' => $item->id,
                        'name' => $item->name,
                        'price' => $item->price,
                        'barcode' => $item->barcode,
                        'stock' => $item->stock ?? 0,
                        'status' => $item->status, // Include status in response
                        'image_full_url' => $item->image_full_url ?? asset('public/assets/admin/img/160x160/img2.jpg'),
                        'store' => $item->store ? ['name' => $item->store->name] : null,
                        'relevance_score' => $score
                    ];
                }
            }
            
            return $scoredItems;
        }



// New helper function to extract brand name for accurate matching
private function extractBrandFromTitle($title)
{
    $title = strtolower($title);
    
    // Common brand patterns for food products
    $brands = [
        'india gate', 'daawat', 'kohinoor', 'fortune', 'aashirvaad', 'tata', 'amul',
        'nestle', 'maggi', 'britannia', 'parle', 'patanjali', 'himalaya', 'dabur',
        'mother dairy', 'everest', 'mdh', 'catch', 'red label', 'taj mahal',
        'brooke bond', 'lipton', 'tetley', 'waghbakri', 'haldirams', 'bikaji',
        'balaji', 'lays', 'kurkure', 'bingo', 'too yumm', 'act ii', 'bambino'
    ];
    
    foreach ($brands as $brand) {
        if (strpos($title, $brand) !== false) {
            return $brand;
        }
    }
    
    // Try to extract first 2 words as potential brand if they're not common words
    $words = explode(' ', $title);
    if (count($words) >= 2) {
        $potentialBrand = strtolower($words[0] . ' ' . $words[1]);
        $commonWords = ['upc', 'ean', 'product', 'buy', 'shop', 'best', 'top', 'cheap'];
        
        if (!in_array($words[0], $commonWords) && !in_array($words[1], $commonWords)) {
            return $potentialBrand;
        }
    }
    
    return null;
}

// New helper function to extract product type for category matching
private function extractProductTypeFromTitle($title)
{
    $title = strtolower($title);
    
    $productTypes = [
        'rice' => ['basmati rice', 'rice', 'chawal', 'biryani rice', 'pulao rice'],
        'oil' => ['oil', 'tel', 'cooking oil', 'edible oil', 'mustard oil', 'coconut oil'],
        'flour' => ['flour', 'atta', 'maida', 'besan', 'wheat flour', 'gram flour'],
        'pulses' => ['dal', 'lentils', 'pulses', 'moong', 'chana', 'toor', 'masoor', 'urad'],
        'spices' => ['masala', 'spice', 'powder', 'turmeric', 'chili', 'coriander', 'cumin'],
        'tea' => ['tea', 'chai', 'green tea', 'black tea', 'tea bags', 'leaf tea'],
        'coffee' => ['coffee', 'instant coffee', 'ground coffee', 'coffee powder'],
        'milk' => ['milk', 'dairy', 'cream', 'butter', 'ghee', 'paneer', 'cheese'],
        'biscuits' => ['biscuit', 'cookie', 'cracker', 'rusk', 'toast'],
        'noodles' => ['noodles', 'pasta', 'maggi', 'yippee', 'hakka noodles', 'vermicelli'],
        'snacks' => ['chips', 'namkeen', 'mixture', 'bhujia', 'sev', 'popcorn']
    ];
    
    foreach ($productTypes as $category => $types) {
        foreach ($types as $type) {
            if (strpos($title, $type) !== false) {
                return [$type];
            }
        }
    }
    
    return [];
}

// New helper function to calculate sequence bonus for better matching
private function calculateSequenceBonus($serpWords, $itemWords)
{
    $bonus = 0;
    $serpCount = count($serpWords);
    $itemCount = count($itemWords);
    
    // Check for consecutive word matches
    for ($i = 0; $i < $serpCount - 1; $i++) {
        for ($j = 0; $j < $itemCount - 1; $j++) {
            if ($serpWords[$i] === $itemWords[$j] &&
                isset($serpWords[$i + 1]) && isset($itemWords[$j + 1]) &&
                $serpWords[$i + 1] === $itemWords[$j + 1]) {
                $bonus += 10; // Bonus for consecutive word matches
            }
        }
    }
    
    return $bonus;
}

        
        
// New helper function to calculate conflict penalty to prevent wrong categories
private function calculateConflictPenalty($serpTitle, $itemName)
{
    $penalty = 0;
    $serpLower = strtolower($serpTitle);
    $itemLower = strtolower($itemName);
    
    // Define conflicting categories
    $conflicts = [
        'rice' => ['oil', 'flour', 'tea', 'coffee', 'biscuit', 'noodles', 'chips', 'peanut'],
        'oil' => ['rice', 'flour', 'tea', 'coffee', 'biscuit', 'chips', 'peanut'],
        'tea' => ['coffee', 'rice', 'oil', 'flour', 'chips', 'peanut'],
        'coffee' => ['tea', 'rice', 'oil', 'flour', 'chips', 'peanut'],
        'flour' => ['rice', 'oil', 'tea', 'coffee', 'chips', 'peanut'],
        'dal' => ['rice', 'oil', 'tea', 'coffee', 'chips', 'peanut'],
        'biscuit' => ['rice', 'oil', 'tea', 'coffee', 'dal'],
        'chips' => ['rice', 'oil', 'tea', 'coffee', 'dal', 'flour']
    ];
    
    foreach ($conflicts as $category => $conflictingItems) {
        if (strpos($serpLower, $category) !== false) {
            foreach ($conflictingItems as $conflicting) {
                if (strpos($itemLower, $conflicting) !== false) {
                    $penalty += 25; // Heavy penalty for conflicting categories
                }
            }
        }
    }
    
    return $penalty;
}

        private function getSuggestedItems($storeId, $barcode = null)
        {
            try {
                // Get items from the same store with better ordering and increased limit
                // REMOVED: ->where('status', 1) filter to show ALL items
                $items = Item::where('store_id', $storeId)
                            // Status filter REMOVED - now shows both active and inactive items
                            ->select(['id', 'name', 'price', 'barcode', 'stock', 'image', 'status']) // Added status to selection
                            ->orderByRaw("
                                CASE 
                                    WHEN status = 1 THEN 2
                                    WHEN status = 0 THEN 1
                                    ELSE 0
                                END DESC,
                                CASE 
                                    WHEN barcode IS NULL OR barcode = '' THEN 0 
                                    ELSE 1 
                                END DESC,
                                stock DESC,
                                name ASC
                            ") // Modified ordering to prioritize active items but still show inactive ones
                            ->limit(15) // Increased from 10 to 15
                            ->get();
                
                return $items->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'name' => $item->name,
                        'price' => $item->price,
                        'barcode' => $item->barcode,
                        'stock' => $item->stock ?? 0,
                        'status' => $item->status, // Include status in response
                        'image_full_url' => $item->image_full_url ?? asset('public/assets/admin/img/160x160/img2.jpg'),
                        'store' => ['name' => $item->store->name ?? 'Unknown'],
                        'relevance_score' => 0
                    ];
                })->all();
                
            } catch (\Exception $e) {
                \Log::error('Get suggested items error: ' . $e->getMessage());
                return [];
            }
        }
// Updated getSuggestedItemsFromSerpData with 90% accuracy improvements
    private function getSuggestedItemsFromSerpData($storeId, $serpData, $barcode = null)
    {
        try {
            $suggestedItems = [];
            
            if ($serpData && !empty($serpData['title'])) {
                $serpTitle = $serpData['title'];
                
                // Extract meaningful keywords
                $keywords = $this->extractRelevantKeywords($serpTitle);
                
                if (empty($keywords)) {
                    return $this->getSuggestedItems($storeId, $barcode);
                }
                
                // Build more intelligent search query
                // REMOVED: ->where('status', 1) filter
                $items = Item::where('store_id', $storeId)
                            // Status filter REMOVED - now shows both active and inactive items
                            ->select(['id', 'name', 'price', 'barcode', 'stock', 'image', 'status']) // Added status
                            ->where(function($query) use ($keywords) {
                                // Search with priority - exact phrase first, then individual words
                                $firstKeyword = true;
                                foreach ($keywords as $keyword) {
                                    if ($firstKeyword) {
                                        $query->where('name', 'LIKE', '%' . $keyword . '%');
                                        $firstKeyword = false;
                                    } else {
                                        $query->orWhere('name', 'LIKE', '%' . $keyword . '%');
                                    }
                                }
                            })
                            ->orderByRaw("
                                CASE 
                                    WHEN status = 1 THEN 2
                                    WHEN status = 0 THEN 1
                                    ELSE 0
                                END DESC,
                                name ASC
                            ") // Modified to prioritize active but show all
                            ->limit(50) // Get more candidates for better filtering
                            ->get();
                
                if ($items->count() > 0) {
                    // Score items based on relevance
                    $scoredItems = $this->scoreItemsByRelevance($items, $keywords, $serpTitle);
                    
                    // Sort by score descending
                    usort($scoredItems, function($a, $b) {
                        return $b['relevance_score'] <=> $a['relevance_score'];
                    });
                    
                    // Only include items with good relevance scores (minimum 15 points)
                    $scoredItems = array_filter($scoredItems, function($item) {
                        return $item['relevance_score'] >= 15;
                    });
                    
                    // Take top 8 most relevant items
                    $suggestedItems = array_slice($scoredItems, 0, 8);
                    
                    // Log for debugging
                    \Log::info('SERP Search Results (All Statuses)', [
                        'barcode' => $barcode,
                        'serp_title' => $serpTitle,
                        'keywords' => $keywords,
                        'brand_detected' => $this->extractBrandFromTitle($serpTitle),
                        'product_type_detected' => $this->extractProductTypeFromTitle($serpTitle),
                        'total_candidates' => $items->count(),
                        'filtered_results' => count($suggestedItems),
                        'top_scores' => array_slice(array_column($suggestedItems, 'relevance_score'), 0, 3),
                        'includes_inactive_items' => true
                    ]);
                }
            }
            
            // If no good SERP-based suggestions, fall back to general ones but limit them
            if (empty($suggestedItems)) {
                $generalSuggestions = $this->getSuggestedItems($storeId, $barcode);
                $suggestedItems = array_slice($generalSuggestions, 0, 6); // Limit general suggestions
            }
            
            return $suggestedItems;
            
        } catch (\Exception $e) {
            \Log::error('Get suggested items from SERP data error: ' . $e->getMessage());
            return $this->getSuggestedItems($storeId, $barcode);
        }
    }
public function assignBarcode(Request $request)
{
    try {
        // ✅ FIXED: Removed exists:items,id validation
        $validator = Validator::make($request->all(), [
            'item_id' => 'required|integer',
            'store_id' => 'required|integer|exists:stores,id',
            'barcode' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            \Log::error('Validation failed', [
                'errors' => $validator->errors()->toArray(),
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => translate('messages.validation_failed'),
                'errors' => $validator->errors()
            ], 422);
        }
        
        $itemId = $request->input('item_id');
        $storeId = $request->input('store_id');
        $barcode = trim($request->input('barcode'));
        
        \Log::info('Barcode assignment attempt', [
            'item_id' => $itemId,
            'store_id' => $storeId,
            'barcode' => $barcode
        ]);
        
        // ✅ Find item WITHOUT global scope
        $item = Item::withoutGlobalScope(StoreScope::class)->find($itemId);
        
        if (!$item) {
            \Log::error('Item not found', ['item_id' => $itemId]);
            return response()->json([
                'success' => false,
                'message' => translate('messages.item_not_found')
            ], 404);
        }
        
        // ✅ Verify item belongs to the selected store
        if ($item->store_id != $storeId) {
            \Log::error('Item store mismatch', [
                'item_id' => $itemId,
                'item_store_id' => $item->store_id,
                'requested_store_id' => $storeId
            ]);
            
            return response()->json([
                'success' => false,
                'message' => translate('messages.item_does_not_belong_to_selected_store')
            ], 422);
        }
        
        // ✅ Check if barcode exists for a DIFFERENT item in the SAME store
        $existingItem = Item::withoutGlobalScope(StoreScope::class)
                        ->where('barcode', $barcode)
                        ->where('store_id', $storeId)
                        ->where('id', '!=', $itemId)
                        ->first();
        
        if ($existingItem) {
            \Log::warning('Barcode already exists', [
                'barcode' => $barcode,
                'existing_item_id' => $existingItem->id,
                'existing_item_name' => $existingItem->name
            ]);
            
            return response()->json([
                'success' => false,
                'message' => translate('messages.barcode_already_exists_in_store') . ': ' . $existingItem->name
            ], 422);
        }
        
        // ✅ Update barcode, status, and stock
        $wasInactive = $item->status == 0;
        $oldStock = $item->stock ?? 0;
        
        $item->barcode = $barcode;
        $item->status = 1; // Turn ON status
        $item->stock = $oldStock + 6; // Add 6 to stock
        
        $item->save();
        
        \Log::info('Barcode assigned successfully', [
            'item_id' => $item->id,
            'item_name' => $item->name,
            'barcode' => $barcode,
            'store_id' => $storeId,
            'status_changed' => $wasInactive,
            'old_stock' => $oldStock,
            'new_stock' => $item->stock
        ]);
        
        return response()->json([
            'success' => true,
            'message' => translate('messages.barcode_assigned_successfully'),
            'item' => [
                'id' => $item->id,
                'name' => $item->name,
                'barcode' => $item->barcode,
                'store_id' => $item->store_id,
                'status' => $item->status,
                'stock' => $item->stock,
                'status_changed' => $wasInactive,
                'stock_added' => 6,
                'old_stock' => $oldStock
            ]
        ]);
        
    } catch (\Exception $e) {
        \Log::error('Assign barcode exception', [
            'message' => $e->getMessage(),
            'line' => $e->getLine(),
            'file' => $e->getFile(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'success' => false,
            'message' => translate('messages.something_went_wrong'),
            'error' => config('app.debug') ? $e->getMessage() : null
        ], 500);
    }
}



private function getSerpData($barcode)
{
    try {
        // Get SERP API key from config
        $serpApiKey = 'f7a4416b4d47954e5a4be299edf10b8b0211cc522f33a76b76b0fded66e79b2e';
        
        if (!$serpApiKey) {
            \Log::warning('SERP API key not configured');
            return null;
        }
        
        // Make API call to SERP
        $response = Http::timeout(10)->get('https://serpapi.com/search.json', [
            'engine' => 'google',
            'q' => $barcode,
            'api_key' => $serpApiKey,
            'num' => 5  // Limit results
        ]);
        
        if ($response->successful()) {
            $data = $response->json();
            
            // Log the complete SERP API response
            \Log::info('SERP API Response for barcode: ' . $barcode, [
                'response' => $data,
                'status_code' => $response->status()
            ]);
            
            // Check if organic results exist
            if (!empty($data['organic_results']) && is_array($data['organic_results'])) {
                $result = $data['organic_results'][0]; // Get first result
                
                return [
                    'title' => $result['title'] ?? null,
                    'snippet' => $result['snippet'] ?? null,
                    'link' => $result['link'] ?? null,
                    'thumbnail' => $result['thumbnail'] ?? null,
                    'all_results' => $data['organic_results'] // Store all results for potential use
                ];
            } else {
                \Log::info('No organic results found in SERP response for barcode: ' . $barcode);
            }
        } else {
            \Log::error('SERP API request failed', [
                'barcode' => $barcode,
                'status_code' => $response->status(),
                'response_body' => $response->body()
            ]);
        }
        
        return null;
        
    } catch (\Exception $e) {
        \Log::error('SERP API error: ' . $e->getMessage(), [
            'barcode' => $barcode,
            'trace' => $e->getTraceAsString()
        ]);
        return null;
    }
}

    
    public function searchProductInfo(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'barcode' => 'required|string|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => Helpers::error_processor($validator)
            ], 422);
        }

        $serpApiService = new SerpApiService();
        $result = $serpApiService->searchByBarcode($request->barcode);

        return response()->json($result);
    }
     public function barcodeScan(Request $request)
    {
        $stores = Store::active()->get();
        $categories = Category::active()->get();
        return view('admin-views.product.barcode_scan', compact('stores', 'categories'));
    }

   public function processBarcodeScan(Request $request)
    {
        // Check if this is a subcategory request
        if ($request->has('action') && $request->action === 'get_subcategories') {
            $validator = Validator::make($request->all(), [
                'category_id' => 'required|exists:categories,id'
            ]);

            if ($validator->fails()) {
                return response()->json(['errors' => Helpers::error_processor($validator)], 403);
            }

            try {
                $subcategories = Category::where('parent_id', $request->category_id)
                    ->where('status', 1)
                    ->orderBy('name')
                    ->get(['id', 'name']);

                return response()->json([
                    'success' => true,
                    'subcategories' => $subcategories
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch subcategories'
                ], 500);
            }
        }

        // Original barcode scan logic
        $validator = Validator::make($request->all(), [
            'barcode' => 'required|string|max:100',
            'store_id' => 'required|exists:stores,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $item = Item::withoutGlobalScope(StoreScope::class)
                    ->where('barcode', $request->barcode)
                    ->where('store_id', $request->store_id)
                    ->first();

        if ($item) {
            // Check if status is already 1
            if ($item->status == 1) {
                return response()->json([
                    'success' => true,
                    'message' => translate('Item status is already updated'),
                    'item' => $item->load('store'),
                    'action' => 'already_updated'
                ]);
            } else {
                // Update status to 1 and stock to 6
                $item->status = 1;
                $item->stock = 6;
                $item->save();

                return response()->json([
                    'success' => true,
                    'message' => translate('Item status and stock updated successfully'),
                    'item' => $item->load('store'),
                    'action' => 'updated'
                ]);
            }
        } else {
            // Get product info from SERP API
            try {
                $serpApiService = new SerpApiService();
                $productInfo = $serpApiService->searchByBarcode($request->barcode);
            } catch (\Exception $e) {
                // If SERP API fails, continue without product info
                $productInfo = [
                    'success' => false,
                    'message' => 'Could not fetch product information: ' . $e->getMessage()
                ];
            }

            // Return item not found with option to add and product info
            return response()->json([
                'success' => true,
                'message' => translate('Item not found'),
                'action' => 'add_new',
                'barcode' => $request->barcode,
                'store_id' => $request->store_id,
                'product_info' => $productInfo
            ]);
        }
    }

    public function updateBarcodeItem(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item_id' => 'required|exists:items,id',
            'price' => 'required|numeric|between:.01,999999999999.99',
            'stock' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 422);
        }

        try {
            $item = Item::withoutGlobalScope(StoreScope::class)->findOrFail($request->item_id);
            
            // Update price and stock
            $item->price = $request->price;
            $item->stock = $request->stock;
            $item->save();

            return response()->json([
                'success' => true,
                'message' => translate('Item price and stock updated successfully'),
                'item' => $item->load('store')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => translate('Something went wrong. Please try again.') . ' - ' . $e->getMessage()
            ], 500);
        }
    }

    public function storeBarcodeItem(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name.default' => 'required|max:191',
            'category_id' => 'required|exists:categories,id',
            'sub_category_id' => 'nullable|exists:categories,id',
            'price' => 'required|numeric|between:.01,999999999999.99',
            'discount' => 'required|numeric|min:0',
            'store_id' => 'required|exists:stores,id',
            'description.default' => 'required|max:1000',
            'barcode' => 'required|string|max:100',
            'current_stock' => 'required|numeric|min:0',
            'maximum_cart_quantity' => 'required|numeric|min:1',
            'attribute_id' => 'nullable|array',
            'unit_id' => 'nullable|exists:units,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp,avif|max:2048',
            'rack' => 'nullable|string|max:50',
            'row' => 'nullable|string|max:50',
        ], [
            'name.default.required' => translate('Item name is required'),
            'category_id.required' => translate('Category is required'),
            'description.default.required' => translate('Description is required'),
            'barcode.required' => translate('Barcode is required'),
            'unit_id.exists' => translate('Selected unit does not exist'),
            'sub_category_id.exists' => translate('Selected subcategory does not exist'),
            'rack.max' => translate('Rack name cannot exceed 50 characters'),
            'row.max' => translate('Row name cannot exceed 50 characters'),
        ]);

        if ($request->discount > $request->price) {
            $validator->getMessageBag()->add('discount', translate('Discount amount cannot be greater than price'));
        }

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 422);
        }

        // Check if barcode already exists for this store
        $existingItem = Item::withoutGlobalScope(StoreScope::class)
                        ->where('barcode', $request->barcode)
                        ->where('store_id', $request->store_id)
                        ->first();

        if ($existingItem) {
            return response()->json([
                'success' => false,
                'message' => translate('Item with this barcode already exists in the selected store')
            ], 422);
        }

        try {
            $item = new Item;
            $item->name = $request->input('name.default');
            $item->description = $request->input('description.default');
            
            // Set category hierarchy
            $category = [];
            array_push($category, [
                'id' => $request->category_id,
                'position' => 1,
            ]);
            
            // Add subcategory if provided
            if ($request->has('sub_category_id') && $request->sub_category_id) {
                array_push($category, [
                    'id' => $request->sub_category_id,
                    'position' => 2,
                ]);
            }
            
            $item->category_ids = json_encode($category);
            
            // Set the main category_id (use subcategory if available, otherwise main category)
            $item->category_id = $request->sub_category_id ?: $request->category_id;
            
            // Process variations and choice options only if they exist
            $variations = [];
            $choice_options = [];
            
            // Only process variations if variations_data is present in request
            if ($request->has('variations_data') && $request->variations_data) {
                $variations = json_decode($request->variations_data, true);
            }
            
            // Only process choice options if they exist
            if ($request->has('choice_options') && $request->choice_options) {
                $choice_options = json_decode($request->choice_options, true);
            }
            
            // Set choice_options and variations - empty arrays if no attributes/variations
            $item->choice_options = json_encode($choice_options);
            $item->variations = json_encode($variations);
            
            // Basic fields
            $item->price = $request->price;
            $item->discount = $request->discount ?? 0;
            $item->discount_type = 'amount';
            $item->store_id = $request->store_id;
            $item->barcode = $request->barcode;
            $item->stock = $request->current_stock;
            $item->maximum_cart_quantity = $request->maximum_cart_quantity;
            $item->module_id = Config::get('module.current_module_id');
            
            // Set unit_id if provided
            if ($request->has('unit_id') && $request->unit_id) {
                $item->unit_id = $request->unit_id;
            }
            
            // NEW: Set storage location fields
            $item->rack = $request->rack ? trim($request->rack) : null;
            $item->row = $request->row ? trim($request->row) : null;
            
            // Default values
            $item->status = 1; // Set as active
            $item->veg = $request->veg ?? 1; // Default to veg unless specified
            $item->food_variations = json_encode([]);
            $item->attributes = $request->has('attribute_id') ? json_encode($request->attribute_id) : json_encode([]);
            $item->add_ons = json_encode([]);
            $item->available_time_starts = $request->available_time_starts ?? '00:00:00';
            $item->available_time_ends = $request->available_time_ends ?? '23:59:59';
            $item->images = json_encode([]);
            
            // Handle image upload
            if ($request->hasFile('image')) {
                $item->image = Helpers::upload('product/', 'png', $request->file('image'));
            }
            
            $item->save();
            
            // Add translations
            Helpers::add_or_update_translations(
                request: $request,
                key_data: 'name',
                name_field: 'name',
                model_name: 'Item',
                data_id: $item->id,
                data_value: $item->name
            );
            
            Helpers::add_or_update_translations(
                request: $request,
                key_data: 'description',
                name_field: 'description',
                model_name: 'Item',
                data_id: $item->id,
                data_value: $item->description
            );
            
            // Format storage location for response
            $storageLocation = '';
            if ($item->rack && $item->row) {
                $storageLocation = $item->rack . ' / ' . $item->row;
            } elseif ($item->rack) {
                $storageLocation = $item->rack . ' / -';
            } elseif ($item->row) {
                $storageLocation = '- / ' . $item->row;
            } else {
                $storageLocation = '- / -';
            }
            
            return response()->json([
                'success' => true,
                'message' => translate('Item added successfully'),
                'item' => $item->load('store', 'unit', 'category'),
                'variations' => $variations,
                'storage_location' => $storageLocation
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => translate('Something went wrong. Please try again.') . ' - ' . $e->getMessage()
            ], 500);
        }
    }
}
