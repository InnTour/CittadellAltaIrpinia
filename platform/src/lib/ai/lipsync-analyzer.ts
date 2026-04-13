/**
 * Analizzatore di Lip Sync
 * Mappa il testo ai visemes e ai tempi di pronuncia
 * Usa una mappatura semplificata che può essere estesa con modelli ML più sofisticati
 */

const VISEME_MAP: Record<string, string> = {
  // Vocali principali
  'a': 'A',
  'e': 'E',
  'i': 'I',
  'o': 'O',
  'u': 'U',
  'à': 'A',
  'è': 'E',
  'é': 'E',
  'ì': 'I',
  'ò': 'O',
  'ù': 'U',

  // Consonanti che influenzano il lip sync
  'm': 'U', // Labbra chiuse
  'p': 'U', // Labbra chiuse
  'b': 'U', // Labbra chiuse
  'f': 'E', // Denti inferiori
  'v': 'E', // Denti inferiori
  'd': 'E', // Denti
  't': 'E', // Denti
  'n': 'E', // Denti
  'l': 'E', // Denti
  'r': 'E', // Denti
  's': 'E', // Denti stretti
  'z': 'E', // Denti stretti
  'g': 'O', // Gola
  'c': 'O', // Gola
  'k': 'O', // Gola
  'h': 'A', // Aperto
  'j': 'I', // Sorriso
  'y': 'I', // Sorriso
  'w': 'U', // Labbra arrotondate
  'q': 'O', // Labbra arrotondate

  // Default
  ' ': 'neutral',
  ',': 'neutral',
  '.': 'neutral',
  '!': 'A',
  '?': 'A',
}

interface VisemeTiming {
  time: number
  viseme: string
  character: string
}

export class LipsyncAnalyzer {
  private averageCharacterDuration: number = 0.1 // secondi per carattere

  /**
   * Analizza il testo e ritorna un array di tempi e visemes
   * @param text Il testo da analizzare
   * @param totalDuration La durata totale dell'audio (opzionale)
   * @returns Array di tempi e visemes
   */
  analyzeText(text: string, totalDuration?: number): VisemeTiming[] {
    const cleanedText = text.toLowerCase()
    let currentTime = 0
    const visemeTiming: VisemeTiming[] = []

    // Se conosciamo la durata totale, calcola la durata media per carattere
    if (totalDuration) {
      this.averageCharacterDuration = totalDuration / cleanedText.length
    }

    for (let i = 0; i < cleanedText.length; i++) {
      const char = cleanedText[i]
      const viseme = this.getVisemeForCharacter(char)

      visemeTiming.push({
        time: currentTime,
        viseme,
        character: char,
      })

      currentTime += this.averageCharacterDuration
    }

    return visemeTiming
  }

  /**
   * Ritorna il viseme per un carattere
   * @param char Il carattere da analizzare
   * @returns Il viseme corrispondente
   */
  private getVisemeForCharacter(char: string): string {
    return VISEME_MAP[char] || 'neutral'
  }

  /**
   * Estrae solo i visemes rilevanti dal testo
   * Riduce il numero di visemes per una animazione più fluida
   */
  extractVisemeSequence(text: string): string[] {
    const visemeTiming = this.analyzeText(text)
    const visemes: string[] = []
    let lastViseme = 'neutral'

    for (const timing of visemeTiming) {
      // Aggiungi solo i visemes che cambiano
      if (timing.viseme !== lastViseme) {
        visemes.push(timing.viseme)
        lastViseme = timing.viseme
      }
    }

    return visemes
  }

  /**
   * Analizza i dati audio in tempo reale da un AudioAnalyser
   * Questo potrebbe essere usato per un lip sync più accurato basato su frequency analysis
   */
  analyzeAudioFrequency(
    frequencyData: Uint8Array,
    sampleRate: number = 44100
  ): string {
    // Analizza le frequenze dominanti per determinare il viseme attuale
    // Questa è una implementazione semplificata
    // Per risultati migliori, usare un modello ML dedicato

    let sum = 0
    for (let i = 0; i < frequencyData.length; i++) {
      sum += frequencyData[i]
    }
    const average = sum / frequencyData.length

    // Mappatura semplice: le frequenze alte corrispondono a visemes diversi
    if (average > 150) return 'A' // Aperto
    if (average > 100) return 'E' // Semiaperto
    if (average > 50) return 'I' // Sorriso
    return 'O' // Chiuso

    // TODO: Implementare una mappatura più sofisticata basata su FFT
  }

  /**
   * Calcola la durata stimata della pronuncia di un testo
   * Basato su caratteri e pause
   */
  estimateSpeechDuration(text: string, wordsPerMinute: number = 150): number {
    const words = text.trim().split(/\s+/).length
    const minutes = words / wordsPerMinute
    return minutes * 60 // ritorna secondi
  }
}
