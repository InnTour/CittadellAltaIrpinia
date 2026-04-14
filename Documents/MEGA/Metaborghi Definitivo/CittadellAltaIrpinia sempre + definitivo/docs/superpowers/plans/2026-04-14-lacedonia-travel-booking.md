# Lacedonia Travel Booking Widget — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Sostituire le due card placeholder "Presto disponibile" nella scheda Lacedonia con il widget travel booking a 6 tab: Voli · Treni · Bus · Carpooling · Auto · Transfer.

**Architecture:** Edit chirurgico del bundle React compilato `assets/BoroughDetailPage-DmXnedyp.js`. Il componente `R1` esiste già nel bundle ma non è mai chiamato — va sbloccato. I nuovi componenti `T1` (Treni), `B1_BUS` (BlaBlaCar Bus) e `B1_CARPOOL` (BlaBlaCar Carpooling) vengono inseriti prima di `function R1(` con mock data inline. Nessuna ricompilazione Vite necessaria.

**Tech Stack:** React 18 (già nel bundle), JSX compiled (`e.jsx`/`e.jsxs`), Zustand store (`D`/`M`), Tailwind CSS tokens (`ambra-*`, `cielo-*`, `energia-*`, `natura-*`), colori BlaBlaCar inline (`#00D084`, `#009966`).

---

## File Map

| File | Azione | Righe coinvolte |
|------|--------|-----------------|
| `assets/BoroughDetailPage-DmXnedyp.js` | **Modify** — 5 edit chirurgici | 4092–4113, 4114, 4170–4173, 5046–5095 |
| `assets/BoroughDetailPage-DmXnedyp.js.bak` | **Create** — backup prima di tutto | — |

**Nessun altro file viene toccato.** `borghi/lacedonia/index.html` NON richiede modifiche (carica il bundle via `<script>`).

---

## Task 1: Backup obbligatorio

**Files:**
- Create: `assets/BoroughDetailPage-DmXnedyp.js.bak`

- [ ] **Step 1.1: Crea il backup**

```bash
cp "assets/BoroughDetailPage-DmXnedyp.js" "assets/BoroughDetailPage-DmXnedyp.js.bak"
```

- [ ] **Step 1.2: Verifica che il backup esista e sia identico**

```bash
diff assets/BoroughDetailPage-DmXnedyp.js assets/BoroughDetailPage-DmXnedyp.js.bak
```

Output atteso: nessun output (file identici).

- [ ] **Step 1.3: Verifica che `function R1(` esista nel bundle**

```bash
grep -n "function R1(" assets/BoroughDetailPage-DmXnedyp.js
```

Output atteso: `4115:function R1({ boroughName: t = "Nusco", className: s }) {`
(numero riga potrebbe variare leggermente — annota il valore per i task successivi)

- [ ] **Step 1.4: Verifica che le 2 card placeholder siano presenti**

```bash
grep -n "border-dashed border-ambra-200" assets/BoroughDetailPage-DmXnedyp.js
```

Output atteso: una riga intorno alla 5052.

- [ ] **Step 1.5: Commit del backup**

```bash
git add assets/BoroughDetailPage-DmXnedyp.js.bak
git commit -m "chore: backup bundle prima degli edit chirurgici travel booking"
```

---

## Task 2: Inserire componenti T1, B1_BUS, B1_CARPOOL prima di R1

**Files:**
- Modify: `assets/BoroughDetailPage-DmXnedyp.js:4114` (inserimento prima di `function R1(`)

Questi componenti vanno inseriti prima di `function R1(` in modo che R1 possa usarli nei suoi render. L'ordine di inserimento: prima `Z1_TRAINS + V1_TRAINS + T1`, poi `B1_BUS_DATA + B1_CARPOOL_DATA + B1_BUS + B1_CARPOOL`.

- [ ] **Step 2.1: Verifica l'ancora di inserimento**

```bash
grep -n "function R1(" assets/BoroughDetailPage-DmXnedyp.js
```

La riga trovata (es. `4115`) è il punto **esatto** dove inserire il blocco — il nuovo codice va inserito **prima** di questa riga.

- [ ] **Step 2.2: Inserisci Z1_TRAINS, V1_TRAINS e T1**

Usa il tool Edit: trova la stringa esatta `function R1({ boroughName: t = "Nusco", className: s }) {` e sostituiscila con il blocco seguente (il codice T1 + la stessa function R1 a seguire):

