<?php

namespace App\Services;

use GoogleSearchResults;
use Exception;

class SerpApiService
{
    private $client;
    private $apiKey;

    public function __construct()
    {
        $this->apiKey = 'f7a4416b4d47954e5a4be299edf10b8b0211cc522f33a76b76b0fded66e79b2e';
        $this->client = new GoogleSearchResults($this->apiKey);
    }

    /**
     * Search for product information using barcode
     */
    public function searchByBarcode($barcode)
    {
        try {
            $query = [
                'q' => $barcode,
                'hl' => 'en',
                'gl' => 'in',
                'google_domain' => 'google.co.in',
                'num' => 5 // Limit results
            ];

            $response = $this->client->get_json($query);
            
            if (isset($response->organic_results) && count($response->organic_results) > 0) {
                $firstResult = $response->organic_results[0];
                
                return [
                    'success' => true,
                    'data' => [
                        'title' => $firstResult->title ?? null,
                        'snippet' => $firstResult->snippet ?? null,
                        'thumbnail' => $firstResult->thumbnail ?? null,
                        'link' => $firstResult->link ?? null,
                        'source' => $firstResult->source ?? null
                    ]
                ];
            }

            return [
                'success' => false,
                'message' => 'No results found for this barcode'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error fetching product information: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Search for product by general query
     */
    public function searchByQuery($query)
    {
        try {
            $searchQuery = [
                'q' => $query,
                'hl' => 'en',
                'gl' => 'in',
                'google_domain' => 'google.co.in',
                'num' => 3
            ];

            $response = $this->client->get_json($searchQuery);
            
            if (isset($response->organic_results) && count($response->organic_results) > 0) {
                return [
                    'success' => true,
                    'data' => $response->organic_results
                ];
            }

            return [
                'success' => false,
                'message' => 'No results found'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error fetching information: ' . $e->getMessage()
            ];
        }
    }

     public function extractProductName($title)
    {
        // Remove common e-commerce words and patterns
        $cleanTitle = preg_replace('/\b(buy|online|price|rs\.?|₹|\$|usd|inr|shipping|free|delivery|amazon|flipkart|myntra|ajio)\b/i', '', $title);
        
        // Remove extra spaces and trim
        $cleanTitle = preg_replace('/\s+/', ' ', trim($cleanTitle));
        
        // Remove special characters but keep alphanumeric and spaces
        $cleanTitle = preg_replace('/[^\w\s-]/', '', $cleanTitle);
        
        return trim($cleanTitle);
    }
}