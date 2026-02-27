import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import path from 'path';

export default defineConfig({
  plugins: [vue()],
  base: './',
  resolve: {
    alias: {
      '@': path.resolve(__dirname, 'vueapp'),
      '@studip': path.resolve(__dirname, 'vueapp/components/Studip'),
    },
    extensions: ['.vue', '.js'],
  },
  build: {
    outDir: 'static',
    emptyOutDir: true,
    manifest: '.vite/manifest.json',
    sourcemap: true,
    assetsDir: '',
    rollupOptions: {
      input: path.resolve(__dirname, 'vueapp/app.js'),
      output: {
        entryFileNames: '[name].[hash].js',
        chunkFileNames: '[name].[hash].js',
        assetFileNames: '[name].[hash][extname]',
      },
    },
  },
});
