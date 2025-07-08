<?php

declare(strict_types=1);

namespace Blazemedia\AmazonProductApiV2;

use Blazemedia\AmazonProductApiV2\Auth\AwsSignature;
use Blazemedia\AmazonProductApiV2\Models\ProductItem;
use Blazemedia\AmazonProductApiV2\Models\AmazonItem;
use Blazemedia\AmazonProductApiV2\Exceptions\AmazonApiException;
use Blazemedia\AmazonProductApiV2\Exceptions\AuthenticationException;
use Blazemedia\AmazonProductApiV2\Exceptions\InvalidParameterException;

/**
 * Amazon Product Advertising API v5 Client
 * 
 * @package Blazemedia\AmazonProductApiV2
 * @author Giuseppe
 */
class AmazonProductApiClient
{
    private const API_VERSION = '5.0';
    private const CONTENT_TYPE = 'application/json; charset=UTF-8';
    private const CONTENT_ENCODING = 'amz-1.0';
    private const AMZ_TARGET = 'com.amazon.paapi5.v1.ProductAdvertisingAPIv1.GetItems';

    private string $accessKey;
    private string $secretKey;
    private string $partnerTag;
    private string $marketplace;
    private string $region;
    private string $endpoint;
    private string $trackingPlaceholder;
    private int $timeout;
    private AwsSignature $awsSignature;

    /**
     * Marketplace endpoints configuration
     */
    private const MARKETPLACE_ENDPOINTS = [
        'www.amazon.com' => ['endpoint' => 'webservices.amazon.com', 'region' => 'us-east-1'],
        'www.amazon.ca' => ['endpoint' => 'webservices.amazon.ca', 'region' => 'us-east-1'],
        'www.amazon.com.mx' => ['endpoint' => 'webservices.amazon.com.mx', 'region' => 'us-east-1'],
        'www.amazon.co.uk' => ['endpoint' => 'webservices.amazon.co.uk', 'region' => 'eu-west-1'],
        'www.amazon.de' => ['endpoint' => 'webservices.amazon.de', 'region' => 'eu-west-1'],
        'www.amazon.fr' => ['endpoint' => 'webservices.amazon.fr', 'region' => 'eu-west-1'],
        'www.amazon.it' => ['endpoint' => 'webservices.amazon.it', 'region' => 'eu-west-1'],
        'www.amazon.es' => ['endpoint' => 'webservices.amazon.es', 'region' => 'eu-west-1'],
        'www.amazon.co.jp' => ['endpoint' => 'webservices.amazon.co.jp', 'region' => 'us-west-2'],
        'www.amazon.in' => ['endpoint' => 'webservices.amazon.in', 'region' => 'eu-west-1'],
        'www.amazon.com.br' => ['endpoint' => 'webservices.amazon.com.br', 'region' => 'us-east-1'],
        'www.amazon.com.au' => ['endpoint' => 'webservices.amazon.com.au', 'region' => 'us-west-2'],
        'www.amazon.sg' => ['endpoint' => 'webservices.amazon.sg', 'region' => 'us-west-2'],
    ];

    /**
     * Default resources to retrieve (valid PAAPI5 resources)
     */
    private const DEFAULT_RESOURCES = [
        "ItemInfo.Title",
        "ItemInfo.ByLineInfo",
        "ItemInfo.ProductInfo",
        "ItemInfo.TechnicalInfo",
        "ItemInfo.Features",
        "Images.Primary.Small",
        "Images.Primary.Medium",
        "Images.Primary.Large",
        "Images.Variants.Small",
        "Images.Variants.Medium",
        "Images.Variants.Large",
        "OffersV2.Listings.Price",
        "OffersV2.Listings.DealDetails",
        "OffersV2.Listings.IsBuyBoxWinner",
        "OffersV2.Listings.MerchantInfo",
        "OffersV2.Listings.Availability",
        "OffersV2.Listings.Condition",
        "OffersV2.Listings.LoyaltyPoints"
    ];


