// Capturas de pantalla del informe de análisis funcional (09), sobre la app en local.
//
//   node docs/proyecto/_pruebas/capturas.mjs
//
// Mismos requisitos que pruebas-funcionales.mjs (correrlo antes, para que haya datos en las bandejas
// del administrador). Reemplaza las descripciones de relleno del profesional demo por textos
// realistas y crea una reserva confirmada con chat. Guarda las imágenes en ../img.
import { existsSync } from 'node:fs'
import { createRequire } from 'node:module'
import { fileURLToPath } from 'node:url'
import { join } from 'node:path'

const BACKEND = fileURLToPath(new URL('../../../', import.meta.url))
const require = createRequire(join(BACKEND, 'package.json'))
const puppeteer = require('puppeteer-core')
const CHROME = [
  process.env.CHROME_PATH,
  'C:/Program Files/Google/Chrome/Application/chrome.exe',
  'C:/Program Files (x86)/Microsoft/Edge/Application/msedge.exe',
  '/Applications/Google Chrome.app/Contents/MacOS/Google Chrome',
  '/usr/bin/google-chrome',
].find((path) => path && existsSync(path))

const API = 'http://127.0.0.1:8000/api/v1'
const FRONT = 'http://localhost:5173'
const IMG = join(BACKEND, 'docs/proyecto/img')
const wait = (ms) => new Promise((r) => setTimeout(r, ms))

async function api(method, path, { token, json } = {}) {
  const headers = { Accept: 'application/json' }
  if (token) headers.Authorization = `Bearer ${token}`
  if (json) headers['Content-Type'] = 'application/json'
  const res = await fetch(API + path, { method, headers, body: json ? JSON.stringify(json) : undefined })
  return { status: res.status, body: await res.json().catch(() => null) }
}
const login = async (email) => (await api('POST', '/auth/login', { json: { email, password: 'password' } })).body.data

const cliente = await login('cliente@maestros.test')
const pro = await login('profesional@maestros.test')
const admin = await login('admin@maestros.test')
const pendiente = await login('pendiente@maestros.test')

// ---------------------------------------------------------------- datos legibles del profesional demo
await api('PATCH', '/professional/profile', {
  token: pro.token,
  json: {
    description:
      'Plomero con siete años de experiencia en viviendas y apartamentos de Medellín. Reparo fugas, instalo grifería y destapo tuberías con equipo propio. Llego puntual, explico el trabajo antes de empezar y dejo todo limpio.',
  },
})
const DESCRIPTIONS = {
  'Reparación de fugas': 'Detección y reparación de fugas en lavamanos, sanitarios, cocinas y tubería a la vista. Incluye revisión de presión.',
  'Instalación de grifería': 'Instalación o cambio de grifería de cocina, lavamanos y ducha. El cliente puede comprar la grifería o la cotizamos.',
  'Destape de tuberías': 'Destape de sifones, lavaplatos, sanitarios y desagües con sonda manual o eléctrica, sin romper.',
}
const services = (await api('GET', '/professional/services', { token: pro.token })).body.data
for (const s of services) {
  if (DESCRIPTIONS[s.title]) await api('PATCH', `/professional/services/${s.id}`, { token: pro.token, json: { description: DESCRIPTIONS[s.title] } })
}
const service = services.find((s) => s.title === 'Destape de tuberías') ?? services[0]

