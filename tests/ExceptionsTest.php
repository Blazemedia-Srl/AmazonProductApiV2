<?php

declare(strict_types=1);

namespace Blazemedia\AmazonProductApiV2\Tests;

use PHPUnit\Framework\TestCase;
use Blazemedia\AmazonProductApiV2\Exceptions\AmazonApiException;
use Blazemedia\AmazonProductApiV2\Exceptions\AuthenticationException;
use Blazemedia\AmazonProductApiV2\Exceptions\InvalidParameterException;

/**
 * Test class for Exceptions
 * 
 * @package Blazemedia\AmazonProductApiV2\Tests
 */
class ExceptionsTest extends TestCase
{
    /**
     * Test AmazonApiException constructor
     */
    public function testAmazonApiException(): void
    {
        $message = 'Test API exception message';
        $code = 500;
        $previous = new \Exception('Previous exception');
        
        $exception = new AmazonApiException($message, $code, $previous);
        
        $this->assertInstanceOf(AmazonApiException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
        $this->assertEquals($previous, $exception->getPrevious());
    }

    /**
     * Test AmazonApiException with default parameters
     */
    public function testAmazonApiExceptionWithDefaults(): void
    {
        $exception = new AmazonApiException();
        
        $this->assertInstanceOf(AmazonApiException::class, $exception);
        $this->assertEquals('', $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    /**
     * Test AuthenticationException constructor
     */
    public function testAuthenticationException(): void
    {
        $message = 'Authentication failed';
        $code = 401;
        $previous = new \Exception('Previous exception');
        
        $exception = new AuthenticationException($message, $code, $previous);
        
        $this->assertInstanceOf(AuthenticationException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
        $this->assertEquals($previous, $exception->getPrevious());
    }

    /**
     * Test AuthenticationException with default parameters
     */
    public function testAuthenticationExceptionWithDefaults(): void
    {
        $exception = new AuthenticationException();
        
        $this->assertInstanceOf(AuthenticationException::class, $exception);
        $this->assertEquals('', $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    /**
     * Test InvalidParameterException constructor
     */
    public function testInvalidParameterException(): void
    {
        $message = 'Invalid parameter provided';
        $code = 400;
        $previous = new \Exception('Previous exception');
        
        $exception = new InvalidParameterException($message, $code, $previous);
        
        $this->assertInstanceOf(InvalidParameterException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
        $this->assertEquals($previous, $exception->getPrevious());
    }

    /**
     * Test InvalidParameterException with default parameters
     */
    public function testInvalidParameterExceptionWithDefaults(): void
    {
        $exception = new InvalidParameterException();
        
        $this->assertInstanceOf(InvalidParameterException::class, $exception);
        $this->assertEquals('', $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    /**
     * Test exception inheritance hierarchy
     */
    public function testExceptionInheritance(): void
    {
        $amazonException = new AmazonApiException('Test');
        $authException = new AuthenticationException('Test');
        $paramException = new InvalidParameterException('Test');
        
        // All should inherit from base Exception class
        $this->assertInstanceOf(\Exception::class, $amazonException);
        $this->assertInstanceOf(\Exception::class, $authException);
        $this->assertInstanceOf(\Exception::class, $paramException);
        
        // Specific exception types
        $this->assertInstanceOf(AmazonApiException::class, $amazonException);
        $this->assertInstanceOf(AuthenticationException::class, $authException);
        $this->assertInstanceOf(InvalidParameterException::class, $paramException);
    }

    /**
     * Test exception message formatting
     */
    public function testExceptionMessageFormatting(): void
    {
        $message = 'Test exception message';
        $exception = new AmazonApiException($message);
        
        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($message, (string) $exception);
    }

    /**
     * Test exception with special characters in message
     */
    public function testExceptionWithSpecialCharacters(): void
    {
        $message = 'Test exception with special chars: !@#$%^&*()';
        $exception = new InvalidParameterException($message);
        
        $this->assertEquals($message, $exception->getMessage());
    }

    /**
     * Test exception with empty message
     */
    public function testExceptionWithEmptyMessage(): void
    {
        $exception = new AuthenticationException('');
        
        $this->assertEquals('', $exception->getMessage());
    }

    /**
     * Test exception with null message
     */
    public function testExceptionWithNullMessage(): void
    {
        $exception = new AmazonApiException(null);
        
        $this->assertEquals('', $exception->getMessage());
    }

    /**
     * Test exception with zero code
     */
    public function testExceptionWithZeroCode(): void
    {
        $exception = new InvalidParameterException('Test', 0);
        
        $this->assertEquals(0, $exception->getCode());
    }

    /**
     * Test exception with negative code
     */
    public function testExceptionWithNegativeCode(): void
    {
        $exception = new AuthenticationException('Test', -1);
        
        $this->assertEquals(-1, $exception->getCode());
    }

    /**
     * Test exception with large code
     */
    public function testExceptionWithLargeCode(): void
    {
        $exception = new AmazonApiException('Test', 999999);
        
        $this->assertEquals(999999, $exception->getCode());
    }
} 