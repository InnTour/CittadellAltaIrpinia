'use client'

import React, { useEffect, useRef, useState } from 'react'
import { motion } from 'framer-motion'
import { LipSyncComponent } from './LipSync'
import { useCXGenieStore } from '@/lib/stores/cx-genie-store'

interface AvatarProps {
  isListening?: boolean
  isSpeaking?: boolean
  visemes?: string[]
}

export const Avatar: React.FC<AvatarProps> = ({
  isListening = false,
  isSpeaking = false,
  visemes = []
}) => {
  const containerRef = useRef<HTMLDivElement>(null)
  const { currentState } = useCXGenieStore()
  const [eyeState, setEyeState] = useState({ leftX: 0, leftY: 0, rightX: 0, rightY: 0 })

  // Animazione degli occhi verso il cursore
  useEffect(() => {
    const handleMouseMove = (e: MouseEvent) => {
      if (!containerRef.current) return

      const rect = containerRef.current.getBoundingClientRect()
      const centerX = rect.left + rect.width / 2
      const centerY = rect.top + rect.height / 2

      const angle = Math.atan2(e.clientY - centerY, e.clientX - centerX)
      const distance = 15

      const leftX = Math.cos(angle) * distance
      const leftY = Math.sin(angle) * distance
      const rightX = Math.cos(angle) * distance
      const rightY = Math.sin(angle) * distance

      setEyeState({ leftX, leftY, rightX, rightY })
    }

    window.addEventListener('mousemove', handleMouseMove)
    return () => window.removeEventListener('mousemove', handleMouseMove)
  }, [])

  return (
    <div
      ref={containerRef}
      className="flex flex-col items-center justify-center w-full h-full perspective"
    >
      <motion.div
        className={`w-80 h-80 rounded-full glass-dark flex items-center justify-center ${
          isListening ? 'avatar-listening' : isSpeaking ? 'avatar-glow' : 'avatar'
        }`}
        animate={{
          scale: isListening ? [1, 1.05, 1] : isSpeaking ? 1.05 : 1,
          opacity: currentState === 'thinking' ? 0.8 : 1,
        }}
        transition={{
          duration: 0.3,
          repeat: isListening ? Infinity : 0,
          repeatType: 'mirror',
        }}
      >
        <svg
          viewBox="0 0 200 200"
          width={300}
          height={300}
          className="filter drop-shadow-lg"
        >
          {/* Viso */}
          <circle cx="100" cy="100" r="90" fill="#00D084" opacity="0.8" />

          {/* Occhio sinistro */}
          <circle cx="75" cy="85" r="15" fill="white" />
          <circle
            cx={75 + eyeState.leftX}
            cy={85 + eyeState.leftY}
            r="8"
            fill="#1A1A2E"
          />
          <circle cx={75 + eyeState.leftX + 2} cy={85 + eyeState.leftY - 2} r="3" fill="white" />

          {/* Occhio destro */}
          <circle cx="125" cy="85" r="15" fill="white" />
          <circle
            cx={125 + eyeState.rightX}
            cy={85 + eyeState.rightY}
            r="8"
            fill="#1A1A2E"
          />
          <circle cx={125 + eyeState.rightX + 2} cy={85 + eyeState.rightY - 2} r="3" fill="white" />

          {/* Naso */}
          <path d="M 100 100 L 95 130 L 105 130" stroke="#00B4D8" strokeWidth="2" fill="none" />

          {/* Bocca con Lip Sync */}
          <LipSyncComponent visemes={visemes} />

          {/* Blush (quando parla) */}
          {isSpeaking && (
            <>
              <circle cx="55" cy="120" r="8" fill="#F5A623" opacity="0.4" />
              <circle cx="145" cy="120" r="8" fill="#F5A623" opacity="0.4" />
            </>
          )}
        </svg>
      </motion.div>

      {/* Status Indicator */}
      <div className="mt-6 text-center">
        {isListening && (
          <div className="flex items-center gap-2 justify-center">
            <div className="w-2 h-2 bg-green-500 rounded-full animate-pulse" />
            <span className="text-sm text-green-500 font-medium">In ascolto...</span>
          </div>
        )}
        {isSpeaking && (
          <div className="flex items-center gap-2 justify-center">
            <div className="w-2 h-2 bg-cyan-400 rounded-full animate-pulse" />
            <span className="text-sm text-cyan-400 font-medium">Parlando...</span>
          </div>
        )}
        {currentState === 'thinking' && (
          <div className="flex items-center gap-2 justify-center">
            <div className="flex gap-1">
              <div className="w-2 h-2 bg-yellow-400 rounded-full animate-bounce" />
              <div className="w-2 h-2 bg-yellow-400 rounded-full animate-bounce" style={{ animationDelay: '0.1s' }} />
              <div className="w-2 h-2 bg-yellow-400 rounded-full animate-bounce" style={{ animationDelay: '0.2s' }} />
            </div>
          </div>
        )}
      </div>
    </div>
  )
}