    /**
     * Constructor
     * 
     * Configuration array can include:
     * - accessKey (or access_key): AWS Access Key ID
     * - secretKey (or secret_key): AWS Secret Access Key  
     * - partnerTag (or partner_tag): Amazon Associate Partner Tag
     * - marketplace: Amazon marketplace (e.g., 'www.amazon.com')
     * - host: Amazon API host (e.g., 'webservices.amazon.com')
     * - region: AWS region (optional, auto-detected from marketplace)
     * - timeout: Request timeout in seconds (default: 30, max: 300)
     * - trackingPlaceholder (or tracking_placeholder): Tracking placeholder (optional)
     * 
     * @param array|string $config Configuration array or path to JSON config file
     * @throws InvalidParameterException
     */
    public function __construct($config)
    {
        if (is_string($config)) {
            $config = $this->loadConfigFromFile($config);
        }
        
        $this->validateConfig($config);
        
        // Support both old format (access_key) and new format (accessKey)
        $this->accessKey = $config['accessKey'] ?? $config['access_key'];
        $this->secretKey = $config['secretKey'] ?? $config['secret_key'];
        $this->partnerTag = $config['partnerTag'] ?? $config['partner_tag'];
        $this->trackingPlaceholder = $config['trackingPlaceholder'] ?? $config['tracking_placeholder'] ?? 'booBLZTRKood';
        $this->timeout = $config['timeout'] ?? 30; // Default 30 seconds timeout
        
        // Handle marketplace/host configuration
        if (isset($config['host'])) {
            // New JSON format uses 'host' directly
            $this->endpoint = $config['host'];
            $this->marketplace = $this->getMarketplaceFromHost($config['host']);
        } else {
            // Old format uses 'marketplace'
            $this->marketplace = $config['marketplace'];
            $marketplaceConfig = self::MARKETPLACE_ENDPOINTS[$this->marketplace];
            $this->endpoint = $marketplaceConfig['endpoint'];
        }
        
        // Set region (use provided or default from marketplace)
        if (isset($config['region'])) {
            $this->region = $config['region'];
        } else {
            $marketplaceConfig = self::MARKETPLACE_ENDPOINTS[$this->marketplace] ?? null;
            $this->region = $marketplaceConfig['region'] ?? 'us-east-1';
        }
        
        $this->awsSignature = new AwsSignature($this->accessKey, $this->secretKey, $this->region);
    }

    /**
     * Get item details by ASIN
     * 
     * @param string $asin Product ASIN
     * @param array $resources Optional resources to retrieve
     * @param int $offerCount Number of offers to retrieve (default: 1)
     * @return ProductItem
     * @throws AmazonApiException
     */
    public function getItem(string $asin, array $resources = [], int $offerCount = 1): ProductItem
    {
        return $this->getItems([$asin], $resources, $offerCount)[0];
    }

    /**
     * Get multiple items by ASINs
     * 
     * @param array $asins Array of ASINs
     * @param array $resources Optional resources to retrieve
     * @param int $offerCount Number of offers to retrieve (default: 1)
     * @return ProductItem[]
     * @throws AmazonApiException
     */
    public function getItems(array $asins, array $resources = [], int $offerCount = 1): array
    {
        if (empty($asins)) {
            throw new InvalidParameterException('ASINs array cannot be empty');
        }

        if (count($asins) > 10) {
            throw new InvalidParameterException('Maximum 10 ASINs allowed per request');
        }

        $resources = empty($resources) ? self::DEFAULT_RESOURCES : $resources;

        $payload = [
            'ItemIds' => $asins,
            'ItemIdType' => 'ASIN',
            'Marketplace' => $this->marketplace,
            'PartnerTag' => $this->partnerTag,
            'PartnerType' => 'Associates',
            'OfferCount' => $offerCount,
            'Resources' => $resources
        ];

        $response = $this->makeRequest($payload);
        
        return $this->parseItemsResponse($response);
    }

