# Istruzioni scraper sistemairpinia.provincia.avellino.it

## 1. Installa le dipendenze (una volta sola)

```bash
pip install playwright beautifulsoup4 lxml
playwright install chromium
```

## 2. Lancia lo scraper

```bash
python scraper_sistemairpinia.py
```

Lo script gira in automatico e produce 3 file JSON nella stessa cartella:
- `borghi_raw.json`
- `eventi_raw.json`
- `itinerari_raw.json`

## 3. Inviami i 3 file JSON

Una volta completato, inviami i 3 file e io:
- Riprocesso i dati
- Riscrivo le descrizioni con sinonimi
- Costruisco il populate script per il DB
- Pusho tutto sul branch

## Nota tempi

Lo scraper rispetta pause tra richieste (1-1.5 secondi) per non sovraccaricare il server.
Con ~100 pagine stima circa **5-10 minuti** di esecuzione totale.
