import { LipsyncAnalyzer } from '../ai/lipsync-analyzer'

interface AudioManagerOptions {
  onLipsyncUpdate?: (viseme: string) => void
  onSpeakingEnd?: () => void
}

export class AudioManager {
  private audioContext: AudioContext | null = null
  private mediaSource: MediaElementAudioSourceNode | null = null
  private analyser: AnalyserNode | null = null
  private audioElement: HTMLAudioElement | null = null
  private lipsyncAnalyzer: LipsyncAnalyzer
  private animationFrameId: number | null = null
  private options: AudioManagerOptions

  constructor(options: AudioManagerOptions = {}) {
    this.options = options
    this.lipsyncAnalyzer = new LipsyncAnalyzer()
    this.initializeAudioContext()
  }

  private initializeAudioContext() {
    try {
      const audioContext = new (typeof window !== 'undefined'
        ? window.AudioContext || (window as any).webkitAudioContext
        : null)()

      if (!audioContext) {
        console.warn('AudioContext not available in this environment')
        return
      }

      this.audioContext = audioContext
      this.analyser = audioContext.createAnalyser()
      this.analyser.fftSize = 256

      // Crea l'elemento audio per la riproduzione
      if (typeof document !== 'undefined') {
        this.audioElement = new Audio()
        this.audioElement.crossOrigin = 'anonymous'
      }
    } catch (error) {
      console.error('Errore nell\'inizializzazione AudioContext:', error)
    }
  }

  async playAudio(
    audioUrl: string,
    transcription?: string
  ): Promise<void> {
    if (!this.audioElement || !this.audioContext || !this.analyser) {
      throw new Error('AudioContext not initialized')
    }

    return new Promise((resolve, reject) => {
      try {
        this.audioElement!.src = audioUrl

        // Configura il listener per quando l'audio finisce
        this.audioElement!.onended = () => {
          this.stopLipsyncAnimation()
          this.options.onSpeakingEnd?.()
          resolve()
        }

        this.audioElement!.onerror = (error) => {
          this.stopLipsyncAnimation()
          reject(new Error(`Audio playback error: ${error}`))
        }

        // Avvia il lip sync animation loop
        this.startLipsyncAnimation(transcription || '')

        // Riproduci l'audio
        this.audioElement!.play().catch((error) => {
          this.stopLipsyncAnimation()
          reject(error)
        })
      } catch (error) {
        reject(error)
      }
    })
  }

  private startLipsyncAnimation(transcription: string) {
    if (!this.audioElement || !this.analyser) return

    // Ottieni i tempi dei visemes dalla trascrizione
    const visemeTiming = this.lipsyncAnalyzer.analyzeText(transcription)

    const animate = () => {
      if (!this.audioElement) return

      const currentTime = this.audioElement.currentTime
      const currentViseme = this.findVisemeAtTime(visemeTiming, currentTime)

      if (currentViseme) {
        this.options.onLipsyncUpdate?.(currentViseme)
      }

      if (!this.audioElement.paused) {
        this.animationFrameId = requestAnimationFrame(animate)
      }
    }

    this.animationFrameId = requestAnimationFrame(animate)
  }

  private stopLipsyncAnimation() {
    if (this.animationFrameId !== null) {
      cancelAnimationFrame(this.animationFrameId)
      this.animationFrameId = null
    }
  }

  private findVisemeAtTime(
    timing: Array<{ time: number; viseme: string }>,
    currentTime: number
  ): string | null {
    let closestViseme = null
    let minDifference = Infinity

    for (const entry of timing) {
      const difference = Math.abs(entry.time - currentTime)
      if (difference < minDifference && entry.time <= currentTime) {
        minDifference = difference
        closestViseme = entry.viseme
      }
    }

    return closestViseme
  }

  cleanup() {
    this.stopLipsyncAnimation()
    if (this.audioElement) {
      this.audioElement.pause()
      this.audioElement.src = ''
    }
  }

  pause() {
    if (this.audioElement) {
      this.audioElement.pause()
      this.stopLipsyncAnimation()
    }
  }

  resume() {
    if (this.audioElement) {
      this.audioElement.play()
      this.startLipsyncAnimation('')
    }
  }
}
