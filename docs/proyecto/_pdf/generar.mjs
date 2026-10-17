#!/usr/bin/env node
/**
 * Genera los PDF de la documentación del proyecto a partir de los Markdown de docs/proyecto.
 *
 *   npm run docs:pdf
 *
 * - Cada `NN-*.md` produce `pdf/NN-*.pdf`, y además se arma `pdf/documentacion-completa.pdf`.
 * - Los diagramas ```mermaid se dibujan con Mermaid antes de imprimir.
 * - Usa el Chrome o Edge instalado (puppeteer-core no descarga navegador). Si no lo encuentra,
 *   indica la ruta con la variable CHROME_PATH.
 * - La portada sale de `portada.json` (integrantes, institución, curso…).
 *
 * Los Markdown son la fuente: no edites los PDF a mano, se regeneran.
 */
import { existsSync, mkdirSync, readFileSync, readdirSync, writeFileSync } from 'node:fs'
import { dirname, join, resolve } from 'node:path'
import { fileURLToPath, pathToFileURL } from 'node:url'

import { marked } from 'marked'
import puppeteer from 'puppeteer-core'

const HERE = dirname(fileURLToPath(import.meta.url))
const DOCS = resolve(HERE, '..')
const OUT = join(DOCS, 'pdf')

const CHROME_CANDIDATES = [
  process.env.CHROME_PATH,
  'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
  'C:\\Program Files (x86)\\Microsoft\\Edge\\Application\\msedge.exe',
  'C:\\Program Files\\Microsoft\\Edge\\Application\\msedge.exe',
  '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
  '/usr/bin/google-chrome',
  '/usr/bin/chromium',
  '/usr/bin/chromium-browser',
].filter(Boolean)

const portada = JSON.parse(readFileSync(join(HERE, 'portada.json'), 'utf8'))
const css = readFileSync(join(HERE, 'estilos.css'), 'utf8')

/** Los bloques ```mermaid pasan a <pre class="mermaid"> para que Mermaid los dibuje. */
const renderer = new marked.Renderer()
const baseCode = renderer.code.bind(renderer)
renderer.code = (token) =>
  token.lang === 'mermaid'
    ? `<pre class="mermaid">${token.text.replace(/</g, '&lt;')}</pre>`
    : baseCode(token)
marked.use({ renderer, gfm: true })

function escape(text) {
  return String(text).replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' })[c])
}

function cover(title) {
  const people = portada.integrantes.map((p) => `<li>${escape(p)}</li>`).join('')
  return `<section class="portada">
    <p class="proyecto">${escape(portada.proyecto)}</p>
    <h1 class="portada-titulo">${escape(title)}</h1>
    <p class="subtitulo">${escape(portada.subtitulo)}</p>
    <div class="portada-datos">
      <p><strong>Integrantes</strong></p><ul>${people}</ul>
      <p><strong>${escape(portada.institucion)}</strong><br>${escape(portada.programa)}<br>${escape(portada.curso)}</p>
      ${portada.docente ? `<p>Docente: ${escape(portada.docente)}</p>` : ''}
      <p>${escape(portada.ciudad)}, ${escape(portada.fecha)}</p>
    </div>
  </section>`
}

function page(title, body) {
  return `<!doctype html><html lang="es"><head><meta charset="utf-8">
  <title>${escape(title)}</title>
  <base href="${pathToFileURL(DOCS).href}/">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
  <style>${css}</style>
  <script src="https://cdn.jsdelivr.net/npm/mermaid@11/dist/mermaid.min.js"></script>
  </head><body>${cover(title)}<main>${body}</main>
  <script>
    mermaid.initialize({ startOnLoad: false, theme: 'neutral', securityLevel: 'loose', fontFamily: 'Plus Jakarta Sans, sans-serif' })
    // Mermaid mide el texto al dibujar: primero hay que esperar la fuente, o recorta las etiquetas.
    document.fonts.ready
      .then(() => mermaid.run({ querySelector: '.mermaid' }))
      .finally(() => { window.__listo = true })
  </script></body></html>`
}

/** Título = primer encabezado de nivel 1; el resto del documento va después de la portada. */
function split(markdown) {
  const match = markdown.match(/^#\s+(.+)$/m)
  const title = match ? match[1].trim() : 'Documento'
  return { title, body: match ? markdown.replace(match[0], '') : markdown }
}

async function print(browser, html, file) {
  const tmp = join(OUT, `.${Date.now()}.html`)
  writeFileSync(tmp, html)
  const tab = await browser.newPage()
  await tab.goto(pathToFileURL(tmp).href, { waitUntil: 'networkidle0', timeout: 120_000 })
  await tab.waitForFunction('window.__listo === true', { timeout: 120_000 })
  await tab.pdf({
    path: file,
    format: 'A4',
    printBackground: true,
    margin: { top: '18mm', bottom: '18mm', left: '16mm', right: '16mm' },
    displayHeaderFooter: true,
    headerTemplate: '<span></span>',
    footerTemplate: `<div style="width:100%;font-size:8px;color:#64748b;padding:0 16mm;display:flex;justify-content:space-between;font-family:sans-serif">
      <span>${escape(portada.proyecto)}</span><span><span class="pageNumber"></span> / <span class="totalPages"></span></span></div>`,
  })
  await tab.close()
  const { rmSync } = await import('node:fs')
  rmSync(tmp)
}

const chrome = CHROME_CANDIDATES.find((path) => existsSync(path))
if (!chrome) {
  console.error('No encontré Chrome ni Edge. Indica la ruta con CHROME_PATH.')
  process.exit(1)
}

const files = readdirSync(DOCS).filter((name) => /^\d{2}-.+\.md$/.test(name)).sort()
mkdirSync(OUT, { recursive: true })

const browser = await puppeteer.launch({ executablePath: chrome, headless: true })
const sections = []
for (const name of files) {
  const { title, body } = split(readFileSync(join(DOCS, name), 'utf8'))
  const html = marked.parse(body)
  sections.push(`<article class="seccion"><h1>${escape(title)}</h1>${html}</article>`)
  const pdf = join(OUT, name.replace(/\.md$/, '.pdf'))
  await print(browser, page(title, html), pdf)
  console.log(`✔ ${name} → pdf/${name.replace(/\.md$/, '.pdf')}`)
}
await print(browser, page('Documentación del proyecto', sections.join('\n')), join(OUT, 'documentacion-completa.pdf'))
console.log('✔ pdf/documentacion-completa.pdf')
await browser.close()
