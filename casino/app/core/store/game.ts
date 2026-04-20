import { watch, ref } from 'vue'
import { defineStore } from 'pinia'

const REFRESH_BALANCE_TIMER = 5000 //ms

export const useGameStore = defineStore('game', () => {
  //models
  const activePlaySession = ref<string>('')
  const interval = ref()
  //methods
  const setActivePlaySession = (_payload: string) => {
    activePlaySession.value = _payload
  }
  const refreshBalance = async (): Promise<void> => {
    console.warn('REFRESH BALANCE TIMER')
  }

  watch(activePlaySession, () => {
    if (activePlaySession.value) {
      interval.value = setInterval(() => {
        refreshBalance()
      }, REFRESH_BALANCE_TIMER)
    } else {
      clearInterval(interval.value)
    }
  })

  return {
    activePlaySession,
    setActivePlaySession,
  }
})
