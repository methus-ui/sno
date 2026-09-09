<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use thiagoalessio\TesseractOCR\TesseractOCR;

class TesseractOcrService
{
    protected $languages = ['eng', 'hin'];

    /**
     * Extract text from image using Tesseract OCR
     *
     * @param string $imagePath Full path to the image file
     * @return array
     */
    public function extractText(string $imagePath): array
    {
        try {
            $ocr = new TesseractOCR($imagePath);
            $ocr->lang(...$this->languages);
            $ocr->psm(6); // Assume a single uniform block of text

            $rawText = $ocr->run();

            if (empty(trim($rawText))) {
                return [
                    'success' => false,
                    'error' => 'No text could be extracted from the image',
                    'raw_text' => '',
                ];
            }

            return [
                'success' => true,
                'raw_text' => $rawText,
            ];

        } catch (\Exception $e) {
            Log::error('TesseractOcrService: OCR extraction failed', [
                'image_path' => $imagePath,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'OCR extraction failed: ' . $e->getMessage(),
                'raw_text' => '',
            ];
        }
    }

    /**
     * Parse raw OCR text into structured bill data
     *
     * @param string $rawText
     * @return array
     */
    public function parseBillText(string $rawText, array $learnedPatterns = [], array $learnedLayouts = []): array
    {
        $data = [
            'store_name' => null,
            'bill_date' => null,
            'customer_name' => null,
            'items' => [],
            'subtotal' => 0,
            'discount' => 0,
            'tax' => 0,
            'grand_total' => 0,
            'currency_symbol' => 'Rs',
        ];

        $lines = array_map('trim', explode("\n", $rawText));
        $lines = array_filter($lines, fn($line) => !empty($line));
        $lines = array_values($lines);

        // Extract store name (usually first 1-2 non-empty lines before any numbers/address)
        $data['store_name'] = $this->extractStoreName($lines);

        // Apply learned OCR corrections to store name
        $data['store_name'] = $this->applyLearnedCorrections($data['store_name'], 'store_name', $learnedLayouts);

        // Extract date
        $data['bill_date'] = $this->extractDate($rawText);

        // Extract customer name
        $data['customer_name'] = $this->extractCustomerName($rawText);

        // Apply learned OCR corrections to customer name
        $data['customer_name'] = $this->applyLearnedCorrections($data['customer_name'], 'customer_name', $learnedLayouts);

        // Extract grand total - try learned patterns first, with layout hints
        $totalLayoutRatio = $learnedLayouts['total']['line_ratio'] ?? null;
        $data['grand_total'] = $this->extractGrandTotal($rawText, $learnedPatterns['total'] ?? [], $lines, $totalLayoutRatio);

        // Extract subtotal
        $data['subtotal'] = $this->extractSubtotal($rawText, $learnedPatterns['subtotal'] ?? []);

        // Extract discount
        $data['discount'] = $this->extractDiscount($rawText, $learnedPatterns['discount'] ?? []);

        // Extract tax
        $data['tax'] = $this->extractTax($rawText, $learnedPatterns['tax'] ?? []);

        // Extract line items
        $data['items'] = $this->extractItems($lines);

        // Extract currency symbol
        $data['currency_symbol'] = $this->extractCurrency($rawText);

        return $data;
    }

    /**
     * Calculate confidence score based on extracted data
     *
     * @param array $data Parsed bill data
     * @return int Confidence score (0-100)
     */
    public function calculateConfidence(array $data): int
    {
        $score = 0;
        $weights = [
            'store_name' => 15,
            'grand_total' => 30,
            'items' => 25,
            'bill_date' => 10,
            'customer_name' => 10,
            'subtotal' => 5,
            'tax' => 5,
        ];

        // Store name
        if (!empty($data['store_name']) && strlen($data['store_name']) >= 3) {
            $score += $weights['store_name'];
        }

        // Grand total (most important)
        if (!empty($data['grand_total']) && $data['grand_total'] > 0) {
            $score += $weights['grand_total'];
        }

        // Items
        if (!empty($data['items']) && count($data['items']) > 0) {
            $itemScore = min(count($data['items']) / 3, 1) * $weights['items'];

            // Check if items have valid prices
            $validItems = array_filter($data['items'], function($item) {
                return !empty($item['name']) && ($item['total'] > 0 || $item['unit_price'] > 0);
            });

            if (count($validItems) > 0) {
                $score += $itemScore;
            }
        }

        // Date
        if (!empty($data['bill_date'])) {
            $score += $weights['bill_date'];
        }

        // Customer name
        if (!empty($data['customer_name'])) {
            $score += $weights['customer_name'];
        }

        // Subtotal
        if (!empty($data['subtotal']) && $data['subtotal'] > 0) {
            $score += $weights['subtotal'];
        }

        // Tax
        if (!empty($data['tax']) && $data['tax'] > 0) {
            $score += $weights['tax'];
        }

        return (int) min(100, $score);
    }

    /**
     * Try extracting a numeric value using learned label patterns
     */
    protected function extractWithLearnedLabels(string $text, array $labels): float
    {
        foreach ($labels as $label) {
            $escaped = preg_quote(trim($label), '/');
            $pattern = '/' . $escaped . '[:\s]*(?:rs\.?|inr|₹|\$)?\s*([\d,]+(?:\.\d{1,2})?)/i';
            if (preg_match($pattern, $text, $matches)) {
                $value = (float) str_replace(',', '', $matches[1]);
                if ($value > 0) {
                    return $value;
                }
            }
        }
        return 0;
    }

    /**
     * Extract store name from lines
     */
    protected function extractStoreName(array $lines): ?string
    {
        // Take first few lines and find the store name
        // Usually the store name is in the first 3 lines and doesn't contain typical address/phone patterns
        $addressPatterns = [
            '/\d{10}/',  // Phone numbers
            '/\d{6}/',   // PIN codes
            '/ph[:\.]?/i',
            '/tel[:\.]?/i',
            '/mob[:\.]?/i',
            '/address/i',
            '/road|street|lane|colony|nagar|area/i',
            '/gst[:\s]?[a-z0-9]/i',
            '/invoice|bill|receipt|tax/i',
        ];

        for ($i = 0; $i < min(5, count($lines)); $i++) {
            $line = $lines[$i];

            // Skip if it's a number line or very short
            if (strlen($line) < 3 || preg_match('/^[\d\s\-\.]+$/', $line)) {
                continue;
            }

            // Skip if it matches address/phone patterns
            $isAddress = false;
            foreach ($addressPatterns as $pattern) {
                if (preg_match($pattern, $line)) {
                    $isAddress = true;
                    break;
                }
            }

            if (!$isAddress) {
                // Clean up the line
                $cleaned = preg_replace('/[^\w\s\-\'&]/', '', $line);
                $cleaned = trim($cleaned);

                if (strlen($cleaned) >= 3 && strlen($cleaned) <= 50) {
                    return $cleaned;
                }
            }
        }

        return null;
    }

    /**
     * Extract date from text
     */
    protected function extractDate(string $text): ?string
    {
        $patterns = [
            // DD/MM/YYYY or DD-MM-YYYY
            '/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})/',
            // YYYY-MM-DD
            '/(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})/',
            // DD/MM/YY
            '/(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{2})\b/',
            // DD MMM YYYY (e.g., 28 Jan 2026)
            '/(\d{1,2})\s+(Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)[a-z]*\s+(\d{4})/i',
            // Date: or Dt: patterns
            '/(?:date|dt)[:\s]+(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{2,4})/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                // Try to parse and format as YYYY-MM-DD
                try {
                    if (count($matches) >= 4) {
                        // Check if first group is year (YYYY-MM-DD format)
                        if (strlen($matches[1]) === 4) {
                            $year = $matches[1];
                            $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
                            $day = str_pad($matches[3], 2, '0', STR_PAD_LEFT);
                        } else {
                            $day = str_pad($matches[1], 2, '0', STR_PAD_LEFT);

                            // Check if month is text
                            if (preg_match('/[a-z]/i', $matches[2])) {
                                $monthMap = [
                                    'jan' => '01', 'feb' => '02', 'mar' => '03', 'apr' => '04',
                                    'may' => '05', 'jun' => '06', 'jul' => '07', 'aug' => '08',
                                    'sep' => '09', 'oct' => '10', 'nov' => '11', 'dec' => '12'
                                ];
                                $month = $monthMap[strtolower(substr($matches[2], 0, 3))] ?? '01';
                            } else {
                                $month = str_pad($matches[2], 2, '0', STR_PAD_LEFT);
                            }

                            $year = $matches[3];
                            // Handle 2-digit year
                            if (strlen($year) === 2) {
                                $year = '20' . $year;
                            }
                        }

                        return "{$year}-{$month}-{$day}";
                    }
                } catch (\Exception $e) {
                    continue;
                }
            }
        }

        return null;
    }