```js
const Z1_TRAINS = [
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
function V1_TRAINS({ onSearch: r }) {
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
          ] }),
          e.jsxs("div", { children: [
            e.jsx("label", { className: "block text-xs font-semibold text-warm-600 mb-1 uppercase tracking-wide", children: "Arrivo" }),
            e.jsxs("select", { value: arr, onChange: x => setArr(x.target.value), className: "w-full border border-warm-300 rounded-xl px-3 py-2.5 text-sm text-warm-800 bg-white focus:ring-2 focus:ring-ambra-400 outline-none", children: [
              e.jsx("option", { value: "napoli", children: "🌊 Napoli Centrale (Tirrenica)" }),
              e.jsx("option", { value: "foggia", children: "🌾 Foggia (Adriatica)" }),
            ] }),
          ] }),
          e.jsxs("div", { children: [
            e.jsx("label", { className: "block text-xs font-semibold text-warm-600 mb-1 uppercase tracking-wide", children: "Data" }),
            e.jsx("input", { type: "date", value: date, onChange: x => setDate(x.target.value), className: "w-full border border-warm-300 rounded-xl px-3 py-2.5 text-sm text-warm-800 bg-white focus:ring-2 focus:ring-ambra-400 outline-none" }),
          ] }),
          e.jsxs("div", { children: [
            e.jsx("label", { className: "block text-xs font-semibold text-warm-600 mb-1 uppercase tracking-wide", children: "Passeggeri" }),
            e.jsx("input", { type: "number", min: 1, max: 9, value: guests, onChange: x => setGuests(Number(x.target.value)), className: "w-full border border-warm-300 rounded-xl px-3 py-2.5 text-sm text-warm-800 bg-white focus:ring-2 focus:ring-ambra-400 outline-none" }),
          ] }),
        ],
      }),
      e.jsx("button", {
        onClick: () => r({ dep, arr, date, guests }),
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
      id: "train-" + train.id,
      type: "train",
      provider: "omio",
      train,
      total_price: train.total_price,
    });
    addToast({ message: train.carrier + " " + train.train_number + " aggiunto al carrello — " + train.total_price + "€", type: "success" });
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
      ] }),
    }),
    loading && e.jsx("div", { className: "space-y-4", children: [0, 1, 2].map((_, i) =>
      e.jsx("div", { className: "rounded-2xl glass-strong p-6 animate-pulse", children: e.jsx("div", { className: "h-20 bg-warm-200 rounded" }) }, i)
    ) }),
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
                  train.outbound.stops === 0 ? " · Diretto" : " · " + train.outbound.stops + " fermate",
                ] }),
                e.jsx("div", { className: "text-xs text-warm-500 mt-0.5", children: train.note_transfer }),
              ] }),
            ] }),
            e.jsxs("div", { className: "flex items-center gap-4", children: [
              e.jsxs("div", { className: "text-right", children: [
                e.jsxs("div", { className: "text-2xl font-bold text-warm-900", children: ["€", train.total_price] }),
                e.jsx("div", { className: "text-xs text-warm-500", children: "A/R · " + train.class }),
              ] }),
              e.jsx("button", {
                onClick: () => handleAdd(train),
                className: "px-5 py-2.5 bg-ambra-600 hover:bg-ambra-700 text-white rounded-xl font-semibold text-sm transition-colors shadow-sm whitespace-nowrap",
                children: "Aggiungi →",
              }),
            ] }),
          ],
        }, train.id)
      )
    }),
  ] });
}
const B1_BUS_DATA = [
  {
    id: "bbc-bus-001",
    operator: "BlaBlaCar Bus",
    type: "SCHEDULED",
    origin: { address: "Napoli, Piazza Garibaldi", lat: 40.8518, lng: 14.2681 },
    destination: { address: "Lacedonia area, SS7", lat: 41.0529, lng: 15.5672 },
    departure_date: "2026-09-10T08:30:00",
    arrival_date: "2026-09-10T10:15:00",
    duration_minutes: 105,
    seats: { available: 6, total: 24 },
    price: { amount: 9, currency: "EUR" },
    booking_url: "https://www.blablacar.it/bus",
  },
  {
    id: "bbc-bus-002",
    operator: "BlaBlaCar Bus",
    type: "SCHEDULED",
    origin: { address: "Roma, Tiburtina", lat: 41.9028, lng: 12.5234 },
    destination: { address: "Foggia, Stazione", lat: 41.4636, lng: 15.5444 },
    departure_date: "2026-09-10T07:00:00",
    arrival_date: "2026-09-10T11:30:00",
    duration_minutes: 270,
    seats: { available: 2, total: 24 },
    price: { amount: 12, currency: "EUR" },
    booking_url: "https://www.blablacar.it/bus",
  },
  {
    id: "bbc-bus-003",
    operator: "BlaBlaCar Bus",
    type: "SCHEDULED",
    origin: { address: "Milano, Lampugnano", lat: 45.4654, lng: 9.1859 },
    destination: { address: "Napoli, Piazza Garibaldi", lat: 40.8518, lng: 14.2681 },
    departure_date: "2026-09-10T06:00:00",
    arrival_date: "2026-09-10T14:00:00",
    duration_minutes: 480,
    seats: { available: 12, total: 24 },
    price: { amount: 19, currency: "EUR" },
    booking_url: "https://www.blablacar.it/bus",
  },
];
const B1_CARPOOL_DATA = [
  {
    id: "bbc-cp-001",
    operator: "BlaBlaCar Daily",
    type: "DYNAMIC",
    origin: { address: "Napoli, Centrale", lat: 40.8518, lng: 14.2681 },
    destination: { address: "Lacedonia", lat: 41.0529, lng: 15.5672 },
    departure_date: "2026-09-10T07:45:00",
    arrival_date: "2026-09-10T09:15:00",
    duration_minutes: 90,
    seats: { available: 2, total: 3 },
    price: { amount: 7, currency: "EUR" },
    driver: { alias: "Marco R.", rating: 4.9, trips: 127 },
    booking_url: "https://www.blablacar.it",
  },
  {
    id: "bbc-cp-002",
    operator: "BlaBlaCar Daily",
    type: "DYNAMIC",
    origin: { address: "Foggia, centro", lat: 41.4636, lng: 15.5444 },
    destination: { address: "Lacedonia", lat: 41.0529, lng: 15.5672 },
    departure_date: "2026-09-10T08:00:00",
    arrival_date: "2026-09-10T08:50:00",
    duration_minutes: 50,
    seats: { available: 1, total: 4 },
    price: { amount: 4, currency: "EUR" },
    driver: { alias: "Giovanni S.", rating: 4.7, trips: 54 },
    booking_url: "https://www.blablacar.it",
  },
  {
    id: "bbc-cp-003",
    operator: "BlaBlaCar Daily",
    type: "DYNAMIC",
    origin: { address: "Avellino, centro", lat: 40.9145, lng: 14.7905 },
    destination: { address: "Lacedonia", lat: 41.0529, lng: 15.5672 },
    departure_date: "2026-09-10T09:00:00",
    arrival_date: "2026-09-10T10:05:00",
    duration_minutes: 65,
    seats: { available: 3, total: 4 },
    price: { amount: 5, currency: "EUR" },
    driver: { alias: "Sofia L.", rating: 5.0, trips: 211 },
    booking_url: "https://www.blablacar.it",
  },
];
function B1_BUS() {
  const [results, setResults] = a.useState([]);
  const [loading, setLoading] = a.useState(false);
  const [searched, setSearched] = a.useState(false);
  const handleSearch = () => {
    setLoading(true);
    setSearched(true);
    setTimeout(() => { setResults(B1_BUS_DATA); setLoading(false); }, 800);
  };
  return e.jsxs("div", { children: [
    e.jsxs("div", { className: "glass-strong rounded-2xl p-6 mb-8", children: [
      e.jsx("p", { className: "text-sm text-warm-600 mb-4", children: "Cerca bus intercity BlaBlaCar verso Alta Irpinia (da Napoli · Foggia · Roma · Milano)" }),
      e.jsx("button", {
        onClick: handleSearch,
        className: "w-full py-3 rounded-xl font-semibold text-white transition-colors shadow-sm",
        style: { background: "#00D084" },
        children: "Cerca bus BlaBlaCar",
      }),
    ] }),
    loading && e.jsx("div", { className: "space-y-4", children: [0, 1, 2].map((_, i) =>
      e.jsx("div", { className: "rounded-2xl glass-strong p-6 animate-pulse", children: e.jsx("div", { className: "h-16 bg-warm-200 rounded" }) }, i)
    ) }),
    !loading && searched && e.jsx("div", { className: "space-y-4", children:
      results.map(j =>
        e.jsxs("div", {
          className: "rounded-2xl glass-strong p-6 flex flex-col md:flex-row md:items-center gap-4",
          children: [
            e.jsxs("div", { className: "flex-1", children: [
              e.jsx("span", { className: "text-xs px-2 py-0.5 rounded-full font-semibold text-white mr-2", style: { background: "#00D084" }, children: "🚌 Bus" }),
              e.jsx("span", { className: "font-bold text-warm-900", children: j.operator }),
              e.jsxs("div", { className: "text-sm text-warm-700 mt-1", children: [j.origin.address, " → ", j.destination.address] }),
              e.jsxs("div", { className: "text-xs text-warm-500 mt-0.5", children: [
                new Date(j.departure_date).toLocaleTimeString("it", { hour: "2-digit", minute: "2-digit" }),
                " → ",
                new Date(j.arrival_date).toLocaleTimeString("it", { hour: "2-digit", minute: "2-digit" }),
                " · ", Math.floor(j.duration_minutes / 60), "h",
                j.duration_minutes % 60 ? " " + (j.duration_minutes % 60) + "min" : "",
                " · ", j.seats.available, "/", j.seats.total, " posti",
              ] }),
            ] }),
            e.jsxs("div", { className: "flex items-center gap-4", children: [
              e.jsxs("div", { className: "text-right", children: [
                e.jsxs("div", { className: "text-2xl font-bold text-warm-900", children: ["€", j.price.amount] }),
                e.jsx("div", { className: "text-xs text-warm-500", children: "per persona" }),
              ] }),
              e.jsx("a", {
                href: j.booking_url, target: "_blank", rel: "noopener noreferrer",
                className: "px-5 py-2.5 rounded-xl font-semibold text-sm text-white transition-opacity hover:opacity-90 shadow-sm whitespace-nowrap",
                style: { background: "#00D084" },
                children: "Prenota su BlaBlaCar →",
              }),
            ] }),
          ],
        }, j.id)
      )
    }),
  ] });
}
function B1_CARPOOL() {
  const [results, setResults] = a.useState([]);
  const [loading, setLoading] = a.useState(false);
  const [searched, setSearched] = a.useState(false);
  const handleSearch = () => {
    setLoading(true);
    setSearched(true);
    setTimeout(() => { setResults(B1_CARPOOL_DATA); setLoading(false); }, 800);
  };
  return e.jsxs("div", { children: [
    e.jsxs("div", { className: "glass-strong rounded-2xl p-6 mb-8", children: [
      e.jsx("p", { className: "text-sm text-warm-600 mb-4", children: "Cerca passaggi in carpooling verso Lacedonia (BlaBlaCar Daily)" }),
      e.jsx("button", {
        onClick: handleSearch,
        className: "w-full py-3 rounded-xl font-semibold text-white transition-colors shadow-sm",
        style: { background: "#009966" },
        children: "Cerca passaggi",
      }),
    ] }),
    loading && e.jsx("div", { className: "space-y-4", children: [0, 1, 2].map((_, i) =>
      e.jsx("div", { className: "rounded-2xl glass-strong p-6 animate-pulse", children: e.jsx("div", { className: "h-16 bg-warm-200 rounded" }) }, i)
    ) }),
    !loading && searched && e.jsx("div", { className: "space-y-4", children:
      results.map(j =>
        e.jsxs("div", {
          className: "rounded-2xl glass-strong p-6 flex flex-col md:flex-row md:items-center gap-4",
          children: [
            e.jsxs("div", { className: "flex-1", children: [
              e.jsx("span", { className: "text-xs px-2 py-0.5 rounded-full font-semibold text-white mr-2", style: { background: "#009966" }, children: "🚗 Carpooling" }),
              e.jsxs("span", { className: "font-bold text-warm-900", children: [j.driver.alias, " ⭐ ", j.driver.rating] }),
              e.jsxs("div", { className: "text-sm text-warm-700 mt-1", children: [j.origin.address, " → ", j.destination.address] }),
              e.jsxs("div", { className: "text-xs text-warm-500 mt-0.5", children: [
                new Date(j.departure_date).toLocaleTimeString("it", { hour: "2-digit", minute: "2-digit" }),
                " → ",
                new Date(j.arrival_date).toLocaleTimeString("it", { hour: "2-digit", minute: "2-digit" }),
                " · ", j.seats.available, " posti disponibili",
              ] }),
            ] }),
            e.jsxs("div", { className: "flex items-center gap-4", children: [
              e.jsxs("div", { className: "text-right", children: [
                e.jsxs("div", { className: "text-2xl font-bold text-warm-900", children: ["€", j.price.amount] }),
                e.jsx("div", { className: "text-xs text-warm-500", children: "per persona" }),
              ] }),
              e.jsx("a", {
                href: j.booking_url, target: "_blank", rel: "noopener noreferrer",
                className: "px-5 py-2.5 rounded-xl font-semibold text-sm text-white transition-opacity hover:opacity-90 shadow-sm whitespace-nowrap",
                style: { background: "#009966" },
                children: "Prenota su BlaBlaCar →",
              }),
            ] }),
          ],
        }, j.id)
      )
    }),
  ] });
}
function R1({ boroughName: t = "Nusco", className: s }) {
```

