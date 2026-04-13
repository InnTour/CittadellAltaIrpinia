'use client'

import React, { useMemo } from 'react'

interface LipSyncComponentProps {
  visemes?: string[]
}

const VISEME_PATHS: Record<string, string> = {
  'A': 'M 100 140 Q 95 150 75 150 Q 55 150 55 140 Q 75 130 100 130 Q 125 130 145 140 Q 125 150 100 150',
  'E': 'M 100 142 Q 95 148 75 148 Q 55 148 55 140 Q 75 132 100 132 Q 125 132 145 140 Q 125 148 100 148',
  'I': 'M 98 145 L 102 135',
  'O': 'M 100 150 Q 75 165 50 150 Q 30 135 50 120 Q 75 105 100 105 Q 125 105 150 120 Q 170 135 150 150 Q 125 165 100 150',
  'U': 'M 100 140 Q 75 155 55 145 Q 45 140 50 130 Q 75 120 100 120 Q 125 120 150 130 Q 155 140 145 145 Q 125 155 100 140',
  'neutral': 'M 100 145 L 100 135',
}

export const LipSyncComponent: React.FC<LipSyncComponentProps> = ({ visemes = [] }) => {
  // Prendi il viseme corrente (il più recente)
  const currentViseme = useMemo(() => {
    return visemes.length > 0 ? visemes[visemes.length - 1] : 'neutral'
  }, [visemes])

  const mouthPath = VISEME_PATHS[currentViseme] || VISEME_PATHS['neutral']

  return (
    <path
      d={mouthPath}
      stroke="#1A1A2E"
      strokeWidth="3"
      fill="none"
      strokeLinecap="round"
      strokeLinejoin="round"
      className="mouth-transition"
    />
  )
}
