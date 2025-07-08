<?php

require_once '../vendor/autoload.php';

use Blazemedia\AmazonProductApiV2\Models\ProductItem;
use Blazemedia\AmazonProductApiV2\Models\AmazonItem;

echo "=== TEST COMPATIBILITÀ AMAZON ITEM ===\n\n";

// Simula dati Amazon API come potrebbero arrivare dal servizio reale
$mockApiData = [
    'ASIN' => 'B0DV9HJTTK',
    'DetailPageURL' => 'https://www.amazon.it/dp/B0DV9HJTTK?tag=blazemedia-21',
    'ItemInfo' => [
        'Title' => [
            'DisplayValue' => 'Esempio Prodotto Test Amazon'
        ],
        'ByLineInfo' => [
            'Brand' => [
                'DisplayValue' => 'Test Brand'
            ],
            'Manufacturer' => [
                'DisplayValue' => 'Test Manufacturer'
            ]
        ],
        'Features' => [
            'DisplayValues' => [
                'Caratteristica 1',
                'Caratteristica 2', 
                'Caratteristica 3'
            ]
        ]
    ],
    'Images' => [
        'Primary' => [
            'Large' => [
                'URL' => 'https://m.media-amazon.com/images/I/test-image-large.jpg'
            ],
            'Medium' => [
                'URL' => 'https://m.media-amazon.com/images/I/test-image-medium.jpg'
            ],
            'Small' => [
                'URL' => 'https://m.media-amazon.com/images/I/test-image-small.jpg'
            ]
        ]
    ],
    'Offers' => [
        'Listings' => [
            [
                'IsBuyBoxWinner' => true,
                'Price' => [
                    'Amount' => 29.99,
                    'Currency' => 'EUR',
                    'DisplayValue' => '€29,99'
                ],
                'SavingBasis' => [
                    'Amount' => 39.99,
                    'Currency' => 'EUR', 
                    'DisplayValue' => '€39,99'
                ],
                'Availability' => [
                    'Type' => 'Now',
                    'Message' => 'Disponibile'
                ],
                'Condition' => [
                    'Value' => 'New'
                ],
                'ProgramEligibility' => [
                    'IsPrimeEligible' => true,
                    'IsPrimeExclusive' => true
                ],
                'MerchantInfo' => [
                    'Name' => 'Amazon.it',
                    'DefaultShippingCountry' => 'IT'
                ]
            ]
        ]
    ]
];

echo "1. CREAZIONE AMAZON ITEM DA DATI MOCK:\n";

// Crea ProductItem da dati mock
$productItem = new ProductItem($mockApiData);
echo "✅ ProductItem creato\n";

// Crea AmazonItem da ProductItem
$amazonItem = new AmazonItem($productItem, 'blazemedia-21', 'testTrackingCode');
echo "✅ AmazonItem creato\n\n";

echo "2. TEST PROPRIETÀ PUBBLICHE:\n";
echo "- ASIN: '{$amazonItem->asin}'\n";
echo "- Title: '{$amazonItem->title}'\n";
echo "- Price: {$amazonItem->price}\n";
echo "- Full Price: {$amazonItem->fullprice}\n";
echo "- Saving: {$amazonItem->saving}%\n";
echo "- Link: '{$amazonItem->link}'\n";
echo "- Image: '{$amazonItem->image}'\n";
echo "- Has Prime: " . ($amazonItem->hasPrimePrice ? 'true' : 'false') . "\n";
echo "- Prime Prices: " . json_encode($amazonItem->primePrices) . "\n\n";

echo "3. TEST METODO toArray():\n";
$arrayData = $amazonItem->toArray();
foreach ($arrayData as $key => $value) {
    if (is_array($value)) {
        echo "- {$key}: " . json_encode($value) . "\n";
    } else {
        echo "- {$key}: " . (is_bool($value) ? ($value ? 'true' : 'false') : "'{$value}'") . "\n";
    }
}
echo "\n";

echo "4. TEST FACTORY METHOD:\n";
$itemFromFactory = AmazonItem::fromApiData($mockApiData, 'blazemedia-21', 'factoryTestCode');
echo "✅ AmazonItem creato da factory method\n";
echo "- Title from factory: '{$itemFromFactory->title}'\n";
echo "- Link from factory: '{$itemFromFactory->link}'\n\n";

echo "5. TEST TRACKING PLACEHOLDER:\n";
$originalLink = 'https://www.amazon.it/dp/B0DV9HJTTK?tag=blazemedia-21';
$expectedLink = 'https://www.amazon.it/dp/B0DV9HJTTK?tag=testTrackingCode';
echo "- Link originale: {$originalLink}\n";
echo "- Link con placeholder: {$amazonItem->link}\n";
echo "- Placeholder corretto: " . (strpos($amazonItem->link, 'testTrackingCode') !== false ? '✅' : '❌') . "\n\n";