> **Nota tecnica:** L'old_string del tool Edit deve essere **solo** `function R1({ boroughName: t = "Nusco", className: s }) {`. Il new_string è tutto il blocco sopra (da `const Z1_TRAINS = [` fino a `function R1({ boroughName: t = "Nusco", className: s }) {` inclusa). In questo modo la firma di R1 rimane intatta a fine blocco.

- [ ] **Step 2.3: Verifica inserimento T1**

```bash
grep -n "function T1(" assets/BoroughDetailPage-DmXnedyp.js
```

Output atteso: una riga con numero < 4300.

- [ ] **Step 2.4: Verifica inserimento B1_BUS**

```bash
grep -n "function B1_BUS(" assets/BoroughDetailPage-DmXnedyp.js
grep -n "function B1_CARPOOL(" assets/BoroughDetailPage-DmXnedyp.js
```

Output atteso: entrambe trovate, numeri di riga crescenti (B1_BUS prima, B1_CARPOOL dopo, entrambe prima di R1).

- [ ] **Step 2.5: Commit intermedio**

```bash
git add assets/BoroughDetailPage-DmXnedyp.js
git commit -m "feat: inserisce T1 (Treni), B1_BUS e B1_CARPOOL nel bundle"
```

---

## Task 3: Aggiornare P1 (array tab)

