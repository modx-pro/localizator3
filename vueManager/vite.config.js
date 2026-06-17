import { fileURLToPath, URL } from 'node:url'

import vue from '@vitejs/plugin-vue'
import prefixSelector from 'postcss-prefix-selector'
import { defineConfig } from 'vite'

// Пути вывода в assets/. Относительно vueManager/, т.к. build.outDir = '../'.
const outDir = '../'
const assetFileNames = 'assets/components/localizator3/css/mgr/vue-dist/[name].min[extname]'
const chunkFileNames = 'assets/components/localizator3/js/mgr/vue-dist/[name]-[hash].min.js'
const entryFileNames = 'assets/components/localizator3/js/mgr/vue-dist/[name].min.js'

// Все entry-точки Localizator3. Каждая собирается отдельным вызовом vite build
// (через переменную окружения BUILD_ENTRY), чтобы применить inlineDynamicImports
// и гарантировать ровно один .min.js на entry — без code-split chunk'ов вида
// `_plugin-vue_export-helper-*.min.js`, которые ранее давали 404.
const ENTRIES = {
  languages: 'src/entries/languages.js',
  content: 'src/entries/content.js',
}

// Префикс .vueApp изолирует стили от ExtJS-обёртки MODX manager.
const postcssPlugins = [
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
]

// Vue-стек (vue, pinia, primevue) и composables (@vuetools/*) предоставляет
// VueTools ≥1.1.2-pl через Import Map. Не включаем их в бандл — entry остаётся
// «тощим» (~десятки КБ вместо ~800 КБ).
const EXTERNAL = [/^vue$/, /^vue\//, /^pinia$/, /^pinia\//, /^primevue$/, /^primevue\//, /^@vuetools\//]

export default defineConfig(({ command }) => {
  // Dev-сервер — одна html-точка как раньше.
  if (command === 'serve') {
    return {
      plugins: [vue()],
      resolve: {
        alias: { '@': fileURLToPath(new URL('./src', import.meta.url)) },
      },
      css: { postcss: { plugins: postcssPlugins } },
      build: {
        rollupOptions: {
          input: { languages: 'index.html' },
        },
      },
    }
  }

  // Production build: одна entry за вызов (inlineDynamicImports требует один input).
  const buildEntry = process.env.BUILD_ENTRY
  const input = buildEntry
    ? { [buildEntry]: ENTRIES[buildEntry] }
    : ENTRIES // fallback: собираем обе точки вместе (без inline)

  return {
    build: {
      outDir,
      emptyOutDir: false, // не затирать соседний entry при последовательных сборках
      rollupOptions: {
        external: EXTERNAL,
        input,
        output: buildEntry
          ? {
              assetFileNames,
              chunkFileNames,
              entryFileNames,
              inlineDynamicImports: true,
            }
          : { assetFileNames, chunkFileNames, entryFileNames },
      },
      cssMinify: false,
      minify: 'esbuild',
    },
    plugins: [vue()],
    resolve: {
      alias: { '@': fileURLToPath(new URL('./src', import.meta.url)) },
    },
    css: { postcss: { plugins: postcssPlugins } },
  }
})