// Una reserva confirmada con una conversación realista, para el detalle y el chat.
const existing = (await api('GET', '/bookings?status[]=confirmed', { token: cliente.token })).body.data.find((b) =>
  (b.description ?? '').startsWith('El lavaplatos de la cocina'),
)
let showcase = existing
if (!showcase) {
  let slot = null
  for (let i = 2; i <= 12 && !slot; i++) {
    const d = new Date(Date.now() + i * 86400000).toLocaleDateString('en-CA', { timeZone: 'America/Bogota' })
    slot = (await api('GET', `/professionals/${pro.user.id}/slots?date=${d}&service_id=${service.id}`, { token: cliente.token })).body.data?.slots?.[1] ?? null
  }
  showcase = (await api('POST', '/bookings', {
    token: cliente.token,
    json: { professional_service_id: service.id, starts_at: slot.starts_at, address: 'Carrera 43A # 1-50, apto 1204', description: 'El lavaplatos de la cocina no desagua desde ayer. Ya probé con destapador y no funcionó.' },
  })).body.data
  await api('POST', `/bookings/${showcase.id}/accept`, { token: pro.token })
  await api('POST', `/bookings/${showcase.id}/confirm`, { token: cliente.token })
  const say = async (who, body) => {
    const f = new FormData()
    f.append('body', body)
    await fetch(`${API}/bookings/${showcase.id}/messages`, { method: 'POST', headers: { Accept: 'application/json', Authorization: `Bearer ${who.token}` }, body: f })
  }
  await say(cliente, 'Hola, Juan. ¿El edificio le pide algún dato para dejarlo entrar?')
  await say(pro, 'Hola, Camila. Sí, por favor regístreme en portería con mi cédula. Llevo la sonda eléctrica.')
  await say(cliente, 'Listo, ya lo registré. ¡Gracias!')
}
console.log(`Reserva de muestra #${showcase.id}`)

// ---------------------------------------------------------------- capturas
const browser = await puppeteer.launch({ executablePath: CHROME, headless: true })

async function open(session, { width = 1366, height = 820, mobile = false } = {}) {
  const ctx = await browser.createBrowserContext()
  const page = await ctx.newPage()
  await page.setViewport({ width, height, isMobile: mobile, hasTouch: mobile })
  if (session) {
    await page.evaluateOnNewDocument((t, u) => {
      localStorage.setItem('maestros.auth.token', t)
      localStorage.setItem('maestros.auth.user', u)
    }, session.token, JSON.stringify(session.user))
  }
  return { page, close: () => ctx.close() }
}
async function go(page, path) {
  await page.goto(FRONT + path, { waitUntil: 'networkidle2', timeout: 60000 })
  await page.addStyleTag({ content: '[id*="devtools"],[class*="devtools"]{display:none!important}' })
  await wait(900)
}
async function snap(page, file, full = false) {
  await page.addStyleTag({ content: '[id*="devtools"],[class*="devtools"]{display:none!important}' })
  if (full) {
    // Con scroll, la barra fija queda a mitad de la imagen de página completa.
    await page.evaluate(() => window.scrollTo(0, 0))
    await wait(300)
  }
  await page.screenshot({ path: join(IMG, file), type: 'jpeg', quality: 80, fullPage: full })
  console.log(`  📷 ${file}`)
}
async function clickByText(page, selector, text) {
  for (const h of await page.$$(selector)) {
    if ((await h.evaluate((el) => el.textContent.trim())).includes(text)) return h.click()
  }
  throw new Error(`No encontré "${text}"`)
}
const categories = (await api('GET', '/categories')).body.data
const plomeria = categories.find((c) => c.name === 'Plomería') ?? categories[0]