**Files:**
- Modify: `assets/BoroughDetailPage-DmXnedyp.js:4092–4113`

Il `P1` attuale ha 3 tab: `flights`, `transfers`, `car_rental`. Va sostituito con 6 tab nell'ordine: `flights`, `trains`, `blablacar_bus`, `blablacar_carpool`, `car_rental`, `transfers`.

- [ ] **Step 3.1: Verifica l'ancora P1**

```bash
grep -n "const P1 = \[" assets/BoroughDetailPage-DmXnedyp.js
```

Output atteso: una riga con numero intorno a 4092 (il numero esatto dipende dall'inserimento del Task 2 — annota il nuovo numero).

- [ ] **Step 3.2: Sostituisci P1**

Usa il tool Edit. Old string (esatta, whitespace incluso — 3 tab attuali):

```js
const P1 = [
  {
    id: "flights",
    label: "Voli",
    icon: e.jsx(C, { size: 20 }),
    color: "text-warm-600 hover:text-cielo-600 hover:bg-cielo-50",
    activeColor: "text-cielo-700 bg-cielo-100 shadow-sm",
  },
  {
    id: "transfers",
    label: "Transfer",
    icon: e.jsx(S, { size: 20 }),
    color: "text-warm-600 hover:text-natura-600 hover:bg-natura-50",
    activeColor: "text-natura-700 bg-natura-100 shadow-sm",
  },
  {
    id: "car_rental",
    label: "Noleggio auto",
    icon: e.jsx(k, { size: 20 }),
    color: "text-warm-600 hover:text-energia-600 hover:bg-energia-50",
    activeColor: "text-energia-700 bg-energia-100 shadow-sm",
  },
];
```

New string (6 tab):

```js
const P1 = [
  {
    id: "flights",
    label: "Voli",
    icon: e.jsx(C, { size: 20 }),
    color: "text-warm-600 hover:text-cielo-600 hover:bg-cielo-50",
    activeColor: "text-cielo-700 bg-cielo-100 shadow-sm",
  },
  {
    id: "trains",
    label: "Treni",
    icon: e.jsx("span", { children: "🚂" }),
    color: "text-warm-600 hover:text-ambra-600 hover:bg-ambra-50",
    activeColor: "text-ambra-700 bg-ambra-100 shadow-sm",
  },
  {
    id: "blablacar_bus",
    label: "Bus",
    icon: e.jsx("span", { children: "🚌" }),
    color: "text-warm-600 hover:bg-[#00D084]/10",
    activeColor: "text-white bg-[#00D084] shadow-sm",
  },
  {
    id: "blablacar_carpool",
    label: "Carpooling",
    icon: e.jsx("span", { children: "🚗" }),
    color: "text-warm-600 hover:bg-[#009966]/10",
    activeColor: "text-white bg-[#009966] shadow-sm",
  },
  {
    id: "car_rental",
    label: "Noleggio auto",
    icon: e.jsx(k, { size: 20 }),
    color: "text-warm-600 hover:text-energia-600 hover:bg-energia-50",
    activeColor: "text-energia-700 bg-energia-100 shadow-sm",
  },
  {
    id: "transfers",
    label: "Transfer",
    icon: e.jsx(S, { size: 20 }),
    color: "text-warm-600 hover:text-natura-600 hover:bg-natura-50",
    activeColor: "text-natura-700 bg-natura-100 shadow-sm",
  },
];
```

- [ ] **Step 3.3: Verifica il conteggio tab**

```bash
grep -c '"id": "' assets/BoroughDetailPage-DmXnedyp.js
grep -o '"blablacar_bus"' assets/BoroughDetailPage-DmXnedyp.js
grep -o '"blablacar_carpool"' assets/BoroughDetailPage-DmXnedyp.js
grep -o '"trains"' assets/BoroughDetailPage-DmXnedyp.js
```

Output atteso per gli ultimi 3: una occorrenza ciascuno (quella in P1 + quella nel render di R1 che aggiungeremo al Task 4).

- [ ] **Step 3.4: Commit**

```bash
git add assets/BoroughDetailPage-DmXnedyp.js
git commit -m "feat: P1 aggiornato a 6 tab — trains + blablacar_bus + blablacar_carpool"
```

---

## Task 4: Aggiornare render conditions in R1

**Files:**
- Modify: `assets/BoroughDetailPage-DmXnedyp.js` (blocco condizionale in R1)

- [ ] **Step 4.1: Verifica l'ancora render**

```bash
grep -n 'n === "flights"' assets/BoroughDetailPage-DmXnedyp.js
```

Output atteso: una riga intorno a 4170 (il numero è cambiato dopo i task precedenti).

- [ ] **Step 4.2: Sostituisci le render conditions**

Old string (3 righe esatte):

```js
            n === "flights" && e.jsx(H1, {}),
            n === "transfers" && e.jsx(C1, { boroughName: t }),
            n === "car_rental" && e.jsx(I1, {}),
```

New string (6 condizioni):

```js
            n === "flights" && e.jsx(H1, {}),
            n === "trains" && e.jsx(T1, {}),
            n === "blablacar_bus" && e.jsx(B1_BUS, {}),
            n === "blablacar_carpool" && e.jsx(B1_CARPOOL, {}),
            n === "car_rental" && e.jsx(I1, {}),
            n === "transfers" && e.jsx(C1, { boroughName: t }),
```

- [ ] **Step 4.3: Verifica le 6 condizioni**

```bash
grep -A 8 'n === "flights"' assets/BoroughDetailPage-DmXnedyp.js
```

Output atteso: le 6 righe condizionali in sequenza.

- [ ] **Step 4.4: Commit**

```bash
git add assets/BoroughDetailPage-DmXnedyp.js
git commit -m "feat: R1 render conditions aggiornate per 6 tab"
```

---

## Task 5: Attivare R1 (sostituire le 2 card placeholder)

**Files:**
- Modify: `assets/BoroughDetailPage-DmXnedyp.js:5046–5095` (le righe esatte dipenderanno dagli inserimenti precedenti)

Questo è il "abbattimento del muro": sostituiamo le 2 card dashed `section.mb-12` con `e.jsx(R1, { boroughName: s?.name || "Lacedonia" })`.

- [ ] **Step 5.1: Trova le righe esatte della section placeholder**

```bash
grep -n "border-dashed border-ambra-200" assets/BoroughDetailPage-DmXnedyp.js
```

Annota il numero di riga. La `section.mb-12` padre inizia circa 6 righe prima.

- [ ] **Step 5.2: Sostituisci la section con R1**

Old string (inizio e fine unici nel file — usa questi come delimitatori):

```js
          e.jsx("section", {
            className: "mb-12",
            children: e.jsxs("div", {
              className: "grid md:grid-cols-2 gap-8",
              children: [
                e.jsxs("div", {
                  className: "glass-strong rounded-2xl p-8 md:p-10 text-center border-2 border-dashed border-ambra-200",
                  children: [
                    e.jsx("div", {
                      className: "flex items-center justify-center w-14 h-14 rounded-2xl bg-ambra-100 text-ambra-600 mx-auto mb-4",
                      children: e.jsx(H, { size: 28, weight: "duotone" }),
                    }),
                    e.jsx("h3", {
                      className: "font-display text-lg font-bold text-warm-900 mb-2",
                      children: "Prenota il tuo soggiorno",
                    }),
                    e.jsx("p", {
                      className: "text-warm-500 text-sm mb-4",
                      children: "Prenotazione diretta con le strutture del borgo",
                    }),
                    e.jsx("span", {
                      className: "inline-flex items-center gap-2 px-5 py-2.5 bg-ambra-50 text-ambra-600 font-semibold rounded-full text-sm border border-ambra-200",
                      children: "Presto disponibile",
                    }),
                  ],
                }),
                e.jsxs("div", {
                  className: "glass-strong rounded-2xl p-8 md:p-10 text-center border-2 border-dashed border-natura-200",
                  children: [
                    e.jsx("div", {
                      className: "flex items-center justify-center w-14 h-14 rounded-2xl bg-natura-100 text-natura-600 mx-auto mb-4",
                      children: e.jsx(V0, { size: 28, weight: "duotone" }),
                    }),
                    e.jsx("h3", {
                      className: "font-display text-lg font-bold text-warm-900 mb-2",
                      children: "Organizza il viaggio",
                    }),
                    e.jsx("p", {
                      className: "text-warm-500 text-sm mb-4",
                      children: "Voli, transfer e noleggio auto per il borgo",
                    }),
                    e.jsx("span", {
                      className: "inline-flex items-center gap-2 px-5 py-2.5 bg-natura-50 text-natura-600 font-semibold rounded-full text-sm border border-natura-200",
                      children: "Presto disponibile",
                    }),
                  ],
                }),
              ],
            }),
          }),
```

New string (una sola riga che chiama R1):

```js
          e.jsx(R1, { boroughName: s?.name || "Lacedonia" }),
```

- [ ] **Step 5.3: Verifica che le card dashed siano sparite**

```bash
grep -c "border-dashed border-ambra-200" assets/BoroughDetailPage-DmXnedyp.js
```

Output atteso: `0`

- [ ] **Step 5.4: Verifica che R1 sia ora chiamato**

```bash
grep -n "e.jsx(R1" assets/BoroughDetailPage-DmXnedyp.js
```

Output atteso: una riga con `e.jsx(R1, { boroughName: s?.name || "Lacedonia" })`.

- [ ] **Step 5.5: Commit**

```bash
git add assets/BoroughDetailPage-DmXnedyp.js
git commit -m "feat: attiva R1 — sostituisce 2 card placeholder con widget travel booking"
```

---

## Task 6: Verifica manuale in browser

Non esiste un test runner per il bundle compilato. La verifica è manuale in browser. I passi seguenti vanno eseguiti su `metaborghi.org/borghi/lacedonia` (o server locale se disponibile).

- [ ] **Step 6.1: Apri la scheda Lacedonia**

Naviga a `metaborghi.org/borghi/lacedonia`. Scorri fino in fondo alla pagina.

Atteso: compare la sezione "Come arrivare a Lacedonia" (non le 2 card grigie "Presto disponibile").

- [ ] **Step 6.2: Verifica tab bar**

Atteso: 6 tab visibili — ✈️ Voli · 🚂 Treni · 🚌 Bus · 🚗 Carpooling · 🚙 Noleggio auto · 🚐 Transfer.

- [ ] **Step 6.3: Tab Treni — cerca Tirrenica**

Clicca "🚂 Treni". Lascia "Napoli Centrale" selezionato nel dropdown Arrivo. Clicca "Cerca treni".

Atteso dopo ~800ms: 3 card (FR 9601, ITA 8901, FR 9605) con badge "Linea Tirrenica". Clicca "Aggiungi →" su uno — il toast appare e il carrello si apre.

- [ ] **Step 6.4: Tab Treni — cerca Adriatica**

Seleziona "🌾 Foggia (Adriatica)" nel dropdown. Clicca "Cerca treni".

Atteso: 3 card (FR 9701, ITA 8701, REG 5401) con badge "Linea Adriatica".

- [ ] **Step 6.5: Tab Bus**

Clicca "🚌 Bus". Clicca "Cerca bus BlaBlaCar".

Atteso dopo ~800ms: 3 card bus con badge verde "🚌 Bus" e bottone "Prenota su BlaBlaCar →" che apre `blablacar.it/bus` in nuova tab.

- [ ] **Step 6.6: Tab Carpooling**

Clicca "🚗 Carpooling". Clicca "Cerca passaggi".

Atteso dopo ~800ms: 3 card con driver alias, rating ⭐, posti disponibili e bottone "Prenota su BlaBlaCar →" che apre `blablacar.it` in nuova tab.

- [ ] **Step 6.7: Tab Voli, Auto, Transfer**

Clicca ciascuno dei 3 tab rimanenti. Atteso: funzionano come prima (mock data esistenti).

- [ ] **Step 6.8: Verifica mobile (viewport 375px)**

Rimpicciolisci la finestra o usa DevTools mobile. Atteso: i tab scrollano orizzontalmente senza rompersi. Le card si staccano in colonna verticale.

---

## Task 7: Push e cleanup

- [ ] **Step 7.1: Verifica git log**

```bash
git log --oneline -6
```

Output atteso: i 4 commit di questa implementazione + il commit del backup.

- [ ] **Step 7.2: Push sul branch**

```bash
git push -u origin claude/filter-lacedonia-municipality-azcGs
```

- [ ] **Step 7.3: (opzionale) Rimuovi il backup se tutto funziona**

Lasciare il `.bak` per sicurezza durante il periodo di test. Rimuoverlo solo quando il widget è validato in produzione:

```bash
# Solo dopo validazione completa in produzione:
# git rm assets/BoroughDetailPage-DmXnedyp.js.bak
# git commit -m "chore: rimuove backup bundle post-validazione"
```

---

## Self-Review

**Spec coverage:**

| Requisito spec | Task che lo implementa |
|---|---|
| Backup obbligatorio | Task 1 |
| Sostituire 2 card placeholder con R1 | Task 5 |
| Aggiungere tab Treni (ambra) con Z1_TRAINS | Task 2 (T1) + Task 3 (P1) + Task 4 (render) |
| Aggiungere tab Bus BlaBlaCar (#00D084) con B1_BUS_DATA | Task 2 (B1_BUS) + Task 3 + Task 4 |
| Aggiungere tab Carpooling BlaBlaCar (#009966) con B1_CARPOOL_DATA | Task 2 (B1_CARPOOL) + Task 3 + Task 4 |
| Ordine 6 tab: flights→trains→bus→carpool→car_rental→transfers | Task 3 |
| Booking BlaBlaCar via link esterno (no in-app) | Task 2 (tag `<a>` con `target="_blank"`) |
| Addtocart per Treni via Zustand | Task 2 (handleAdd in T1) |
| Note transfer post-treno (45min/75min) | Task 2 (campo `note_transfer` in Z1_TRAINS) |
| Verifica manuale browser | Task 6 |

**Placeholder scan:** nessun TBD/TODO presente — tutti gli step hanno codice completo.

**Type consistency:** 
- `Z1_TRAINS` usato solo in `T1` — coerente.
- `B1_BUS_DATA` usato solo in `B1_BUS` — coerente.
- `B1_CARPOOL_DATA` usato solo in `B1_CARPOOL` — coerente.
- `D` (Zustand cart store) e `M` (Zustand UI store) usati solo in `T1` — stesso pattern di `H1`, `C1`, `I1` già nel bundle.
- `a` (alias React) — stesso pattern esistente nel bundle.

---

**Piano salvato e committato.** Due opzioni per l'esecuzione:

**1. Subagent-Driven (raccomandato)** — subagent fresco per ogni task, revisione tra un task e l'altro, iterazione veloce

**2. Inline Execution** — esecuzione in questa sessione con executing-plans, checkpoint di revisione a ogni task

Quale preferisci?
