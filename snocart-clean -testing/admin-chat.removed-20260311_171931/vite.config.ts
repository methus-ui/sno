import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

// https://vite.dev/config/
export default defineConfig({
  plugins: [react()],
  base: '/admin-chat/',
  server: {
    port: 3001,
    host: '127.0.0.1',
  },
  preview: {
    port: 3001,
    host: '127.0.0.1',
  },
})
