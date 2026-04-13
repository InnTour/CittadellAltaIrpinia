# CX Genie Avatar with Lip Sync - Setup Guide

## 📋 Overview

Il **CX Genie Avatar** è un assistente IA conversazionale con un avatar animato e lip sync perfetto sincronizzato con le risposte vocali. È stato implementato come parte della strategia MetaBorghi per fornire un'esperienza utente immersiva nei borghi dell'Alta Irpinia.

### Caratteristiche

✅ **Avatar animato 3D/2D** con espressioni facciali dinamiche
✅ **Lip sync intelligente** sincronizzato con audio ElevenLabs
✅ **Chat conversazionale** alimentato da OpenAI GPT-4
✅ **Text-to-Speech** con voce italiana naturale
✅ **State management** con Zustand per reattività
✅ **Responsive design** con Tailwind CSS
✅ **Streaming responses** con Vercel AI SDK

---

## 🚀 Setup Rapido

### 1. Installa le dipendenze

```bash
cd platform
npm install
```

### 2. Configura le variabili d'ambiente

Crea un file `.env.local` copiando da `.env.example`:

```bash
cp .env.example .env.local
```

Poi compila le API keys:

```env
# ElevenLabs API Key (per TTS italiano)
ELEVENLABS_API_KEY=sk_xxxxxxxxxxxxxxxxxx

# OpenAI API Key (per Chat AI)
OPENAI_API_KEY=sk-xxxxxxxxxxxxxxxxxx
```

### 3. Avvia il server di sviluppo

```bash
npm run dev
```

Accedi a `http://localhost:3000` per vedere il CX Genie in azione!

---

## 🎨 Architettura dei Componenti

```
src/
├── app/
│   ├── layout.tsx                    # Layout principale con font
│   ├── page.tsx                      # Home page
│   └── api/
│       └── ai/
│           ├── chat/route.ts        # API endpoint per chat (streaming)
│           └── tts/route.ts         # API endpoint per Text-to-Speech
├── components/
│   └── cx-genie/
│       ├── Avatar.tsx               # Componente avatar principale
│       ├── LipSync.tsx              # Lip sync visemes
│       └── CXGenieChat.tsx          # Chat UI + avatar integration
├── lib/
│   ├── stores/
│   │   └── cx-genie-store.ts        # Zustand state management
│   ├── audio/
│   │   └── audio-manager.ts         # Gestione riproduzione audio
│   └── ai/
│       └── lipsync-analyzer.ts      # Analisi visemes dal testo
└── styles/
    └── globals.css                   # Stili globali + animazioni
```

---

## 🔧 Configurazione API

### ElevenLabs TTS

Ottieni l'API key da: https://elevenlabs.io/sign-up

Voci italiane disponibili:
- **italian-female**: Voce femminile naturale (default)
- **italian-male**: Voce maschile naturale

### OpenAI Chat

Ottieni l'API key da: https://platform.openai.com/api-keys

Il sistema usa **GPT-4-Turbo** per risposte intelligenti e contextuali.

---

## 📝 Prompt Customizzazione

Per modificare il comportamento dell'assistente, edita il `SYSTEM_PROMPT` in:
```
src/app/api/ai/chat/route.ts
```

Esempio:

```typescript
const SYSTEM_PROMPT = `Sei il CX Genie, un assistente specializzato in borghi irpini...`
```

---

## 🎭 Lip Sync Intelligence

Il lip sync utilizza una **mappatura semplificata di visemes** basata sui caratteri:

- **A**: Bocca aperta (vocali aperte: a, e)
- **E**: Semiaperta (vocali semiaperte)
- **I**: Sorriso (i, j, y)
- **O**: Arrotondata (o, u)
- **U**: Labbra chiuse (consonanti bilabiali: m, p, b)
- **neutral**: Posizione neutra (pause, spazi)

Per implementazioni future:
- Integrare **Wav2Lip** per lip sync ML-based
- Usare **voice activity detection** (VAD) per precision timing
- Implementare **facial emotion recognition** per espressioni contestuali

---

## 🎬 Avatar Animazioni

### Stati disponibili

1. **idle**: Avatar a riposo con lieve float
2. **speaking**: Avatar con glow e animazioni facciali
3. **listening**: Avatar con pulse ring animato
4. **thinking**: Avatar opaco con loader animato

### Personalizzazione

Modifica le animazioni in:
```
src/styles/globals.css
src/components/cx-genie/Avatar.tsx
```

---

## 🌐 Integrazione con MetaBorghi

Il CX Genie è completamente integrato con la knowledge base di MetaBorghi:

- **25 borghi catalogati** (Lacedonia, Nusco, Calitri, etc.)
- **Esperienze locali** (trekking, gastronomia, artigianato)
- **Informazioni turistiche** contestuali

In futuro (Fase V2), il sistema integrerà:
- **pgvector** embeddings da PostgreSQL Neon
- **RAG (Retrieval-Augmented Generation)** per risposte più accurate
- **Geolocalizzazione GPS** con trigger automatico di narrativa

---

## 🧪 Testing

```bash
# Avvia i test
npm run test

# Test end-to-end con Playwright
npm run test:e2e

# Type checking
npm run typecheck
```

---

## 📦 Deploy

### Hostinger Cloud (Production)

```bash
# Build
npm run build

# Start
npm start
```

Configura PM2 per gestione processi:

```bash
pm2 start npm --name "metaborghi" -- start
pm2 save
pm2 startup
```

### Vercel (alternativa)

```bash
vercel deploy
```

---

## 🐛 Troubleshooting

### "AudioContext not initialized"
- Assicurati di essere in un browser moderno
- Verifica che il site sia servito su HTTPS (AudioContext richiede secure context)

### "ElevenLabs API error"
- Verifica che `ELEVENLABS_API_KEY` sia corretto
- Controlla i limiti di rate (free tier: 1.000 caratteri/mese)

### "Chat API timeout"
- Aumenta il timeout in `route.ts` (default: 60s)
- Verifica la connessione OpenAI

### Lip sync non sincronizzato
- Verifica che la durata audio matchi il testo
- Controllae la latenza di rete (fondamentale per streaming)

---

## 🎓 Documentazione Correlata

- [PIANO_STRATEGICO_INTEGRAZIONI.md](../PIANO_STRATEGICO_INTEGRAZIONI.md) - Roadmap completo MetaBorghi
- [PROJECT_CONTEXT.md](../PROJECT_CONTEXT.md) - Context del progetto
- [Vercel AI SDK Docs](https://sdk.vercel.ai/)
- [ElevenLabs API](https://elevenlabs.io/docs/)

---

## 📞 Support

Per problemi o suggerimenti:
1. Controlla la sezione Troubleshooting
2. Apri un issue su GitHub
3. Contatta il team InnTour

---

*CX Genie Avatar - InnTour S.R.L. - Aprile 2026*