    /**
     * Make HTTP request to Amazon API
     * 
     * @param array $payload Request payload
     * @return array Response data
     * @throws AmazonApiException
     */
    private function makeRequest(array $payload): array
    {
        $url = "https://{$this->endpoint}/paapi5/getitems";
        $jsonPayload = json_encode($payload, JSON_THROW_ON_ERROR);
        
        $headers = $this->buildHeaders($jsonPayload);
        
        $curl = curl_init();
        
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => min($this->timeout, 10), // Connection timeout (max 10 seconds)
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $jsonPayload,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        $errorCode = curl_errno($curl);
        
        curl_close($curl);

        if ($response === false) {
            // Check for specific timeout errors
            if ($errorCode === CURLE_OPERATION_TIMEDOUT || $errorCode === CURLE_OPERATION_TIMEOUTED) {
                throw new AmazonApiException("Request timeout after {$this->timeout} seconds. Try increasing the timeout or check your network connection.");
            } elseif ($errorCode === CURLE_COULDNT_CONNECT) {
                throw new AmazonApiException("Could not connect to Amazon API. Please check your network connection.");
            } else {
                throw new AmazonApiException("cURL Error (Code: {$errorCode}): {$error}");
            }
        }

        $decodedResponse = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

        // Handle HTTP errors (4xx, 5xx status codes)
        if ($httpCode !== 200) {
            $this->handleApiError($httpCode, $decodedResponse);
        }
        
        // Handle API errors that come with HTTP 200 status but contain error information
        if (isset($decodedResponse['Errors']) && !empty($decodedResponse['Errors'])) {
            $error = $decodedResponse['Errors'][0];
            $errorMessage = $error['Message'] ?? 'Unknown API error';
            $errorCode = $error['Code'] ?? 'UNKNOWN_ERROR';
            
            // Map common error codes to appropriate exception types
            switch ($errorCode) {
                case 'InvalidParameterValue':
                case 'MissingParameter':
                case 'InvalidParameter':
                    throw new InvalidParameterException("API Error ({$errorCode}): {$errorMessage}");
                case 'RequestThrottled':
                case 'TooManyRequests':
                    throw new AmazonApiException("Too Many Requests: {$errorMessage}", 429);
                case 'UnauthorizedOperation':
                case 'SignatureDoesNotMatch':
                case 'IncompleteSignature':
                case 'InvalidAccessKeyId':
                    throw new AuthenticationException("Authentication failed: {$errorMessage}");
                default:
                    throw new AmazonApiException("API Error ({$errorCode}): {$errorMessage}");
            }
        }

        return $decodedResponse;
    }

    /**
     * Build HTTP headers for the request
     * 
     * @param string $payload JSON payload
     * @return array Headers array
     */
    private function buildHeaders(string $payload): array
    {
        $timestamp = gmdate('Ymd\THis\Z');
        $contentSha256 = hash('sha256', $payload);
        
        $headersForSigning = [
            'Host' => $this->endpoint,
            'Content-Type' => self::CONTENT_TYPE,
            'Content-Encoding' => self::CONTENT_ENCODING,
            'X-Amz-Target' => self::AMZ_TARGET,
            'X-Amz-Content-Sha256' => $contentSha256,
            'X-Amz-Date' => $timestamp,
        ];
        
        $authHeader = $this->awsSignature->generateAuthorizationHeader(
            'POST',
            '/paapi5/getitems',
            $payload,
            $timestamp,
            $headersForSigning
        );

        return [
            'Host: ' . $this->endpoint,
            'Content-Type: ' . self::CONTENT_TYPE,
            'Content-Encoding: ' . self::CONTENT_ENCODING,
            'X-Amz-Target: ' . self::AMZ_TARGET,
            'X-Amz-Content-Sha256: ' . $contentSha256,
            'X-Amz-Date: ' . $timestamp,
            'Authorization: ' . $authHeader,
        ];
    }

