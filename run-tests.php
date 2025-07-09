<?php

/**
 * Simple test runner for Amazon Product API V2
 * 
 * This script can be used to run tests without PHPUnit if needed
 */

require_once __DIR__ . '/vendor/autoload.php';

// Simple test runner
class SimpleTestRunner
{
    private array $results = [];
    
    public function runTests(): void
    {
        echo "Running Amazon Product API V2 Tests...\n";
        echo "=====================================\n\n";
        
        $this->runExceptionTests();
        $this->runPriceTests();
        $this->runProductItemTests();
        $this->runClientTests();
        
        $this->printResults();
    }
    
    private function runExceptionTests(): void
    {
        echo "Testing Exceptions...\n";
        
        try {
            $exception = new \Blazemedia\AmazonProductApiV2\Exceptions\AmazonApiException('Test message');
            $this->assert($exception instanceof \Exception, 'AmazonApiException should inherit from Exception');
            $this->assert($exception->getMessage() === 'Test message', 'Exception message should match');
            echo "✓ AmazonApiException tests passed\n";
        } catch (Exception $e) {
            echo "✗ AmazonApiException tests failed: " . $e->getMessage() . "\n";
        }
        
        try {
            $exception = new \Blazemedia\AmazonProductApiV2\Exceptions\InvalidParameterException('Invalid param');
            $this->assert($exception instanceof \Exception, 'InvalidParameterException should inherit from Exception');
            echo "✓ InvalidParameterException tests passed\n";
        } catch (Exception $e) {
            echo "✗ InvalidParameterException tests failed: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
    
    private function runPriceTests(): void
    {
        echo "Testing Price Model...\n";
        
        try {
            $data = ['Amount' => 29.99, 'Currency' => 'USD'];
            $price = new \Blazemedia\AmazonProductApiV2\Models\Price($data);
            
            $this->assert($price->getAmount() === 29.99, 'Price amount should match');
            $this->assert($price->getCurrency() === 'USD', 'Price currency should match');
            $this->assert($price->formatPrice() === '$29.99', 'Price formatting should work');
            $this->assert($price->isAvailable() === true, 'Price should be available');
            
            echo "✓ Price model tests passed\n";
        } catch (Exception $e) {
            echo "✗ Price model tests failed: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
    
    private function runProductItemTests(): void
    {
        echo "Testing ProductItem Model...\n";
        
        try {
            $data = [
                'ASIN' => 'B08N5WRWNW',
                'ItemInfo' => [
                    'Title' => ['DisplayValue' => 'Test Product'],
                    'ByLineInfo' => [
                        'Brand' => ['DisplayValue' => 'Test Brand']
                    ]
                ]
            ];
            
            $item = new \Blazemedia\AmazonProductApiV2\Models\ProductItem($data);
            
            $this->assert($item->getAsin() === 'B08N5WRWNW', 'ASIN should match');
            $this->assert($item->getTitle() === 'Test Product', 'Title should match');
            $this->assert($item->getBrand() === 'Test Brand', 'Brand should match');
            
            echo "✓ ProductItem model tests passed\n";
        } catch (Exception $e) {
            echo "✗ ProductItem model tests failed: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
    
    private function runClientTests(): void
    {
        echo "Testing AmazonProductApiClient...\n";
        
        try {
            $config = [
                'accessKey' => 'test-key',
                'secretKey' => 'test-secret',
                'partnerTag' => 'test-tag',
                'marketplace' => 'www.amazon.com'
            ];
            
            $client = new \Blazemedia\AmazonProductApiV2\AmazonProductApiClient($config);
            
            $this->assert($client instanceof \Blazemedia\AmazonProductApiV2\AmazonProductApiClient, 'Client should be instantiated');
            $this->assert($client->getTimeout() === 30, 'Default timeout should be 30');
            
            echo "✓ Client constructor tests passed\n";
        } catch (Exception $e) {
            echo "✗ Client constructor tests failed: " . $e->getMessage() . "\n";
        }
        
        // Test invalid configuration
        try {
            $invalidConfig = ['accessKey' => 'test-key']; // Missing required fields
            new \Blazemedia\AmazonProductApiV2\AmazonProductApiClient($invalidConfig);
            echo "✗ Client should throw exception for invalid config\n";
        } catch (\Blazemedia\AmazonProductApiV2\Exceptions\InvalidParameterException $e) {
            echo "✓ Client properly validates configuration\n";
        } catch (Exception $e) {
            echo "✗ Unexpected exception: " . $e->getMessage() . "\n";
        }
        
        echo "\n";
    }
    
    private function assert(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new Exception($message);
        }
    }
    
    private function printResults(): void
    {
        echo "Test Summary:\n";
        echo "=============\n";
        echo "Total tests run: " . count($this->results) . "\n";
        echo "Passed: " . count(array_filter($this->results, fn($r) => $r === true)) . "\n";
        echo "Failed: " . count(array_filter($this->results, fn($r) => $r === false)) . "\n";
    }
}

// Run tests if this file is executed directly
if (php_sapi_name() === 'cli') {
    $runner = new SimpleTestRunner();
    $runner->runTests();
} 