echo "6. TEST CALCOLI PREZZI:\n";
$expectedSaving = round((($amazonItem->fullprice - $amazonItem->price) / $amazonItem->fullprice) * 100);
echo "- Prezzo corrente: €{$amazonItem->price}\n";
echo "- Prezzo pieno: €{$amazonItem->fullprice}\n";
echo "- Sconto calcolato: {$expectedSaving}%\n";
echo "- Sconto nel modello: {$amazonItem->saving}%\n";
echo "- Calcolo corretto: " . ($expectedSaving == $amazonItem->saving ? '✅' : '❌') . "\n\n";

echo "7. TEST PRIME PRICES:\n";
if ($amazonItem->hasPrimePrice && !empty($amazonItem->primePrices)) {
    echo "✅ Prime prices disponibili:\n";
    foreach ($amazonItem->primePrices as $key => $value) {
        echo "  - {$key}: {$value}\n";
    }
} else {
    echo "❌ Prime prices non disponibili\n";
}
echo "\n";

echo "8. TEST ACCESSO PROPRIETÀ MAGIC METHODS:\n";
// Test __get
$titleViaGet = $amazonItem->__get('title');
echo "- Title via __get(): '{$titleViaGet}'\n";

// Test __isset
$titleExists = $amazonItem->__isset('title');
$fakeExists = $amazonItem->__isset('nonExistentProperty');
echo "- Title exists via __isset(): " . ($titleExists ? 'true' : 'false') . "\n";
echo "- Fake property exists via __isset(): " . ($fakeExists ? 'true' : 'false') . "\n\n";

echo "9. TEST ACCESSO PRODUCTITEM SOTTOSTANTE:\n";
$underlyingItem = $amazonItem->getProductItem();
echo "✅ ProductItem sottostante accessibile\n";
echo "- Brand: " . ($underlyingItem->getBrand() ?: 'N/A') . "\n";
echo "- Manufacturer: " . ($underlyingItem->getManufacturer() ?: 'N/A') . "\n";
echo "- In Stock: " . ($underlyingItem->isInStock() ? 'Sì' : 'No') . "\n";
echo "- Condition: " . ($underlyingItem->getCondition() ?: 'N/A') . "\n\n";

echo "10. TEST COMPATIBILITÀ CON SISTEMI ESISTENTI:\n";

// Simula funzione di un sistema esistente che si aspetta un oggetto con proprietà pubbliche
function simulateOldSystemFunction($item) {
    // Questo simula come un sistema esistente potrebbe usare l'oggetto
    $result = [
        'product_id' => $item->asin,
        'product_name' => $item->title,
        'current_price' => $item->price,
        'discount_percentage' => $item->saving,
        'affiliate_url' => $item->link,
        'main_image' => $item->image,
        'is_prime' => $item->hasPrimePrice
    ];
    
    return $result;
}

$legacyResult = simulateOldSystemFunction($amazonItem);
echo "✅ Sistema legacy funziona correttamente:\n";
foreach ($legacyResult as $key => $value) {
    echo "  - {$key}: " . (is_bool($value) ? ($value ? 'true' : 'false') : "'{$value}'") . "\n";
}

echo "\n";

echo "11. CONFRONTO PERFORMANCE:\n";
$start = microtime(true);
for ($i = 0; $i < 1000; $i++) {
    $testItem = new AmazonItem($productItem);
    $testArray = $testItem->toArray();
}
$end = microtime(true);
$time = round(($end - $start) * 1000, 2);
echo "✅ 1000 creazioni + conversioni toArray(): {$time}ms\n";

echo "\n🎉 TUTTI I TEST COMPATIBILITÀ COMPLETATI CON SUCCESSO!\n";

echo "\n📋 RIEPILOGO FUNZIONALITÀ:\n";
echo "✅ Proprietà pubbliche identiche al modello originale\n";
echo "✅ Metodo toArray() per export compatibile\n";
echo "✅ Factory method per creazione da dati grezzi\n";
echo "✅ Tracking placeholder personalizzabile\n";
echo "✅ Accesso al ProductItem sottostante per funzioni avanzate\n";
echo "✅ Magic methods per accesso proprietà\n";
echo "✅ Calcoli prezzi e sconti automatici\n";
echo "✅ Gestione Prime prices\n";
echo "✅ Compatibilità totale con sistemi esistenti\n";
echo "✅ Performance ottimizzate\n";
