<?php

declare(strict_types=1);

namespace Blazemedia\AmazonProductApiV2\Models;

/**
 * Price Model
 * 
 * @package Blazemedia\AmazonProductApiV2\Models
 */
class Price
{
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Get price amount as float
     * 
     * @return float
     */
    public function getAmount(): float
    {
        return (float) ($this->data['Amount'] ?? 0);
    }

    /**
     * Get price currency
     * 
     * @return string
     */
    public function getCurrency(): string
    {
        return $this->data['Currency'] ?? 'EUR';
    }

    /**
     * Get formatted price display value
     * 
     * @return string
     */
    public function getDisplayValue(): string
    {
        return $this->data['DisplayValue'] ?? $this->formatPrice();
    }

    /**
     * Format price for display
     * 
     * @return string
     */
    public function formatPrice(): string
    {
        $amount = $this->getAmount();
        $currency = $this->getCurrency();
        
        // Format based on currency
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

    /**
     * Get price per unit (if available)
     * 
     * @return string|null
     */
    public function getPricePerUnit(): ?string
    {
        return $this->data['PricePerUnit'] ?? null;
    }

    /**
     * Check if price is available
     * 
     * @return bool
     */
    public function isAvailable(): bool
    {
        return $this->getAmount() > 0;
    }

    /**
     * Convert to array representation
     * 
     * @return array
     */
    public function toArray(): array
    {
        return [
            'amount' => $this->getAmount(),
            'currency' => $this->getCurrency(),
            'display_value' => $this->getDisplayValue(),
            'formatted' => $this->formatPrice(),
            'price_per_unit' => $this->getPricePerUnit(),
            'is_available' => $this->isAvailable(),
        ];
    }

    /**
     * Convert to string
     * 
     * @return string
     */
    public function __toString(): string
    {
        return $this->getDisplayValue();
    }

    /**
     * Get raw price data
     * 
     * @return array
     */
    public function getRawData(): array
    {
        return $this->data;
    }
}
