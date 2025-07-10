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
     * Get current price
     * 
     * @return Price|null
     */
    public function getPrice(): ?Price
    {
        $offer = $this->getBuyBoxOffer();
        
        if (!$offer || !isset($offer['Price'])) {
            return null;
        }

        // Handle OffersV2 structure with nested Money object
        if (isset($offer['Price']['Money'])) {
            return new Price($offer['Price']['Money']);
        }
        
        // Handle Offers structure (direct price object)
        return new Price($offer['Price']);
    }

    /**
     * Get original price (before discount)
     * 
     * @return Price|null
     */
    public function getOriginalPrice(): ?Price
    {
        $offer = $this->getBuyBoxOffer();
        
        if (!$offer) {
            return $this->getPrice();
        }

        // Handle OffersV2 structure with nested SavingBasis
        if (isset($offer['Price']['SavingBasis']['Money'])) {
            return new Price($offer['Price']['SavingBasis']['Money']);
        }
        
        // Handle Offers structure with direct SavingBasis
        if (isset($offer['SavingBasis'])) {
            return new Price($offer['SavingBasis']);
        }

        return $this->getPrice();
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
     * Check if product has Prime eligibility
     * 
     * @return bool
     */
    public function hasPrimeOffer(): bool
    {
        $offer = $this->getBuyBoxOffer();
        
        // Check for Prime exclusive deal in new API structure
        if ($offer && isset($offer['DealDetails']['AccessType'])) {
            return $offer['DealDetails']['AccessType'] === 'PRIME_EXCLUSIVE';
        }
        
        // Check old API structure
        if (!$offer || !isset($offer['ProgramEligibility']['IsPrimeExclusive'])) {
            return false;
        }

        return $offer['ProgramEligibility']['IsPrimeExclusive'] === true ||
               $offer['ProgramEligibility']['IsPrimeEligible'] === true;
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
     * Get product availability
     * 
     * @return string|null
     */
    public function getAvailability(): ?string
    {
        $offer = $this->getBuyBoxOffer();
        return $offer['Availability']['Message'] ?? null;
    }

    /**
     * Check if product is in stock
     * 
     * @return bool
     */
    public function isInStock(): bool
    {
        $offer = $this->getBuyBoxOffer();
        
        if (!$offer || !isset($offer['Availability'])) {
            return false;
        }

        // Check Type field (preferred)
        if (isset($offer['Availability']['Type'])) {
            return $offer['Availability']['Type'] === 'Now';
        }
        
        // Check Message field as fallback
        if (isset($offer['Availability']['Message'])) {
            $message = strtolower($offer['Availability']['Message']);
            return strpos($message, 'in stock') !== false;
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
     * Get all offers
     * 
     * @return array
     */
    public function getAllOffers(): array
    {
        // Prioritize Offers over OffersV2 as per resource configuration
        return $this->data['Offers']['Listings'] ?? $this->data['OffersV2']['Listings'] ?? [];
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
