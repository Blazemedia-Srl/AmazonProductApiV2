# Examples - Amazon Product API v5

Questa cartella contiene esempi di utilizzo della libreria Amazon Product API v5.

## File di Esempio

### 🚀 Esempi Base

- **`example.php`** - Esempio completo con tutte le funzionalità della libreria
- **`simple_example.php`** - Esempio semplificato per iniziare rapidamente
- **`test.php`** - Test delle classi principali per verificare il funzionamento

### 🛠️ Esempi Utilities

- **`utilities_demo.php`** - Dimostrazione di tutte le utilities helper disponibili

### ⚙️ Configurazione

- **`config.example.php`** - File di configurazione esempio con tutte le opzioni

### 📦 Senza Composer

- **`example_no_composer.php`** - Esempio per chi non usa Composer (usa autoload.php)

### 🔄 Compatibilità

- **`compatibility_example.php`** - Esempio di utilizzo della classe AmazonItem per compatibilità
- **`compatibility_test.php`** - Test completo della compatibilità con sistemi esistenti

## Come Utilizzare

### 1. Con Composer (Raccomandato)

```bash
# Nella directory principale del progetto
composer require blazemedia/amazon-product-api-v2
```

Poi usa gli esempi che iniziano con `require_once '../vendor/autoload.php';`

### 2. Senza Composer

Usa `example_no_composer.php` che carica l'autoloader personalizzato:

```php
require_once '../autoload.php';
```

## Configurazione Richiesta

Prima di eseguire gli esempi, devi configurare le tue credenziali:

1. **AWS Access Key e Secret Key**
2. **Amazon Associates Partner Tag**
3. **Marketplace Amazon**

### Sostituisci nei file:

```php
$config = [
    'access_key' => 'YOUR_AWS_ACCESS_KEY',     // ← Le tue credenziali
    'secret_key' => 'YOUR_AWS_SECRET_KEY',     // ← Le tue credenziali
    'partner_tag' => 'blazemedia-21',          // ← Il tuo partner tag
    'marketplace' => 'www.amazon.it'           // ← Il tuo marketplace
];
```

## Esecuzione

```bash
cd examples
php example.php
```

oppure

```bash
cd examples
php simple_example.php
```

## Note Importanti

- ✅ Tutti gli esempi utilizzano il nuovo namespace `Blazemedia\AmazonProductApiV2`
- ✅ I path sono corretti per l'esecuzione dalla cartella `examples`
- ✅ Include gestione errori completa
- ✅ Compatibile con PHP 8.1+

## Test ASIN

Gli esempi utilizzano ASIN di test. Per i tuoi test, sostituisci con ASIN validi:

```php
$asin = 'B0DV9HJTTK'; // ← Sostituisci con un ASIN reale del tuo marketplace
```

## Supporto

Per domande o problemi:
- Consulta `DOCUMENTATION.md` nella directory principale
- Verifica che le tue credenziali AWS siano corrette
- Assicurati che il Partner Tag sia valido per il marketplace selezionato
