import { NextRequest, NextResponse } from 'next/server'
import { ElevenLabsClient } from '@elevenlabs/elevenlabs-js'

const elevenLabsKey = process.env.ELEVENLABS_API_KEY

if (!elevenLabsKey) {
  throw new Error('ELEVENLABS_API_KEY is not set')
}

const client = new ElevenLabsClient({ apiKey: elevenLabsKey })

// Mappa delle voci italiane disponibili in ElevenLabs
const VOICE_MAP: Record<string, string> = {
  'italian-female': 'IZ5OUeIHIjN3uLia8N9l', // Voce italiana femminile
  'italian-male': 'onwK4e9ZLuTAKzWW9F7h', // Voce italiana maschile
}

export async function POST(request: NextRequest) {
  try {
    const body = await request.json()
    const { text, voice = 'italian-female' } = body

    if (!text || typeof text !== 'string') {
      return NextResponse.json(
        { error: 'Text is required and must be a string' },
        { status: 400 }
      )
    }

    if (text.length > 5000) {
      return NextResponse.json(
        { error: 'Text is too long (max 5000 characters)' },
        { status: 400 }
      )
    }

    const voiceId = VOICE_MAP[voice] || VOICE_MAP['italian-female']

    // Genera l'audio usando ElevenLabs
    const audio = await client.generate({
      voice_id: voiceId,
      text,
      model_id: 'eleven_monolingual_v1',
      voice_settings: {
        stability: 0.5,
        similarity_boost: 0.75,
      },
    })

    // Converti l'audio in blob
    const audioBuffer = await audio.arrayBuffer()

    return new NextResponse(audioBuffer, {
      status: 200,
      headers: {
        'Content-Type': 'audio/mpeg',
        'Content-Length': audioBuffer.byteLength.toString(),
        'Cache-Control': 'public, max-age=86400', // Cache per 1 giorno
      },
    })
  } catch (error) {
    console.error('TTS Error:', error)

    if (error instanceof Error) {
      if (error.message.includes('API')) {
        return NextResponse.json(
          { error: 'ElevenLabs API error', details: error.message },
          { status: 503 }
        )
      }
    }

    return NextResponse.json(
      { error: 'Internal server error' },
      { status: 500 }
    )
  }
}

export const config = {
  maxDuration: 60, // Timeout di 60 secondi
}
