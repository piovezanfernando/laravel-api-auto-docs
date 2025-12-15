import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import { fileURLToPath, URL } from 'node:url'

export default defineConfig({
  base: process.env.PUBLIC_BASE ? `/${process.env.PUBLIC_BASE}/` : '/api-auto-docs/',
  plugins: [
    vue(),
  ],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
      'primevue/resources': fileURLToPath(new URL('./node_modules/primevue/resources', import.meta.url))
    }
  },
  build: {
    outDir: '../resources/dist',
    emptyOutDir: true,
    rollupOptions: {
      output: {
        entryFileNames: `[name].js`,
        chunkFileNames: `[name].js`,
        assetFileNames: `[name].[ext]`
      }
    }
  },
  server: {
    port: 4321
  }
})