    /**
     * Parse API response into ProductItem objects
     * 
     * @param array $response API response
     * @return ProductItem[]
     * @throws AmazonApiException
     */
    private function parseItemsResponse(array $response): array
    {
        // Handle different possible response structures
        $items = null;
        
        if (isset($response['ItemsResult']['Items'])) {
            $items = $response['ItemsResult']['Items'];
        } elseif (isset($response['GetItemsResponse']['Items'])) {
            $items = $response['GetItemsResponse']['Items'];
        } elseif (isset($response['Items'])) {
            $items = $response['Items'];
        }
        
        if ($items === null) {
            // If we got here but have errors in the response, it means there was an API error
            // that should have been caught earlier but wasn't properly handled
            if (isset($response['Errors'])) {
                $error = $response['Errors'][0];
                $errorMessage = $error['Message'] ?? 'Unknown API error';
                $errorCode = $error['Code'] ?? 'UNKNOWN_ERROR';
                throw new AmazonApiException("API Error ({$errorCode}): {$errorMessage}");
            }
            
            // Debug the actual response structure
            throw new AmazonApiException('Invalid response format. Response structure: ' . json_encode(array_keys($response)));
        }

        $result = [];
        foreach ($items as $itemData) {
            $result[] = new ProductItem($itemData);
        }

        return $result;
    }

    /**
     * Handle API errors
     * 
     * @param int $httpCode HTTP status code
     * @param array $response Response data
     * @throws AmazonApiException
     */
    private function handleApiError(int $httpCode, array $response): void
    {
        $errorMessage = $response['Errors'][0]['Message'] ?? 'Unknown API error';
        $errorCode = $response['Errors'][0]['Code'] ?? 'UNKNOWN_ERROR';

        switch ($httpCode) {
            case 400:
                throw new InvalidParameterException("Bad Request: {$errorMessage}", $httpCode);
            case 401:
            case 403:
                throw new AuthenticationException("Authentication failed: {$errorMessage}", $httpCode);
            case 429:
                throw new AmazonApiException("Too Many Requests: {$errorMessage}", $httpCode);
            case 500:
                throw new AmazonApiException("Internal Server Error: {$errorMessage}", $httpCode);
            default:
                throw new AmazonApiException("API Error ({$errorCode}): {$errorMessage}", $httpCode);
        }
    }

    /**
     * Validate configuration parameters
     * 
     * @param array $config Configuration array
     * @throws InvalidParameterException
     */
    private function validateConfig(array $config): void
    {
        // Support both new format (accessKey) and old format (access_key)
        $accessKey = $config['accessKey'] ?? $config['access_key'] ?? null;
        $secretKey = $config['secretKey'] ?? $config['secret_key'] ?? null;
        $partnerTag = $config['partnerTag'] ?? $config['partner_tag'] ?? null;
        
        // Check for required fields
        if (empty($accessKey)) {
            throw new InvalidParameterException("Missing required configuration: accessKey (or access_key)");
        }
        
        if (empty($secretKey)) {
            throw new InvalidParameterException("Missing required configuration: secretKey (or secret_key)");
        }
        
        if (empty($partnerTag)) {
            throw new InvalidParameterException("Missing required configuration: partnerTag (or partner_tag)");
        }

        // Check for marketplace or host
        $marketplace = $config['marketplace'] ?? null;
        $host = $config['host'] ?? null;
        
        if (empty($marketplace) && empty($host)) {
            throw new InvalidParameterException("Missing required configuration: marketplace or host");
        }

        // If marketplace is provided, validate it
        if (!empty($marketplace) && !isset(self::MARKETPLACE_ENDPOINTS[$marketplace])) {
            throw new InvalidParameterException("Unsupported marketplace: {$marketplace}");
        }
    }

    /**
     * Get item as AmazonItem (compatible format)
     * 
     * @param string $asin Product ASIN
     * @param array $resources Optional resources to retrieve
     * @param int $offerCount Number of offers to retrieve (default: 1)
     * @param string|null $partnerTag Partner tag for tracking (defaults to config value)
     * @param string|null $trackingPlaceholder Tracking placeholder (defaults to config value)
     * @return AmazonItem
     * @throws AmazonApiException
     */
    public function getAmazonItem(string $asin, array $resources = [], int $offerCount = 1, string $partnerTag = null, string $trackingPlaceholder = null): AmazonItem
    {
        $productItem = $this->getItem($asin, $resources, $offerCount);
        return new AmazonItem(
            $productItem, 
            $partnerTag ?? $this->partnerTag, 
            $trackingPlaceholder ?? $this->trackingPlaceholder
        );
    }

