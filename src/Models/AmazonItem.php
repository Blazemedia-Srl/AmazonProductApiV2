<?php

declare(strict_types=1);

namespace Blazemedia\AmazonProductApiV2\Models;

/**
 * AmazonItem - Modello compatibile con sistemi esistenti
 * 
 * Questa classe mantiene la stessa interfaccia della classe originale
 * ma utilizza internamente ProductItem per la compatibilità
 * 
 * @package Blazemedia\AmazonProductApiV2\Models
 */
class AmazonItem 
{
    private ProductItem $item;

    public string $title     = '';
    public float  $price     = 0.0;
    public float  $fullprice = 0.0;

    public int    $saving = 0;
    public string $link   = '';
    public string $asin   = '';
    public string $image  = '';

    public bool  $hasPrimePrice = false;
    public array $primePrices = [];

    private string $partnerTag;
    private string $trackingPlaceholder;

    /**
     * Constructor
     * 
     * @param ProductItem $item L'oggetto ProductItem dalla nuova libreria
     * @param string $partnerTag Partner tag per il tracking
     * @param string $trackingPlaceholder Placeholder per il tracking
     */
    public function __construct(ProductItem $item, string $partnerTag = 'blazemedia-21', string $trackingPlaceholder = 'booBLZTRKood') 
    {
        $this->item = $item;
        $this->partnerTag = $partnerTag;
        $this->trackingPlaceholder = $trackingPlaceholder;

        $this->asin      = $this->item->getAsin() ?? '';
        $this->title     = $this->item->getTitle() ?? '';
        $this->price     = $this->getPrice();
        $this->saving    = $this->getSaving();
        $this->fullprice = $this->getFullPrice();
        $this->link      = $this->addTrackingPlaceholder($this->item->getDetailPageURL() ?? '');
        $this->image     = $this->getImage();

        $this->hasPrimePrice = $this->hasPrimeExclusive();
        $this->primePrices   = ($this->hasPrimePrice) ? $this->getPrimePrice() : [];
    }

    /**
     * Convert to array format compatible with existing systems
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'title'             => $this->title,
            'asin'              => $this->asin,
            'price'             => $this->price,
            'fullprice'         => $this->fullprice,
            'saving'            => $this->saving,
            'link'              => $this->link,
            'images'            => $this->image,
            'hasPrimeExclusive' => $this->hasPrimePrice,
            'primePrices'       => $this->primePrices
        ];
    }

    /**
     * Check if item has offer
     * 
     * @return bool
     */
    private function hasOffer(): bool
    {
        $price = $this->item->getPrice();
        return $price !== null && $price->isAvailable();
    }

    /**
     * Check if item has saving/discount
     * 
     * @return bool
     */
    private function hasSaving(): bool
    {
        return $this->item->getDiscountPercentage() !== null && $this->item->getDiscountPercentage() > 0;
    }

    /**
     * Add tracking placeholder to link
     * 
     * @param string $link
     * @return string
     */
    private function addTrackingPlaceholder(string $link): string
    {
        return str_replace($this->partnerTag, $this->trackingPlaceholder, $link);
    }

    /**
     * Get product image URL
     * 
     * @return string
     */
    private function getImage(): string
    {
        return $this->item->getImageUrl('Large') ?? '';
    }

    /**
     * Get current price as float
     * 
     * @return float
     */
    private function getPrice(): float
    {
        $price = $this->item->getPrice();
        return $price ? $price->getAmount() : 0.0;
    }

    /**
     * Get saving percentage as integer
     * 
     * @return int
     */
    private function getSaving(): int
    {
        $discount = $this->item->getDiscountPercentage();
        return $discount ? (int) round($discount) : 0;
    }

    /**
     * Get full price (original price before discount)
     * 
     * @return float
     */
    private function getFullPrice(): float
    {
        $originalPrice = $this->item->getOriginalPrice();
        if ($originalPrice) {
            return $originalPrice->getAmount();
        }

        // Se non c'è prezzo originale, ritorna il prezzo corrente
        return $this->getPrice();
    }

    /**
     * Check if product has Prime exclusive offers
     * 
     * @return bool
     */
    private function hasPrimeExclusive(): bool
    {
        return $this->item->hasPrimeOffer();
    }

    /**
     * Get Prime exclusive price information
     * 
     * @return array
     */
    private function getPrimePrice(): array
    {
        if (!$this->hasPrimeExclusive()) {
            return [];
        }

        $currentPrice = $this->getPrice();
        $fullPrice = $this->getFullPrice();
        
        // Calcola il risparmio Prime
        $savingAmount = $fullPrice - $currentPrice;
        $savingPercentage = $fullPrice > 0 ? (($savingAmount / $fullPrice) * 100) : 0;

        return [
            'price'     => $currentPrice,
            'saving'    => round($savingAmount, 2),
            'fullprice' => $fullPrice,
            'saving_percentage' => round($savingPercentage, 2)
        ];
    }

    /**
     * Get the underlying ProductItem object
     * 
     * @return ProductItem
     */
    public function getProductItem(): ProductItem
    {
        return $this->item;
    }

    /**
     * Static factory method to create AmazonItem from raw API data
     * 
     * @param array $itemData Raw item data from Amazon API
     * @param string $partnerTag Partner tag
     * @param string $trackingPlaceholder Tracking placeholder
     * @return self
     */
    public static function fromApiData(array $itemData, string $partnerTag = 'blazemedia-21', string $trackingPlaceholder = 'booBLZTRKood'): self
    {
        $productItem = new ProductItem($itemData);
        return new self($productItem, $partnerTag, $trackingPlaceholder);
    }

    /**
     * Magic method to get properties
     * 
     * @param string $name
     * @return mixed
     */
    public function __get(string $name)
    {
        if (property_exists($this, $name)) {
            return $this->$name;
        }
        return null;
    }

    /**
     * Magic method to check if property exists
     * 
     * @param string $name
     * @return bool
     */
    public function __isset(string $name): bool
    {
        return property_exists($this, $name);
    }
}
