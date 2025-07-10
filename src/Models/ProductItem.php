<?php

declare(strict_types=1);

namespace Blazemedia\AmazonProductApiV2\Models;

/**
 * Amazon Product Item Model
 * 
 * @package Blazemedia\AmazonProductApiV2\Models
 */
class ProductItem
{
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Get product ASIN
     * 
     * @return string|null
     */
    public function getAsin(): ?string
    {
        return $this->data['ASIN'] ?? null;
    }

    /**
     * Get product title
     * 
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->data['ItemInfo']['Title']['DisplayValue'] ?? null;
    }

    /**
     * Get product description/features
     * 
     * @return array
     */
    public function getFeatures(): array
    {
        if (!isset($this->data['ItemInfo']['Features']['DisplayValues'])) {
            return [];
        }

        return $this->data['ItemInfo']['Features']['DisplayValues'];
    }

    /**
     * Get product description as string
     * 
     * @return string|null
     */
    public function getDescription(): ?string
    {
        $features = $this->getFeatures();
        return empty($features) ? null : implode('. ', $features);
    }

    /**
     * Get product brand
     * 
     * @return string|null
     */
    public function getBrand(): ?string
    {
        return $this->data['ItemInfo']['ByLineInfo']['Brand']['DisplayValue'] ?? null;
    }

    /**
     * Get product manufacturer
     * 
     * @return string|null
     */
    public function getManufacturer(): ?string
    {
        return $this->data['ItemInfo']['ByLineInfo']['Manufacturer']['DisplayValue'] ?? null;
    }

    /**
     * Get primary image URL
     * 
     * @param string $size Image size (Small, Medium, Large)
     * @return string|null
     */
    public function getImageUrl(string $size = 'Large'): ?string
    {
        return $this->data['Images']['Primary'][$size]['URL'] ?? null;
    }

    /**
     * Get all available images
     * 
     * @param string $size Image size (Small, Medium, Large)
     * @return array
     */
    public function getAllImages(string $size = 'Large'): array
    {
        $images = [];
        
        // Primary image
        if (isset($this->data['Images']['Primary'][$size]['URL'])) {
            $images[] = $this->data['Images']['Primary'][$size]['URL'];
        }
        
        // Variant images
        if (isset($this->data['Images']['Variants'])) {
            foreach ($this->data['Images']['Variants'] as $variant) {
                if (isset($variant[$size]['URL'])) {
                    $images[] = $variant[$size]['URL'];
                }
            }
        }
        
        return $images;
    }

    /**
     * Get current price (always uses the lowest price from all offers)
     * 
     * @return Price|null
     */
    public function getPrice(): ?Price
    {
        $offers = $this->getAllOffers();
        
        if (empty($offers)) {
            return null;
        }

        $lowestPrice = null;
        $lowestAmount = PHP_FLOAT_MAX;

        foreach ($offers as $offer) {
            $priceData = null;
            
            // Handle OffersV2 structure with nested Money object
            if (isset($offer['Price']['Money'])) {
                $priceData = $offer['Price']['Money'];
            }
            // Handle Offers structure (direct price object)
            elseif (isset($offer['Price'])) {
                $priceData = $offer['Price'];
            }
            
            if ($priceData && isset($priceData['Amount'])) {
                if ($priceData['Amount'] < $lowestAmount) {
                    $lowestAmount = $priceData['Amount'];
                    $lowestPrice = $priceData;
                }
            }
        }

        return $lowestPrice ? new Price($lowestPrice) : null;
    }

