# Design Spec — Lacedonia Travel Booking Widget
**Data:** 2026-04-14  
**Progetto:** MetaBorghi — InnTour S.R.L.  
**Branch:** `claude/filter-lacedonia-municipality-azcGs`  
**Scope:** Modulo B (Scheda Lacedonia) + Modulo A (Booking Viaggio)

---

## 1. Obiettivo

Abilitare il widget di prenotazione viaggio sulla pagina `metaborghi.org/borghi/lacedonia` sostituendo due card placeholder ("Presto disponibile") con il componente travel booking già presente nel bundle React ma non renderizzato. Aggiungere il tab **Treni italiani** (linea Tirrenica → Napoli e Adriatica → Foggia) alla sequenza esistente.

**Risultato atteso:** L'utente sulla scheda Lacedonia trova in fondo alla pagina una sezione "Organizza il viaggio" con 4 tab funzionanti: Voli → Treni → Auto → Transfer.

---

## 2. Contesto Tecnico

### File chiave identificati

| File | Stato | Ruolo |
|------|-------|-------|
| `assets/BoroughDetailPage-DmXnedyp.js` | Bundle compilato (readable) | Contiene `R1`, `H1`, `C1`, `I1`, `P1`, `Z1` già scritti |
| `borghi/lacedonia/index.html` | Shell pre-renderizzata | Entry point HTML del SPA per Lacedonia |
| `prerender.js` | Script Node.js | Rigenera le shell HTML di tutti i borghi |

### Componenti esistenti nel bundle (da sbloccare)

- **`R1({ boroughName })`** — Sezione "Organizza il viaggio". Definita ma mai chiamata con `jsx(R1)`. Ha tab system con `useState("flights")` e `P1[]` come config tab.
- **`H1()`** — Tab Voli. Form ricerca `V1` + mock data `Z1` (Lufthansa MUC→NAP). Loading 800ms simulato. Aggiunge a carrello via `addTravelItem`.
- **`C1({ boroughName })`** — Tab Transfer privati. Mock data `k1`. Stesso pattern.
- **`I1()`** — Tab Noleggio auto. Mock data `D1`. Filtro per categoria.
- **`P1[]`** — Array configurazione tab (id, label, icon, colori active/inactive).

### Il "muro": perché R1 non è visibile

Nel render del `BoroughDetailPage` (`ge()` function), al posto di `e.jsx(R1, {...})` ci sono due card dashed "Presto disponibile":

```js
e.jsx("section", {
  className: "mb-12",
  children: e.jsxs("div", {
    className: "grid md:grid-cols-2 gap-8",
    children: [
      // card "Prenota il tuo soggiorno" (border-dashed border-ambra-200)
      // card "Organizza il viaggio" (border-dashed border-natura-200)
    ]
  })
})
```

---

## 3. Interventi (Edit Chirurgico)

### 3.1 Backup obbligatorio
```bash
cp assets/BoroughDetailPage-DmXnedyp.js assets/BoroughDetailPage-DmXnedyp.js.bak
```

### 3.2 Intervento 1 — Sostituire le 2 card placeholder con R1

**Trovare** (stringa unica nel file):
```
e.jsx("section",{className:"mb-12",children:e.jsxs("div",{className:"grid md:grid-cols-2 gap-8"
```
*(versione minificata — cercare `border-dashed border-ambra-200` come anchor, poi selezionare il `section.mb-12` padre)*

**Sostituire** l'intero blocco `section.mb-12` con:
```js
e.jsx(R1, { boroughName: s?.name || "Lacedonia" })
```

### 3.3 Intervento 2 — Aggiungere tab Treni a P1

**Trovare** `const P1 = [` e aggiungere dopo il tab `flights` (primo elemento) il nuovo tab `trains`:

