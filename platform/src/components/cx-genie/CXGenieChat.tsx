'use client'

import React, { useRef, useEffect, useState } from 'react'
import { useChat } from 'ai/react'
import { Avatar } from './Avatar'
import { useCXGenieStore } from '@/lib/stores/cx-genie-store'
import { AudioManager } from '@/lib/audio/audio-manager'
import { LipsyncAnalyzer } from '@/lib/ai/lipsync-analyzer'

interface ChatMessage {
  id: string
  content: string
  role: 'user' | 'assistant'
  timestamp: Date
}

export const CXGenieChat: React.FC = () => {
  const { messages, input, handleInputChange, handleSubmit, isLoading } = useChat({
    api: '/api/ai/chat',
    onResponse: async (response) => {
      if (response.ok) {
        updateCurrentState('speaking')
      }
    },
  })

  const audioManagerRef = useRef<AudioManager | null>(null)
  const lipsyncAnalyzerRef = useRef<LipsyncAnalyzer | null>(null)
  const inputRef = useRef<HTMLInputElement>(null)
  const messagesEndRef = useRef<HTMLDivElement>(null)

  const [localMessages, setLocalMessages] = useState<ChatMessage[]>([])
  const [visemes, setVisemes] = useState<string[]>([])
  const [isListening, setIsListening] = useState(false)
  const [isSpeaking, setIsSpeaking] = useState(false)

  const { updateCurrentState } = useCXGenieStore()

  // Inizializza AudioManager e LipsyncAnalyzer
  useEffect(() => {
    audioManagerRef.current = new AudioManager({
      onLipsyncUpdate: (viseme) => {
        setVisemes((prev) => [...prev.slice(-10), viseme])
      },
      onSpeakingEnd: () => {
        setIsSpeaking(false)
        updateCurrentState('idle')
      },
    })

    lipsyncAnalyzerRef.current = new LipsyncAnalyzer()

    return () => {
      audioManagerRef.current?.cleanup()
    }
  }, [updateCurrentState])

  // Sincronizza i messaggi
  useEffect(() => {
    setLocalMessages(
      messages.map((msg, idx) => ({
        id: msg.id || `msg-${idx}`,
        content: msg.content,
        role: msg.role as 'user' | 'assistant',
        timestamp: new Date(),
      }))
    )
  }, [messages])

  // Auto-scroll all'ultimo messaggio
  useEffect(() => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' })
  }, [localMessages])

  // Gestisce la ricezione di un nuovo messaggio dall'assistente
  useEffect(() => {
    const lastMessage = localMessages[localMessages.length - 1]
    if (lastMessage?.role === 'assistant' && isSpeaking === false && isLoading === false) {
      playAssistantResponse(lastMessage.content)
    }
  }, [localMessages, isLoading])

  const playAssistantResponse = async (text: string) => {
    if (!audioManagerRef.current) return

    try {
      setIsSpeaking(true)
      updateCurrentState('speaking')

      // Chiama l'API di TTS per generare l'audio
      const audioUrl = await fetchAudioFromTTS(text)

      // Riproduci l'audio con lip sync
      await audioManagerRef.current.playAudio(audioUrl, text)
    } catch (error) {
      console.error('Errore nella riproduzione audio:', error)
      setIsSpeaking(false)
      updateCurrentState('idle')
    }
  }

  const fetchAudioFromTTS = async (text: string): Promise<string> => {
    const response = await fetch('/api/ai/tts', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ text, voice: 'italian-female' }),
    })

    if (!response.ok) {
      throw new Error('TTS API error')
    }

    const blob = await response.blob()
    return URL.createObjectURL(blob)
  }

  const handleSendMessage = (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault()
    if (!input.trim()) return

    updateCurrentState('thinking')
    handleSubmit(e)
  }

  return (
    <div className="flex flex-col h-screen bg-gradient-to-b from-notte via-notte-lt to-notte">
      {/* Avatar Section */}
      <div className="flex-1 flex items-center justify-center p-8">
        <Avatar
          isListening={isListening}
          isSpeaking={isSpeaking}
          visemes={visemes}
        />
      </div>

      {/* Chat Section */}
      <div className="flex-1 flex flex-col gap-4 p-6 bg-glass-dark rounded-t-3xl">
        {/* Messages Container */}
        <div className="flex-1 overflow-y-auto space-y-4 pb-4">
          {localMessages.length === 0 ? (
            <div className="flex items-center justify-center h-full text-center">
              <div>
                <h2 className="text-2xl font-playfair font-bold text-green mb-2">
                  Benvenuto nel CX Genie
                </h2>
                <p className="text-gray-400">
                  Poni una domanda sui borghi dell'Alta Irpinia...
                </p>
              </div>
            </div>
          ) : (
            <>
              {localMessages.map((msg) => (
                <div
                  key={msg.id}
                  className={`flex ${msg.role === 'user' ? 'justify-end' : 'justify-start'}`}
                >
                  <div
                    className={`max-w-xs lg:max-w-md px-4 py-2 rounded-lg ${
                      msg.role === 'user'
                        ? 'bg-green text-notte rounded-br-none'
                        : 'bg-cyan-dark text-white rounded-bl-none'
                    }`}
                  >
                    <p className="text-sm">{msg.content}</p>
                    <span className="text-xs opacity-70 mt-1 block">
                      {msg.timestamp.toLocaleTimeString('it-IT', {
                        hour: '2-digit',
                        minute: '2-digit',
                      })}
                    </span>
                  </div>
                </div>
              ))}
              {isLoading && (
                <div className="flex justify-start">
                  <div className="bg-cyan-dark text-white px-4 py-2 rounded-lg rounded-bl-none">
                    <div className="flex gap-1">
                      <div className="w-2 h-2 bg-white rounded-full animate-bounce" />
                      <div className="w-2 h-2 bg-white rounded-full animate-bounce" style={{ animationDelay: '0.1s' }} />
                      <div className="w-2 h-2 bg-white rounded-full animate-bounce" style={{ animationDelay: '0.2s' }} />
                    </div>
                  </div>
                </div>
              )}
              <div ref={messagesEndRef} />
            </>
          )}
        </div>

        {/* Input Area */}
        <form onSubmit={handleSendMessage} className="flex gap-2">
          <input
            ref={inputRef}
            type="text"
            value={input}
            onChange={handleInputChange}
            placeholder="Scrivi un messaggio..."
            disabled={isLoading || isSpeaking}
            className="flex-1 px-4 py-3 rounded-lg bg-notte-lt border border-green focus:outline-none focus:border-cyan text-white placeholder:text-gray-500 disabled:opacity-50"
          />
          <button
            type="submit"
            disabled={isLoading || isSpeaking || !input.trim()}
            className="px-6 py-3 bg-green text-notte font-semibold rounded-lg hover:bg-green-dark disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            Invia
          </button>
        </form>
      </div>
    </div>
  )
}
