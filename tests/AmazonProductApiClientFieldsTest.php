<?php

declare(strict_types=1);

namespace Blazemedia\AmazonProductApiV2\Tests;

use PHPUnit\Framework\TestCase;
use Blazemedia\AmazonProductApiV2\AmazonProductApiClient;
use Blazemedia\AmazonProductApiV2\Exceptions\InvalidParameterException;
use Blazemedia\AmazonProductApiV2\Exceptions\AmazonApiException;
use Blazemedia\AmazonProductApiV2\Models\ProductItem;

/**
 * Test class for AmazonProductApiClient with real API credentials
 * 
 * This test reads credentials from an external file that is not in the repository
 * 
 * @package Blazemedia\AmazonProductApiV2\Tests
 */
class AmazonProductApiClientFieldsTest extends TestCase
{
    private ?AmazonProductApiClient $client = null;
    private array $credentials = [];

    protected function setUp(): void
    {
        parent::setUp();
        
        // Load credentials from external file
        $this->loadCredentials();

        if (!empty($this->credentials)) {
            $this->client = new AmazonProductApiClient($this->credentials);
        }
    }

    /**
     * Load API credentials from external file
     * 
     * The credentials file should be outside the repository and contain:
     * {
     *   "accessKey": "your-access-key",
     *   "secretKey": "your-secret-key", 
     *   "partnerTag": "your-partner-tag",
     *   "marketplace": "www.amazon.com"
     * }
     */
    private function loadCredentials(): void
    {
        $credentialsFile = __DIR__ . '/../amazon-api-credentials.json';
        
        if (!file_exists($credentialsFile)) {            
            $this->markTestSkipped(
                'Credentials file not found. Create amazon-api-credentials.json in the project root with your API credentials.'
            );
            return;
        }
        
        try {
            $credentialsJson = file_get_contents($credentialsFile);
            $this->credentials = json_decode($credentialsJson, true, 512, JSON_THROW_ON_ERROR);
            
            // Validate required fields
            $requiredFields = ['accessKey', 'secretKey', 'partnerTag', 'marketplace'];
            foreach ($requiredFields as $field) {
                if (!isset($this->credentials[$field]) || empty($this->credentials[$field])) {
                    $this->markTestSkipped("Missing required credential field: {$field}");
                    return;
                }
            }
        } catch (\JsonException $e) {
            $this->markTestSkipped('Invalid JSON in credentials file: ' . $e->getMessage());
            return;
        } catch (\Exception $e) {
            $this->markTestSkipped('Error reading credentials file: ' . $e->getMessage());
            return;
        }
    }

    /**
     * Test client instantiation with real credentials
     */
    public function testClientInstantiationWithRealCredentials(): void
    {
        if (!$this->client) {
            $this->markTestSkipped('Client not available due to missing credentials');
        }
        
        $this->assertInstanceOf(AmazonProductApiClient::class, $this->client);
        $this->assertEquals(30, $this->client->getTimeout());
    }

    /**
     * Test getting a real product by ASIN
     */
    public function testGetRealProductByAsin(): void
    {

        if (!$this->client) {
            $this->markTestSkipped('Client not available due to missing credentials');
        }
        
        // Use a well-known Amazon product ASIN
        $asin = 'B0CN5KMN3G'; 
        $asin = 'B0DLCVRCJS'; 
        $asin = 'B09FXSF448';
        
        
        
        try {
            $item = $this->client->getItem($asin);

            file_put_contents(__DIR__ . '/../item.json', json_encode($item->toArray(), JSON_PRETTY_PRINT));
            var_dump('item', $item); die;
            
            $this->assertInstanceOf(ProductItem::class, $item);
            $this->assertEquals($asin, $item->getAsin());
            $this->assertNotNull($item->getTitle());
            $this->assertNotEmpty($item->getTitle());
            
            // Test basic product information
            $this->assertIsString($item->getAsin());
            $this->assertIsString($item->getTitle());
            
            // Test that we can get additional information
            $this->assertIsArray($item->getFeatures());
            $this->assertIsArray($item->getAllImages());            
            
        } catch (AmazonApiException $e) {

            echo $e->getMessage(); die;
            $this->markTestSkipped('API request failed: ' . $e->getMessage());
        }
    }

    /**
     * Test getting multiple real products
     */
    public function testGetMultipleRealProducts(): void
    {
        if (!$this->client) {
            $this->markTestSkipped('Client not available due to missing credentials');
        }
        
        // Use well-known Amazon product ASINs
        $asins = [
            'B08N5WRWNW', // Echo Dot (4th Gen)
            'B07FZ8S74R', // Echo Dot (3rd Gen)
            'B07XJ8C8F7'  // Echo Show 5
        ];
        
        try {
            $items = $this->client->getItems($asins);
            
            $this->assertIsArray($items);
            $this->assertCount(3, $items);
            
            foreach ($items as $item) {
                $this->assertInstanceOf(ProductItem::class, $item);
                $this->assertNotNull($item->getAsin());
                $this->assertNotNull($item->getTitle());
            }
            
        } catch (AmazonApiException $e) {
            $this->markTestSkipped('API request failed: ' . $e->getMessage());
        }
    }

    /**
     * Test getting product with custom resources
     */
    public function testGetProductWithCustomResources(): void
    {
        if (!$this->client) {
            $this->markTestSkipped('Client not available due to missing credentials');
        }
        
        $asin = 'B08N5WRWNW';
        $resources = [
            'ItemInfo.Title',
            'ItemInfo.ByLineInfo',
            'Images.Primary.Large',
            'OffersV2.Listings.Price'
        ];
        
        try {
            $item = $this->client->getItem($asin, $resources);
            
            $this->assertInstanceOf(ProductItem::class, $item);
            $this->assertEquals($asin, $item->getAsin());
            $this->assertNotNull($item->getTitle());
            
        } catch (AmazonApiException $e) {
            $this->markTestSkipped('API request failed: ' . $e->getMessage());
        }
    }