```js
{
  id: "trains",
  label: "Treni",
  icon: e.jsx(/* N — variabile phosphor Train/TrainSimple se importata, altrimenti SVG inline 🚂 */, { size: 20 }),
  color: "text-warm-600 hover:text-ambra-600 hover:bg-ambra-50",
  activeColor: "text-ambra-700 bg-ambra-100 shadow-sm",
},
```
> **Colore:** `ambra-*` — unico token disponibile non ancora assegnato a un tab. (`cielo`=voli, `natura`=transfer, `energia`=auto)  
> **Icona:** verificare se `Train` o `TrainSimple` di phosphor-icons è importato nel bundle; in caso contrario usare emoji 🚂 come fallback SVG inline.

**Riordinare P1** risultante: `["flights", "trains", "car_rental", "transfers"]`

### 3.4 Intervento 3 — Aggiungere componente T1 (Treni)

Inserire prima di `function R1(` il nuovo componente `T1`:

```js
const Z1_TRAINS = [
  // Linea Tirrenica → Napoli Centrale
  {
    id: "mock-train-001",
    provider: "omio",
    type: "alta_velocita",
    train_number: "FR 9601",
    carrier: "Trenitalia",
    carrier_logo: "https://upload.wikimedia.org/wikipedia/commons/thumb/8/8b/Trenitalia_logo.svg/320px-Trenitalia_logo.svg.png",
    line: "tirrenica",
    line_label: "Linea Tirrenica",
    outbound: {
      date: "2026-09-10",
      departure: { station: "Roma Termini", code: "ROM", time: "07:00" },
      arrival: { station: "Napoli Centrale", code: "NAP", time: "08:08" },
      duration_minutes: 68,
      stops: 0,
    },
    return: {
      date: "2026-09-13",
      departure: { station: "Napoli Centrale", code: "NAP", time: "16:00" },
      arrival: { station: "Roma Termini", code: "ROM", time: "17:08" },
      duration_minutes: 68,
      stops: 0,
    },
    passengers: 2,
    class: "Standard",
    total_price: 58,
    note_transfer: "Da Napoli: ~1h 15m via A16 verso Lacedonia",
  },
  {
    id: "mock-train-002",
    provider: "omio",
    type: "alta_velocita",
    train_number: "ITA 8901",
    carrier: "Italo",
    carrier_logo: "https://upload.wikimedia.org/wikipedia/commons/thumb/d/d5/Logo_Italo_Treno.svg/320px-Logo_Italo_Treno.svg.png",
    line: "tirrenica",
    line_label: "Linea Tirrenica",
    outbound: {
      date: "2026-09-10",
      departure: { station: "Milano Centrale", code: "MIL", time: "06:25" },
      arrival: { station: "Napoli Centrale", code: "NAP", time: "10:35" },
      duration_minutes: 250,
      stops: 1,
    },
    return: {
      date: "2026-09-13",
      departure: { station: "Napoli Centrale", code: "NAP", time: "15:30" },
      arrival: { station: "Milano Centrale", code: "MIL", time: "19:40" },
      duration_minutes: 250,
      stops: 1,
    },
    passengers: 2,
    class: "Smart",
    total_price: 98,
    note_transfer: "Da Napoli: ~1h 15m via A16 verso Lacedonia",
  },
  {
    id: "mock-train-003",
    provider: "omio",
    type: "alta_velocita",
    train_number: "FR 9605",
    carrier: "Trenitalia",
    carrier_logo: "https://upload.wikimedia.org/wikipedia/commons/thumb/8/8b/Trenitalia_logo.svg/320px-Trenitalia_logo.svg.png",
    line: "tirrenica",
    line_label: "Linea Tirrenica",
    outbound: {
      date: "2026-09-10",
      departure: { station: "Milano Centrale", code: "MIL", time: "07:00" },
      arrival: { station: "Napoli Centrale", code: "NAP", time: "11:15" },
      duration_minutes: 255,
      stops: 1,
    },
    return: {
      date: "2026-09-13",
      departure: { station: "Napoli Centrale", code: "NAP", time: "17:00" },
      arrival: { station: "Milano Centrale", code: "MIL", time: "21:08" },
      duration_minutes: 248,
      stops: 1,
    },
    passengers: 2,
    class: "Standard",
    total_price: 110,
    note_transfer: "Da Napoli: ~1h 15m via A16 verso Lacedonia",
  },
  // Linea Adriatica → Foggia
  {
    id: "mock-train-004",
    provider: "omio",
    type: "alta_velocita",
    train_number: "FR 9701",
    carrier: "Trenitalia",
    carrier_logo: "https://upload.wikimedia.org/wikipedia/commons/thumb/8/8b/Trenitalia_logo.svg/320px-Trenitalia_logo.svg.png",
    line: "adriatica",
    line_label: "Linea Adriatica",
    outbound: {
      date: "2026-09-10",
      departure: { station: "Roma Termini", code: "ROM", time: "08:05" },
      arrival: { station: "Foggia", code: "FOG", time: "10:30" },
      duration_minutes: 145,
      stops: 0,
    },
    return: {
      date: "2026-09-13",
      departure: { station: "Foggia", code: "FOG", time: "16:30" },
      arrival: { station: "Roma Termini", code: "ROM", time: "18:55" },
      duration_minutes: 145,
      stops: 0,
    },
    passengers: 2,
    class: "Standard",
    total_price: 70,
    note_transfer: "Da Foggia: ~45 min via SS655 verso Lacedonia",
  },
  {
    id: "mock-train-005",
    provider: "omio",
    type: "alta_velocita",
    train_number: "ITA 8701",
    carrier: "Italo",
    carrier_logo: "https://upload.wikimedia.org/wikipedia/commons/thumb/d/d5/Logo_Italo_Treno.svg/320px-Logo_Italo_Treno.svg.png",
    line: "adriatica",
    line_label: "Linea Adriatica",
    outbound: {
      date: "2026-09-10",
      departure: { station: "Milano Centrale", code: "MIL", time: "07:10" },
      arrival: { station: "Foggia", code: "FOG", time: "12:05" },
      duration_minutes: 295,
      stops: 2,
    },
    return: {
      date: "2026-09-13",
      departure: { station: "Foggia", code: "FOG", time: "14:00" },
      arrival: { station: "Milano Centrale", code: "MIL", time: "19:00" },
      duration_minutes: 300,
      stops: 2,
    },
    passengers: 2,
    class: "Smart",
    total_price: 118,
    note_transfer: "Da Foggia: ~45 min via SS655 verso Lacedonia",
  },
  {
    id: "mock-train-006",
    provider: "omio",
    type: "regionale",
    train_number: "REG 5401",
    carrier: "Trenitalia",
    carrier_logo: "https://upload.wikimedia.org/wikipedia/commons/thumb/8/8b/Trenitalia_logo.svg/320px-Trenitalia_logo.svg.png",
    line: "adriatica",
    line_label: "Linea Adriatica",
    outbound: {
      date: "2026-09-10",
      departure: { station: "Bari Centrale", code: "BAR", time: "09:15" },
      arrival: { station: "Foggia", code: "FOG", time: "10:20" },
      duration_minutes: 65,
      stops: 3,
    },
    return: {
      date: "2026-09-13",
      departure: { station: "Foggia", code: "FOG", time: "17:00" },
      arrival: { station: "Bari Centrale", code: "BAR", time: "18:05" },
      duration_minutes: 65,
      stops: 3,
    },
    passengers: 2,
    class: "Seconda",
    total_price: 18,
    note_transfer: "Da Foggia: ~45 min via SS655 verso Lacedonia",
  },
];

function V1_TRAINS({ onSearch }) {
  const [dep, setDep] = a.useState("Roma Termini");
  const [arr, setArr] = a.useState("napoli");
  const [date, setDate] = a.useState("2026-09-10");
  const [guests, setGuests] = a.useState(2);
  return e.jsxs("div", {
    className: "glass-strong rounded-2xl p-6 mb-8",
    children: [
      e.jsxs("div", {
        className: "grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-4",
        children: [
          e.jsxs("div", { children: [
            e.jsx("label", { className: "block text-xs font-semibold text-warm-600 mb-1 uppercase tracking-wide", children: "Partenza" }),
            e.jsx("input", { value: dep, onChange: x => setDep(x.target.value), className: "w-full border border-warm-300 rounded-xl px-3 py-2.5 text-sm text-warm-800 bg-white focus:ring-2 focus:ring-ambra-400 focus:border-ambra-400 outline-none", placeholder: "Es. Roma Termini" }),
          ]}),
          e.jsxs("div", { children: [
            e.jsx("label", { className: "block text-xs font-semibold text-warm-600 mb-1 uppercase tracking-wide", children: "Arrivo" }),
            e.jsxs("select", { value: arr, onChange: x => setArr(x.target.value), className: "w-full border border-warm-300 rounded-xl px-3 py-2.5 text-sm text-warm-800 bg-white focus:ring-2 focus:ring-ambra-400 outline-none", children: [
              e.jsx("option", { value: "napoli", children: "🌊 Napoli Centrale (Tirrenica)" }),
              e.jsx("option", { value: "foggia", children: "🌾 Foggia (Adriatica)" }),
            ]}),
          ]}),
          e.jsxs("div", { children: [
            e.jsx("label", { className: "block text-xs font-semibold text-warm-600 mb-1 uppercase tracking-wide", children: "Data" }),
            e.jsx("input", { type: "date", value: date, onChange: x => setDate(x.target.value), className: "w-full border border-warm-300 rounded-xl px-3 py-2.5 text-sm text-warm-800 bg-white focus:ring-2 focus:ring-ambra-400 outline-none" }),
          ]}),
          e.jsxs("div", { children: [
            e.jsx("label", { className: "block text-xs font-semibold text-warm-600 mb-1 uppercase tracking-wide", children: "Passeggeri" }),
            e.jsx("input", { type: "number", min: 1, max: 9, value: guests, onChange: x => setGuests(Number(x.target.value)), className: "w-full border border-warm-300 rounded-xl px-3 py-2.5 text-sm text-warm-800 bg-white focus:ring-2 focus:ring-ambra-400 outline-none" }),
          ]}),
        ],
      }),
      e.jsx("button", {
        onClick: () => onSearch({ dep, arr, date, guests }),
        className: "w-full py-3 bg-ambra-600 hover:bg-ambra-700 text-white rounded-xl font-semibold transition-colors shadow-sm",
        children: "Cerca treni",
      }),
    ],
  });
}

function T1({ className: t }) {
  const addTravelItem = D((i) => i.addTravelItem);
  const addToast = M((i) => i.addToast);
  const toggleCartSidebar = M((i) => i.toggleCartSidebar);
  const [results, setResults] = a.useState([]);
  const [loading, setLoading] = a.useState(false);
  const [searched, setSearched] = a.useState(false);
  const [lineFilter, setLineFilter] = a.useState("all");

  const filtered = a.useMemo(() =>
    lineFilter === "all" ? results : results.filter(r => r.line === lineFilter),
    [results, lineFilter]
  );

  const handleSearch = (params) => {
    setLoading(true);
    setSearched(true);
    setTimeout(() => {
      const res = Z1_TRAINS.filter(r =>
        params.arr === "foggia" ? r.line === "adriatica" : r.line === "tirrenica"
      );
      setResults(res.length > 0 ? res : Z1_TRAINS);
      setLoading(false);
    }, 800);
  };

  const handleAdd = (train) => {
    addTravelItem({
      id: `train-${train.id}`,
      type: "train",
      provider: "omio",
      train,
      total_price: train.total_price,
    });
    addToast({
      message: `${train.carrier} ${train.train_number} aggiunto al carrello — ${train.total_price}€`,
      type: "success",
    });
    toggleCartSidebar();
  };

  return e.jsxs("div", { className: t, children: [
    e.jsx(V1_TRAINS, { onSearch: handleSearch }),

    searched && !loading && results.length > 0 && e.jsx("div", {
      className: "flex justify-center gap-2 mb-6",
      children: e.jsxs("div", { className: "inline-flex gap-2 p-1.5 rounded-2xl glass-strong", children: [
        e.jsx("button", { onClick: () => setLineFilter("all"), className: "px-4 py-2 rounded-xl text-sm font-semibold transition-all " + (lineFilter === "all" ? "bg-warm-800 text-white shadow-sm" : "text-warm-600 hover:bg-warm-100"), children: "Tutte" }),
        e.jsx("button", { onClick: () => setLineFilter("tirrenica"), className: "px-4 py-2 rounded-xl text-sm font-semibold transition-all " + (lineFilter === "tirrenica" ? "bg-cielo-600 text-white shadow-sm" : "text-warm-600 hover:bg-cielo-50"), children: "🌊 Tirrenica → Napoli" }),
        e.jsx("button", { onClick: () => setLineFilter("adriatica"), className: "px-4 py-2 rounded-xl text-sm font-semibold transition-all " + (lineFilter === "adriatica" ? "bg-energia-600 text-white shadow-sm" : "text-warm-600 hover:bg-energia-50"), children: "🌾 Adriatica → Foggia" }),
      ]}),
    }),

    loading && e.jsx("div", { className: "space-y-4", children: Array.from({length: 3}).map((_, i) =>
      e.jsx("div", { className: "rounded-2xl glass-strong p-6 animate-pulse", children:
        e.jsx("div", { className: "h-20 bg-warm-200 rounded" })
      }, i)
    )}),

    !loading && searched && e.jsx("div", { className: "space-y-4", children:
      filtered.map(train =>
        e.jsxs("div", {
          className: "rounded-2xl glass-strong p-6 flex flex-col md:flex-row md:items-center gap-4",
          children: [
            e.jsxs("div", { className: "flex items-center gap-3 flex-1", children: [
              e.jsx("img", { src: train.carrier_logo, alt: train.carrier, className: "h-8 w-auto object-contain" }),
              e.jsxs("div", { children: [
                e.jsxs("span", { className: "font-display font-bold text-warm-900 text-lg", children: [train.carrier, " ", train.train_number] }),
                e.jsx("span", { className: "ml-2 text-xs px-2 py-0.5 rounded-full font-semibold " + (train.line === "tirrenica" ? "bg-cielo-100 text-cielo-700" : "bg-energia-100 text-energia-700"), children: train.line_label }),
                e.jsxs("div", { className: "text-sm text-warm-600 mt-0.5", children: [
                  train.outbound.departure.station, " → ", train.outbound.arrival.station,
                  " · ", Math.floor(train.outbound.duration_minutes / 60), "h ", train.outbound.duration_minutes % 60, "min",
                  train.outbound.stops === 0 ? " · Diretto" : ` · ${train.outbound.stops} fermate`,
                ]}),
                e.jsx("div", { className: "text-xs text-warm-500 mt-0.5", children: train.note_transfer }),
              ]}),
            ]}),
            e.jsxs("div", { className: "flex items-center gap-4", children: [
              e.jsxs("div", { className: "text-right", children: [
                e.jsxs("div", { className: "text-2xl font-bold text-warm-900", children: ["€", train.total_price] }),
                e.jsx("div", { className: "text-xs text-warm-500", children: "A/R · " + train.class }),
              ]}),
              e.jsx("button", {
                onClick: () => handleAdd(train),
                className: "px-5 py-2.5 bg-ambra-600 hover:bg-ambra-700 text-white rounded-xl font-semibold text-sm transition-colors shadow-sm whitespace-nowrap",
                children: "Aggiungi →",
              }),
            ]}),
          ],
        }, train.id)
      )
    }),
  ]});
}
```

