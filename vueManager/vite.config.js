import { fileURLToPath, URL } from 'node:url'

import vue from '@vitejs/plugin-vue'
import prefixSelector from 'postcss-prefix-selector'
import { defineConfig } from 'vite'

const output = {
  dir: '../',
  assetFileNames: 'assets/components/localizator3/css/mgr/vue-dist/[name].min[extname]',
  chunkFileNames: 'assets/components/localizator3/js/mgr/vue-dist/[name]-[hash].min.js',
  entryFileNames: 'assets/components/localizator3/js/mgr/vue-dist/[name].min.js',
}

// Для MODX manager нужен полный бандл — external отключён

const cssConfig = {
  postcss: {
    plugins: [
      prefixSelector({
        prefix: '.vueApp',
        exclude: [
          /^:root/,
          /^html/,
          /^body/,
          /^@keyframes/,
          /^@-webkit-keyframes/,
          /^@font-face/,
          /^@media/,
          /^\.vueApp/,
          /^\.pi/,
          /^\.p-/,
          /^\[data-p-/,
          /^\[data-pc-/,
        ],
        transform: (prefix, selector) =>
          selector === ':root' ? '.vueApp' : prefix + ' ' + selector,
      }),
    ],
  },
}

export default defineConfig(({ command }) => {
  const input =
    command === 'serve'
      ? { 'languages': 'index.html' }
      : {
          'languages': 'src/entries/languages.js',
          'content': 'src/entries/content.js',
        }

  return {
    build: {
      rollupOptions: {
        output,
        input,
      },
      cssMinify: false,
      minify: 'esbuild',
    },
    plugins: [vue()],
    resolve: {
      alias: {
        '@': fileURLToPath(new URL('./src', import.meta.url)),
      },
    },
    css: cssConfig.postcss ? { postcss: cssConfig.postcss } : undefined,
  }
})
