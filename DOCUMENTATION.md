# Amazon Product Advertising API v5 - Documentazione Completa

## Panoramica

Questa libreria PHP 8.1+ fornisce un'interfaccia completa per l'Amazon Product Advertising API v5, permettendo di recuperare informazioni dettagliate sui prodotti Amazon.

## Installazione

### Con Composer (Raccomandato)
```bash
composer require blazemedia/amazon-product-api-v2
```

### Senza Composer
```php
require_once 'autoload.php';
```

## Configurazione

### Credenziali Richieste

1. **AWS Access Key e Secret Key**: Ottieni dalle credenziali IAM AWS
2. **Partner Tag**: Ottieni dal programma Amazon Associates
3. **Marketplace**: Il marketplace Amazon di destinazione

### Esempio Configurazione

```php
use Blazemedia\AmazonProductApiV2\AmazonProductApiClient;

$client = new AmazonProductApiClient([
    'access_key' => 'AKIAIOSFODNN7EXAMPLE',
    'secret_key' => 'wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY',
    'partner_tag' => 'blazemedia-21',
    'marketplace' => 'www.amazon.it',
    'region' => 'eu-west-1' // Opzionale, viene impostato automaticamente
]);
```

## Utilizzo Base

### Recupero Singolo Prodotto

```php
try {
    $product = $client->getItem('B0DV9HJTTK');
    
    echo "Titolo: " . $product->getTitle() . "\n";
    echo "Prezzo: " . $product->getPrice() . "\n";
    echo "Brand: " . $product->getBrand() . "\n";
    
} catch (AmazonApiException $e) {
    echo "Errore: " . $e->getMessage();
}
```

### Recupero Multipli Prodotti

```php
$products = $client->getItems(['B0DV9HJTTK', 'B08N5WRWNW']);

foreach ($products as $product) {
    echo $product->getTitle() . " - " . $product->getPrice() . "\n";
}
```

## Classi Principali

### AmazonProductApiClient

Classe principale per le chiamate API.

**Metodi:**
- `getItem(string $asin, array $resources = [], int $offerCount = 1): ProductItem`
- `getItems(array $asins, array $resources = [], int $offerCount = 1): ProductItem[]`

### ProductItem

Rappresenta un prodotto Amazon con tutti i suoi dettagli.

**Metodi Informativi:**
- `getAsin(): ?string` - ASIN del prodotto
- `getTitle(): ?string` - Titolo del prodotto
- `getBrand(): ?string` - Brand/Marca
- `getManufacturer(): ?string` - Produttore
- `getDescription(): ?string` - Descrizione completa
- `getFeatures(): array` - Caratteristiche principali

**Metodi Prezzi:**
- `getPrice(): ?Price` - Prezzo attuale
- `getOriginalPrice(): ?Price` - Prezzo originale (prima dello sconto)
- `getDiscountAmount(): ?Price` - Importo sconto
- `getDiscountPercentage(): ?float` - Percentuale sconto

**Metodi Immagini:**
- `getImageUrl(string $size = 'Large'): ?string` - Immagine principale
- `getAllImages(string $size = 'Large'): array` - Tutte le immagini

**Metodi Disponibilità:**
- `isInStock(): bool` - Verifica disponibilità
- `getAvailability(): ?string` - Messaggio disponibilità
- `hasPrimeOffer(): bool` - Verifica eligibilità Prime
- `hasCoupons(): bool` - Verifica presenza coupon

**Metodi Informazioni Aggiuntive:**
- `getCondition(): ?string` - Condizione prodotto
- `getMerchantInfo(): ?array` - Info venditore
- `getDeliveryInfo(): ?array` - Info consegna
- `getDimensions(): ?array` - Dimensioni prodotto
- `getWeight(): ?array` - Peso prodotto
- `getDetailPageURL(): ?string` - URL pagina prodotto

**Metodi Export:**
- `toArray(): array` - Esporta tutti i dati in array
- `getRawData(): array` - Dati grezzi dall'API

### Price

Rappresenta un prezzo con formattazione e utilità.

**Metodi:**
- `getAmount(): float` - Importo numerico
- `getCurrency(): string` - Codice valuta
- `getDisplayValue(): string` - Valore formattato
- `formatPrice(): string` - Formattazione personalizzata
- `isAvailable(): bool` - Verifica se il prezzo è disponibile
- `toArray(): array` - Esporta in array

## Utilities Helper

### AmazonHelper

Classe helper con metodi di utilità.

```php
use Blazemedia\AmazonProductApiV2\Utils\AmazonHelper;

$helper = new AmazonHelper($client);
```

**Metodi Principali:**

#### getSimpleProductInfo(string $asin): array
Recupera informazioni semplificate di un prodotto.

```php
$info = $helper->getSimpleProductInfo('B0DV9HJTTK');

if ($info['success']) {
    echo $info['title'];
    echo $info['price'];
    echo $info['prime'] ? 'Prime' : 'No Prime';
}
```

