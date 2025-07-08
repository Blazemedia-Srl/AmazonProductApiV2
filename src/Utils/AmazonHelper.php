<?php

declare(strict_types=1);

namespace Blazemedia\AmazonProductApiV2\Utils;

use Blazemedia\AmazonProductApiV2\AmazonProductApiClient;
use Blazemedia\AmazonProductApiV2\Models\ProductItem;
use Blazemedia\AmazonProductApiV2\Models\AmazonItem;
use Blazemedia\AmazonProductApiV2\Exceptions\AmazonApiException;

/**
 * Helper e utilities per Amazon Product API
 * 
 * @package Blazemedia\AmazonProductApiV2\Utils
 */
class AmazonHelper
{
    private AmazonProductApiClient $client;
    
    public function __construct(AmazonProductApiClient $client)
    {
        $this->client = $client;
    }

    /**
     * Ottieni informazioni prodotto semplificate
     * 
     * @param string $asin
     * @return array
     */
    public function getSimpleProductInfo(string $asin): array
    {
        try {
            $product = $this->client->getItem($asin);
            
            return [
                'success' => true,
                'asin' => $product->getAsin(),
                'title' => $product->getTitle(),
                'brand' => $product->getBrand(),
                'price' => $product->getPrice() ? $product->getPrice()->getDisplayValue() : null,
                'original_price' => $product->getOriginalPrice() ? $product->getOriginalPrice()->getDisplayValue() : null,
                'discount_percentage' => $product->getDiscountPercentage(),
                'image' => $product->getImageUrl(),
                'prime' => $product->hasPrimeOffer(),
                'in_stock' => $product->isInStock(),
                'url' => $product->getDetailPageURL()
            ];
            
        } catch (AmazonApiException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ];
        }
    }

    /**
     * Confronta prezzi di più prodotti
     * 
     * @param array $asins
     * @return array
     */
    public function compareProducts(array $asins): array
    {
        $results = [];
        
        try {
            $products = $this->client->getItems($asins);
            
            foreach ($products as $product) {
                $price = $product->getPrice();
                $originalPrice = $product->getOriginalPrice();
                
                $results[] = [
                    'asin' => $product->getAsin(),
                    'title' => $product->getTitle(),
                    'brand' => $product->getBrand(),
                    'current_price' => $price ? $price->getAmount() : 0,
                    'current_price_display' => $price ? $price->getDisplayValue() : 'N/A',
                    'original_price' => $originalPrice ? $originalPrice->getAmount() : 0,
                    'discount_percentage' => $product->getDiscountPercentage(),
                    'prime' => $product->hasPrimeOffer(),
                    'in_stock' => $product->isInStock()
                ];
            }
            
            // Ordina per prezzo crescente
            usort($results, function($a, $b) {
                return $a['current_price'] <=> $b['current_price'];
            });
            
        } catch (AmazonApiException $e) {
            return [
                'error' => $e->getMessage(),
                'products' => []
            ];
        }
        
        return [
            'products' => $results,
            'count' => count($results)
        ];
    }

    /**
     * Trova prodotti con sconto
     * 
     * @param array $asins
     * @param float $minDiscountPercentage
     * @return array
     */
    public function findDiscountedProducts(array $asins, float $minDiscountPercentage = 10.0): array
    {
        $discounted = [];
        
        try {
            $products = $this->client->getItems($asins);
            
            foreach ($products as $product) {
                $discount = $product->getDiscountPercentage();
                
                if ($discount && $discount >= $minDiscountPercentage) {
                    $price = $product->getPrice();
                    $originalPrice = $product->getOriginalPrice();
                    
                    $discounted[] = [
                        'asin' => $product->getAsin(),
                        'title' => $product->getTitle(),
                        'current_price' => $price ? $price->getDisplayValue() : 'N/A',
                        'original_price' => $originalPrice ? $originalPrice->getDisplayValue() : 'N/A',
                        'discount_percentage' => $discount,
                        'savings' => $product->getDiscountAmount() ? $product->getDiscountAmount()->getDisplayValue() : null,
                        'prime' => $product->hasPrimeOffer(),
                        'image' => $product->getImageUrl()
                    ];
                }
            }
            
            // Ordina per sconto decrescente
            usort($discounted, function($a, $b) {
                return $b['discount_percentage'] <=> $a['discount_percentage'];
            });
            
        } catch (AmazonApiException $e) {
            return [
                'error' => $e->getMessage(),
                'products' => []
            ];
        }
        
        return [
            'products' => $discounted,
            'count' => count($discounted)
        ];
    }

    /**
     * Filtra prodotti Prime
     * 
     * @param array $asins
     * @return array
     */
    public function findPrimeProducts(array $asins): array
    {
        $primeProducts = [];
        
        try {
            $products = $this->client->getItems($asins);
            
            foreach ($products as $product) {
                if ($product->hasPrimeOffer()) {
                    $price = $product->getPrice();
                    
                    $primeProducts[] = [
                        'asin' => $product->getAsin(),
                        'title' => $product->getTitle(),
                        'brand' => $product->getBrand(),
                        'price' => $price ? $price->getDisplayValue() : 'N/A',
                        'in_stock' => $product->isInStock(),
                        'image' => $product->getImageUrl(),
                        'url' => $product->getDetailPageURL()
                    ];
                }
            }
            
        } catch (AmazonApiException $e) {
            return [
                'error' => $e->getMessage(),
                'products' => []
            ];
        }
        
        return [
            'products' => $primeProducts,
            'count' => count($primeProducts)
        ];
    }

    /**
     * Genera report prodotto completo
     * 
     * @param string $asin
     * @return array
     */
    public function generateProductReport(string $asin): array
    {
        try {
            $product = $this->client->getItem($asin);
            
            $report = [
                'basic_info' => [
                    'asin' => $product->getAsin(),
                    'title' => $product->getTitle(),
                    'brand' => $product->getBrand(),
                    'manufacturer' => $product->getManufacturer(),
                    'condition' => $product->getCondition(),
                    'url' => $product->getDetailPageURL()
                ],
                'pricing' => [
                    'current_price' => $product->getPrice() ? $product->getPrice()->toArray() : null,
                    'original_price' => $product->getOriginalPrice() ? $product->getOriginalPrice()->toArray() : null,
                    'discount_amount' => $product->getDiscountAmount() ? $product->getDiscountAmount()->toArray() : null,
                    'discount_percentage' => $product->getDiscountPercentage()
                ],
                'availability' => [
                    'in_stock' => $product->isInStock(),
                    'availability_message' => $product->getAvailability(),
                    'prime_eligible' => $product->hasPrimeOffer(),
                    'has_coupons' => $product->hasCoupons()
                ],
                'media' => [
                    'primary_image' => $product->getImageUrl(),
                    'all_images' => $product->getAllImages(),
                    'image_count' => count($product->getAllImages())
                ],
                'details' => [
                    'description' => $product->getDescription(),
                    'features' => $product->getFeatures(),
                    'dimensions' => $product->getDimensions(),
                    'weight' => $product->getWeight(),
                    'classifications' => $product->getClassifications()
                ],
                'merchant' => [
                    'merchant_info' => $product->getMerchantInfo(),
                    'delivery_info' => $product->getDeliveryInfo()
                ],
                'generated_at' => date('Y-m-d H:i:s')
            ];
            
            return [
                'success' => true,
                'report' => $report
            ];
            
        } catch (AmazonApiException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ];
        }
    }

    /**
     * Ottieni AmazonItem compatibile (per sistemi legacy)
     * 
     * @param string $asin
     * @param string $partnerTag
     * @param string $trackingPlaceholder
     * @return array
     */
    public function getCompatibleItem(string $asin, string $partnerTag = 'blazemedia-21', string $trackingPlaceholder = 'booBLZTRKood'): array
    {
        try {
            $amazonItem = $this->client->getAmazonItem($asin, [], 1, $partnerTag, $trackingPlaceholder);
            
            return [
                'success' => true,
                'item' => $amazonItem,
                'data' => $amazonItem->toArray()
            ];
            
        } catch (AmazonApiException $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => $e->getCode()
            ];
        }
    }

    /**
     * Converti ProductItem in AmazonItem per compatibilità
     * 
     * @param ProductItem $productItem
     * @param string $partnerTag
     * @param string $trackingPlaceholder
     * @return AmazonItem
     */
    public function convertToAmazonItem(ProductItem $productItem, string $partnerTag = 'blazemedia-21', string $trackingPlaceholder = 'booBLZTRKood'): AmazonItem
    {
        return new AmazonItem($productItem, $partnerTag, $trackingPlaceholder);
    }

    /**
     * Batch conversion da ProductItem[] a AmazonItem[]
     * 
     * @param ProductItem[] $productItems
     * @param string $partnerTag
     * @param string $trackingPlaceholder
     * @return AmazonItem[]
     */
    public function convertMultipleToAmazonItems(array $productItems, string $partnerTag = 'blazemedia-21', string $trackingPlaceholder = 'booBLZTRKood'): array
    {
        $amazonItems = [];
        foreach ($productItems as $productItem) {
            $amazonItems[] = new AmazonItem($productItem, $partnerTag, $trackingPlaceholder);
        }
        return $amazonItems;
    }

    /**
     * Valida un ASIN
     * 
     * @param string $asin
     * @return bool
     */
    public static function isValidAsin(string $asin): bool
    {
        return preg_match('/^[A-Z0-9]{10}$/', $asin) === 1;
    }

    /**
     * Estrae ASIN da URL Amazon
     * 
     * @param string $url
     * @return string|null
     */
    public static function extractAsinFromUrl(string $url): ?string
    {
        $patterns = [
            '/\/dp\/([A-Z0-9]{10})/',
            '/\/gp\/product\/([A-Z0-9]{10})/',
            '/\/exec\/obidos\/ASIN\/([A-Z0-9]{10})/',
            '/asin=([A-Z0-9]{10})/',
            '/\/([A-Z0-9]{10})(?:\/|\?|$)/'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $matches)) {
                return $matches[1];
            }
        }
        
        return null;
    }

    /**
     * Formatta prezzo per display
     * 
     * @param float $amount
     * @param string $currency
     * @return string
     */
    public static function formatPrice(float $amount, string $currency = 'EUR'): string
    {
        switch ($currency) {
            case 'EUR':
                return '€ ' . number_format($amount, 2, ',', '.');
            case 'USD':
                return '$' . number_format($amount, 2);
            case 'GBP':
                return '£' . number_format($amount, 2);
            case 'JPY':
                return '¥' . number_format($amount, 0);
            default:
                return $currency . ' ' . number_format($amount, 2);
        }
    }
}