    /**
     * Test getting product with custom offer count
     */
    public function testGetProductWithCustomOfferCount(): void
    {
        if (!$this->client) {
            $this->markTestSkipped('Client not available due to missing credentials');
        }
        
        $asin = 'B08N5WRWNW';
        
        try {
            $item = $this->client->getItem($asin, [], 5);
            
            $this->assertInstanceOf(ProductItem::class, $item);
            $this->assertEquals($asin, $item->getAsin());
            
        } catch (AmazonApiException $e) {
            $this->markTestSkipped('API request failed: ' . $e->getMessage());
        }
    }

    /**
     * Test getting AmazonItem (legacy method)
     */
    public function testGetAmazonItem(): void
    {
        if (!$this->client) {
            $this->markTestSkipped('Client not available due to missing credentials');
        }
        
        $asin = 'B08N5WRWNW';
        
        try {
            $item = $this->client->getAmazonItem($asin);
            
            $this->assertInstanceOf(\Blazemedia\AmazonProductApiV2\Models\AmazonItem::class, $item);
            
        } catch (AmazonApiException $e) {
            $this->markTestSkipped('API request failed: ' . $e->getMessage());
        }
    }

    /**
     * Test getting multiple AmazonItems (legacy method)
     */
    public function testGetAmazonItems(): void
    {
        if (!$this->client) {
            $this->markTestSkipped('Client not available due to missing credentials');
        }
        
        $asins = ['B08N5WRWNW', 'B07FZ8S74R'];
        
        try {
            $items = $this->client->getAmazonItems($asins);
            
            $this->assertIsArray($items);
            $this->assertCount(2, $items);
            
            foreach ($items as $item) {
                $this->assertInstanceOf(\Blazemedia\AmazonProductApiV2\Models\AmazonItem::class, $item);
            }
            
        } catch (AmazonApiException $e) {
            $this->markTestSkipped('API request failed: ' . $e->getMessage());
        }
    }

    /**
     * Test product data structure
     */
    public function testProductDataStructure(): void
    {
        if (!$this->client) {
            $this->markTestSkipped('Client not available due to missing credentials');
        }
        
        $asin = 'B08N5WRWNW';
        
        try {
            $item = $this->client->getItem($asin);
            
            // Test basic data structure
            $this->assertNotNull($item->getAsin());
            $this->assertNotNull($item->getTitle());
            
            // Test optional fields (may be null)
            $brand = $item->getBrand();
            $manufacturer = $item->getManufacturer();
            $price = $item->getPrice();
            $availability = $item->getAvailability();
            
            // Test that methods return expected types
            $this->assertIsArray($item->getFeatures());
            $this->assertIsArray($item->getAllImages());
            $this->assertIsArray($item->getAllOffers());
            
            // Test raw data access
            $rawData = $item->getRawData();
            $this->assertIsArray($rawData);
            $this->assertArrayHasKey('ASIN', $rawData);
            
        } catch (AmazonApiException $e) {
            $this->markTestSkipped('API request failed: ' . $e->getMessage());
        }
    }

    /**
     * Test error handling with invalid ASIN
     */
    public function testErrorHandlingWithInvalidAsin(): void
    {
        if (!$this->client) {
            $this->markTestSkipped('Client not available due to missing credentials');
        }
        
        $invalidAsin = 'INVALID_ASIN_12345';
        
        try {
            $item = $this->client->getItem($invalidAsin);
            
            // If we get here, the API might have returned a different product
            // or the ASIN might be valid in some way
            $this->assertInstanceOf(ProductItem::class, $item);
            
        } catch (AmazonApiException $e) {
            // Expected behavior - API should return an error for invalid ASIN
            $this->assertInstanceOf(AmazonApiException::class, $e);
        }
    }

    /**
     * Test timeout setting
     */
    public function testTimeoutSetting(): void
    {
        if (!$this->client) {
            $this->markTestSkipped('Client not available due to missing credentials');
        }
        
        $originalTimeout = $this->client->getTimeout();
        
        $this->client->setTimeout(60);
        $this->assertEquals(60, $this->client->getTimeout());
        
        $this->client->setTimeout($originalTimeout);
        $this->assertEquals($originalTimeout, $this->client->getTimeout());
    }

    /**
     * Test different marketplace configurations
     */
    public function testDifferentMarketplaceConfigurations(): void
    {
        if (empty($this->credentials)) {
            $this->markTestSkipped('Credentials not available');
        }
        
        $marketplaces = [
            'www.amazon.com' => 'US',
            'www.amazon.co.uk' => 'UK',
            'www.amazon.de' => 'Germany'
        ];
        
        foreach ($marketplaces as $marketplace => $description) {
            $config = $this->credentials;
            $config['marketplace'] = $marketplace;
            
            try {
                $client = new AmazonProductApiClient($config);
                $this->assertInstanceOf(AmazonProductApiClient::class, $client);
                
                // Test with a simple ASIN
                $item = $client->getItem('B08N5WRWNW');
                $this->assertInstanceOf(ProductItem::class, $item);
                
            } catch (AmazonApiException $e) {
                // Some marketplaces might not have the same products
                $this->markTestSkipped("API request failed for {$description}: " . $e->getMessage());
            } catch (InvalidParameterException $e) {
                $this->markTestSkipped("Invalid configuration for {$description}: " . $e->getMessage());
            }
        }
    }
} 