// Público
{
  const { page, close } = await open(null)
  await go(page, '/'); await snap(page, 'inicio.jpg')
  await go(page, `/search?category_id=${plomeria.id}`); await snap(page, 'busqueda.jpg')
  await go(page, `/professionals/${pro.user.id}`); await snap(page, 'perfil-profesional.jpg', true)
  await go(page, '/login'); await snap(page, 'login.jpg')
  await go(page, '/forgot-password'); await snap(page, 'recuperar-contrasena.jpg')
  // Registro de profesional: paso 1 y 2 sin enviar (no crea la cuenta).
  await go(page, '/register/professional')
  await page.type('input[autocomplete="name"]', 'Laura Gómez')
  await page.type('input[type="email"]', 'laura.gomez@correo.com')
  await page.select('select', await page.$eval('select option:not([disabled])', (o) => o.value))
  const pw = await page.$$('input[type="password"]')
  await pw[0].type('password123'); await pw[1].type('password123')
  await snap(page, 'registro-profesional-1.jpg')
  await page.click('button[type="submit"]')
  await page.waitForSelector('textarea')
  await clickByText(page, 'fieldset button', 'Electricidad')
  const nums = await page.$$('input[type="number"]')
  await nums[0].type('6'); await nums[1].type('45000')
  await page.type('textarea', 'Electricista con seis años de experiencia en instalaciones residenciales, tableros y mantenimiento preventivo.')
  await snap(page, 'registro-profesional-2.jpg')
  // Login con contraseña incorrecta (un intento).
  await go(page, '/login')
  await page.type('input[type="email"]', 'cliente@maestros.test')
  await page.type('input[type="password"]', 'equivocada')
  await page.click('button[type="submit"]')
  await wait(1500)
  await snap(page, 'login-error.jpg')
  await close()
}
{
  const { page, close } = await open(null, { width: 390, height: 844, mobile: true })
  await go(page, `/search?category_id=${plomeria.id}`); await snap(page, 'busqueda-movil.jpg')
  await go(page, `/professionals/${pro.user.id}`); await snap(page, 'perfil-movil.jpg')
  await close()
}

// Cliente
{
  const { page, close } = await open(cliente)
  await go(page, '/client/dashboard'); await snap(page, 'cliente-inicio.jpg')
  await go(page, '/client/bookings'); await snap(page, 'cliente-reservas.jpg')
  await go(page, `/client/bookings/${showcase.id}`); await snap(page, 'cliente-detalle-reserva.jpg', true)
  await go(page, `/client/bookings/new?professional=${pro.user.id}&service=${service.id}`)
  for (const day of await page.$$('[aria-label="Día"] button')) {
    await day.click(); await wait(700)
    const times = await page.$$('[aria-label="Hora"] button')
    if (times.length) { await times[0].click(); break }
  }
  await page.type('textarea', 'El sifón del lavamanos del baño principal gotea.')
  await wait(400)
  await snap(page, 'cliente-nueva-reserva.jpg', true)
  await go(page, '/client/favorites'); await snap(page, 'cliente-favoritos.jpg')
  await go(page, '/client/profile'); await snap(page, 'cliente-perfil.jpg')
  await go(page, '/client/security'); await snap(page, 'cliente-seguridad.jpg')
  await close()
}

// Profesional
{
  const { page, close } = await open(pro)
  await go(page, '/professional/dashboard'); await snap(page, 'profesional-inicio.jpg')
  await go(page, '/professional/bookings'); await snap(page, 'profesional-reservas.jpg')
  await go(page, '/professional/availability'); await snap(page, 'profesional-agenda.jpg', true)
  await go(page, '/professional/services'); await snap(page, 'profesional-servicios.jpg')
  await go(page, '/professional/about-me'); await snap(page, 'profesional-sobre-mi.jpg')
  await go(page, '/professional/earnings'); await snap(page, 'profesional-ingresos.jpg')
  await go(page, `/professional/bookings/${showcase.id}`)
  await clickByText(page, 'button', 'Ver perfil del cliente')
  await wait(1200)
  await snap(page, 'profesional-perfil-cliente.jpg')
  await close()
}

// Cliente sin verificar y administrador
{
  const { page, close } = await open(pendiente)
  await go(page, '/client/verification'); await snap(page, 'verificacion-en-revision.jpg')
  await close()
}
{
  const { page, close } = await open(admin)
  await go(page, '/admin/verifications'); await snap(page, 'admin-verificaciones.jpg')
  await clickByText(page, 'button', 'Revisar documentos')
  await wait(1200)
  await snap(page, 'admin-verificacion-detalle.jpg')
  await go(page, '/admin/users'); await snap(page, 'admin-usuarios.jpg')
  await go(page, '/admin/reports'); await snap(page, 'admin-reportes.jpg')
  await go(page, '/admin/alerts'); await snap(page, 'admin-alertas.jpg')
  await close()
}
await browser.close()
console.log('listo')
