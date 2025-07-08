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
        
        if (!$offer || !isset($offer['SavingBasis'])) {
            return $this->getPrice();
        }

        return new Price($offer['SavingBasis']);
    }

    /**
     * Get discount amount
     * 
     * @return Price|null
     */
    public function getDiscountAmount(): ?Price
    {
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
        $original = $this->getOriginalPrice();
        $current = $this->getPrice();
        
        if (!$original || !$current || $original->getAmount() <= 0) {
            return null;
        }

        return round((($original->getAmount() - $current->getAmount()) / $original->getAmount()) * 100, 2);
    }

    /**
     * Check if product has Prime eligibility
     * 
     * @return bool
     */
    public function hasPrimeOffer(): bool
    {
        $offer = $this->getBuyBoxOffer();
        
        if (!$offer || !isset($offer['ProgramEligibility']['IsPrimeExclusive'])) {
            return false;
        }

        return $offer['ProgramEligibility']['IsPrimeExclusive'] === true ||
               $offer['ProgramEligibility']['IsPrimeEligible'] === true;
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
        
        if (!$offer || !isset($offer['Availability']['Type'])) {
            return false;
        }

        return $offer['Availability']['Type'] === 'Now';
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
        return $this->data['Offers']['Listings'] ?? [];
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
        
        // Return first offer if no buy box winner found
        return $offers[0] ?? null;
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
        ];
    }
}
