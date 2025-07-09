# Integration Tests con Credenziali Reali

Questo documento spiega come eseguire i test di integrazione che utilizzano credenziali reali dell'API Amazon.

## File di Test

### `AmazonProductApiClientFieldsTest.php`

Questo file contiene test che utilizzano credenziali reali per testare l'API Amazon Product Advertising API v5.

## Configurazione delle Credenziali

### 1. Crea il file delle credenziali

Copia il file di esempio e rinominalo:

```bash
cp amazon-api-credentials.example.json amazon-api-credentials.json
```

### 2. Inserisci le tue credenziali

Modifica il file `amazon-api-credentials.json` con le tue credenziali reali:

```json
{
    "accessKey": "AKIAIOSFODNN7EXAMPLE",
    "secretKey": "wJalrXUtnFEMI/K7MDENG/bPxRfiCYEXAMPLEKEY",
    "partnerTag": "your-partner-tag-here",
    "marketplace": "www.amazon.com",
    "timeout": 30
}
```

### 3. Sicurezza

⚠️ **IMPORTANTE**: Il file `amazon-api-credentials.json` è già incluso nel `.gitignore` e **NON** verrà mai committato nel repository.

## Esecuzione dei Test

### Lanciare solo i test di integrazione

```bash
# Tutti i test di integrazione
./vendor/bin/phpunit --filter AmazonProductApiClientFieldsTest

# Test specifico
./vendor/bin/phpunit --filter "testGetRealProductByAsin"

# Test con output verboso
./vendor/bin/phpunit --filter AmazonProductApiClientFieldsTest --verbose
```

### Lanciare tutti i test (inclusi quelli di integrazione)

```bash
./vendor/bin/phpunit
```

## Test Disponibili

### Test di Base
- `testClientInstantiationWithRealCredentials()` - Test di istanziazione del client
- `testGetRealProductByAsin()` - Test di recupero prodotto reale
- `testGetMultipleRealProducts()` - Test di recupero multipli prodotti

### Test Avanzati
- `testGetProductWithCustomResources()` - Test con risorse personalizzate
- `testGetProductWithCustomOfferCount()` - Test con numero offerte personalizzato
- `testProductDataStructure()` - Test della struttura dati del prodotto
- `testErrorHandlingWithInvalidAsin()` - Test gestione errori

### Test Legacy
- `testGetAmazonItem()` - Test metodo legacy AmazonItem
- `testGetAmazonItems()` - Test metodo legacy multipli AmazonItem

### Test di Configurazione
- `testTimeoutSetting()` - Test impostazione timeout
- `testDifferentMarketplaceConfigurations()` - Test marketplace diversi

## Gestione degli Errori

I test sono progettati per gestire graziosamente gli errori:

### Se il file delle credenziali non esiste:
```
Skipped: Credentials file not found. Create amazon-api-credentials.json in the project root with your API credentials.
```

### Se le credenziali sono invalide:
```
Skipped: API request failed: [messaggio di errore Amazon]
```

### Se mancano campi richiesti:
```
Skipped: Missing required credential field: accessKey
```

## Prodotti di Test

I test utilizzano ASIN reali di prodotti Amazon:

- `B08N5WRWNW` - Echo Dot (4th Gen)
- `B07FZ8S74R` - Echo Dot (3rd Gen)  
- `B07XJ8C8F7` - Echo Show 5

## Marketplace Supportati

I test supportano diversi marketplace:

- `www.amazon.com` - Stati Uniti
- `www.amazon.co.uk` - Regno Unito
- `www.amazon.de` - Germania

## Output dei Test

### Test di Successo
```
✓ testGetRealProductByAsin
✓ testGetMultipleRealProducts
✓ testProductDataStructure
```

### Test Saltati (quando appropriato)
```
S testGetRealProductByAsin
S testGetMultipleRealProducts
```

## Troubleshooting

### Problema: "Credentials file not found"
**Soluzione**: Crea il file `amazon-api-credentials.json` nella root del progetto

### Problema: "Missing required credential field"
**Soluzione**: Assicurati che tutti i campi richiesti siano presenti nel file JSON

### Problema: "API request failed"
**Soluzione**: Verifica che le credenziali siano corrette e che l'account abbia accesso all'API

### Problema: "Invalid JSON in credentials file"
**Soluzione**: Verifica la sintassi JSON del file delle credenziali

## Best Practices

1. **Non committare mai** il file `amazon-api-credentials.json`
2. **Usa credenziali di test** quando possibile
3. **Monitora l'uso dell'API** per evitare di superare i limiti
4. **Esegui i test in ambiente di sviluppo** prima di produzione

## Limitazioni

- I test fanno chiamate reali all'API Amazon
- Possono essere limitati da rate limiting
- Alcuni marketplace potrebbero non avere gli stessi prodotti
- I test potrebbero fallire se i prodotti cambiano o vengono rimossi 