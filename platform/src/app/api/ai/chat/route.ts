import { NextRequest, NextResponse } from 'next/server'
import { streamText } from 'ai'
import { openai } from '@ai-sdk/openai'

interface ChatMessage {
  id?: string
  role: 'user' | 'assistant'
  content: string
}

// Validazione della API key
const openaiKey = process.env.OPENAI_API_KEY
if (!openaiKey) {
  throw new Error('OPENAI_API_KEY is not set')
}

// Contenuto di knowledge base per il RAG (semplificato)
// In produzione, questo verrebbe da pgvector in PostgreSQL
const KNOWLEDGE_BASE = `
MetaBorghi è una piattaforma dedicata ai borghi dell'Alta Irpinia in Campania.
I principali borghi sono:
- Lacedonia: Borgo millenario affacciato sulla valle del Calaggio, con una bellissima cattedrale romanica
- Calitri: La perla della ceramica irpina, nota per l'artigianato ceramico
- Bisaccia: Borgo dalla storia millenaria con parco archeologico
- Nusco: Il balcone dell'Irpinia, uno dei borghi più alti con una cattedrale romanica di rara bellezza
- Monteverde: Arroccato su uno sperone roccioso, offre viste sulla valle dell'Ofanto
- Conza della Campania: L'antica Compsa romana, oggi oasi naturalistica
- Cairano: Il borgo più piccolo con vista sulla diga di Conza

Esperienze disponibili:
- L'Arte della Ceramica a Calitri
- Sentiero dell'Ofanto escursione natura
- Sapori d'Irpinia degustazione gastronomica
- Notte al Castello visita notturna
- Yoga all'Alba sessione benessere
- Kayak al Lago di Conza avventura

La piattaforma fornisce informazioni su borghi, esperienze, artigianato locale, gastronomia irpina e attrazioni turistiche.
`

const SYSTEM_PROMPT = `Sei il CX Genie, un assistente intelligente e cordiale che rappresenta MetaBorghi,
una piattaforma per i borghi dell'Alta Irpinia. Rispondi sempre in italiano con entusiasmo e gentilezza.

Sei un esperto di:
- Borghi dell'Alta Irpinia
- Esperienze e attrazioni locali
- Gastronomia irpina
- Artigianato locale
- Turismo sostenibile

Quando rispondi:
1. Usa il nome dell'utente se disponibile
2. Sii accogliente e incoraggiante
3. Fornisci informazioni accurate sui borghi
4. Suggerisci esperienze relevanti
5. Mantieni risposte concise e piacevoli (max 150 parole)

Knowledge Base:
${KNOWLEDGE_BASE}

Se non conosci la risposta, ammettilo cortesemente e offri aiuto alternativo.`

export async function POST(request: NextRequest) {
  try {
    const body = await request.json()
    const messages = body.messages as ChatMessage[]

    if (!Array.isArray(messages) || messages.length === 0) {
      return NextResponse.json(
        { error: 'Messages array is required and must not be empty' },
        { status: 400 }
      )
    }

    // Streaming response usando Vercel AI SDK
    const result = await streamText({
      model: openai('gpt-4-turbo'),
      system: SYSTEM_PROMPT,
      messages: messages.map((msg) => ({
        role: msg.role,
        content: msg.content,
      })),
      temperature: 0.7,
      maxTokens: 300,
      topP: 0.9,
    })

    // Ritorna lo stream al client
    return result.toTextStreamResponse()
  } catch (error) {
    console.error('Chat API Error:', error)

    if (error instanceof Error) {
      // Controlla se è un errore di API key
      if (error.message.includes('API') || error.message.includes('401')) {
        return NextResponse.json(
          {
            error: 'API Configuration Error',
            details: 'Verificare che le API keys siano configurate correttamente',
          },
          { status: 503 }
        )
      }

      // Controllo rate limit
      if (error.message.includes('rate') || error.message.includes('429')) {
        return NextResponse.json(
          { error: 'Rate limit exceeded. Please try again later.' },
          { status: 429 }
        )
      }
    }

    return NextResponse.json(
      { error: 'Internal server error during chat processing' },
      { status: 500 }
    )
  }
}

// Configurazione del timeout
export const config = {
  maxDuration: 60, // 60 secondi
}