    /**
     * Get original price (uses SavingBasis if greater than current price, otherwise highest price from Summaries)
     * 
     * @return Price|null
     */
    public function getOriginalPrice(): ?Price
    {
        $currentPrice = $this->getPrice();
        if (!$currentPrice) {
            return null;
        }
        
        $currentAmount = $currentPrice->getAmount();
        $originalPrice = null;
        $originalAmount = 0;

        // First, check for SavingBasis in all offers and find the highest one
        $offers = $this->getAllOffers();
        foreach ($offers as $offer) {
            $savingBasisData = null;
            
            // Handle OffersV2 structure with nested SavingBasis
            if (isset($offer['Price']['SavingBasis']['Money'])) {
                $savingBasisData = $offer['Price']['SavingBasis']['Money'];
            }
            // Handle Offers structure with direct SavingBasis
            elseif (isset($offer['SavingBasis'])) {
                $savingBasisData = $offer['SavingBasis'];
            }
            
            if ($savingBasisData && isset($savingBasisData['Amount']) && $savingBasisData['Amount'] > $currentAmount) {
                if ($savingBasisData['Amount'] > $originalAmount) {
                    $originalAmount = $savingBasisData['Amount'];
                    $originalPrice = $savingBasisData;
                }
            }
        }
        
        // If we found a valid SavingBasis, use it
        if ($originalPrice) {
            return new Price($originalPrice);
        }
        
        // Otherwise, look for the highest price in Summaries
        $summaries = $this->data['Offers']['Summaries'] ?? $this->data['OffersV2']['Summaries'] ?? [];
        foreach ($summaries as $summary) {
            if (isset($summary['HighestPrice']) && isset($summary['HighestPrice']['Amount'])) {
                if ($summary['HighestPrice']['Amount'] > $currentAmount && $summary['HighestPrice']['Amount'] > $originalAmount) {
                    $originalAmount = $summary['HighestPrice']['Amount'];
                    $originalPrice = $summary['HighestPrice'];
                }
            }
        }
        
        // If we found a valid Summaries price, use it
        if ($originalPrice) {
            return new Price($originalPrice);
        }
        
        // Fallback to current price if no better original price found
        return $currentPrice;
    }

    /**
     * Get discount amount
     * 
     * @return Price|null
     */
    public function getDiscountAmount(): ?Price
    {
        $offer = $this->getBuyBoxOffer();
        
        // Handle OffersV2 structure with direct savings information
        if ($offer && isset($offer['Price']['Savings']['Money'])) {
            return new Price($offer['Price']['Savings']['Money']);
        }
        
        // Fallback to calculation method for both Offers and OffersV2
        $original = $this->getOriginalPrice();
        $current = $this->getPrice();
        
        if (!$original || !$current) {
            return null;
        }

        $discountAmount = $original->getAmount() - $current->getAmount();
        
        if ($discountAmount <= 0) {
            return null;
        }

        return new Price([
            'Amount' => $discountAmount,
            'Currency' => $current->getCurrency(),
            'DisplayValue' => $current->getCurrency() . ' ' . number_format($discountAmount, 2)
        ]);
    }

    /**
     * Get discount percentage
     * 
     * @return float|null
     */
    public function getDiscountPercentage(): ?float
    {
        $offer = $this->getBuyBoxOffer();
        
        // Handle OffersV2 structure with direct percentage information
        if ($offer && isset($offer['Price']['Savings']['Percentage'])) {
            return (float) $offer['Price']['Savings']['Percentage'];
        }
        
        // Fallback to calculation method for both Offers and OffersV2
        $original = $this->getOriginalPrice();
        $current = $this->getPrice();
        
        if (!$original || !$current || $original->getAmount() <= 0) {
            return null;
        }

        return round((($original->getAmount() - $current->getAmount()) / $original->getAmount()) * 100, 1);
    }

