<?php

declare(strict_types=1);

namespace Blazemedia\AmazonProductApiV2\Tests;

use PHPUnit\Framework\TestCase;
use Blazemedia\AmazonProductApiV2\Models\ProductItem;
use Blazemedia\AmazonProductApiV2\Models\Price;

/**
 * Test class for ProductItem
 * 
 * @package Blazemedia\AmazonProductApiV2\Tests
 */
class ProductItemTest extends TestCase
{
    private array $sampleData;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->sampleData = [
            'ASIN' => 'B08N5WRWNW',
            'ItemInfo' => [
                'Title' => [
                    'DisplayValue' => 'Test Product Title'
                ],
                'ByLineInfo' => [
                    'Brand' => [
                        'DisplayValue' => 'Test Brand'
                    ],
                    'Manufacturer' => [
                        'DisplayValue' => 'Test Manufacturer'
                    ]
                ],
                'Features' => [
                    'DisplayValues' => [
                        'Feature 1',
                        'Feature 2',
                        'Feature 3'
                    ]
                ]
            ],
            'Images' => [
                'Primary' => [
                    'Small' => ['URL' => 'https://example.com/small.jpg'],
                    'Medium' => ['URL' => 'https://example.com/medium.jpg'],
                    'Large' => ['URL' => 'https://example.com/large.jpg']
                ],
                'Variants' => [
                    [
                        'Small' => ['URL' => 'https://example.com/variant1-small.jpg'],
                        'Medium' => ['URL' => 'https://example.com/variant1-medium.jpg'],
                        'Large' => ['URL' => 'https://example.com/variant1-large.jpg']
                    ]
                ]
            ],
            'OffersV2' => [
                'Listings' => [
                    [
                        'Price' => [
                            'Money' => [
                                'Amount' => 29.99,
                                'Currency' => 'USD'
                            ],
                            'Savings' => [
                                'Money' => [
                                    'Amount' => 10.00,
                                    'Currency' => 'USD'
                                ]
                            ],
                            'SavingBasis' => [
                                'Money' => [
                                    'Amount' => 39.99,
                                    'Currency' => 'USD'
                                ]
                            ]
                        ],
                        'IsBuyBoxWinner' => true,
                        'Availability' => [
                            'Message' => 'In Stock'
                        ],
                        'Condition' => [
                            'Value' => 'New'
                        ],
                        'MerchantInfo' => [
                            'Name' => 'Amazon.com'
                        ]
                    ]
                ]
            ]
        ];
    }

    /**
     * Test constructor with valid data
     */
    public function testConstructorWithValidData(): void
    {
        $item = new ProductItem($this->sampleData);
        
        $this->assertInstanceOf(ProductItem::class, $item);
    }

    /**
     * Test getAsin method
     */
    public function testGetAsin(): void
    {
        $item = new ProductItem($this->sampleData);
        
        $this->assertEquals('B08N5WRWNW', $item->getAsin());
    }

    /**
     * Test getAsin method with missing ASIN
     */
    public function testGetAsinWithMissingAsin(): void
    {
        $data = $this->sampleData;
        unset($data['ASIN']);
        
        $item = new ProductItem($data);
        
        $this->assertNull($item->getAsin());
    }

    /**
     * Test getTitle method
     */
    public function testGetTitle(): void
    {
        $item = new ProductItem($this->sampleData);
        
        $this->assertEquals('Test Product Title', $item->getTitle());
    }

    /**
     * Test getTitle method with missing title
     */
    public function testGetTitleWithMissingTitle(): void
    {
        $data = $this->sampleData;
        unset($data['ItemInfo']['Title']);
        
        $item = new ProductItem($data);
        
        $this->assertNull($item->getTitle());
    }

    /**
     * Test getFeatures method
     */
    public function testGetFeatures(): void
    {
        $item = new ProductItem($this->sampleData);
        
        $expectedFeatures = ['Feature 1', 'Feature 2', 'Feature 3'];
        $this->assertEquals($expectedFeatures, $item->getFeatures());
    }

    /**
     * Test getFeatures method with missing features
     */
    public function testGetFeaturesWithMissingFeatures(): void
    {
        $data = $this->sampleData;
        unset($data['ItemInfo']['Features']);
        
        $item = new ProductItem($data);
        
        $this->assertEquals([], $item->getFeatures());
    }

    /**
     * Test getDescription method
     */
    public function testGetDescription(): void
    {
        $item = new ProductItem($this->sampleData);
        
        $expectedDescription = 'Feature 1. Feature 2. Feature 3';
        $this->assertEquals($expectedDescription, $item->getDescription());
    }

    /**
     * Test getDescription method with no features
     */
    public function testGetDescriptionWithNoFeatures(): void
    {
        $data = $this->sampleData;
        unset($data['ItemInfo']['Features']);
        
        $item = new ProductItem($data);
        
        $this->assertNull($item->getDescription());
    }

    /**
     * Test getBrand method
     */
    public function testGetBrand(): void
    {
        $item = new ProductItem($this->sampleData);
        
        $this->assertEquals('Test Brand', $item->getBrand());
    }

    /**
     * Test getManufacturer method
     */
    public function testGetManufacturer(): void
    {
        $item = new ProductItem($this->sampleData);
        
        $this->assertEquals('Test Manufacturer', $item->getManufacturer());
    }

    /**
     * Test getImageUrl method
     */
    public function testGetImageUrl(): void
    {
        $item = new ProductItem($this->sampleData);
        
        $this->assertEquals('https://example.com/large.jpg', $item->getImageUrl('Large'));
        $this->assertEquals('https://example.com/medium.jpg', $item->getImageUrl('Medium'));
        $this->assertEquals('https://example.com/small.jpg', $item->getImageUrl('Small'));
    }

    /**
     * Test getImageUrl method with missing image
     */
    public function testGetImageUrlWithMissingImage(): void
    {
        $data = $this->sampleData;
        unset($data['Images']['Primary']['Large']);
        
        $item = new ProductItem($data);
        
        $this->assertNull($item->getImageUrl('Large'));
    }

    /**
     * Test getAllImages method
     */
    public function testGetAllImages(): void
    {
        $item = new ProductItem($this->sampleData);
        
        $expectedImages = [
            'https://example.com/large.jpg',
            'https://example.com/variant1-large.jpg'
        ];
        
        $this->assertEquals($expectedImages, $item->getAllImages('Large'));
    }

    /**
     * Test getPrice method
     */
    public function testGetPrice(): void
    {
        $item = new ProductItem($this->sampleData);
        
        $price = $item->getPrice();
        
        $this->assertInstanceOf(Price::class, $price);
        $this->assertEquals(29.99, $price->getAmount());
        $this->assertEquals('USD', $price->getCurrency());
    }

    /**
     * Test getPrice method with no buy box offer
     */
    public function testGetPriceWithNoBuyBoxOffer(): void
    {
        $data = $this->sampleData;
        $data['OffersV2']['Listings'][0]['IsBuyBoxWinner'] = false;
        
        $item = new ProductItem($data);
        
        $this->assertNull($item->getPrice());
    }

    /**
     * Test getOriginalPrice method
     */
    public function testGetOriginalPrice(): void
    {
        $item = new ProductItem($this->sampleData);
        
        $originalPrice = $item->getOriginalPrice();
        
        $this->assertInstanceOf(Price::class, $originalPrice);
        $this->assertEquals(39.99, $originalPrice->getAmount());
        $this->assertEquals('USD', $originalPrice->getCurrency());
    }

    /**
     * Test getDiscountAmount method
     */
    public function testGetDiscountAmount(): void
    {
        $item = new ProductItem($this->sampleData);
        
        $discountAmount = $item->getDiscountAmount();
        
        $this->assertInstanceOf(Price::class, $discountAmount);
        $this->assertEquals(10.00, $discountAmount->getAmount());
        $this->assertEquals('USD', $discountAmount->getCurrency());
    }

    /**
     * Test getDiscountPercentage method
     */
    public function testGetDiscountPercentage(): void
    {
        $item = new ProductItem($this->sampleData);
        
        $discountPercentage = $item->getDiscountPercentage();
        
        $this->assertEquals(25.0, $discountPercentage); // (10.00 / 39.99) * 100
    }

    /**
     * Test hasPrimeOffer method
     */
    public function testHasPrimeOffer(): void
    {
        $item = new ProductItem($this->sampleData);
        
        // Default should be false since we don't have Prime info in sample data
        $this->assertFalse($item->hasPrimeOffer());
    }

    /**
     * Test hasActiveDeal method
     */
    public function testHasActiveDeal(): void
    {
        $item = new ProductItem($this->sampleData);
        
        // Should be true since we have savings
        $this->assertTrue($item->hasActiveDeal());
    }

    /**
     * Test getAvailability method
     */
    public function testGetAvailability(): void
    {
        $item = new ProductItem($this->sampleData);
        
        $this->assertEquals('In Stock', $item->getAvailability());
    }

    /**
     * Test isInStock method
     */
    public function testIsInStock(): void
    {
        $item = new ProductItem($this->sampleData);
        
        $this->assertTrue($item->isInStock());
    }

    /**
     * Test getCondition method
     */
    public function testGetCondition(): void
    {
        $item = new ProductItem($this->sampleData);
        
        $this->assertEquals('New', $item->getCondition());
    }

    /**
     * Test getMerchantInfo method
     */
    public function testGetMerchantInfo(): void
    {
        $item = new ProductItem($this->sampleData);
        
        $merchantInfo = $item->getMerchantInfo();
        
        $this->assertIsArray($merchantInfo);
        $this->assertEquals('Amazon.com', $merchantInfo['Name']);
    }

    /**
     * Test getBuyBoxOffer method
     */
    public function testGetBuyBoxOffer(): void
    {
        $item = new ProductItem($this->sampleData);
        
        $buyBoxOffer = $item->getBuyBoxOffer();
        
        $this->assertIsArray($buyBoxOffer);
        $this->assertTrue($buyBoxOffer['IsBuyBoxWinner']);
    }

    /**
     * Test getAllOffers method
     */
    public function testGetAllOffers(): void
    {
        $item = new ProductItem($this->sampleData);
        
        $allOffers = $item->getAllOffers();
        
        $this->assertIsArray($allOffers);
        $this->assertCount(1, $allOffers);
    }

    /**
     * Test getRawData method
     */
    public function testGetRawData(): void
    {
        $item = new ProductItem($this->sampleData);
        
        $rawData = $item->getRawData();
        
        $this->assertEquals($this->sampleData, $rawData);
    }

    /**
     * Test toArray method
     */
    public function testToArray(): void
    {
        $item = new ProductItem($this->sampleData);
        
        $array = $item->toArray();
        
        $this->assertIsArray($array);
        $this->assertEquals($this->sampleData, $array);
    }

    /**
     * Test with empty data
     */
    public function testWithEmptyData(): void
    {
        $item = new ProductItem([]);
        
        $this->assertNull($item->getAsin());
        $this->assertNull($item->getTitle());
        $this->assertEquals([], $item->getFeatures());
        $this->assertNull($item->getDescription());
        $this->assertNull($item->getBrand());
        $this->assertNull($item->getManufacturer());
        $this->assertNull($item->getImageUrl());
        $this->assertEquals([], $item->getAllImages());
        $this->assertNull($item->getPrice());
        $this->assertNull($item->getOriginalPrice());
        $this->assertNull($item->getDiscountAmount());
        $this->assertNull($item->getDiscountPercentage());
        $this->assertFalse($item->hasPrimeOffer());
        $this->assertFalse($item->hasActiveDeal());
        $this->assertNull($item->getAvailability());
        $this->assertFalse($item->isInStock());
        $this->assertNull($item->getCondition());
        $this->assertNull($item->getMerchantInfo());
        $this->assertNull($item->getBuyBoxOffer());
        $this->assertEquals([], $item->getAllOffers());
    }
} 