### 3.5 Aggiornare il render di R1 per includere T1

Trovare in `R1` il blocco condizionale:
```js
n === "flights" && e.jsx(H1, {})
n === "transfers" && e.jsx(C1, { boroughName: t })
n === "car_rental" && e.jsx(I1, {})
```

Sostituire con:
```js
n === "flights" && e.jsx(H1, {})
n === "trains" && e.jsx(T1, {})
n === "car_rental" && e.jsx(I1, {})
n === "transfers" && e.jsx(C1, { boroughName: t })
```

---

## 4. Mock Data Summary

### Voli (Z1 — già presente)
- Lufthansa LH1876 MUC → NAP
- Altri voli esistenti nel bundle

### Treni (Z1_TRAINS — nuovo)
**Linea Tirrenica → Napoli Centrale:**
- FR 9601: Roma Termini → Napoli C. 07:00–08:08 (1h 08m, €58 A/R)
- ITA 8901: Milano C. → Napoli C. 06:25–10:35 (4h 10m, €98 A/R)
- FR 9605: Milano C. → Napoli C. 07:00–11:15 (4h 15m, €110 A/R)

**Linea Adriatica → Foggia:**
- FR 9701: Roma Termini → Foggia 08:05–10:30 (2h 25m, €70 A/R)
- ITA 8701: Milano C. → Foggia 07:10–12:05 (4h 55m, €118 A/R)
- REG 5401: Bari C. → Foggia 09:15–10:20 (1h 05m, €18 A/R)

