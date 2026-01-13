# Creators API PHP SDK Example

## Prerequisites

### PHP Version Support
- **Supported**: To run the SDK you need PHP version 8.1 or higher.

## Setup Instructions

### 1. Install and Configure PHP

For PHP installation, you can download it from the official website: https://www.php.net/downloads

```bash
# Check PHP version
php --version
```

### 2. Install Dependencies
```bash
cd {path_to_dir}/creatorsapi-php-sdk
composer install
```

### 3. Run Sample Code
Before running the samples, you'll need to configure your API credentials in the sample files by replacing the following placeholders:

- `<YOUR CREDENTIAL ID>` - Your API credential ID
- `<YOUR CREDENTIAL SECRET>` - Your API credential secret  
- `<YOUR CREDENTIAL VERSION>` - Your credential version (e.g., "2.1" for NA, "2.2" for EU, "2.3" for FE region)
- `<YOUR MARKETPLACE>` - Your marketplace (e.g., "www.amazon.com" for US marketplace)

**List all available feeds:**
```bash
php SampleListFeeds.php
```

**Get a specific feed:**
```bash
php SampleGetFeed.php
```
