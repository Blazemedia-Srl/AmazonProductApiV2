<?php

declare(strict_types=1);

namespace Blazemedia\AmazonProductApiV2\Tests;

use PHPUnit\Framework\TestCase;
use Blazemedia\AmazonProductApiV2\Models\Price;

/**
 * Test class for Price
 * 
 * @package Blazemedia\AmazonProductApiV2\Tests
 */
class PriceTest extends TestCase
{
    /**
     * Test constructor with valid data
     */
    public function testConstructorWithValidData(): void
    {
        $data = [
            'Amount' => 29.99,
            'Currency' => 'USD',
            'DisplayValue' => '$29.99'
        ];
        
        $price = new Price($data);
        
        $this->assertInstanceOf(Price::class, $price);
    }

    /**
     * Test getAmount method
     */
    public function testGetAmount(): void
    {
        $data = ['Amount' => 29.99, 'Currency' => 'USD'];
        $price = new Price($data);
        
        $this->assertEquals(29.99, $price->getAmount());
    }

    /**
     * Test getAmount method with string amount
     */
    public function testGetAmountWithStringAmount(): void
    {
        $data = ['Amount' => '29.99', 'Currency' => 'USD'];
        $price = new Price($data);
        
        $this->assertEquals(29.99, $price->getAmount());
    }

    /**
     * Test getAmount method with missing amount
     */
    public function testGetAmountWithMissingAmount(): void
    {
        $data = ['Currency' => 'USD'];
        $price = new Price($data);
        
        $this->assertEquals(0.0, $price->getAmount());
    }

    /**
     * Test getCurrency method
     */
    public function testGetCurrency(): void
    {
        $data = ['Amount' => 29.99, 'Currency' => 'USD'];
        $price = new Price($data);
        
        $this->assertEquals('USD', $price->getCurrency());
    }

    /**
     * Test getCurrency method with missing currency
     */
    public function testGetCurrencyWithMissingCurrency(): void
    {
        $data = ['Amount' => 29.99];
        $price = new Price($data);
        
        $this->assertEquals('EUR', $price->getCurrency());
    }

    /**
     * Test getDisplayValue method
     */
    public function testGetDisplayValue(): void
    {
        $data = [
            'Amount' => 29.99,
            'Currency' => 'USD',
            'DisplayValue' => '$29.99'
        ];
        $price = new Price($data);
        
        $this->assertEquals('$29.99', $price->getDisplayValue());
    }

    /**
     * Test getDisplayValue method with missing display value
     */
    public function testGetDisplayValueWithMissingDisplayValue(): void
    {
        $data = ['Amount' => 29.99, 'Currency' => 'USD'];
        $price = new Price($data);
        
        $this->assertEquals('$29.99', $price->getDisplayValue());
    }

    /**
     * Test formatPrice method for USD
     */
    public function testFormatPriceForUSD(): void
    {
        $data = ['Amount' => 29.99, 'Currency' => 'USD'];
        $price = new Price($data);
        
        $this->assertEquals('$29.99', $price->formatPrice());
    }

    /**
     * Test formatPrice method for EUR
     */
    public function testFormatPriceForEUR(): void
    {
        $data = ['Amount' => 29.99, 'Currency' => 'EUR'];
        $price = new Price($data);
        
        $this->assertEquals('€ 29,99', $price->formatPrice());
    }

    /**
     * Test formatPrice method for GBP
     */
    public function testFormatPriceForGBP(): void
    {
        $data = ['Amount' => 29.99, 'Currency' => 'GBP'];
        $price = new Price($data);
        
        $this->assertEquals('£29.99', $price->formatPrice());
    }

    /**
     * Test formatPrice method for JPY
     */
    public function testFormatPriceForJPY(): void
    {
        $data = ['Amount' => 2999, 'Currency' => 'JPY'];
        $price = new Price($data);
        
        $this->assertEquals('¥2,999', $price->formatPrice());
    }

