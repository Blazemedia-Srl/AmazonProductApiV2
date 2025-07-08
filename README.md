# Amazon Product Advertising API v5 Client Library

Una libreria PHP 8.1 per interagire con l'Amazon Product Advertising API v5, che permette di recuperare informazioni dettagliate sui prodotti Amazon.

## Caratteristiche

- 🚀 Supporto completo per Amazon PA API v5
- 🔐 Autenticazione AWS Signature Version 4
- 📦 Gestione automatica delle richieste e risposte
- 🛡️ Gestione robusta degli errori
- 🏷️ Supporto per tutti i marketplace Amazon
- 💰 Recupero prezzi, offerte, coupon e informazioni Prime
- 🖼️ Gestione immagini prodotto
- 📊 Modelli di dati strutturati

## Installazione

```bash
composer require blazemedia/amazon-product-api-v2
```

## Configurazione

```php
<?php
require_once 'vendor/autoload.php';

use Blazemedia\AmazonProductApiV2\AmazonProductApiClient;

$client = new AmazonProductApiClient([
    'access_key' => 'YOUR_ACCESS_KEY',
    'secret_key' => 'YOUR_SECRET_KEY',
    'partner_tag' => 'YOUR_PARTNER_TAG',
    'marketplace' => 'www.amazon.it', // o altro marketplace
    'region' => 'eu-west-1'
]);
```

## Utilizzo Base

```php
// Recupera informazioni prodotto per ASIN
$product = $client->getItem('B0DV9HJTTK');

echo "Titolo: " . $product->getTitle() . "\n";
echo "Prezzo: " . $product->getPrice() . "\n";
echo "Descrizione: " . $product->getDescription() . "\n";
echo "Immagine: " . $product->getImageUrl() . "\n";

// Verifica se ha offerte Prime
if ($product->hasPrimeOffer()) {
    echo "Prodotto disponibile con Prime\n";
}

// Verifica coupon disponibili
if ($product->hasCoupons()) {
    echo "Coupon disponibili: " . $product->getCouponDiscount() . "\n";
}
```

## Compatibilità con Sistemi Esistenti

La libreria include la classe `AmazonItem` per mantenere compatibilità totale con sistemi che utilizzavano la precedente interfaccia:

```php
// Utilizzo compatibile (identico al sistema precedente)
$item = $client->getAmazonItem('B0DV9HJTTK');

echo $item->title;      // Accesso diretto
echo $item->price;      // Stesso utilizzo
echo $item->asin;       // Zero modifiche
echo $item->image;      // Compatibilità totale

$data = $item->toArray(); // Export array
```

**Vantaggi:**
- ✅ Zero modifiche ai sistemi esistenti
- ✅ Stesse proprietà pubbliche
- ✅ Metodo `toArray()` identico
- ✅ Tracking placeholder personalizzabile
- ✅ Accesso alle funzionalità avanzate quando necessario

## Licenza

MIT License
