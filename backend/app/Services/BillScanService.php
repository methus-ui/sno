<?php

namespace App\Services;

use App\Models\Order;
use App\Models\StoreBillPattern;
use App\Models\StoreBillLayout;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class BillScanService
{
    protected $apiKey;
    protected $model;
    protected $baseUrl = 'https://api.anthropic.com/v1/messages';
    protected $tesseractService;

    public function __construct()
    {
        $this->apiKey = config('services.anthropic.api_key');
        // Use Haiku for cost efficiency - lowest credit consumption
        $this->model = config('services.anthropic.bill_scan_model', 'claude-3-haiku-20240307');
        $this->tesseractService = new TesseractOcrService();
    }

    /**
     * Scan bill image and verify against order data
     * Uses hybrid approach: Tesseract OCR first, AI fallback if confidence is low
     *
     * @param Order $order
     * @return array
     */
    public function scanBill(Order $order): array
    {
        // Get bill image data first
        $billImageData = $this->getBillImageBase64($order);
        if (!$billImageData['success']) {
            return $billImageData;
        }

        $scanMethod = 'ai'; // Default
        $extractionResult = null;
        $tesseractConfidence = 0;
        $tesseractResult = null;

        // Try Tesseract OCR first if enabled
        if (config('services.bill_scan.tesseract_enabled', true)) {
            $tesseractResult = $this->scanWithTesseract($order);

            if ($tesseractResult['success']) {
                $tesseractConfidence = (int) $tesseractResult['confidence'];
                $confidenceThreshold = (int) config('services.bill_scan.confidence_threshold', 70);

                // If confidence is good enough, use Tesseract result
                if ($tesseractConfidence >= $confidenceThreshold) {
                    $extractionResult = [
                        'success' => true,
                        'data' => $tesseractResult['data'],
                    ];
                    $scanMethod = 'tesseract';

                    Log::info('BillScanService: Using Tesseract OCR result', [
                        'order_id' => $order->id,
                        'confidence' => $tesseractConfidence,
                    ]);
                } else {
                    Log::info('BillScanService: Tesseract confidence too low, checking fallback', [
                        'order_id' => $order->id,
                        'confidence' => $tesseractConfidence,
                        'threshold' => $confidenceThreshold,
                    ]);
                }
            }
        }

        // Fall back to AI if Tesseract didn't provide good enough result
        if ($extractionResult === null) {
            $aiFallbackEnabled = config('services.bill_scan.ai_fallback_enabled', true);

            // Check if AI fallback is disabled - use Tesseract result anyway
            if (!$aiFallbackEnabled && $tesseractResult && $tesseractResult['success']) {
                $extractionResult = [
                    'success' => true,
                    'data' => $tesseractResult['data'],
                ];
                $scanMethod = 'tesseract';

                Log::info('BillScanService: AI fallback disabled, using Tesseract result', [
                    'order_id' => $order->id,
                    'confidence' => $tesseractConfidence,
                ]);
            }

            // Try AI if fallback is enabled and we still don't have a result
            if ($extractionResult === null && $aiFallbackEnabled) {
                if (empty($this->apiKey)) {
                    // No API key - use Tesseract result if available
                    if ($tesseractResult && $tesseractResult['success']) {
                        $extractionResult = [
                            'success' => true,
                            'data' => $tesseractResult['data'],
                        ];
                        $scanMethod = 'tesseract';
                        Log::info('BillScanService: No API key, using Tesseract result', [
                            'order_id' => $order->id,
                            'confidence' => $tesseractConfidence,
                        ]);
                    } else {
                        return [
                            'success' => false,
                            'error' => 'Anthropic API key is not configured and Tesseract OCR failed.',
                        ];
                    }
                } else {
                    // Try AI extraction
                    $aiResult = $this->extractBillData($billImageData['base64'], $billImageData['mime_type']);

                    Log::info('BillScanService: Attempted AI for bill extraction', [
                        'order_id' => $order->id,
                        'ai_success' => $aiResult['success'],
                        'tesseract_confidence' => $tesseractConfidence,
                    ]);

                    if ($aiResult['success']) {
                        $extractionResult = $aiResult;
                        $scanMethod = 'ai';
                    } else {
                        // AI failed - fall back to Tesseract result if available
                        if ($tesseractResult && $tesseractResult['success']) {
                            $extractionResult = [
                                'success' => true,
                                'data' => $tesseractResult['data'],
                            ];
                            $scanMethod = 'tesseract';

                            Log::info('BillScanService: AI failed, using Tesseract result anyway', [
                                'order_id' => $order->id,
                                'confidence' => $tesseractConfidence,
                                'ai_error' => $aiResult['error'] ?? 'unknown',
                            ]);
                        } else {
                            // Both failed
                            return $aiResult;
                        }
                    }
                }
            }
        }

        // Final fallback - if we still have no result but have Tesseract data, use it
        if ($extractionResult === null && $tesseractResult && $tesseractResult['success']) {
            $extractionResult = [
                'success' => true,
                'data' => $tesseractResult['data'],
            ];
            $scanMethod = 'tesseract';
        }

        if (!$extractionResult || !$extractionResult['success']) {
            return [
                'success' => false,
                'error' => 'Failed to extract bill data. Please try again.',
            ];
        }

        // Verify extracted data against order
        $verificationResult = $this->verifyAgainstOrder($extractionResult['data'], $order);

        // Learn patterns from successful scan
        $rawText = $tesseractResult['raw_text'] ?? '';
        if (!empty($rawText) && !empty($extractionResult['data'])) {
            try {
                $this->learnPatterns($order, $extractionResult['data'], $rawText);
            } catch (\Exception $e) {
                Log::warning('BillScanService: Pattern learning failed', ['error' => $e->getMessage()]);
            }
        }

        // Learn positions and OCR corrections from verification
        try {
            $this->learnFromVerification($order, $extractionResult['data'], $rawText, $verificationResult);
        } catch (\Exception $e) {
            Log::warning('BillScanService: Layout learning failed', ['error' => $e->getMessage()]);
        }

        return [
            'success' => true,
            'scanned_at' => now()->toIso8601String(),
            'scanned_by' => auth('admin')->id(),
            'scan_method' => $scanMethod,
            'tesseract_confidence' => $tesseractConfidence,
            'raw_extraction' => $extractionResult['data'],
            'checks' => $verificationResult['checks'],
            'overall_status' => $verificationResult['overall_status'],
        ];
    }

    /**
     * Scan bill using Tesseract OCR
     *
     * @param Order $order
     * @return array
     */
    protected function scanWithTesseract(Order $order): array
    {
        try {
            // Get the image file path
            $imagePath = $this->getBillImagePath($order);

            if (!$imagePath['success']) {
                return [
                    'success' => false,
                    'error' => $imagePath['error'],
                    'confidence' => 0,
                    'data' => null,
                ];
            }

            // Load learned patterns and layouts for this store
            $learnedPatterns = $this->loadLearnedPatterns($order->store_id);
            $learnedLayouts = $this->loadLearnedLayouts($order->store_id);

            // Process image with Tesseract
            $result = $this->tesseractService->processImage($imagePath['path'], $learnedPatterns, $learnedLayouts);

            // Clean up temp file if created
            if (isset($imagePath['is_temp']) && $imagePath['is_temp'] && file_exists($imagePath['path'])) {
                @unlink($imagePath['path']);
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('BillScanService: Tesseract OCR failed', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Tesseract OCR failed: ' . $e->getMessage(),
                'confidence' => 0,
                'data' => null,
            ];
        }
    }

    /**
     * Get bill image file path for Tesseract
     *
     * @param Order $order
     * @return array
     */
    protected function getBillImagePath(Order $order): array
    {
        $billData = is_array($order->bill_image)
            ? $order->bill_image
            : (is_string($order->bill_image) ? json_decode($order->bill_image, true) : []);

        if (empty($billData)) {
            return [
                'success' => false,
                'error' => 'No bill image found for this order.',
            ];
        }

        $firstImage = $billData[0] ?? null;
        if (!$firstImage) {
            return [
                'success' => false,
                'error' => 'Bill image data is empty.',
            ];
        }

        $imageInfo = is_array($firstImage)
            ? $firstImage
            : ['img' => $firstImage, 'storage' => 'public'];

        $filename = $imageInfo['img'];
        $storage = $imageInfo['storage'] ?? 'public';

        try {
            $disk = $storage === 's3' ? 's3' : 'public';
            $path = 'order/' . $filename;

            if (!Storage::disk($disk)->exists($path)) {
                return [
                    'success' => false,
                    'error' => 'Bill image file not found in storage.',
                ];
            }

            // For local storage, get the full path
            if ($disk === 'public') {
                $fullPath = Storage::disk($disk)->path($path);
                return [
                    'success' => true,
                    'path' => $fullPath,
                    'is_temp' => false,
                ];
            }

            // For S3, download to temp file
            $content = Storage::disk($disk)->get($path);
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $tempPath = sys_get_temp_dir() . '/bill_' . $order->id . '_' . time() . '.' . $extension;
            file_put_contents($tempPath, $content);

            return [
                'success' => true,
                'path' => $tempPath,
                'is_temp' => true,
            ];

        } catch (\Exception $e) {
            Log::error('BillScanService: Error getting bill image path', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'error' => 'Failed to get bill image path: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Get bill image as base64
     */
    protected function getBillImageBase64(Order $order): array
    {
        $billData = is_array($order->bill_image)
            ? $order->bill_image
            : (is_string($order->bill_image) ? json_decode($order->bill_image, true) : []);

        if (empty($billData)) {
            return [
                'success' => false,
                'error' => 'No bill image found for this order.',
            ];
        }

        // Get the first bill image
        $firstImage = $billData[0] ?? null;
        if (!$firstImage) {
            return [
                'success' => false,
                'error' => 'Bill image data is empty.',
            ];
        }

        $imageInfo = is_array($firstImage)
            ? $firstImage
            : ['img' => $firstImage, 'storage' => 'public'];

        $filename = $imageInfo['img'];
        $storage = $imageInfo['storage'] ?? 'public';

        try {
            $disk = $storage === 's3' ? 's3' : 'public';
            $path = 'order/' . $filename;

            if (!Storage::disk($disk)->exists($path)) {
                return [
                    'success' => false,
                    'error' => 'Bill image file not found in storage.',
                ];
            }

            $content = Storage::disk($disk)->get($path);
            $base64 = base64_encode($content);

            // Determine mime type from extension
            $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            $mimeTypes = [
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'webp' => 'image/webp',
            ];
            $mimeType = $mimeTypes[$extension] ?? 'image/jpeg';

            return [
                'success' => true,
                'base64' => $base64,
                'mime_type' => $mimeType,
            ];

        } catch (\Exception $e) {
            Log::error('BillScanService: Error reading bill image', [
                'order_id' => $order->id,
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'error' => 'Failed to read bill image: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Extract bill data using Claude Vision
     */
    protected function extractBillData(string $base64Image, string $mimeType): array
    {
        $prompt = 'You are a bill/receipt OCR analyst. Analyze this bill/receipt image and extract the following information. Return ONLY valid JSON with no additional text or markdown formatting.

Extract these fields:
1. "store_name": The store/shop name printed on the bill
2. "bill_date": Date on the bill in YYYY-MM-DD format (if visible)
3. "customer_name": Customer/buyer name if shown (look for Name/Customer/Bill To/Sold To/M/s)
4. "items": Array of line items, each with {"name": "item name", "quantity": number, "unit_price": number, "total": number}
5. "subtotal": Subtotal before tax/discount (number, 0 if not shown)
6. "discount": Discount amount (number, 0 if none). Look for Discount/Disc/Less/Savings/You Save
7. "tax": Total tax amount (number, 0 if not shown). Sum up CGST+SGST or GST or VAT if separate
8. "grand_total": Final total amount to pay (number). This is the LAST/FINAL total on the bill. Different bills label this differently - look for: Grand Total, Net Total, Total Amount, Total Sales, Total Bill, Bill Total, Bill Amount, Amount Payable, Net Payable, You Pay, To Pay, Rounded Total, Total Due, Balance Due. Pick the largest final total value.
9. "currency_symbol": Currency symbol used (e.g., "Rs", "INR", "$", etc.)

Important:
- Extract ALL line items you can see on the bill - these are individual products/goods, NOT totals/tax/discount lines
- For prices, extract only the numeric value without currency symbols
- If a field is not visible or unclear, use null for strings and 0 for numbers
- The grand_total should be the FINAL payable amount after all taxes and discounts
- Return valid JSON only, no markdown code blocks

Example output format:
{"store_name":"ABC Store","bill_date":"2026-01-28","customer_name":"SnoCart","items":[{"name":"Rice 1kg","quantity":2,"unit_price":50,"total":100}],"subtotal":100,"discount":0,"tax":5,"grand_total":105,"currency_symbol":"Rs"}';

        try {
            $response = Http::timeout(60)
                ->withHeaders([
                    'x-api-key' => $this->apiKey,
                    'anthropic-version' => '2023-06-01',
                    'Content-Type' => 'application/json',
                ])
                ->post($this->baseUrl, [
                    'model' => $this->model,
                    'max_tokens' => 2048,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => [
                                [
                                    'type' => 'image',
                                    'source' => [
                                        'type' => 'base64',
                                        'media_type' => $mimeType,
                                        'data' => $base64Image,
                                    ],
                                ],
                                [
                                    'type' => 'text',
                                    'text' => $prompt,
                                ],
                            ],
                        ],
                    ],
                ]);

            if ($response->failed()) {
                $errorBody = $response->json();
                $errorMessage = $errorBody['error']['message'] ?? 'Unknown API error';
                Log::error('BillScanService: Claude API error', ['response' => $errorBody]);
                return [
                    'success' => false,
                    'error' => "Claude API error: {$errorMessage}",
                ];
            }

            $data = $response->json();
            $generatedText = $data['content'][0]['text'] ?? null;

            if (!$generatedText) {
                return [
                    'success' => false,
                    'error' => 'No content generated from Claude API',
                ];
            }

            // Clean up the response - remove markdown code blocks if present
            $generatedText = trim($generatedText);
            $generatedText = preg_replace('/^```(?:json)?\s*/is', '', $generatedText);
            $generatedText = preg_replace('/\s*```\s*$/is', '', $generatedText);
            $generatedText = trim($generatedText);

            // Try to extract JSON object if there's extra text
            $firstBrace = strpos($generatedText, '{');
            $lastBrace = strrpos($generatedText, '}');
            if ($firstBrace !== false && $lastBrace !== false && $lastBrace > $firstBrace) {
                $generatedText = substr($generatedText, $firstBrace, $lastBrace - $firstBrace + 1);
            }

            $parsed = json_decode($generatedText, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::warning('BillScanService: JSON parsing failed', ['text' => $generatedText]);
                return [
                    'success' => false,
                    'error' => 'Failed to parse bill data. Please try again.',
                ];
            }

            return [
                'success' => true,
                'data' => $parsed,
            ];

        } catch (\Exception $e) {
            Log::error('BillScanService: Exception during extraction', ['message' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => 'Failed to analyze bill: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Verify extracted bill data against order
     */
    protected function verifyAgainstOrder(array $billData, Order $order): array
    {
        $checks = [];
        $hasFailure = false;
        $hasWarning = false;

        // 1. Store name check (fuzzy match at 70%)
        $expectedStoreName = $order->store?->name ?? '';
        $foundStoreName = $billData['store_name'] ?? '';
        $storeNameMatch = $this->fuzzyMatch($expectedStoreName, $foundStoreName, 70);
        $checks['store_name'] = [
            'status' => $storeNameMatch ? 'pass' : 'fail',
            'expected' => $expectedStoreName,
            'found' => $foundStoreName,
        ];
        if (!$storeNameMatch) $hasFailure = true;

        // 2. Total price check (5% tolerance)
        $expectedTotal = (float) $order->order_amount;
        $foundTotal = (float) ($billData['grand_total'] ?? 0);
        $totalDiff = abs($expectedTotal - $foundTotal);
        $totalTolerance = $expectedTotal * 0.05;
        $totalMatch = $totalDiff <= $totalTolerance;
        $checks['total_price'] = [
            'status' => $totalMatch ? 'pass' : ($totalDiff <= $expectedTotal * 0.15 ? 'warn' : 'fail'),
            'expected' => $expectedTotal,
            'found' => $foundTotal,
            'difference' => round($totalDiff, 2),
        ];
        if (!$totalMatch) {
            if ($totalDiff <= $expectedTotal * 0.15) {
                $hasWarning = true;
            } else {
                $hasFailure = true;
            }
        }

        // 3. Item prices comparison
        $orderItems = [];
        foreach ($order->details as $detail) {
            $itemName = $detail->item?->name ?? ($detail->campaign?->name ?? 'Unknown Item');
            $orderItems[] = [
                'name' => $itemName,
                'quantity' => $detail->quantity,
                'price' => (float) $detail->price,
                'total' => (float) ($detail->price * $detail->quantity),
            ];
        }

        $billItems = $billData['items'] ?? [];
        $itemChecks = [];
        $extraItems = [];

        foreach ($billItems as $billItem) {
            $billItemName = $billItem['name'] ?? 'Unknown';
            $billItemPrice = (float) ($billItem['unit_price'] ?? $billItem['total'] ?? 0);
            $billItemTotal = (float) ($billItem['total'] ?? 0);
            $billItemQty = (int) ($billItem['quantity'] ?? 1);

            // Try to find matching order item
            $matched = false;
            foreach ($orderItems as $orderItem) {
                if ($this->fuzzyMatch($orderItem['name'], $billItemName, 60)) {
                    $priceDiff = abs($orderItem['price'] - $billItemPrice);
                    $priceTolerance = $orderItem['price'] * 0.10; // 10% tolerance for items
                    $priceMatch = $priceDiff <= $priceTolerance;

                    $itemChecks[] = [
                        'name' => $billItemName,
                        'matched_to' => $orderItem['name'],
                        'expected_price' => $orderItem['price'],
                        'found_price' => $billItemPrice,
                        'quantity' => $billItemQty,
                        'status' => $priceMatch ? 'pass' : 'warn',
                    ];

                    if (!$priceMatch) $hasWarning = true;
                    $matched = true;
                    break;
                }
            }

            if (!$matched) {
                $extraItems[] = [
                    'name' => $billItemName,
                    'price' => $billItemPrice,
                    'quantity' => $billItemQty,
                    'total' => $billItemTotal,
                ];
                $hasWarning = true;
            }
        }

        $checks['items'] = $itemChecks;
        $checks['extra_items'] = $extraItems;

        // 4. Discount check
        $expectedDiscount = (float) ($order->store_discount_amount + $order->coupon_discount_amount);
        $foundDiscount = (float) ($billData['discount'] ?? 0);
        $discountMatch = abs($expectedDiscount - $foundDiscount) <= max($expectedDiscount * 0.10, 5);
        $checks['discount'] = [
            'status' => $discountMatch ? 'pass' : 'warn',
            'expected' => $expectedDiscount,
            'found' => $foundDiscount,
        ];
        if (!$discountMatch) $hasWarning = true;

        // 5. Customer name check (should be "SnoCart")
        $expectedCustomer = 'SnoCart';
        $foundCustomer = $billData['customer_name'] ?? '';
        $customerMatch = $this->fuzzyMatch($expectedCustomer, $foundCustomer, 70) ||
                         stripos($foundCustomer, 'snocart') !== false ||
                         stripos($foundCustomer, 'sno cart') !== false;
        $checks['customer_name'] = [
            'status' => $customerMatch ? 'pass' : ($foundCustomer ? 'warn' : 'warn'),
            'expected' => $expectedCustomer,
            'found' => $foundCustomer ?: 'Not found on bill',
        ];
        if (!$customerMatch && $foundCustomer) $hasWarning = true;

        // 6. Date check
        $createdAt = $order->created_at;
        if (is_string($createdAt)) {
            $createdAt = \Carbon\Carbon::parse($createdAt);
        }
        $expectedDate = $createdAt->format('Y-m-d');
        $foundDate = $billData['bill_date'] ?? null;
        $dateMatch = $foundDate === $expectedDate;
        // Also allow 1 day difference
        if (!$dateMatch && $foundDate) {
            try {
                $diff = abs(strtotime($expectedDate) - strtotime($foundDate));
                $dateMatch = $diff <= 86400; // 1 day
            } catch (\Exception $e) {
                $dateMatch = false;
            }
        }
        $checks['date'] = [
            'status' => $dateMatch ? 'pass' : 'warn',
            'expected' => $expectedDate,
            'found' => $foundDate ?? 'Not found',
        ];
        if (!$dateMatch) $hasWarning = true;

        // Determine overall status
        $overallStatus = 'pass';
        if ($hasWarning) $overallStatus = 'warn';
        if ($hasFailure) $overallStatus = 'fail';

        return [
            'checks' => $checks,
            'overall_status' => $overallStatus,
        ];
    }

    /**
     * Load learned bill patterns for a store, grouped by field
     */
    protected function loadLearnedPatterns(?int $storeId): array
    {
        if (!$storeId) return [];

        try {
            $patterns = StoreBillPattern::forStore($storeId)->get();
            $grouped = [];
            foreach ($patterns as $p) {
                $grouped[$p->field][] = $p->pattern;
            }
            return $grouped;
        } catch (\Exception $e) {
            Log::warning('BillScanService: Failed to load learned patterns', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Learn patterns from a successful scan by finding which labels matched extracted values
     */
    public function learnPatterns(Order $order, array $extractionData, string $rawText): void
    {
        if (!$order->store_id || empty($rawText)) return;

        $fieldMap = [
            'grand_total' => 'total',
            'subtotal' => 'subtotal',
            'discount' => 'discount',
            'tax' => 'tax',
        ];

        $lines = array_map('trim', explode("\n", $rawText));

        foreach ($fieldMap as $dataKey => $field) {
            $value = (float) ($extractionData[$dataKey] ?? 0);
            if ($value <= 0) continue;

            // Format value for matching (with and without decimals)
            $formatted = number_format($value, 2, '.', '');
            $formattedNoDecimal = number_format($value, 0, '.', '');
            $formattedWithComma = number_format($value, 2, '.', ',');

            foreach ($lines as $line) {
                // Check if this line contains the extracted value
                if (strpos($line, $formatted) === false
                    && strpos($line, $formattedNoDecimal) === false
                    && strpos($line, $formattedWithComma) === false
                    && strpos($line, str_replace(',', '', $formattedWithComma)) === false) {
                    continue;
                }

                // Extract the label portion (text before the number)
                if (preg_match('/^(.+?)[:\s]+(?:rs\.?|inr|₹|\$)?\s*[\d,]+/', $line, $m)) {
                    $label = trim($m[1]);
                    // Skip if label is too short or too long or just punctuation
                    if (strlen($label) < 2 || strlen($label) > 100 || preg_match('/^[\-\=\_\*\#\.\s]+$/', $label)) {
                        continue;
                    }

                    try {
                        StoreBillPattern::updateOrCreate(
                            ['store_id' => $order->store_id, 'field' => $field, 'pattern' => $label],
                            ['frequency' => \DB::raw('frequency + 1')]
                        );

                        Log::info('BillScanService: Learned pattern', [
                            'store_id' => $order->store_id,
                            'field' => $field,
                            'pattern' => $label,
                        ]);
                    } catch (\Exception $e) {
                        Log::warning('BillScanService: Failed to save learned pattern', ['error' => $e->getMessage()]);
                    }

                    break; // Only learn the first matching line per field
                }
            }
        }
    }

    /**
     * Load learned bill layouts (positions + corrections) for a store
     */
    protected function loadLearnedLayouts(?int $storeId): array
    {
        if (!$storeId) return [];

        try {
            $layouts = StoreBillLayout::forStore($storeId)->get();
            $result = [];

            foreach ($layouts as $layout) {
                $field = $layout->field;

                // Store position data
                if ($layout->line_ratio !== null) {
                    $result[$field]['line_ratio'] = $layout->line_ratio;
                    $result[$field]['position'] = $layout->position;
                }

                // Store corrections
                if ($layout->correction_from && $layout->correction_to) {
                    $result[$field]['corrections'][] = [
                        'from' => $layout->correction_from,
                        'to' => $layout->correction_to,
                        'frequency' => $layout->frequency,
                    ];
                }
            }

            return $result;
        } catch (\Exception $e) {
            Log::warning('BillScanService: Failed to load learned layouts', ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Learn field positions and OCR corrections from a verified scan
     */
    public function learnFromVerification(Order $order, array $extractionData, string $rawText, array $verificationResult): void
    {
        if (!$order->store_id || empty($rawText)) return;

        $checks = $verificationResult['checks'] ?? [];
        $lines = array_map('trim', explode("\n", $rawText));
        $lines = array_filter($lines, fn($line) => !empty($line));
        $lines = array_values($lines);
        $totalLines = count($lines);

        if ($totalLines === 0) return;

        // Learn total position
        $grandTotal = (float) ($extractionData['grand_total'] ?? 0);
        if ($grandTotal > 0) {
            $this->learnFieldPosition($order->store_id, 'total', $grandTotal, $lines, $totalLines);
        }

        // Learn store name corrections
        $expectedStoreName = $order->store?->name ?? '';
        $foundStoreName = $extractionData['store_name'] ?? '';
        $storeCheck = $checks['store_name'] ?? [];

        if (!empty($expectedStoreName) && !empty($foundStoreName)
            && ($storeCheck['status'] ?? '') === 'pass'
            && strcasecmp($expectedStoreName, $foundStoreName) !== 0
        ) {
            // OCR gave a different but matching text — save as correction
            $this->saveCorrection($order->store_id, 'store_name', $foundStoreName, $expectedStoreName);
        }

        // Learn customer name corrections
        $expectedCustomer = 'SnoCart';
        $foundCustomer = $extractionData['customer_name'] ?? '';
        $customerCheck = $checks['customer_name'] ?? [];

        if (!empty($foundCustomer)
            && ($customerCheck['status'] ?? '') === 'pass'
            && strcasecmp($foundCustomer, $expectedCustomer) !== 0
        ) {
            $this->saveCorrection($order->store_id, 'customer_name', $foundCustomer, $expectedCustomer);
        }

        // Learn store name position
        if (!empty($foundStoreName)) {
            $this->learnFieldPositionByText($order->store_id, 'store_name', $foundStoreName, $lines, $totalLines);
        }
    }

    /**
     * Find which line a numeric value appears on and save its position ratio
     */
    protected function learnFieldPosition(int $storeId, string $field, float $value, array $lines, int $totalLines): void
    {
        $formatted = number_format($value, 2, '.', '');
        $formattedNoDecimal = number_format($value, 0, '.', '');

        foreach ($lines as $index => $line) {
            if (strpos($line, $formatted) !== false || strpos($line, $formattedNoDecimal) !== false) {
                $ratio = round($index / max($totalLines - 1, 1), 3);
                $position = $ratio <= 0.33 ? 'top' : ($ratio <= 0.66 ? 'middle' : 'bottom');

                try {
                    StoreBillLayout::updateOrCreate(
                        ['store_id' => $storeId, 'field' => $field, 'correction_from' => null, 'correction_to' => null],
                        ['position' => $position, 'line_ratio' => $ratio, 'frequency' => \DB::raw('frequency + 1')]
                    );
                } catch (\Exception $e) {
                    Log::warning('BillScanService: Failed to save field position', ['error' => $e->getMessage()]);
                }
                return;
            }
        }
    }

    /**
     * Find which line a text value appears on and save its position ratio
     */
    protected function learnFieldPositionByText(int $storeId, string $field, string $text, array $lines, int $totalLines): void
    {
        $lowerText = strtolower($text);
        foreach ($lines as $index => $line) {
            if (stripos($line, $lowerText) !== false || stripos($line, $text) !== false) {
                $ratio = round($index / max($totalLines - 1, 1), 3);
                $position = $ratio <= 0.33 ? 'top' : ($ratio <= 0.66 ? 'middle' : 'bottom');

                try {
                    StoreBillLayout::updateOrCreate(
                        ['store_id' => $storeId, 'field' => $field, 'correction_from' => null, 'correction_to' => null],
                        ['position' => $position, 'line_ratio' => $ratio, 'frequency' => \DB::raw('frequency + 1')]
                    );
                } catch (\Exception $e) {
                    Log::warning('BillScanService: Failed to save text field position', ['error' => $e->getMessage()]);
                }
                return;
            }
        }
    }

    /**
     * Save an OCR text correction mapping
     */
    protected function saveCorrection(int $storeId, string $field, string $from, string $to): void
    {
        try {
            StoreBillLayout::updateOrCreate(
                ['store_id' => $storeId, 'field' => $field, 'correction_from' => $from],
                ['correction_to' => $to, 'frequency' => \DB::raw('frequency + 1')]
            );

            Log::info('BillScanService: Learned OCR correction', [
                'store_id' => $storeId,
                'field' => $field,
                'from' => $from,
                'to' => $to,
            ]);
        } catch (\Exception $e) {
            Log::warning('BillScanService: Failed to save correction', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Fuzzy string matching using similar_text
     */
    protected function fuzzyMatch(string $str1, string $str2, int $threshold = 70): bool
    {
        if (empty($str1) || empty($str2)) {
            return false;
        }

        $str1 = strtolower(trim($str1));
        $str2 = strtolower(trim($str2));

        // Exact match
        if ($str1 === $str2) {
            return true;
        }

        // Contains match
        if (strpos($str1, $str2) !== false || strpos($str2, $str1) !== false) {
            return true;
        }

        // Similar text percentage
        similar_text($str1, $str2, $percent);
        return $percent >= $threshold;
    }
}
