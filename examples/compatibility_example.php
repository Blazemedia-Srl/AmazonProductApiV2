<?php

require_once '../vendor/autoload.php';

use Blazemedia\AmazonProductApiV2\AmazonProductApiClient;
use Blazemedia\AmazonProductApiV2\Models\AmazonItem;
use Blazemedia\AmazonProductApiV2\Exceptions\AmazonApiException;

echo "=== ESEMPIO COMPATIBILITÀ AMAZON ITEM ===\n";
echo "Questo esempio mostra come utilizzare la classe AmazonItem\n";
echo "per mantenere compatibilità con sistemi esistenti.\n\n";

// Configurazione del client
$config = [
    'access_key' => 'YOUR_AWS_ACCESS_KEY',
    'secret_key' => 'YOUR_AWS_SECRET_KEY',
    'partner_tag' => 'blazemedia-21',
    'marketplace' => 'www.amazon.it',
    'region' => 'eu-west-1'
];

try {
    // Inizializza il client
    $client = new AmazonProductApiClient($config);
    
    // Test ASIN
    $asin = 'B0DV9HJTTK';
    echo "📦 Recupero prodotto: {$asin}\n\n";
    
    // METODO 1: Usa getAmazonItem per compatibilità diretta
    echo "=== METODO 1: getAmazonItem() ===\n";
    $amazonItem = $client->getAmazonItem($asin);
    
    // Accesso alle proprietà pubbliche (compatibile con il vecchio sistema)
    echo "Titolo: {$amazonItem->title}\n";
    echo "ASIN: {$amazonItem->asin}\n";
    echo "Prezzo: €{$amazonItem->price}\n";
    echo "Prezzo pieno: €{$amazonItem->fullprice}\n";
    echo "Sconto: {$amazonItem->saving}%\n";
    echo "Link: {$amazonItem->link}\n";
    echo "Immagine: {$amazonItem->image}\n";
    echo "Ha Prime: " . ($amazonItem->hasPrimePrice ? 'Sì' : 'No') . "\n";
    
    if ($amazonItem->hasPrimePrice && !empty($amazonItem->primePrices)) {
        echo "Prezzi Prime:\n";
        echo "- Prezzo Prime: €{$amazonItem->primePrices['price']}\n";
        echo "- Risparmio: €{$amazonItem->primePrices['saving']}\n";
        echo "- Prezzo pieno: €{$amazonItem->primePrices['fullprice']}\n";
    }
    
    echo "\n";
    
    // METODO 2: Conversione toArray() (compatibile con il vecchio sistema)
    echo "=== METODO 2: toArray() ===\n";
    $itemArray = $amazonItem->toArray();
    
    echo "Array di compatibilità:\n";
    foreach ($itemArray as $key => $value) {
        if (is_array($value)) {
            echo "- {$key}: " . json_encode($value) . "\n";
        } else {
            echo "- {$key}: " . (is_bool($value) ? ($value ? 'true' : 'false') : $value) . "\n";
        }
    }
    
    echo "\n";
    
    // METODO 3: Factory method da dati grezzi
    echo "=== METODO 3: Factory da dati API ===\n";
    
    // Simula come si potrebbe integrare con sistemi esistenti
    // che già hanno i dati grezzi dall'API
    $productItem = $client->getItem($asin);
    $rawData = $productItem->getRawData();
    
    // Crea AmazonItem da dati grezzi
    $itemFromRaw = AmazonItem::fromApiData($rawData, 'blazemedia-21', 'myTrackingCode');
    
    echo "Creato da dati grezzi:\n";
    echo "Titolo: {$itemFromRaw->title}\n";
    echo "Prezzo: €{$itemFromRaw->price}\n";
    echo "Link con tracking: {$itemFromRaw->link}\n";
    
    echo "\n";
    
    // METODO 4: Multipli prodotti
    echo "=== METODO 4: Multipli prodotti ===\n";
    $multipleAsins = [$asin]; // Aggiungi altri ASIN se disponibili
    
    $amazonItems = $client->getAmazonItems($multipleAsins);
    
    echo "Recuperati " . count($amazonItems) . " prodotti:\n";
    foreach ($amazonItems as $index => $item) {
        echo ($index + 1) . ". {$item->title} - €{$item->price}\n";
    }
    
    echo "\n";
    
    // METODO 5: Accesso alle funzionalità avanzate
    echo "=== METODO 5: Funzionalità avanzate ===\n";
    
    // Puoi ancora accedere all'oggetto ProductItem completo
    $fullProductItem = $amazonItem->getProductItem();
    
    echo "Funzionalità avanzate disponibili:\n";
    echo "- Brand: " . ($fullProductItem->getBrand() ?: 'N/A') . "\n";
    echo "- Manufacturer: " . ($fullProductItem->getManufacturer() ?: 'N/A') . "\n";
    echo "- Condition: " . ($fullProductItem->getCondition() ?: 'N/A') . "\n";
    echo "- In Stock: " . ($fullProductItem->isInStock() ? 'Sì' : 'No') . "\n";
    
    $features = $fullProductItem->getFeatures();
    if (!empty($features)) {
        echo "- Caratteristiche: " . implode(', ', array_slice($features, 0, 3)) . "...\n";
    }
    
    // Dimensioni se disponibili
    $dimensions = $fullProductItem->getDimensions();
    if ($dimensions) {
        echo "- Dimensioni disponibili\n";
    }
    
    echo "\n";
    
    // Simulazione integrazione con sistema esistente
    echo "=== SIMULAZIONE INTEGRAZIONE SISTEMA ESISTENTE ===\n";
    
    // Esempio di come un sistema esistente potrebbe utilizzare questi dati
    function processLegacyItem($item) {
        if (!$item instanceof AmazonItem) {
            throw new InvalidArgumentException('Expected AmazonItem instance');
        }
        
        $data = [
            'product_title' => $item->title,
            'product_asin' => $item->asin,
            'current_price' => $item->price,
            'original_price' => $item->fullprice,
            'discount_percent' => $item->saving,
            'affiliate_link' => $item->link,
            'image_url' => $item->image,
            'has_prime' => $item->hasPrimePrice,
            'prime_data' => $item->primePrices
        ];
        
        return $data;
    }
    
    $legacyData = processLegacyItem($amazonItem);
    echo "Dati processati per sistema legacy:\n";
    echo "- Titolo prodotto: {$legacyData['product_title']}\n";
    echo "- Prezzo corrente: €{$legacyData['current_price']}\n";
    echo "- Sconto: {$legacyData['discount_percent']}%\n";
    echo "- Ha Prime: " . ($legacyData['has_prime'] ? 'Sì' : 'No') . "\n";
    
} catch (AmazonApiException $e) {
    echo "❌ Errore API Amazon: " . $e->getMessage() . "\n";
    echo "Codice errore: " . $e->getCode() . "\n";
} catch (Exception $e) {
    echo "❌ Errore generico: " . $e->getMessage() . "\n";
}

echo "\n💡 VANTAGGI DELLA COMPATIBILITÀ:\n";
echo "✅ Interfaccia identica alla classe originale\n";
echo "✅ Proprietà pubbliche accessibili direttamente\n";
echo "✅ Metodo toArray() per sistemi che usano array\n";
echo "✅ Factory method per creare da dati grezzi\n";
echo "✅ Accesso alle funzionalità avanzate quando necessario\n";
echo "✅ Tracking placeholder personalizzabile\n";
echo "✅ Zero modifiche ai sistemi esistenti\n";

echo "\n🔄 MIGRAZIONE SEMPLICE:\n";
echo "// PRIMA (sistema vecchio):\n";
echo "// \$item = new AmazonItem(\$apiData, \$partnerTag);\n";
echo "// echo \$item->title;\n";
echo "//\n";
echo "// DOPO (sistema nuovo):\n";
echo "// \$item = \$client->getAmazonItem(\$asin);\n";
echo "// echo \$item->title; // Stesso identico utilizzo!\n";

echo "\n🎯 Compatibilità completata!\n";