    /**
     * Check if product has Prime eligibility (checks all offers)
     * 
     * @return bool
     */
    public function hasPrimeOffer(): bool
    {
        $offers = $this->getAllOffers();
        
        foreach ($offers as $offer) {
            // Check for Prime exclusive deal in new API structure
            if (isset($offer['DealDetails']['AccessType']) && $offer['DealDetails']['AccessType'] === 'PRIME_EXCLUSIVE') {
                return true;
            }
            
            // Check old API structure
            if (isset($offer['ProgramEligibility']['IsPrimeExclusive']) && $offer['ProgramEligibility']['IsPrimeExclusive'] === true) {
                return true;
            }
            
            if (isset($offer['ProgramEligibility']['IsPrimeEligible']) && $offer['ProgramEligibility']['IsPrimeEligible'] === true) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if product has an active deal
     * 
     * @return bool
     */
    public function hasActiveDeal(): bool
    {
        $offer = $this->getBuyBoxOffer();
        
        // Check for explicit deal details first
        if ($offer && isset($offer['DealDetails'])) {
            return true;
        }
        
        // Check for savings as indicator of deal
        $discountAmount = $this->getDiscountAmount();
        return $discountAmount !== null && $discountAmount->getAmount() > 0;
    }

    /**
     * Get deal information
     * 
     * @return array|null
     */
    public function getDealInfo(): ?array
    {
        $offer = $this->getBuyBoxOffer();
        return $offer['DealDetails'] ?? null;
    }

    /**
     * Get deal badge text
     * 
     * @return string|null
     */
    public function getDealBadge(): ?string
    {
        $dealInfo = $this->getDealInfo();
        return $dealInfo['Badge'] ?? null;
    }

    /**
     * Get deal end time
     * 
     * @return string|null
     */
    public function getDealEndTime(): ?string
    {
        $dealInfo = $this->getDealInfo();
        return $dealInfo['EndTime'] ?? null;
    }

    /**
     * Get deal start time
     * 
     * @return string|null
     */
    public function getDealStartTime(): ?string
    {
        $dealInfo = $this->getDealInfo();
        return $dealInfo['StartTime'] ?? null;
    }

    /**
     * Check if deal is Prime exclusive
     * 
     * @return bool
     */
    public function isPrimeExclusiveDeal(): bool
    {
        $dealInfo = $this->getDealInfo();
        return $dealInfo && ($dealInfo['AccessType'] ?? '') === 'PRIME_EXCLUSIVE';
    }

    /**
     * Get savings basis type (e.g., "LOWEST_PRICE_STRIKETHROUGH")
     * 
     * @return string|null
     */
    public function getSavingsBasisType(): ?string
    {
        $offer = $this->getBuyBoxOffer();
        return $offer['Price']['SavingBasis']['SavingBasisType'] ?? null;
    }

    /**
     * Get savings basis type label
     * 
     * @return string|null
     */
    public function getSavingsBasisTypeLabel(): ?string
    {
        $offer = $this->getBuyBoxOffer();
        return $offer['Price']['SavingBasis']['SavingBasisTypeLabel'] ?? null;
    }

    /**
     * Check if product has available coupons
     * 
     * @return bool
     */
    public function hasCoupons(): bool
    {
        // This would need to be implemented based on actual Amazon API response structure
        // for coupon information. Currently, the standard resources don't include coupon data.
        return false;
    }

    /**
     * Get coupon discount (if available)
     * 
     * @return string|null
     */
    public function getCouponDiscount(): ?string
    {
        // Placeholder - would need coupon-specific resources
        return null;
    }

    /**
     * Get product availability (returns the first available stock message found)
     * 
     * @return string|null
     */
    public function getAvailability(): ?string
    {
        $offers = $this->getAllOffers();
        
        foreach ($offers as $offer) {
            if (isset($offer['Availability']['Message'])) {
                return $offer['Availability']['Message'];
            }
        }
        
        return null;
    }

    /**
     * Check if product is in stock (checks all offers)
     * 
     * @return bool
     */
    public function isInStock(): bool
    {
        $offers = $this->getAllOffers();
        
        foreach ($offers as $offer) {
            if (!isset($offer['Availability'])) {
                continue;
            }

            // Check Type field (preferred)
            if (isset($offer['Availability']['Type'])) {
                if ($offer['Availability']['Type'] === 'Now') {
                    return true;
                }
            }
            
            // Check Message field as fallback
            if (isset($offer['Availability']['Message'])) {
                $message = strtolower($offer['Availability']['Message']);
                if (strpos($message, 'in stock') !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get merchant information
     * 
     * @return array|null
     */
    public function getMerchantInfo(): ?array
    {
        $offer = $this->getBuyBoxOffer();
        return $offer['MerchantInfo'] ?? null;
    }

    /**
     * Get delivery information
     * 
     * @return array|null
     */
    public function getDeliveryInfo(): ?array
    {
        $offer = $this->getBuyBoxOffer();
        return $offer['DeliveryInfo'] ?? null;
    }

    /**
     * Get product condition
     * 
     * @return string|null
     */
    public function getCondition(): ?string
    {
        $offer = $this->getBuyBoxOffer();
        return $offer['Condition']['Value'] ?? null;
    }

    /**
     * Get product rating
     * 
     * @return float|null
     */
    public function getRating(): ?float
    {
        // This would require CustomerReviews resources which might not be available
        return null;
    }

    /**
     * Get number of reviews
     * 
     * @return int|null
     */
    public function getReviewCount(): ?int
    {
        // This would require CustomerReviews resources which might not be available
        return null;
    }

    /**
     * Get product detail page URL
     * 
     * @return string|null
     */
    public function getDetailPageURL(): ?string
    {
        return $this->data['DetailPageURL'] ?? null;
    }

    /**
     * Get all offers (merges both Offers V1 and OffersV2)
     * 
     * @return array
     */
    public function getAllOffers(): array
    {
        $allOffers = [];
        
        // Add Offers V1
        if (isset($this->data['Offers']['Listings']) && is_array($this->data['Offers']['Listings'])) {
            foreach ($this->data['Offers']['Listings'] as $offer) {
                $offer['_source'] = 'OffersV1';
                $allOffers[] = $offer;
            }
        }
        
        // Add OffersV2
        if (isset($this->data['OffersV2']['Listings']) && is_array($this->data['OffersV2']['Listings'])) {
            foreach ($this->data['OffersV2']['Listings'] as $offer) {
                $offer['_source'] = 'OffersV2';
                $allOffers[] = $offer;
            }
        }
        
        return $allOffers;
    }

    /**
     * Get buy box winning offer
     * 
     * @return array|null
     */
    public function getBuyBoxOffer(): ?array
    {
        $offers = $this->getAllOffers();
        
        foreach ($offers as $offer) {
            if (isset($offer['IsBuyBoxWinner']) && $offer['IsBuyBoxWinner'] === true) {
                return $offer;
            }
        }
        
        // Return null if no buy box winner found (stricter behavior)
        return null;
    }

    /**
     * Get the best offer (lowest price offer)
     * 
     * @return array|null
     */
    public function getBestOffer(): ?array
    {
        $offers = $this->getAllOffers();
        
        if (empty($offers)) {
            return null;
        }

        $bestOffer = null;
        $lowestAmount = PHP_FLOAT_MAX;

        foreach ($offers as $offer) {
            $priceAmount = null;
            
            // Handle OffersV2 structure with nested Money object
            if (isset($offer['Price']['Money']['Amount'])) {
                $priceAmount = $offer['Price']['Money']['Amount'];
            }
            // Handle Offers structure (direct price object)
            elseif (isset($offer['Price']['Amount'])) {
                $priceAmount = $offer['Price']['Amount'];
            }
            
            if ($priceAmount !== null && $priceAmount < $lowestAmount) {
                $lowestAmount = $priceAmount;
                $bestOffer = $offer;
            }
        }

        return $bestOffer;
    }

    /**
     * Get technical specifications
     * 
     * @return array
     */
    public function getTechnicalInfo(): array
    {
        return $this->data['ItemInfo']['TechnicalInfo'] ?? [];
    }

    /**
     * Get product dimensions
     * 
     * @return array|null
     */
    public function getDimensions(): ?array
    {
        $techInfo = $this->getTechnicalInfo();
        return $techInfo['ItemDimensions'] ?? null;
    }

    /**
     * Get product weight
     * 
     * @return array|null
     */
    public function getWeight(): ?array
    {
        $techInfo = $this->getTechnicalInfo();
        return $techInfo['ItemWeight'] ?? null;
    }

    /**
     * Get product classifications (categories)
     * 
     * @return array
     */
    public function getClassifications(): array
    {
        return $this->data['ItemInfo']['Classifications'] ?? [];
    }

    /**
     * Get raw product data
     * 
     * @return array
     */
    public function getRawData(): array
    {
        return $this->data;
    }

    /**
     * Convert to array representation
     * 
     * @return array
     */
    public function toArray(): array
    {
        $price = $this->getPrice();
        $originalPrice = $this->getOriginalPrice();
        $discountAmount = $this->getDiscountAmount();
        
        return [
            'asin' => $this->getAsin(),
            'title' => $this->getTitle(),
            'brand' => $this->getBrand(),
            'manufacturer' => $this->getManufacturer(),
            'description' => $this->getDescription(),
            'features' => $this->getFeatures(),
            'image_url' => $this->getImageUrl(),
            'all_images' => $this->getAllImages(),
            'price' => $price ? $price->toArray() : null,
            'original_price' => $originalPrice ? $originalPrice->toArray() : null,
            'discount_amount' => $discountAmount ? $discountAmount->toArray() : null,
            'discount_percentage' => $this->getDiscountPercentage(),
            'savings_basis_type' => $this->getSavingsBasisType(),
            'savings_basis_type_label' => $this->getSavingsBasisTypeLabel(),
            'has_prime' => $this->hasPrimeOffer(),
            'has_coupons' => $this->hasCoupons(),
            'coupon_discount' => $this->getCouponDiscount(),
            'availability' => $this->getAvailability(),
            'is_in_stock' => $this->isInStock(),
            'condition' => $this->getCondition(),
            'merchant_info' => $this->getMerchantInfo(),
            'delivery_info' => $this->getDeliveryInfo(),
            'detail_page_url' => $this->getDetailPageURL(),
            'dimensions' => $this->getDimensions(),
            'weight' => $this->getWeight(),
            'classifications' => $this->getClassifications(),
            // New deal-related fields
            'has_active_deal' => $this->hasActiveDeal(),
            'deal_info' => $this->getDealInfo(),
            'deal_badge' => $this->getDealBadge(),
            'deal_end_time' => $this->getDealEndTime(),
            'deal_start_time' => $this->getDealStartTime(),
            'is_prime_exclusive_deal' => $this->isPrimeExclusiveDeal(),
        ];
    }
}