#### compareProducts(array $asins): array
Confronta più prodotti ordinandoli per prezzo.

```php
$comparison = $helper->compareProducts(['B0DV9HJTTK', 'B08N5WRWNW']);

foreach ($comparison['products'] as $product) {
    echo "{$product['title']} - {$product['current_price_display']}\n";
}
```

#### findDiscountedProducts(array $asins, float $minDiscount = 10.0): array
Trova prodotti con sconto minimo specificato.

```php
$discounted = $helper->findDiscountedProducts($asins, 15.0);

foreach ($discounted['products'] as $product) {
    echo "{$product['title']} - Sconto: {$product['discount_percentage']}%\n";
}
```

#### findPrimeProducts(array $asins): array
Filtra solo i prodotti eligibili Prime.

```php
$primeProducts = $helper->findPrimeProducts($asins);
```

#### generateProductReport(string $asin): array
Genera un report completo del prodotto.

```php
$report = $helper->generateProductReport('B0DV9HJTTK');

if ($report['success']) {
    $data = $report['report'];
    echo "Titolo: " . $data['basic_info']['title'];
    echo "Prezzo: " . $data['pricing']['current_price']['display_value'];
}
```

**Metodi Statici:**

#### isValidAsin(string $asin): bool
Valida formato ASIN.

```php
if (AmazonHelper::isValidAsin('B0DV9HJTTK')) {
    echo "ASIN valido";
}
```

#### extractAsinFromUrl(string $url): ?string
Estrae ASIN da URL Amazon.

```php
$asin = AmazonHelper::extractAsinFromUrl('https://amazon.it/dp/B0DV9HJTTK/');
echo $asin; // B0DV9HJTTK
```

#### formatPrice(float $amount, string $currency): string
Formatta un prezzo.

```php
echo AmazonHelper::formatPrice(29.99, 'EUR'); // € 29,99
```

## Gestione Errori

La libreria utilizza un sistema di eccezioni strutturato:

### AmazonApiException
Eccezione base per errori API.

### AuthenticationException
Errori di autenticazione AWS.

### InvalidParameterException
Parametri non validi.

**Esempio gestione:**

```php
try {
    $product = $client->getItem('INVALID_ASIN');
} catch (InvalidParameterException $e) {
    echo "Parametro non valido: " . $e->getMessage();
} catch (AuthenticationException $e) {
    echo "Errore autenticazione: " . $e->getMessage();
} catch (AmazonApiException $e) {
    echo "Errore API: " . $e->getMessage();
}
```

## Marketplace Supportati

| Marketplace | Endpoint | Regione |
|-------------|----------|---------|
| www.amazon.com | webservices.amazon.com | us-east-1 |
| www.amazon.ca | webservices.amazon.ca | us-east-1 |
| www.amazon.com.mx | webservices.amazon.com.mx | us-east-1 |
| www.amazon.co.uk | webservices.amazon.co.uk | eu-west-1 |
| www.amazon.de | webservices.amazon.de | eu-west-1 |
| www.amazon.fr | webservices.amazon.fr | eu-west-1 |
| www.amazon.it | webservices.amazon.it | eu-west-1 |
| www.amazon.es | webservices.amazon.es | eu-west-1 |
| www.amazon.co.jp | webservices.amazon.co.jp | us-west-2 |
| www.amazon.in | webservices.amazon.in | eu-west-1 |
| www.amazon.com.br | webservices.amazon.com.br | us-east-1 |
| www.amazon.com.au | webservices.amazon.com.au | us-west-2 |
| www.amazon.sg | webservices.amazon.sg | us-west-2 |

## Resources Disponibili

La libreria supporta tutti i resources dell'API v5:

### ItemInfo
- `ItemInfo.Title` - Titolo prodotto
- `ItemInfo.ByLineInfo` - Brand, autore, produttore
- `ItemInfo.ProductInfo` - Informazioni prodotto
- `ItemInfo.TechnicalInfo` - Specifiche tecniche
- `ItemInfo.Features` - Caratteristiche principali
- `ItemInfo.ContentInfo` - Informazioni contenuto
- `ItemInfo.Classifications` - Categorie

### Images
- `Images.Primary.Small/Medium/Large` - Immagine principale
- `Images.Variants.Small/Medium/Large` - Immagini aggiuntive

### Offers
- `OffersV2.Listings.Price` - Prezzo
- `OffersV2.Listings.SavingBasis` - Prezzo originale
- `OffersV2.Listings.ProgramEligibility` - Eligibilità Prime
- `OffersV2.Listings.DeliveryInfo` - Info consegna
- `OffersV2.Listings.MerchantInfo` - Info venditore
- `OffersV2.Listings.Availability` - Disponibilità
- `OffersV2.Listings.Condition` - Condizione
- `OffersV2.Listings.IsBuyBoxWinner` - Buy Box
- `OffersV2.Listings.ViolatesMAP` - Violazione MAP
- `OffersV2.Listings.LoyaltyPoints` - Punti fedeltà

