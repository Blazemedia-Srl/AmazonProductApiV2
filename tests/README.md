# Amazon Product API V2 - Test Suite

This directory contains comprehensive tests for the Amazon Product API V2 library.

## Test Structure

### Test Files

- **`AmazonProductApiClientTest.php`** - Tests for the main API client class
  - Constructor validation
  - Configuration handling
  - Parameter validation
  - Error handling
  - Timeout settings

- **`ProductItemTest.php`** - Tests for the ProductItem model
  - Data parsing
  - Getter methods
  - Image handling
  - Price calculations
  - Availability checks

- **`PriceTest.php`** - Tests for the Price model
  - Amount and currency handling
  - Price formatting for different currencies
  - Display value generation
  - Availability checks

- **`ExceptionsTest.php`** - Tests for exception classes
  - Exception inheritance
  - Message and code handling
  - Constructor validation

## Running Tests

### Using PHPUnit (Recommended)

1. Install dependencies:
   ```bash
   composer install
   ```

2. Run all tests:
   ```bash
   ./vendor/bin/phpunit
   ```

3. Run specific test file:
   ```bash
   ./vendor/bin/phpunit tests/ProductItemTest.php
   ```

4. Run with coverage:
   ```bash
   ./vendor/bin/phpunit --coverage-html coverage/
   ```

### Using Simple Test Runner

If PHPUnit is not available, you can use the simple test runner:

```bash
php run-tests.php
```

## Test Configuration

The tests are configured in `phpunit.xml` with the following settings:

- **Bootstrap**: Uses Composer's autoloader
- **Test Suite**: Includes all files in the `tests/` directory
- **Coverage**: Includes all files in the `src/` directory
- **Environment**: Sets `APP_ENV=testing`

## Test Categories

### Unit Tests
- **Constructor Tests**: Validate object instantiation with various parameters
- **Getter Tests**: Test all getter methods with valid and invalid data
- **Validation Tests**: Ensure proper parameter validation and error handling
- **Formatting Tests**: Test data formatting and display methods

### Integration Tests
- **Configuration Tests**: Test configuration loading and validation
- **API Response Tests**: Test parsing of API responses (marked as skipped for HTTP mocking)
- **Error Handling Tests**: Test exception throwing and handling

### Edge Cases
- **Empty Data Tests**: Test behavior with empty or null data
- **Missing Data Tests**: Test behavior when required data is missing
- **Invalid Data Tests**: Test behavior with invalid or malformed data

## Test Data

The tests use realistic sample data that mimics Amazon API responses:

```php
$sampleData = [
    'ASIN' => 'B08N5WRWNW',
    'ItemInfo' => [
        'Title' => ['DisplayValue' => 'Test Product Title'],
        'ByLineInfo' => [
            'Brand' => ['DisplayValue' => 'Test Brand']
        ]
    ],
    'OffersV2' => [
        'Listings' => [
            [
                'Price' => [
                    'Money' => ['Amount' => 29.99, 'Currency' => 'USD']
                ]
            ]
        ]
    ]
];
```

## Skipped Tests

Some tests are marked as skipped because they require HTTP request mocking:

- `testGetItemWithValidAsin()`
- `testGetItemsWithValidAsins()`
- `testGetItemWithCustomResources()`
- `testGetItemWithCustomOfferCount()`
- `testGetAmazonItem()`
- `testGetAmazonItems()`

These tests would require a mock HTTP client to simulate API responses without making actual network requests.

## Adding New Tests

When adding new tests:

1. Follow the naming convention: `testMethodNameWithDescription()`
2. Use descriptive test names that explain what is being tested
3. Test both valid and invalid scenarios
4. Include edge cases and error conditions
5. Add appropriate assertions for all expected outcomes

## Test Coverage

The test suite aims to cover:

- ✅ Constructor validation
- ✅ Configuration handling
- ✅ Parameter validation
- ✅ Error handling
- ✅ Data parsing
- ✅ Model methods
- ✅ Exception handling
- ✅ Price formatting
- ✅ Image handling
- ✅ Availability checks

## Continuous Integration

The tests are designed to run in CI/CD environments:

- No external dependencies (except PHPUnit)
- No network requests (HTTP tests are skipped)
- Fast execution
- Clear pass/fail output
- Coverage reporting support 