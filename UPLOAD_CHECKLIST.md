# MetaBorghi — Checklist Upload Server

> Generata il 2026-03-19 | Hosting: Hostinger (shared) | Dominio: public_html

---

## ✅ FILE GIÀ PRESENTI SUL SERVER (non ricaricare)

```
/index.html                              ← NUOVO — appena generato
/favicon.svg                             ← NUOVO — appena generato
/.htaccess                               ✅ OK
/assets/AccountPage-F6sXvbjr.js          ✅ OK
/assets/AdminPage-DTj6PdME.js            ✅ OK
/assets/ArrowLeft.es-CcOGJ4Vp.js         ✅ OK
/assets/B2BDirectoryPage-Boh3L49c.js     ✅ OK
/assets/B2BLandingPage-BCQsl8mv.js       ✅ OK
/assets/B2BOpportunitiesPage-fgzC9R-O.js ✅ OK
/assets/B2BOpportunityDetailPage--sJ84P7p.js ✅ OK
/assets/Backpack.es-Bm0NEW1h.js          ✅ OK
/assets/BoroughDetailPage-DmXnedyp.js    ✅ OK
/assets/BoroughsPage-CYJqwwpc.js         ✅ OK
/assets/Breadcrumb-BXWEMhDl.js           ✅ OK
/assets/boroughs-CXywHoot.js             ← STUB — clicca "Pubblica" nell'admin per popolare
/assets/companies-DS8bqSy6.js            ← STUB — clicca "Pubblica" nell'admin per popolare
/assets/experiences-C_0o8G74.js          ← STUB — clicca "Pubblica" nell'admin per popolare
/assets/craft-products-CcLcqzAP.js       ← STUB — clicca "Pubblica" nell'admin per popolare
```

---

## ❌ FILE MANCANTI — DA CARICARE DAL BUILD LOCALE

Questi file si trovano nella cartella `dist/assets/` del tuo progetto locale
dopo aver eseguito `npm run build` nella cartella `platform/`.

### Bundle principali (CRITICI — il sito non parte senza questi)

| File da caricare | Destinazione sul server |
|-----------------|------------------------|
| `dist/assets/index-Bhg8UQGm.js` | `/assets/index-Bhg8UQGm.js` |
| `dist/assets/vendor-react-CY4oDSF8.js` | `/assets/vendor-react-CY4oDSF8.js` |
| `dist/assets/vendor-motion-DQMF-AB3.js` | `/assets/vendor-motion-DQMF-AB3.js` |
| `dist/assets/vendor-map-BGEQlYai.js` | `/assets/vendor-map-BGEQlYai.js` |
| `dist/assets/marker-shadow-DEcnuomo.js` | `/assets/marker-shadow-DEcnuomo.js` |

### Chunk componenti (necessari per la navigazione)

| File da caricare | Destinazione sul server |
|-----------------|------------------------|
| `dist/assets/CalendarBlank.es-n1YLIwxt.js` | `/assets/` |
| `dist/assets/Certificate.es-x9n-8QaY.js` | `/assets/` |
| `dist/assets/ChatCircleDots.es-D9-aPfRc.js` | `/assets/` |
| `dist/assets/Circle.es-KsnvZe3F.js` | `/assets/` |
| `dist/assets/ExperienceCard-BsWUpXRA.js` | `/assets/` |
| `dist/assets/Funnel.es-Cfp8Uzo5.js` | `/assets/` |
| `dist/assets/Globe.es-U9J1MZbr.js` | `/assets/` |
| `dist/assets/Handshake.es-CBk11L4O.js` | `/assets/` |
| `dist/assets/MagnifyingGlass.es-DVMy85CF.js` | `/assets/` |
| `dist/assets/Mountains.es-CFHGA2gl.js` | `/assets/` |
| `dist/assets/Play.es-ISfum6av.js` | `/assets/` |
| `dist/assets/ProductCard-Bn-iS_kB.js` | `/assets/` |
| `dist/assets/SectionTitle-BXhatj5Q.js` | `/assets/` |
| `dist/assets/TrendUp.es-CsiJZtTG.js` | `/assets/` |
| `dist/assets/Users.es-DtHo7uZv.js` | `/assets/` |

### Eventuali file CSS (se presenti nel build)

| File da caricare | Destinazione sul server |
|-----------------|------------------------|
| `dist/assets/index-*.css` (se esiste) | `/assets/` |

---

## 🚀 PROCEDURA CONSIGLIATA — Upload Completo

### Opzione A: Upload selettivo (solo file mancanti)
1. Apri il File Manager di Hostinger
2. Naviga in `public_html/assets/`
3. Carica TUTTI i file dalla cartella `dist/assets/` del tuo PC
4. Carica `dist/index.html` nella root `public_html/` (sostituisci quello appena creato)

### Opzione B: Upload totale (build completo, consigliato)
1. Esegui `npm run build` nella cartella `platform/` del tuo progetto locale
2. Carica TUTTO il contenuto della cartella `dist/` nel `public_html/`
   - `dist/index.html` → `public_html/index.html`
   - `dist/assets/*` → `public_html/assets/`
3. NON sovrascrivere la cartella `api/` e i file `.htaccess`

---

## ⚡ DOPO L'UPLOAD — Azioni obbligatorie

1. Accedi al pannello admin: `https://tuodominio.it/api/admin/`
2. Clicca il bottone **"Pubblica"** nella Dashboard
3. Questo rigenererà i 4 file dati JS con i dati reali dal DB:
   - `boroughs-CXywHoot.js` (25 borghi)
   - `companies-DS8bqSy6.js` (14 aziende)
   - `experiences-C_0o8G74.js` (15 esperienze)
   - `craft-products-CcLcqzAP.js` (7 prodotti artigianali)

---

## 🔍 VERIFICA RAPIDA POST-DEPLOY

Apri la console del browser (F12) sul sito e controlla che non ci siano errori 404.
I file critici da verificare sono:
- `/assets/index-Bhg8UQGm.js` → 200 OK
- `/assets/vendor-react-CY4oDSF8.js` → 200 OK
- `/assets/vendor-motion-DQMF-AB3.js` → 200 OK
- `/assets/vendor-map-BGEQlYai.js` → 200 OK