## Esempi Avanzati

### Monitoraggio Prezzi

```php
function monitorPrice($client, $asin, $targetPrice) {
    $product = $client->getItem($asin);
    $currentPrice = $product->getPrice();
    
    if ($currentPrice && $currentPrice->getAmount() <= $targetPrice) {
        echo "🎯 PREZZO TARGET RAGGIUNTO!\n";
        echo "Prodotto: " . $product->getTitle() . "\n";
        echo "Prezzo attuale: " . $currentPrice->getDisplayValue() . "\n";
        echo "Prezzo target: " . AmazonHelper::formatPrice($targetPrice, $currentPrice->getCurrency()) . "\n";
        return true;
    }
    
    return false;
}
```

### Ricerca Migliori Offerte

```php
function findBestDeals($client, $asins) {
    $deals = [];
    $products = $client->getItems($asins);
    
    foreach ($products as $product) {
        $discount = $product->getDiscountPercentage();
        
        if ($discount && $discount >= 20) {
            $deals[] = [
                'title' => $product->getTitle(),
                'discount' => $discount,
                'price' => $product->getPrice()->getDisplayValue(),
                'prime' => $product->hasPrimeOffer(),
                'url' => $product->getDetailPageURL()
            ];
        }
    }
    
    // Ordina per sconto decrescente
    usort($deals, function($a, $b) {
        return $b['discount'] <=> $a['discount'];
    });
    
    return $deals;
}
```

### Export in Formato CSV

```php
function exportToCSV($products, $filename) {
    $fp = fopen($filename, 'w');
    
    // Header
    fputcsv($fp, [
        'ASIN', 'Title', 'Brand', 'Price', 'Original Price', 
        'Discount %', 'Prime', 'In Stock', 'URL'
    ]);
    
    foreach ($products as $product) {
        $price = $product->getPrice();
        $originalPrice = $product->getOriginalPrice();
        
        fputcsv($fp, [
            $product->getAsin(),
            $product->getTitle(),
            $product->getBrand(),
            $price ? $price->getDisplayValue() : '',
            $originalPrice ? $originalPrice->getDisplayValue() : '',
            $product->getDiscountPercentage() ?: '',
            $product->hasPrimeOffer() ? 'Yes' : 'No',
            $product->isInStock() ? 'Yes' : 'No',
            $product->getDetailPageURL()
        ]);
    }
    
    fclose($fp);
}
```

## Best Practices

1. **Rate Limiting**: Rispetta i limiti di richieste Amazon (max 8600 richieste per ora)
2. **Caching**: Implementa caching per ridurre le chiamate API
3. **Error Handling**: Gestisci sempre le eccezioni
4. **ASIN Validation**: Valida sempre gli ASIN prima delle chiamate
5. **Resource Selection**: Richiedi solo i resources necessari per ottimizzare le performance

## Troubleshooting

### Errori Comuni

**"InvalidSignature"**: Verifica access key, secret key e configurazione regione/marketplace.

**"InvalidPartnerTag"**: Assicurati che il partner tag sia corretto per il marketplace.

**"TooManyRequests"**: Implementa rate limiting o riduci la frequenza delle richieste.

**"ItemNotAccessible"**: L'ASIN potrebbe non essere disponibile nel marketplace specificato.

### Debug

Abilita il debug catturando l'eccezione e analizzando il messaggio:

```php
try {
    $product = $client->getItem('B0DV9HJTTK');
} catch (AmazonApiException $e) {
    echo "Errore: " . $e->getMessage() . "\n";
    echo "Codice: " . $e->getCode() . "\n";
}
```

## Contributi

Per contribuire al progetto:

1. Fork del repository
2. Crea un branch per la feature
3. Implementa i test
4. Invia una pull request

## Licenza

MIT License - vedi file LICENSE per i dettagli.

### AmazonItem (Compatibilità)

Classe compatibile con sistemi esistenti che utilizzavano la precedente interfaccia.

**Proprietà pubbliche:**
- `$title: string` - Titolo del prodotto
- `$price: float` - Prezzo attuale
- `$fullprice: float` - Prezzo originale
- `$saving: int` - Percentuale di sconto
- `$link: string` - Link con tracking
- `$asin: string` - ASIN del prodotto
- `$image: string` - URL immagine
- `$hasPrimePrice: bool` - Ha offerte Prime
- `$primePrices: array` - Dati prezzi Prime

**Metodi:**
- `toArray(): array` - Esporta in formato array
- `getProductItem(): ProductItem` - Accede al ProductItem sottostante
- `fromApiData(array $data): AmazonItem` - Factory method

**Esempio compatibilità:**
```php
// Utilizzo identico al sistema precedente
$item = $client->getAmazonItem('B0DV9HJTTK');

echo $item->title;      // Accesso diretto alle proprietà
echo $item->price;      // Stesso identico utilizzo
echo $item->asin;       // Zero modifiche necessarie

$data = $item->toArray(); // Array per sistemi legacy
```
