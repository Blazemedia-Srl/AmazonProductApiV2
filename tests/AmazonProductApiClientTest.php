<?php

declare(strict_types=1);

namespace Blazemedia\AmazonProductApiV2\Tests;

use PHPUnit\Framework\TestCase;
use Blazemedia\AmazonProductApiV2\AmazonProductApiClient;
use Blazemedia\AmazonProductApiV2\Exceptions\InvalidParameterException;
use Blazemedia\AmazonProductApiV2\Exceptions\AmazonApiException;
use Blazemedia\AmazonProductApiV2\Models\ProductItem;

/**
 * Test class for AmazonProductApiClient
 * 
 * @package Blazemedia\AmazonProductApiV2\Tests
 */
class AmazonProductApiClientTest extends TestCase
{
    private array $validConfig;
    private array $validConfigWithHost;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->validConfig = [
            'accessKey' => 'test-access-key',
            'secretKey' => 'test-secret-key',
            'partnerTag' => 'test-partner-tag',
            'marketplace' => 'www.amazon.com',
            'timeout' => 30
        ];

        $this->validConfigWithHost = [
            'accessKey' => 'test-access-key',
            'secretKey' => 'test-secret-key',
            'partnerTag' => 'test-partner-tag',
            'host' => 'webservices.amazon.com',
            'region' => 'us-east-1',
            'timeout' => 30
        ];
    }

    /**
     * Test successful client instantiation with marketplace configuration
     */
    public function testConstructorWithValidMarketplaceConfig(): void
    {
        $client = new AmazonProductApiClient($this->validConfig);
        
        $this->assertInstanceOf(AmazonProductApiClient::class, $client);
        
        // Test getter methods
        $this->assertEquals(30, $client->getTimeout());
    }

    /**
     * Test successful client instantiation with host configuration
     */
    public function testConstructorWithValidHostConfig(): void
    {
        $client = new AmazonProductApiClient($this->validConfigWithHost);
        
        $this->assertInstanceOf(AmazonProductApiClient::class, $client);
    }

    /**
     * Test constructor with legacy config format (access_key, secret_key, etc.)
     */
    public function testConstructorWithLegacyConfigFormat(): void
    {
        $legacyConfig = [
            'access_key' => 'test-access-key',
            'secret_key' => 'test-secret-key',
            'partner_tag' => 'test-partner-tag',
            'marketplace' => 'www.amazon.com',
            'tracking_placeholder' => 'test-tracking',
            'timeout' => 60
        ];

        $client = new AmazonProductApiClient($legacyConfig);
        
        $this->assertInstanceOf(AmazonProductApiClient::class, $client);
        $this->assertEquals(60, $client->getTimeout());
    }

    /**
     * Test constructor with custom timeout
     */
    public function testConstructorWithCustomTimeout(): void
    {
        $config = $this->validConfig;
        $config['timeout'] = 120;
        
        $client = new AmazonProductApiClient($config);
        
        $this->assertEquals(120, $client->getTimeout());
    }

    /**
     * Test constructor with custom tracking placeholder
     */
    public function testConstructorWithCustomTrackingPlaceholder(): void
    {
        $config = $this->validConfig;
        $config['trackingPlaceholder'] = 'custom-tracking';
        
        $client = new AmazonProductApiClient($config);
        
        $this->assertInstanceOf(AmazonProductApiClient::class, $client);
    }

    /**
     * Test constructor with different marketplace configurations
     */
    public function testConstructorWithDifferentMarketplaces(): void
    {
        $marketplaces = [
            'www.amazon.com' => ['endpoint' => 'webservices.amazon.com', 'region' => 'us-east-1'],
            'www.amazon.co.uk' => ['endpoint' => 'webservices.amazon.co.uk', 'region' => 'eu-west-1'],
            'www.amazon.de' => ['endpoint' => 'webservices.amazon.de', 'region' => 'eu-west-1'],
            'www.amazon.co.jp' => ['endpoint' => 'webservices.amazon.co.jp', 'region' => 'us-west-2'],
        ];

        foreach ($marketplaces as $marketplace => $expected) {
            $config = $this->validConfig;
            $config['marketplace'] = $marketplace;
            
            $client = new AmazonProductApiClient($config);
            $this->assertInstanceOf(AmazonProductApiClient::class, $client);
        }
    }

    /**
     * Test constructor with missing access key
     */
    public function testConstructorWithMissingAccessKey(): void
    {
        $config = $this->validConfig;
        unset($config['accessKey']);
        
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('accessKey is required');
        
        new AmazonProductApiClient($config);
    }

    /**
     * Test constructor with missing secret key
     */
    public function testConstructorWithMissingSecretKey(): void
    {
        $config = $this->validConfig;
        unset($config['secretKey']);
        
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('secretKey is required');
        
        new AmazonProductApiClient($config);
    }

    /**
     * Test constructor with missing partner tag
     */
    public function testConstructorWithMissingPartnerTag(): void
    {
        $config = $this->validConfig;
        unset($config['partnerTag']);
        
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('partnerTag is required');
        
        new AmazonProductApiClient($config);
    }

    /**
     * Test constructor with missing marketplace and host
     */
    public function testConstructorWithMissingMarketplaceAndHost(): void
    {
        $config = $this->validConfig;
        unset($config['marketplace']);
        
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Either marketplace or host is required');
        
        new AmazonProductApiClient($config);
    }

    /**
     * Test constructor with invalid marketplace
     */
    public function testConstructorWithInvalidMarketplace(): void
    {
        $config = $this->validConfig;
        $config['marketplace'] = 'invalid-marketplace';
        
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Invalid marketplace: invalid-marketplace');
        
        new AmazonProductApiClient($config);
    }

    /**
     * Test constructor with invalid timeout (too low)
     */
    public function testConstructorWithInvalidTimeoutTooLow(): void
    {
        $config = $this->validConfig;
        $config['timeout'] = 0;
        
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('timeout must be between 1 and 300 seconds');
        
        new AmazonProductApiClient($config);
    }

    /**
     * Test constructor with invalid timeout (too high)
     */
    public function testConstructorWithInvalidTimeoutTooHigh(): void
    {
        $config = $this->validConfig;
        $config['timeout'] = 301;
        
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('timeout must be between 1 and 300 seconds');
        
        new AmazonProductApiClient($config);
    }

    /**
     * Test setTimeout method
     */
    public function testSetTimeout(): void
    {
        $client = new AmazonProductApiClient($this->validConfig);
        
        $client->setTimeout(60);
        $this->assertEquals(60, $client->getTimeout());
        
        $client->setTimeout(120);
        $this->assertEquals(120, $client->getTimeout());
    }

    /**
     * Test getItem method with empty ASIN
     */
    public function testGetItemWithEmptyAsin(): void
    {
        $client = new AmazonProductApiClient($this->validConfig);
        
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('ASINs array cannot be empty');
        
        $client->getItems([]);
    }

    /**
     * Test getItems method with too many ASINs
     */
    public function testGetItemsWithTooManyAsins(): void
    {
        $client = new AmazonProductApiClient($this->validConfig);
        
        $asins = array_fill(0, 11, 'B08N5WRWNW'); // 11 ASINs
        
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Maximum 10 ASINs allowed per request');
        
        $client->getItems($asins);
    }

    /**
     * Test getItem method with valid ASIN
     * Note: This test would require mocking the HTTP request
     */
    public function testGetItemWithValidAsin(): void
    {
        $this->markTestSkipped('This test requires HTTP request mocking');
        
        $client = new AmazonProductApiClient($this->validConfig);
        $item = $client->getItem('B08N5WRWNW');
        
        $this->assertInstanceOf(ProductItem::class, $item);
        $this->assertEquals('B08N5WRWNW', $item->getAsin());
    }

    /**
     * Test getItems method with multiple valid ASINs
     * Note: This test would require mocking the HTTP request
     */
    public function testGetItemsWithValidAsins(): void
    {
        $this->markTestSkipped('This test requires HTTP request mocking');
        
        $client = new AmazonProductApiClient($this->validConfig);
        $items = $client->getItems(['B08N5WRWNW', 'B08N5WRWNW']);
        
        $this->assertIsArray($items);
        $this->assertCount(2, $items);
        $this->assertInstanceOf(ProductItem::class, $items[0]);
        $this->assertInstanceOf(ProductItem::class, $items[1]);
    }

    /**
     * Test getItem method with custom resources
     * Note: This test would require mocking the HTTP request
     */
    public function testGetItemWithCustomResources(): void
    {
        $this->markTestSkipped('This test requires HTTP request mocking');
        
        $client = new AmazonProductApiClient($this->validConfig);
        $resources = ['ItemInfo.Title', 'Images.Primary.Large'];
        
        $item = $client->getItem('B08N5WRWNW', $resources);
        
        $this->assertInstanceOf(ProductItem::class, $item);
    }

    /**
     * Test getItem method with custom offer count
     * Note: This test would require mocking the HTTP request
     */
    public function testGetItemWithCustomOfferCount(): void
    {
        $this->markTestSkipped('This test requires HTTP request mocking');
        
        $client = new AmazonProductApiClient($this->validConfig);
        $item = $client->getItem('B08N5WRWNW', [], 5);
        
        $this->assertInstanceOf(ProductItem::class, $item);
    }

    /**
     * Test getAmazonItem method
     * Note: This test would require mocking the HTTP request
     */
    public function testGetAmazonItem(): void
    {
        $this->markTestSkipped('This test requires HTTP request mocking');
        
        $client = new AmazonProductApiClient($this->validConfig);
        $item = $client->getAmazonItem('B08N5WRWNW');
        
        $this->assertInstanceOf(\Blazemedia\AmazonProductApiV2\Models\AmazonItem::class, $item);
    }

    /**
     * Test getAmazonItems method
     * Note: This test would require mocking the HTTP request
     */
    public function testGetAmazonItems(): void
    {
        $this->markTestSkipped('This test requires HTTP request mocking');
        
        $client = new AmazonProductApiClient($this->validConfig);
        $items = $client->getAmazonItems(['B08N5WRWNW', 'B08N5WRWNW']);
        
        $this->assertIsArray($items);
        $this->assertCount(2, $items);
        $this->assertInstanceOf(\Blazemedia\AmazonProductApiV2\Models\AmazonItem::class, $items[0]);
    }

    /**
     * Test configuration loading from file
     */
    public function testConstructorWithConfigFile(): void
    {
        // Create a temporary config file
        $configData = [
            'accessKey' => 'file-access-key',
            'secretKey' => 'file-secret-key',
            'partnerTag' => 'file-partner-tag',
            'marketplace' => 'www.amazon.com',
            'timeout' => 45
        ];
        
        $configFile = tempnam(sys_get_temp_dir(), 'amazon_config_');
        file_put_contents($configFile, json_encode($configData));
        
        try {
            $client = new AmazonProductApiClient($configFile);
            $this->assertInstanceOf(AmazonProductApiClient::class, $client);
            $this->assertEquals(45, $client->getTimeout());
        } finally {
            unlink($configFile);
        }
    }

    /**
     * Test configuration loading from non-existent file
     */
    public function testConstructorWithNonExistentConfigFile(): void
    {
        $this->expectException(InvalidParameterException::class);
        $this->expectExceptionMessage('Configuration file not found');
        
        new AmazonProductApiClient('/path/to/non/existent/config.json');
    }

    /**
     * Test configuration loading from invalid JSON file
     */
    public function testConstructorWithInvalidJsonConfigFile(): void
    {
        $configFile = tempnam(sys_get_temp_dir(), 'amazon_config_');
        file_put_contents($configFile, 'invalid json content');
        
        try {
            $this->expectException(InvalidParameterException::class);
            $this->expectExceptionMessage('Invalid JSON in configuration file');
            
            new AmazonProductApiClient($configFile);
        } finally {
            unlink($configFile);
        }
    }
} 