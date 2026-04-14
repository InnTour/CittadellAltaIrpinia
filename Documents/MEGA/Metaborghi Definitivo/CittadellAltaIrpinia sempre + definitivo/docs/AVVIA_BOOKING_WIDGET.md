# Avvio sessione — Travel Booking Widget Lacedonia

Incolla questo messaggio all'inizio della prossima sessione Claude Code:

---

```
/metaborghi-fullstack

Riprendi l'implementazione del travel booking widget per la scheda Lacedonia.

Il piano è già scritto e committato in:
  docs/superpowers/plans/2026-04-14-lacedonia-travel-booking.md

La spec di design è in:
  docs/superpowers/specs/2026-04-14-lacedonia-travel-booking-design.md

Usa la skill superpowers:subagent-driven-development per eseguire il piano task per task.
Per ogni subagent che dispatchi, includi nel prompt il contesto MetaBorghi rilevante
(pattern bundle compilato: variabili a=React, e=jsx, D=cart store, M=ui store;
token Tailwind: ambra-*, cielo-*, energia-*, natura-*; file target:
assets/BoroughDetailPage-DmXnedyp.js).

Il piano ha 7 task:
  Task 1 — Backup obbligatorio del bundle
  Task 2 — Inserisce T1 (Treni) + B1_BUS + B1_CARPOOL prima di function R1(
  Task 3 — Aggiorna P1 da 3 a 6 tab (flights→trains→blablacar_bus→blablacar_carpool→car_rental→transfers)
  Task 4 — Aggiorna render conditions in R1 per tutti e 6 i tab
  Task 5 — Attiva R1: sostituisce le 2 card placeholder "Presto disponibile" con e.jsx(R1, { boroughName: s?.name || "Lacedonia" })
  Task 6 — Verifica manuale in browser (6 tab, mock data, link BlaBlaCar)
  Task 7 — Push su claude/filter-lacedonia-municipality-azcGs

Branch attivo: claude/filter-lacedonia-municipality-azcGs
File target: assets/BoroughDetailPage-DmXnedyp.js (backup .bak da creare al Task 1)

Inizia dal Task 1.
```

---

## Contesto di riferimento rapido

| Elemento | Valore |
|----------|--------|
| File da editare | `assets/BoroughDetailPage-DmXnedyp.js` |
| Anchor Task 2 | `function R1({ boroughName: t = "Nusco", className: s }) {` |
| Anchor Task 3 | `const P1 = [` (riga ~4092 prima degli inserimenti) |
| Anchor Task 4 | `n === "flights" && e.jsx(H1, {})` |
| Anchor Task 5 | `border-dashed border-ambra-200` (dentro `section.mb-12`) |
| Branch | `claude/filter-lacedonia-municipality-azcGs` |
| Mai pushare su | `main` senza conferma esplicita |

## Tab finali (ordine P1)

```
1. flights       → H1       — Voli (mock Amadeus, già presente)
2. trains        → T1       — Treni Trenitalia/Italo (Z1_TRAINS, nuovo)
3. blablacar_bus → B1_BUS   — BlaBlaCar Bus SCHEDULED (#00D084, nuovo)
4. blablacar_carpool → B1_CARPOOL — BlaBlaCar Daily DYNAMIC (#009966, nuovo)
5. car_rental    → I1       — Noleggio auto (già presente)
6. transfers     → C1       — Transfer privati (già presente)
```

## Skill da invocare (in ordine)

1. `/metaborghi-fullstack` — all'apertura sessione
2. `superpowers:subagent-driven-development` — per eseguire il piano