### Auto (D1 — già presente)
- Dati noleggio auto esistenti nel bundle

### Transfer (k1 — già presente)
- Transfer privati esistenti nel bundle

---

## 5. Note Transfer dopo il treno

| Arrivo | Distanza da Lacedonia | Tempo | Via |
|--------|----------------------|-------|-----|
| Napoli Centrale | ~90 km | ~1h 15m | A16 Napoli-Canosa, uscita Lacedonia |
| Foggia | ~40 km | ~45 min | SS655 Bradanica |

Il tab Transfer (C1) copre questa ultima tratta. Il testo di ogni card treno include `note_transfer` con l'indicazione.

---

## 6. Sequenza Tab Finale

```
R1 (sezione "Organizza il viaggio")
├── Tab 1: ✈️ Voli        (H1) — Amadeus mock, MUC/FCO → NAP
├── Tab 2: 🚂 Treni       (T1) — Omio mock, linea Tirrenica→NAP + Adriatica→FOG  ← NUOVO
├── Tab 3: 🚗 Auto        (I1) — Rentalcars mock, noleggio da NAP/FOG
└── Tab 4: 🚐 Transfer    (C1) — Transfer privati NAP/FOG → Lacedonia
```

---

## 7. File da Non Toccare

- `assets/index-Bhg8UQGm.js` — entry point React
- `assets/vendor-*.js` — librerie
- `api/v1/bookings.php` — API booking (già funzionante, non coinvolta in questa demo)
- Tutti gli altri `borghi/*/index.html` — solo Lacedonia riceve questo widget

---

## 8. Rischi e Mitigazioni

| Rischio | Mitigazione |
|---------|-------------|
| Ricompilazione bundle sovrascrive modifiche | Backup `.bak` + documentare le modifiche in questo spec |
| Icona treno non disponibile in phosphor-icons | Usare `Train`, `TrainSimple`, o SVG inline fallback |
| Icona treno non importata nel bundle | Verificare presenza di `Train`/`TrainSimple` phosphor; fallback: emoji 🚂 come testo nel `icon` field |
| `addTravelItem` store non aggiornato per tipo `train` | Verificare che lo Zustand store accetti tipi arbitrari — pattern esistente lo supporta |

---

## 9. Prossimi Moduli (fuori scope di questo spec)

- **Modulo C:** CX Genie assistente (embed widget — richiede credenziali)
- **Modulo D:** Avatar lip sync (D-ID / HeyGen / Tavus — richiede API key)