    /**
     * Get multiple items as AmazonItem array (compatible format)
     * 
     * @param array $asins Array of ASINs
     * @param array $resources Optional resources to retrieve
     * @param int $offerCount Number of offers to retrieve (default: 1)
     * @param string|null $partnerTag Partner tag for tracking (defaults to config value)
     * @param string|null $trackingPlaceholder Tracking placeholder (defaults to config value)
     * @return AmazonItem[]
     * @throws AmazonApiException
     */
    public function getAmazonItems(array $asins, array $resources = [], int $offerCount = 1, string $partnerTag = null, string $trackingPlaceholder = null): array
    {
        $productItems = $this->getItems($asins, $resources, $offerCount);
        
        $amazonItems = [];
        foreach ($productItems as $productItem) {
            $amazonItems[] = new AmazonItem(
                $productItem, 
                $partnerTag ?? $this->partnerTag, 
                $trackingPlaceholder ?? $this->trackingPlaceholder
            );
        }
        
        return $amazonItems;
    }

    /**
     * Load configuration from JSON file
     * 
     * @param string $configPath Path to the JSON configuration file
     * @return array Configuration array
     * @throws InvalidParameterException
     */
    private function loadConfigFromFile(string $configPath): array
    {
        if (!file_exists($configPath)) {
            throw new InvalidParameterException("Configuration file not found: {$configPath}");
        }

        if (!is_readable($configPath)) {
            throw new InvalidParameterException("Configuration file is not readable: {$configPath}");
        }

        $jsonContent = file_get_contents($configPath);
        if ($jsonContent === false) {
            throw new InvalidParameterException("Failed to read configuration file: {$configPath}");
        }

        try {
            $config = json_decode($jsonContent, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new InvalidParameterException("Invalid JSON in configuration file: {$e->getMessage()}");
        }

        if (!is_array($config)) {
            throw new InvalidParameterException("Configuration file must contain a JSON object");
        }

        return $config;
    }

    /**
     * Get marketplace from host
     * 
     * @param string $host The host (e.g., webservices.amazon.it)
     * @return string The marketplace (e.g., www.amazon.it)
     */
    private function getMarketplaceFromHost(string $host): string
    {
        // Convert host to marketplace format
        $hostToMarketplace = [
            'webservices.amazon.com' => 'www.amazon.com',
            'webservices.amazon.ca' => 'www.amazon.ca',
            'webservices.amazon.com.mx' => 'www.amazon.com.mx',
            'webservices.amazon.co.uk' => 'www.amazon.co.uk',
            'webservices.amazon.de' => 'www.amazon.de',
            'webservices.amazon.fr' => 'www.amazon.fr',
            'webservices.amazon.it' => 'www.amazon.it',
            'webservices.amazon.es' => 'www.amazon.es',
            'webservices.amazon.co.jp' => 'www.amazon.co.jp',
            'webservices.amazon.in' => 'www.amazon.in',
            'webservices.amazon.com.br' => 'www.amazon.com.br',
            'webservices.amazon.com.au' => 'www.amazon.com.au',
            'webservices.amazon.sg' => 'www.amazon.sg',
        ];

        return $hostToMarketplace[$host] ?? $host;
    }

    /**
     * Set request timeout in seconds
     * 
     * @param int $timeout Timeout in seconds (minimum 1, maximum 300)
     * @throws InvalidParameterException
     */
    public function setTimeout(int $timeout): void
    {
        if ($timeout < 1 || $timeout > 300) {
            throw new InvalidParameterException('Timeout must be between 1 and 300 seconds');
        }
        
        $this->timeout = $timeout;
    }

    /**
     * Get current timeout setting
     * 
     * @return int Current timeout in seconds
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }
}
