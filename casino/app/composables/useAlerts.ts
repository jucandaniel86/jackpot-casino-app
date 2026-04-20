import { toast, type ToastOptions } from 'vue3-toastify'

export const useAlerts = () => {
  const info = (message: string, options?: ToastOptions) => {
    toast.info(message, options)
  }

  const error = (message: string, options?: ToastOptions) => {
    toast.error(message, options)
  }

  const success = (message: string, options?: ToastOptions) => {
    toast.success(message, options)
  }

  return {
    info,
    error,
    success,
  }
}
