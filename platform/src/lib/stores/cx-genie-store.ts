import { create } from 'zustand'

export type CXGenieState = 'idle' | 'thinking' | 'speaking' | 'listening' | 'error'

interface CXGenieStore {
  currentState: CXGenieState
  updateCurrentState: (state: CXGenieState) => void
  errorMessage: string | null
  setErrorMessage: (message: string | null) => void
  userData: {
    userName?: string
    borough?: string
  }
  updateUserData: (data: Partial<CXGenieStore['userData']>) => void
}

export const useCXGenieStore = create<CXGenieStore>((set) => ({
  currentState: 'idle',
  updateCurrentState: (state) => set({ currentState: state }),

  errorMessage: null,
  setErrorMessage: (message) => set({ errorMessage: message }),

  userData: {},
  updateUserData: (data) =>
    set((state) => ({
      userData: { ...state.userData, ...data },
    })),
}))