    /**
     * Extract customer name from text
     */
    protected function extractCustomerName(string $text): ?string
    {
        // Priority 0: Detect SnoCart anywhere in text (case-insensitive)
        if (preg_match('/snocart|sno\s*cart|snow\s*cart/i', $text)) {
            return 'SnoCart';
        }

        $patterns = [
            '/(?:customer|cust|name|bill\s*to|sold\s*to|buyer)[:\s]+([a-z\s\-\'\.]+)/i',
            '/(?:m\/s|mr\.|mrs\.|ms\.)[:\s]*([a-z\s\-\'\.]+)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                $name = trim($matches[1]);
                $name = preg_replace('/[^a-zA-Z\s\-\']/', '', $name);
                $name = trim($name);

                if (strlen($name) >= 2 && strlen($name) <= 50) {
                    return $name;
                }
            }
        }

        return null;
    }

    /**
     * Extract grand total from text
     */
    protected function extractGrandTotal(string $text, array $learnedLabels = [], array $lines = [], ?float $layoutRatio = null): float
    {
        // Priority 0: Try learned patterns from previous successful scans
        if (!empty($learnedLabels)) {
            $result = $this->extractWithLearnedLabels($text, $learnedLabels);
            if ($result > 0) return $result;
        }

        // Priority 1: Explicit grand/net/final total labels (most specific)
        $priorityPatterns = [
            '/(?:grand\s*total|net\s*total|total\s*amount|final\s*total|amount\s*payable|net\s*payable|total\s*payable|total\s*due|balance\s*due|amount\s*due|pay(?:able)?\s*amount)[:\s]*(?:rs\.?|inr|₹|\$)?\s*([\d,]+(?:\.\d{1,2})?)/i',
        ];

        foreach ($priorityPatterns as $pattern) {
            if (preg_match_all($pattern, $text, $matches, PREG_SET_ORDER)) {
                $maxVal = 0;
                foreach ($matches as $match) {
                    $val = (float) str_replace(',', '', $match[1]);
                    if ($val > $maxVal) $maxVal = $val;
                }
                if ($maxVal > 0) return $maxVal;
            }
        }

        // Priority 2: Common bill total variations seen across different stores
        $totalPatterns = [
            '/(?:total\s*sales|total\s*bill|bill\s*total|bill\s*amount|sales\s*total|total\s*sale|net\s*amount|net\s*amt|net\s*bill|total\s*amt|total\s*value|invoice\s*total|invoice\s*amount|receipt\s*total)[:\s]*(?:rs\.?|inr|₹|\$)?\s*([\d,]+(?:\.\d{1,2})?)/i',
            '/(?:round(?:ed)?\s*(?:off\s*)?total|round(?:ed)?\s*(?:off\s*)?amount)[:\s]*(?:rs\.?|inr|₹|\$)?\s*([\d,]+(?:\.\d{1,2})?)/i',
            '/(?:you\s*pay|to\s*pay|cash\s*amount|paid\s*amount|amount\s*paid|cash\s*received)[:\s]*(?:rs\.?|inr|₹|\$)?\s*([\d,]+(?:\.\d{1,2})?)/i',
            '/(?<!sub)(?<!sub\s)total(?!\s*(?:items?|qty|quantity|pieces?|nos?|count|disc|saving|mrp))[:\s=]+(?:rs\.?|inr|₹|\$)?\s*([\d,]+(?:\.\d{1,2})?)/i',
            '/(?:amount|amt)[:\s]+(?:rs\.?|inr|₹|\$)?\s*([\d,]+(?:\.\d{1,2})?)\s*$/im',
        ];

        $maxTotal = 0;

        foreach ($totalPatterns as $pattern) {
            if (preg_match_all($pattern, $text, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $value = (float) str_replace(',', '', $match[1]);
                    if ($value > $maxTotal) {
                        $maxTotal = $value;
                    }
                }
            }
        }

        if ($maxTotal > 0) return $maxTotal;

        // Priority 3: Bottom-up fallback — scan last 8 lines for the largest currency value
        if (!empty($lines)) {
            $totalLines = count($lines);
            $searchLines = $lines; // default: all lines

            // If learned layout ratio exists, narrow search to lines around that ratio
            if ($layoutRatio !== null && $layoutRatio > 0) {
                $centerLine = (int) round($layoutRatio * $totalLines);
                $start = max(0, $centerLine - 4);
                $end = min($totalLines - 1, $centerLine + 4);
                $searchLines = array_slice($lines, $start, $end - $start + 1);
            } else {
                // Default: last 8 lines (bottom-up)
                $searchLines = array_slice($lines, max(0, $totalLines - 8));
            }

            $currencyPattern = '/(?:₹|rs\.?|inr)?\s*([\d,]+\.?\d{0,2})/i';
            $largestValue = 0;

            foreach (array_reverse($searchLines) as $line) {
                if (preg_match_all($currencyPattern, $line, $lineMatches, PREG_SET_ORDER)) {
                    foreach ($lineMatches as $m) {
                        $val = (float) str_replace(',', '', $m[1]);
                        if ($val > $largestValue && $val >= 10) { // minimum ₹10 to avoid noise
                            $largestValue = $val;
                        }
                    }
                }
            }

            if ($largestValue > 0) return $largestValue;
        }

        return $maxTotal;
    }

    /**
     * Extract subtotal from text
     */
    protected function extractSubtotal(string $text, array $learnedLabels = []): float
    {
        if (!empty($learnedLabels)) {
            $result = $this->extractWithLearnedLabels($text, $learnedLabels);
            if ($result > 0) return $result;
        }

        $patterns = [
            '/(?:sub\s*total|subtotal|sub\s*amt|sub\s*amount|item\s*total|product\s*total|goods\s*total|total\s*mrp|total\s*before\s*(?:tax|discount))[:\s]*(?:rs\.?|inr|₹|\$)?\s*([\d,]+(?:\.\d{1,2})?)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                return (float) str_replace(',', '', $matches[1]);
            }
        }

        return 0;
    }

    /**
     * Extract discount from text
     */
    protected function extractDiscount(string $text, array $learnedLabels = []): float
    {
        if (!empty($learnedLabels)) {
            $result = $this->extractWithLearnedLabels($text, $learnedLabels);
            if ($result > 0) return $result;
        }

        $patterns = [
            '/(?:discount|disc|less|savings?|you\s*save|offer\s*discount|coupon\s*disc|promo\s*disc)[:\s]*(?:rs\.?|inr|₹|\$)?\s*\-?\s*([\d,]+(?:\.\d{1,2})?)/i',
            '/\-\s*(?:rs\.?|inr|₹|\$)?\s*([\d,]+(?:\.\d{1,2})?)\s*(?:discount|disc|savings?)/i',
            '/(?:total\s*savings?|total\s*discount|total\s*disc)[:\s]*(?:rs\.?|inr|₹|\$)?\s*\-?\s*([\d,]+(?:\.\d{1,2})?)/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                return (float) str_replace(',', '', $matches[1]);
            }
        }

        return 0;
    }

    /**
     * Extract tax from text
     */
    protected function extractTax(string $text, array $learnedLabels = []): float
    {
        if (!empty($learnedLabels)) {
            $result = $this->extractWithLearnedLabels($text, $learnedLabels);
            if ($result > 0) return $result;
        }

        $patterns = [
            '/(?:gst|cgst|sgst|igst|tax|vat)[:\s]*(?:@\s*\d+%?)?\s*(?:rs\.?|inr|₹)?\s*([\d,]+(?:\.\d{1,2})?)/i',
        ];

        $totalTax = 0;

        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $text, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $totalTax += (float) str_replace(',', '', $match[1]);
                }
            }
        }

        return $totalTax;
    }

    /**
     * Extract currency symbol from text
     */
    protected function extractCurrency(string $text): string
    {
        if (preg_match('/₹/', $text)) {
            return '₹';
        }
        if (preg_match('/\bINR\b/i', $text)) {
            return 'INR';
        }
        if (preg_match('/\bRs\.?/i', $text)) {
            return 'Rs';
        }
        if (preg_match('/\$/', $text)) {
            return '$';
        }

        return 'Rs';
    }

    /**
     * Extract line items from text
     */
    protected function extractItems(array $lines): array
    {
        $items = [];

        // Common patterns for item lines:
        // 1. Item name followed by quantity and price
        // 2. Item name at start, price at end

        $itemPatterns = [
            // Name ... Qty x Price = Total
            '/^(.+?)\s+(\d+)\s*[x×\*]\s*(?:rs\.?|₹|\$)?\s*([\d,]+(?:\.\d{1,2})?)\s*[=]?\s*(?:rs\.?|₹|\$)?\s*([\d,]+(?:\.\d{1,2})?)\s*$/i',
            // Name ... Qty ... UnitPrice ... Total (tab/space separated columns)
            '/^(.+?)\s{2,}(\d+)\s{2,}(?:rs\.?|₹|\$)?\s*([\d,]+(?:\.\d{1,2})?)\s{2,}(?:rs\.?|₹|\$)?\s*([\d,]+(?:\.\d{1,2})?)\s*$/i',
            // Name ... Qty ... Total (no unit price)
            '/^(.+?)\s+(\d+)\s+(?:rs\.?|₹|\$)?\s*([\d,]+(?:\.\d{1,2})?)\s*$/i',
            // SR/SNo. Name Qty Price Total (numbered items)
            '/^\d+[\.\)]\s*(.+?)\s+(\d+)\s*[x×\*]?\s*(?:rs\.?|₹|\$)?\s*([\d,]+(?:\.\d{1,2})?)\s*(?:rs\.?|₹|\$)?\s*([\d,]+(?:\.\d{1,2})?)\s*$/i',
            '/^\d+[\.\)]\s*(.+?)\s+(?:rs\.?|₹|\$)?\s*([\d,]+(?:\.\d{1,2})?)\s*$/i',
            // Name followed by price at end of line
            '/^(.+?)\s+(?:rs\.?|₹|\$)?\s*([\d,]+(?:\.\d{1,2})?)\s*$/i',
        ];

        // Skip lines that are likely headers or totals
        $skipPatterns = [
            '/^(item|description|particulars|product|qty|quantity|rate|amount|price|s\.?\s*no|sr\.?\s*no|sl\.?\s*no|hsn)/i',
            '/^(total|sub\s*total|subtotal|grand|net\s*total|total\s*sales|total\s*bill|bill\s*total|total\s*amount|total\s*amt|round)/i',
            '/^(tax|gst|cgst|sgst|igst|vat|service\s*charge|delivery\s*charge|packing)/i',
            '/^(discount|disc|savings?|coupon|offer|you\s*save)/i',
            '/^(date|time|bill|invoice|receipt|customer|name|address|phone|tel|mobile|cash|paid|change|balance)/i',
            '/^[\-\=\_\*\#\.\s]+$/',
            '/^(thank|thanks|visit|welcome|have\s*a|please|e\s*&\s*o|terms|fssai|tin|gstin|pan)/i',
        ];

        foreach ($lines as $line) {
            // Skip if matches skip patterns
            $shouldSkip = false;
            foreach ($skipPatterns as $skipPattern) {
                if (preg_match($skipPattern, $line)) {
                    $shouldSkip = true;
                    break;
                }
            }
            if ($shouldSkip) continue;

            // Try each item pattern
            foreach ($itemPatterns as $pattern) {
                if (preg_match($pattern, $line, $matches)) {
                    $item = [
                        'name' => trim($matches[1]),
                        'quantity' => 1,
                        'unit_price' => 0,
                        'total' => 0,
                    ];

                    if (count($matches) >= 5) {
                        // Full pattern: name, qty, unit_price, total
                        $item['quantity'] = (int) $matches[2];
                        $item['unit_price'] = (float) str_replace(',', '', $matches[3]);
                        $item['total'] = (float) str_replace(',', '', $matches[4]);
                    } elseif (count($matches) >= 4) {
                        // Name, qty, total
                        $item['quantity'] = (int) $matches[2];
                        $item['total'] = (float) str_replace(',', '', $matches[3]);
                        $item['unit_price'] = $item['quantity'] > 0 ? $item['total'] / $item['quantity'] : 0;
                    } elseif (count($matches) >= 3) {
                        // Name, price only
                        $item['total'] = (float) str_replace(',', '', $matches[2]);
                        $item['unit_price'] = $item['total'];
                    }

                    // Validate item
                    if (strlen($item['name']) >= 2 && strlen($item['name']) <= 100 && $item['total'] > 0) {
                        $items[] = $item;
                    }

                    break; // Move to next line after first match
                }
            }
        }

        return $items;
    }

    /**
     * Full extraction pipeline - extract, parse, and calculate confidence
     *
     * @param string $imagePath
     * @return array
     */
    /**
     * Apply learned OCR corrections for a field
     */
    protected function applyLearnedCorrections(?string $value, string $field, array $learnedLayouts): ?string
    {
        if (empty($value) || empty($learnedLayouts)) return $value;

        $corrections = $learnedLayouts[$field]['corrections'] ?? [];
        foreach ($corrections as $correction) {
            $from = $correction['from'] ?? '';
            $to = $correction['to'] ?? '';
            if (empty($from) || empty($to)) continue;

            // Exact match
            if (strcasecmp($value, $from) === 0) {
                return $to;
            }

            // Fuzzy match — if the OCR text is similar enough to a known garbled version
            similar_text(strtolower($value), strtolower($from), $percent);
            if ($percent >= 70) {
                return $to;
            }
        }

        return $value;
    }

    public function processImage(string $imagePath, array $learnedPatterns = [], array $learnedLayouts = []): array
    {
        // Step 1: Extract text
        $extraction = $this->extractText($imagePath);

        if (!$extraction['success']) {
            return [
                'success' => false,
                'error' => $extraction['error'],
                'confidence' => 0,
                'data' => null,
                'raw_text' => '',
            ];
        }

        // Step 2: Parse text into structured data
        $data = $this->parseBillText($extraction['raw_text'], $learnedPatterns, $learnedLayouts);

        // Step 3: Calculate confidence
        $confidence = $this->calculateConfidence($data);

        return [
            'success' => true,
            'confidence' => $confidence,
            'data' => $data,
            'raw_text' => $extraction['raw_text'],
        ];
    }
}
