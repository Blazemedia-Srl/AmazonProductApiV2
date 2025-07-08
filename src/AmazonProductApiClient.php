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
     * Default resources to retrieve
     */
    private const DEFAULT_RESOURCES = [
        'ItemInfo.Title',
        'ItemInfo.ByLineInfo',
        'ItemInfo.ProductInfo',
        'ItemInfo.TechnicalInfo',
        'ItemInfo.Features',
        'ItemInfo.ContentInfo',
        'ItemInfo.Classifications',
        'Images.Primary.Small',
        'Images.Primary.Medium',
        'Images.Primary.Large',
        'Images.Variants.Small',
        'Images.Variants.Medium',
        'Images.Variants.Large',
        'OffersV2.Listings.Price',
        'OffersV2.Listings.ProgramEligibility',
        'OffersV2.Listings.SavingBasis',
        'OffersV2.Listings.DeliveryInfo',
        'OffersV2.Listings.MerchantInfo',
        'OffersV2.Listings.Availability',
        'OffersV2.Listings.Condition',
        'OffersV2.Listings.LoyaltyPoints',
        'OffersV2.Listings.IsBuyBoxWinner',
        'OffersV2.Listings.ViolatesMAP'
    ];

    /**
     * Constructor
     * 
     * @param array $config Configuration array
     * @throws InvalidParameterException
     */
    public function __construct(array $config)
    {
        $this->validateConfig($config);
        
        $this->accessKey = $config['access_key'];
        $this->secretKey = $config['secret_key'];
        $this->partnerTag = $config['partner_tag'];
        $this->marketplace = $config['marketplace'];
        
        $marketplaceConfig = self::MARKETPLACE_ENDPOINTS[$this->marketplace];
        $this->endpoint = $marketplaceConfig['endpoint'];
        $this->region = $config['region'] ?? $marketplaceConfig['region'];
        
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
            CURLOPT_TIMEOUT => 30,
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
        
        curl_close($curl);

        if ($response === false) {
            throw new AmazonApiException("cURL Error: {$error}");
        }

        $decodedResponse = json_decode($response, true, 512, JSON_THROW_ON_ERROR);

        if ($httpCode !== 200) {
            $this->handleApiError($httpCode, $decodedResponse);
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
        
        $authHeader = $this->awsSignature->generateAuthorizationHeader(
            'POST',
            '/paapi5/getitems',
            $payload,
            $timestamp,
            [
                'Content-Type' => self::CONTENT_TYPE,
                'Content-Encoding' => self::CONTENT_ENCODING,
                'X-Amz-Target' => self::AMZ_TARGET,
                'X-Amz-Content-Sha256' => $contentSha256,
                'X-Amz-Date' => $timestamp,
            ]
        );

        return [
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
        if (!isset($response['ItemsResult']['Items'])) {
            throw new AmazonApiException('Invalid response format: missing ItemsResult.Items');
        }

        $items = [];
        foreach ($response['ItemsResult']['Items'] as $itemData) {
            $items[] = new ProductItem($itemData);
        }

        return $items;
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
        $required = ['access_key', 'secret_key', 'partner_tag', 'marketplace'];
        
        foreach ($required as $key) {
            if (empty($config[$key])) {
                throw new InvalidParameterException("Missing required configuration: {$key}");
            }
        }

        if (!isset(self::MARKETPLACE_ENDPOINTS[$config['marketplace']])) {
            throw new InvalidParameterException("Unsupported marketplace: {$config['marketplace']}");
        }
    }

    /**
     * Get item as AmazonItem (compatible format)
     * 
     * @param string $asin Product ASIN
     * @param array $resources Optional resources to retrieve
     * @param int $offerCount Number of offers to retrieve (default: 1)
     * @param string $partnerTag Partner tag for tracking
     * @param string $trackingPlaceholder Tracking placeholder
     * @return AmazonItem
     * @throws AmazonApiException
     */
    public function getAmazonItem(string $asin, array $resources = [], int $offerCount = 1, string $partnerTag = 'blazemedia-21', string $trackingPlaceholder = 'booBLZTRKood'): AmazonItem
    {
        $productItem = $this->getItem($asin, $resources, $offerCount);
        return new AmazonItem($productItem, $partnerTag, $trackingPlaceholder);
    }

    /**
     * Get multiple items as AmazonItem array (compatible format)
     * 
     * @param array $asins Array of ASINs
     * @param array $resources Optional resources to retrieve
     * @param int $offerCount Number of offers to retrieve (default: 1)
     * @param string $partnerTag Partner tag for tracking
     * @param string $trackingPlaceholder Tracking placeholder
     * @return AmazonItem[]
     * @throws AmazonApiException
     */
    public function getAmazonItems(array $asins, array $resources = [], int $offerCount = 1, string $partnerTag = 'blazemedia-21', string $trackingPlaceholder = 'booBLZTRKood'): array
    {
        $productItems = $this->getItems($asins, $resources, $offerCount);
        
        $amazonItems = [];
        foreach ($productItems as $productItem) {
            $amazonItems[] = new AmazonItem($productItem, $partnerTag, $trackingPlaceholder);
        }
        
        return $amazonItems;
    }
}
