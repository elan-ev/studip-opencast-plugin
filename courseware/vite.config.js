import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue2';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

export default defineConfig(({ mode }) => ({
  plugins: [vue()],
  publicDir: false,
  define: {
    'process.env.NODE_ENV': JSON.stringify(mode),
    process: JSON.stringify({ env: { NODE_ENV: mode } }),
  },
  build: {
    outDir: path.resolve(__dirname, '../static_cw'),
    emptyOutDir: true,
    sourcemap: true,
    lib: {
      entry: path.resolve(__dirname, 'vueapp/register.js'),
      name: 'courseware-plugin-opencast-video',
      formats: ['umd'],
      fileName: 'register',
    },
  },
}));