    /**
     * Test formatPrice method for unknown currency
     */
    public function testFormatPriceForUnknownCurrency(): void
    {
        $data = ['Amount' => 29.99, 'Currency' => 'CAD'];
        $price = new Price($data);
        
        $this->assertEquals('CAD 29.99', $price->formatPrice());
    }

    /**
     * Test getPricePerUnit method
     */
    public function testGetPricePerUnit(): void
    {
        $data = [
            'Amount' => 29.99,
            'Currency' => 'USD',
            'PricePerUnit' => '$2.99 per unit'
        ];
        $price = new Price($data);
        
        $this->assertEquals('$2.99 per unit', $price->getPricePerUnit());
    }

    /**
     * Test getPricePerUnit method with missing price per unit
     */
    public function testGetPricePerUnitWithMissingPricePerUnit(): void
    {
        $data = ['Amount' => 29.99, 'Currency' => 'USD'];
        $price = new Price($data);
        
        $this->assertNull($price->getPricePerUnit());
    }

    /**
     * Test isAvailable method with positive amount
     */
    public function testIsAvailableWithPositiveAmount(): void
    {
        $data = ['Amount' => 29.99, 'Currency' => 'USD'];
        $price = new Price($data);
        
        $this->assertTrue($price->isAvailable());
    }

    /**
     * Test isAvailable method with zero amount
     */
    public function testIsAvailableWithZeroAmount(): void
    {
        $data = ['Amount' => 0, 'Currency' => 'USD'];
        $price = new Price($data);
        
        $this->assertFalse($price->isAvailable());
    }

    /**
     * Test isAvailable method with negative amount
     */
    public function testIsAvailableWithNegativeAmount(): void
    {
        $data = ['Amount' => -10, 'Currency' => 'USD'];
        $price = new Price($data);
        
        $this->assertFalse($price->isAvailable());
    }

    /**
     * Test toArray method
     */
    public function testToArray(): void
    {
        $data = [
            'Amount' => 29.99,
            'Currency' => 'USD',
            'DisplayValue' => '$29.99',
            'PricePerUnit' => '$2.99 per unit'
        ];
        $price = new Price($data);
        
        $array = $price->toArray();
        
        $this->assertIsArray($array);
        $this->assertEquals(29.99, $array['amount']);
        $this->assertEquals('USD', $array['currency']);
        $this->assertEquals('$29.99', $array['display_value']);
        $this->assertEquals('$29.99', $array['formatted']);
        $this->assertEquals('$2.99 per unit', $array['price_per_unit']);
        $this->assertTrue($array['is_available']);
    }

    /**
     * Test __toString method
     */
    public function testToString(): void
    {
        $data = ['Amount' => 29.99, 'Currency' => 'USD'];
        $price = new Price($data);
        
        $this->assertEquals('$29.99', (string) $price);
    }

    /**
     * Test getRawData method
     */
    public function testGetRawData(): void
    {
        $data = ['Amount' => 29.99, 'Currency' => 'USD'];
        $price = new Price($data);
        
        $this->assertEquals($data, $price->getRawData());
    }

    /**
     * Test with empty data
     */
    public function testWithEmptyData(): void
    {
        $price = new Price([]);
        
        $this->assertEquals(0.0, $price->getAmount());
        $this->assertEquals('EUR', $price->getCurrency());
        $this->assertEquals('€ 0,00', $price->getDisplayValue());
        $this->assertEquals('€ 0,00', $price->formatPrice());
        $this->assertNull($price->getPricePerUnit());
        $this->assertFalse($price->isAvailable());
        $this->assertEquals('€ 0,00', (string) $price);
    }

    /**
     * Test with decimal formatting
     */
    public function testWithDecimalFormatting(): void
    {
        $data = ['Amount' => 1234.56, 'Currency' => 'USD'];
        $price = new Price($data);
        
        $this->assertEquals('$1,234.56', $price->formatPrice());
    }

    /**
     * Test with large numbers
     */
    public function testWithLargeNumbers(): void
    {
        $data = ['Amount' => 1234567.89, 'Currency' => 'EUR'];
        $price = new Price($data);
        
        $this->assertEquals('€ 1.234.567,89', $price->formatPrice());
    }
} 