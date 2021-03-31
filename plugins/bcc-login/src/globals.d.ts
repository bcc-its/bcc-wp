declare global {
  interface Window {
    bccLoginPostVisibility?: {
      defaultLevel: number,
      levels: Record<string, number>,
    }
  }
}

export {}
