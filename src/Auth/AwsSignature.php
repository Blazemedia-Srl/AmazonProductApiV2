<?php

declare(strict_types=1);

namespace Blazemedia\AmazonProductApiV2\Auth;

/**
 * AWS Signature Version 4 Implementation
 * 
 * @package Blazemedia\AmazonProductApiV2\Auth
 */
class AwsSignature
{
    private string $accessKey;
    private string $secretKey;
    private string $region;
    private string $service = 'ProductAdvertisingAPI';
    private string $algorithm = 'AWS4-HMAC-SHA256';

    public function __construct(string $accessKey, string $secretKey, string $region)
    {
        $this->accessKey = $accessKey;
        $this->secretKey = $secretKey;
        $this->region = $region;
    }

    /**
     * Generate AWS authorization header
     * 
     * @param string $method HTTP method
     * @param string $uri Request URI
     * @param string $payload Request payload
     * @param string $timestamp ISO 8601 timestamp
     * @param array $headers Request headers
     * @return string Authorization header value
     */
    public function generateAuthorizationHeader(
        string $method,
        string $uri,
        string $payload,
        string $timestamp,
        array $headers
    ): string {
        $date = substr($timestamp, 0, 8);
        
        // Step 1: Create canonical request
        $canonicalRequest = $this->createCanonicalRequest($method, $uri, $payload, $headers);
        
        // Step 2: Create string to sign
        $credentialScope = $date . '/' . $this->region . '/' . $this->service . '/aws4_request';
        $stringToSign = $this->algorithm . "\n" .
                       $timestamp . "\n" .
                       $credentialScope . "\n" .
                       hash('sha256', $canonicalRequest);
        
        // Step 3: Calculate signature
        $signature = $this->calculateSignature($stringToSign, $date);
        
        // Step 4: Create authorization header
        $credential = $this->accessKey . '/' . $credentialScope;
        $signedHeaders = $this->getSignedHeaders($headers);
        
        return $this->algorithm . ' ' .
               'Credential=' . $credential . ', ' .
               'SignedHeaders=' . $signedHeaders . ', ' .
               'Signature=' . $signature;
    }

    /**
     * Create canonical request
     * 
     * @param string $method HTTP method
     * @param string $uri Request URI
     * @param string $payload Request payload
     * @param array $headers Request headers
     * @return string Canonical request
     */
    private function createCanonicalRequest(string $method, string $uri, string $payload, array $headers): string
    {
        $canonicalUri = $uri;
        $canonicalQueryString = ''; // Empty for POST requests
        $canonicalHeaders = $this->getCanonicalHeaders($headers);
        $signedHeaders = $this->getSignedHeaders($headers);
        $payloadHash = hash('sha256', $payload);

        return $method . "\n" .
               $canonicalUri . "\n" .
               $canonicalQueryString . "\n" .
               $canonicalHeaders . "\n" .
               $signedHeaders . "\n" .
               $payloadHash;
    }

    /**
     * Get canonical headers
     * 
     * @param array $headers Headers array
     * @return string Canonical headers
     */
    private function getCanonicalHeaders(array $headers): string
    {
        $canonicalHeaders = [];
        
        foreach ($headers as $name => $value) {
            $canonicalHeaders[strtolower($name)] = trim($value);
        }
        
        ksort($canonicalHeaders);
        
        $result = '';
        foreach ($canonicalHeaders as $name => $value) {
            $result .= $name . ':' . $value . "\n";
        }
        
        return $result;
    }

    /**
     * Get signed headers
     * 
     * @param array $headers Headers array
     * @return string Signed headers
     */
    private function getSignedHeaders(array $headers): string
    {
        $headerNames = array_map('strtolower', array_keys($headers));
        sort($headerNames);
        
        return implode(';', $headerNames);
    }

    /**
     * Calculate AWS signature
     * 
     * @param string $stringToSign String to sign
     * @param string $date Date in YYYYMMDD format
     * @return string Signature
     */
    private function calculateSignature(string $stringToSign, string $date): string
    {
        $dateKey = hash_hmac('sha256', $date, 'AWS4' . $this->secretKey, true);
        $regionKey = hash_hmac('sha256', $this->region, $dateKey, true);
        $serviceKey = hash_hmac('sha256', $this->service, $regionKey, true);
        $signingKey = hash_hmac('sha256', 'aws4_request', $serviceKey, true);
        
        return hash_hmac('sha256', $stringToSign, $signingKey);
    }
}
