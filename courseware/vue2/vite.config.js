import { defineConfig } from 'vite';
import vue2 from '@vitejs/plugin-vue2';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

export default defineConfig(({ mode }) => ({
  plugins: [vue2()],
  publicDir: false,
  define: {
    'process.env.NODE_ENV': JSON.stringify(mode),
    process: JSON.stringify({ env: { NODE_ENV: mode } }),
  },
  resolve: {
    alias: {
      'vue/compiler-sfc': 'vue/compiler-sfc',
    },
  },
  build: {
    outDir: path.resolve(__dirname, '../../static_cw'),
    emptyOutDir: false,
    sourcemap: true,
    lib: {
      entry: path.resolve(__dirname, '../vueapp/register.js'),
      name: 'courseware-plugin-opencast-video',
      formats: ['umd'],
      fileName: 'register-vue2',
    },
  },
}));
