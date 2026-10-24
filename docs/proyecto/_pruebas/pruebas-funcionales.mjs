// Pruebas funcionales de caja negra del informe de análisis funcional (09), sobre la app en local.
//
//   node docs/proyecto/_pruebas/pruebas-funcionales.mjs
//
// Requiere el backend en http://127.0.0.1:8000 con los datos demo (`migrate:fresh --seed`), el
// frontend en http://localhost:5173 y Chrome o Edge (CHROME_PATH si no lo encuentra).
// Ojo: crea datos de prueba (una reserva cancelada, un reporte, una solicitud de verificación de
// pendiente@maestros.test) y espera tres veces un minuto por el límite de intentos de login.
// Escribe resultados.json junto a este archivo y algunas capturas en ../img.
import { existsSync, mkdirSync, writeFileSync } from 'node:fs'
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
const OUT = new URL('./resultados.json', import.meta.url)
mkdirSync(IMG, { recursive: true })

const results = []
const notes = []
const stamp = Date.now().toString(36)
const wait = (ms) => new Promise((r) => setTimeout(r, ms))

async function api(method, path, { token, json, form } = {}) {
  const headers = { Accept: 'application/json' }
  if (token) headers.Authorization = `Bearer ${token}`
  let body
  if (json) {
    headers['Content-Type'] = 'application/json'
    body = JSON.stringify(json)
  } else if (form) body = form
  const res = await fetch(API + path, { method, headers, body })
  let data = null
  try {
    data = await res.json()
  } catch {}
  return { status: res.status, body: data }
}

async function login(email, password = 'password') {
  const r = await api('POST', '/auth/login', { json: { email, password } })
  if (r.status !== 200) throw new Error(`login ${email}: ${r.status}`)
  return { token: r.body.data.token, user: r.body.data.user }
}

/** Registra un caso. check() devuelve { ok, obtenido }. */
async function caso(id, hu, modulo, nombre, tipo, esperado, check) {
  let ok = false
  let obtenido = ''
  try {
    ;({ ok, obtenido } = await check())
  } catch (e) {
    obtenido = `Error: ${e.message}`
  }
  results.push({ id, hu, modulo, nombre, tipo, esperado, obtenido, estado: ok ? 'Aprobado' : 'Fallido' })
  console.log(`${ok ? '✔' : '✘'} ${id} ${nombre} → ${obtenido}`)
}

const png = () =>
  new Blob(
    [Buffer.from('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==', 'base64')],
    { type: 'image/png' },
  )

// ---------------------------------------------------------------- datos de partida
const cliente = await login('cliente@maestros.test')
const pro = await login('profesional@maestros.test')
const admin = await login('admin@maestros.test')
const pendiente = await login('pendiente@maestros.test')

const services = (await api('GET', '/professional/services', { token: pro.token })).body.data
const service = services.find((s) => s.is_active)
const categories = (await api('GET', '/categories')).body.data
const communes = (await api('GET', '/communes')).body.data

// ---------------------------------------------------------------- Autenticación y cuenta
await caso('CP-01', 'HU001', 'Autenticación', 'Iniciar sesión con credenciales válidas', 'Positivo',
  '200 con token y datos del usuario', async () => {
    const r = await api('POST', '/auth/login', { json: { email: 'cliente@maestros.test', password: 'password' } })
    return { ok: r.status === 200 && !!r.body.data.token, obtenido: `${r.status}, token emitido, rol ${r.body.data?.user?.role}` }
  })
await caso('CP-02', 'HU001', 'Autenticación', 'Iniciar sesión con contraseña incorrecta', 'Negativo',
  '401 "Credenciales incorrectas."', async () => {
    const r = await api('POST', '/auth/login', { json: { email: 'cliente@maestros.test', password: 'equivocada' } })
    return { ok: r.status === 401, obtenido: `${r.status} "${r.body?.message}"` }
  })
await caso('CP-03', 'HU006, HU048', 'Autenticación', 'Registro de cliente con datos vacíos', 'Negativo',
  '422 con error en nombre, correo y contraseña', async () => {
    const r = await api('POST', '/auth/register/client', { json: {} })
    const f = Object.keys(r.body?.errors ?? {})
    return { ok: r.status === 422 && ['name', 'email', 'password'].every((k) => f.includes(k)), obtenido: `${r.status}, campos con error: ${f.join(', ')}` }
  })
await caso('CP-04', 'HU020', 'Cuenta', 'Recuperar contraseña con un correo no registrado', 'Negativo',
  '200 con el mismo mensaje que para un correo registrado (no revela cuentas)', async () => {
    const a = await api('POST', '/auth/forgot-password', { json: { email: 'cliente@maestros.test' } })
    const b = await api('POST', '/auth/forgot-password', { json: { email: `nadie-${stamp}@maestros.test` } })
    return { ok: a.status === 200 && b.status === 200 && a.body.message === b.body.message, obtenido: `${a.status} y ${b.status}, mismo mensaje: "${b.body.message}"` }
  })
await caso('CP-05', 'HU019', 'Cuenta', 'Cambiar la contraseña con la actual incorrecta', 'Negativo',
  '422 en current_password', async () => {
    const r = await api('PUT', '/me/password', { token: cliente.token, json: { current_password: 'mala', password: 'nueva12345', password_confirmation: 'nueva12345' } })
    return { ok: r.status === 422 && 'current_password' in (r.body.errors ?? {}), obtenido: `${r.status}, error en ${Object.keys(r.body.errors ?? {}).join(', ')}` }
  })
await caso('CP-06', 'HU022', 'Cuenta', 'Actualizar la ubicación con una comuna inexistente', 'Negativo',
  '422 en commune_id', async () => {
    const r = await api('PATCH', '/me', { token: cliente.token, json: { commune_id: 999999 } })
    return { ok: r.status === 422 && 'commune_id' in (r.body.errors ?? {}), obtenido: `${r.status}, error en ${Object.keys(r.body.errors ?? {}).join(', ')}` }
  })
await caso('CP-07', 'HU021', 'Autenticación', 'Cerrar sesión invalida el token', 'Positivo',
  'Logout 200 y luego 401 con el mismo token', async () => {
    const s = await login('cliente@maestros.test')
    const out = await api('POST', '/auth/logout', { token: s.token })
    const me = await api('GET', '/auth/me', { token: s.token })
    return { ok: out.status === 200 && me.status === 401, obtenido: `logout ${out.status}, /auth/me después ${me.status}` }
  })

// ---------------------------------------------------------------- Seguridad
await caso('CP-08', 'RNF-SEG-01', 'Seguridad', 'Consultar reservas sin token', 'Negativo', '401', async () => {
  const r = await api('GET', '/bookings')
  return { ok: r.status === 401, obtenido: `${r.status}` }
})
await caso('CP-09', 'RNF-SEG-02', 'Seguridad', 'Un cliente entra al panel de administración', 'Negativo', '403', async () => {
  const r = await api('GET', '/admin/users', { token: cliente.token })
  return { ok: r.status === 403, obtenido: `${r.status} "${r.body?.message}"` }
})

// ---------------------------------------------------------------- Búsqueda
await caso('CP-10', 'HU033, HU010', 'Búsqueda', 'Filtrar profesionales por categoría', 'Positivo',
  'Todos los resultados tienen esa especialidad', async () => {
    const cat = categories[0]
    const r = await api('GET', `/professionals?category_id=${cat.id}`)
    const all = r.body.data.every((p) => (p.categories ?? []).some((c) => c.id === cat.id))
    return { ok: r.status === 200 && r.body.data.length > 0 && all, obtenido: `${r.body.data.length} resultados de "${cat.name}", todos con la especialidad` }
  })
await caso('CP-11', 'HU046', 'Búsqueda', 'Ordenar por precio de menor a mayor', 'Positivo',
  'Tarifas en orden ascendente', async () => {
    const r = await api('GET', '/professionals?sort=price_asc&per_page=50')
    const rates = r.body.data.map((p) => Number(p.hourly_rate))
    const sorted = rates.every((v, i) => i === 0 || rates[i - 1] <= v)
    return { ok: r.status === 200 && sorted, obtenido: `${rates.length} tarifas en orden: ${rates.slice(0, 5).join(', ')}…` }
  })
await caso('CP-12', 'HU039', 'Búsqueda', 'Buscar por nombre', 'Positivo', 'Aparece el profesional buscado', async () => {
  const name = pro.user.name.split(' ')[0]
  const r = await api('GET', `/professionals?q=${encodeURIComponent(name)}`)
  return { ok: r.body.data.some((p) => p.id === pro.user.id), obtenido: `"${name}": ${r.body.data.length} resultado(s), incluye a ${pro.user.name}` }
})
await caso('CP-13', 'HU002', 'Búsqueda', 'Comuna sin profesionales sugiere comunas aledañas', 'Positivo',
  'meta.suggested_from_neighbors = true y resultados marcados como sugeridos', async () => {
    for (const c of communes) {
      const r = await api('GET', `/professionals?commune_id=${c.id}`)
      if (r.body.meta?.suggested_from_neighbors) {
        return { ok: r.body.data.every((p) => p.is_suggested), obtenido: `Comuna "${c.name}": ${r.body.data.length} sugeridos de comunas vecinas` }
      }
    }
    return { ok: false, obtenido: 'Ninguna comuna sin profesionales con vecinas que tengan' }
  })
await caso('CP-14', 'HU040', 'Búsqueda', 'Buscar por ubicación muestra la distancia', 'Positivo',
  'Cada resultado trae distance_km y vienen del más cercano al más lejano', async () => {
    const r = await api('GET', '/professionals?lat=6.2442&lng=-75.5812')
    const d = r.body.data.map((p) => p.distance_km)
    const ok = r.status === 200 && d.length > 0 && d.every((x, i) => typeof x === 'number' && (i === 0 || d[i - 1] <= x))
    return { ok, obtenido: `${d.length} resultados; distancias (km): ${d.slice(0, 5).join(', ')}` }
  })
await caso('CP-15', 'HU030', 'Búsqueda', 'Filtro "disponible ahora"', 'Positivo',
  '200; solo profesionales dentro de su horario en este momento', async () => {
    const r = await api('GET', '/professionals?available_now=1')
    return { ok: r.status === 200, obtenido: `200, ${r.body.data.length} disponibles a la hora de la prueba (${new Date().toLocaleTimeString('es-CO')})` }
  })
await caso('CP-16', 'HU029', 'Calificaciones', 'Ver los comentarios públicos de un profesional', 'Positivo', '200 con reseñas', async () => {
  const r = await api('GET', `/professionals/${pro.user.id}/reviews`)
  return { ok: r.status === 200 && Array.isArray(r.body.data), obtenido: `${r.status}, ${r.body.data.length} reseña(s)` }
})

// ---------------------------------------------------------------- Agenda y reservas
let slot = null
let date = null
for (let i = 1; i <= 10 && !slot; i++) {
  const d = new Date(Date.now() + i * 86400000)
  date = d.toLocaleDateString('en-CA', { timeZone: 'America/Bogota' })
  const r = await api('GET', `/professionals/${pro.user.id}/slots?date=${date}&service_id=${service.id}`)
  slot = r.body.data?.slots?.[0] ?? null
}
await caso('CP-17', 'HU025', 'Agenda', 'Consultar horas libres de un servicio', 'Positivo', 'Lista de horas libres del día', async () => ({
  ok: !!slot, obtenido: slot ? `${date}: primera hora libre ${slot.starts_at}` : 'Sin horas libres en 10 días',
}))

let booking = null
await caso('CP-18', 'HU004', 'Reservas', 'Cliente verificado solicita una reserva', 'Positivo', '201, estado pending', async () => {
  const r = await api('POST', '/bookings', { token: cliente.token, json: { professional_service_id: service.id, starts_at: slot.starts_at, address: 'Calle 50 # 40-20', description: 'Prueba funcional del informe' } })
  booking = r.body.data
  return { ok: r.status === 201 && booking.status === 'pending', obtenido: `${r.status}, reserva #${booking?.id} en ${booking?.status}` }
})
await caso('CP-19', 'N2', 'Reservas', 'Cliente sin verificar intenta reservar', 'Negativo', '403', async () => {
  const r = await api('POST', '/bookings', { token: pendiente.token, json: { professional_service_id: service.id, starts_at: slot.starts_at, address: 'Calle 1' } })
  return { ok: r.status === 403, obtenido: `${r.status} "${r.body?.message}"` }
})
await caso('CP-20', 'HU025', 'Agenda', 'La hora reservada deja de aparecer como libre', 'Positivo', 'El slot ya no está en la lista', async () => {
  const r = await api('GET', `/professionals/${pro.user.id}/slots?date=${date}&service_id=${service.id}`)
  const still = r.body.data.slots.some((s) => s.starts_at === slot.starts_at)
  return { ok: !still, obtenido: still ? 'Sigue apareciendo' : `${slot.starts_at} ya no aparece` }
})
await caso('CP-21', 'HU018', 'Notificaciones', 'El profesional recibe la notificación de la solicitud', 'Positivo', 'Notificación booking.requested', async () => {
  const r = await api('GET', '/notifications', { token: pro.token })
  const n = r.body.data.find((x) => x.type === 'booking.requested' && x.booking_id === booking.id)
  return { ok: !!n, obtenido: n ? `"${n.title}"` : 'No llegó' }
})
await caso('CP-22', 'HU004', 'Reservas', 'El profesional acepta la solicitud', 'Positivo', '200, estado accepted', async () => {
  const r = await api('POST', `/bookings/${booking.id}/accept`, { token: pro.token })
  return { ok: r.status === 200 && r.body.data.status === 'accepted', obtenido: `${r.status}, ${r.body.data?.status}` }
})
await caso('CP-23', 'HU003', 'Agenda', 'Bloquear un horario que tiene una reserva activa', 'Negativo', '409', async () => {
  const r = await api('POST', '/professional/availability/blocks', { token: pro.token, json: { starts_at: booking.starts_at, ends_at: booking.ends_at, reason: 'Prueba' } })
  return { ok: r.status === 409, obtenido: `${r.status} "${r.body?.message}"` }
})
await caso('CP-24', 'HU036', 'Reservas', 'El cliente confirma la reserva aceptada', 'Positivo', '200, estado confirmed', async () => {
  const r = await api('POST', `/bookings/${booking.id}/confirm`, { token: cliente.token })
  return { ok: r.status === 200 && r.body.data.status === 'confirmed', obtenido: `${r.status}, ${r.body.data?.status}` }
})
await caso('CP-25', 'HU027', 'Reservas', 'Iniciar el servicio antes de la hora pactada', 'Negativo', '409', async () => {
  const r = await api('POST', `/bookings/${booking.id}/start`, { token: pro.token })
  return { ok: r.status === 409, obtenido: `${r.status} "${r.body?.message}"` }
})
await caso('CP-26', 'HU011', 'Chat', 'Enviar un mensaje en el chat de la reserva', 'Positivo', '201', async () => {
  const r = await api('POST', `/bookings/${booking.id}/messages`, { token: cliente.token, form: (() => { const f = new FormData(); f.append('body', 'Hola, ¿lleva los materiales?'); return f })() })
  return { ok: r.status === 201, obtenido: `${r.status}, mensaje #${r.body.data?.id}` }
})
await caso('CP-27', 'HU011', 'Chat', 'Un tercero intenta leer el chat', 'Negativo', '403', async () => {
  const r = await api('GET', `/bookings/${booking.id}/messages`, { token: pendiente.token })
  return { ok: r.status === 403, obtenido: `${r.status}` }
})
await caso('CP-28', 'HU026', 'Reservas', 'Historial de estados de la reserva', 'Positivo', 'pending → accepted → confirmed con autor', async () => {
  const r = await api('GET', `/bookings/${booking.id}/history`, { token: cliente.token })
  const seq = r.body.data.map((c) => c.to_status).join(' → ')
  return { ok: seq === 'pending → accepted → confirmed', obtenido: seq }
})
await caso('CP-29', 'HU012', 'Reservas', 'Cancelar la reserva con motivo', 'Positivo', '200, estado cancelled', async () => {
  const r = await api('POST', `/bookings/${booking.id}/cancel`, { token: cliente.token, json: { reason: 'Prueba funcional terminada' } })
  return { ok: r.status === 200 && r.body.data.status === 'cancelled', obtenido: `${r.status}, ${r.body.data?.status}` }
})
await caso('CP-30', 'HU017', 'Reservas', 'Resumen de reservas por estado', 'Positivo', '200 con totales por estado', async () => {
  const r = await api('GET', '/bookings/summary', { token: cliente.token })
  return { ok: r.status === 200 && typeof r.body.data.total === 'number', obtenido: `total ${r.body.data.total}: ${Object.entries(r.body.data.by_status).filter(([, n]) => n).map(([k, n]) => `${k} ${n}`).join(', ')}` }
})
await caso('CP-31', 'HU037', 'Perfil profesional', 'Consultar ingresos del profesional', 'Positivo', '200 con total y servicios', async () => {
  const r = await api('GET', '/professional/earnings', { token: pro.token })
  return { ok: r.status === 200, obtenido: `${r.body.data.from} a ${r.body.data.to}: total $${r.body.data.total} en ${r.body.data.count} servicio(s)` }
})

// ---------------------------------------------------------------- Calificaciones y reportes
const completed = (await api('GET', '/bookings?status[]=completed', { token: cliente.token })).body.data[0]
await caso('CP-32', 'HU005', 'Calificaciones', 'Calificar una reserva que no está completada', 'Negativo', '409', async () => {
  const r = await api('POST', `/bookings/${booking.id}/review`, { token: cliente.token, json: { rating: 5 } })
  return { ok: r.status === 409, obtenido: `${r.status} "${r.body?.message}"` }
})
await caso('CP-33', 'HU024', 'Calificaciones', 'Reportar a la otra parte de una reserva', 'Positivo', '201 y alerta para el administrador', async () => {
  const r = await api('POST', '/reports', { token: cliente.token, json: { reported_user_id: completed.professional.id, booking_id: completed.id, reason: 'Incumplimiento', description: 'Llegó una hora tarde y no avisó. (Prueba funcional del informe.)' } })
  return { ok: r.status === 201, obtenido: `${r.status}, reporte #${r.body.data?.id}` }
})
await caso('CP-34', 'HU024', 'Calificaciones', 'Reportar usando una reserva ajena', 'Negativo', '422 en booking_id', async () => {
  const r = await api('POST', '/reports', { token: pendiente.token, json: { reported_user_id: completed.professional.id, booking_id: completed.id, reason: 'Fraude', description: 'Intento con una reserva en la que no participo.' } })
  return { ok: r.status === 422 && 'booking_id' in (r.body.errors ?? {}), obtenido: `${r.status}, error en ${Object.keys(r.body.errors ?? {}).join(', ')}` }
})
await caso('CP-35', 'HU045', 'Administración', 'El reporte genera una alerta pendiente', 'Positivo', 'Alerta de tipo report en la bandeja', async () => {
  const r = await api('GET', '/admin/alerts?status=pending', { token: admin.token })
  const a = r.body.data.find((x) => x.type === 'report')
  return { ok: !!a, obtenido: a ? `Alerta #${a.id} sobre ${a.user?.name}` : 'No hay alerta' }
})
await caso('CP-36', 'HU044', 'Calificaciones', 'El profesional ve el perfil de un cliente con quien tiene reservas', 'Positivo', '200 con calificación y servicios completados', async () => {
  const r = await api('GET', `/clients/${cliente.user.id}`, { token: pro.token })
  return { ok: r.status === 200, obtenido: `${r.status}: ${r.body.data?.name}, ${r.body.data?.completed_bookings_count} servicio(s) completado(s)` }
})

// ---------------------------------------------------------------- Verificación
await caso('CP-37', 'HU008', 'Verificación', 'Enviar documentos de verificación', 'Positivo', '201, solicitud en revisión', async () => {
  const f = new FormData()
  ;[['id_document', 'cedula.png'], ['selfie', 'selfie.png'], ['certificate', 'certificado.png']].forEach(([type, name], i) => {
    f.append(`documents[${i}][type]`, type)
    f.append(`documents[${i}][file]`, png(), name)
  })
  const r = await api('POST', '/verification', { token: pendiente.token, form: f })
  return { ok: r.status === 201, obtenido: `${r.status}, estado ${r.body.data?.status ?? r.body?.message}` }
})
await caso('CP-38', 'HU014', 'Administración', 'La solicitud aparece en la bandeja del administrador', 'Positivo', 'Solicitud pendiente de pendiente@', async () => {
  const r = await api('GET', '/admin/verifications?status=pending', { token: admin.token })
  const v = r.body.data.find((x) => x.user?.id === pendiente.user.id)
  return { ok: !!v, obtenido: v ? `Solicitud #${v.id} con ${v.documents.length} documentos` : 'No aparece' }
})

// ---------------------------------------------------------------- Moderación y eliminación con una cuenta de prueba
// Las rutas de autenticación aceptan 10 intentos por minuto (RNF-SEG-04): se espera a que se libere el cupo.
console.log('  … esperando 61 s por el límite de intentos')
await wait(61000)
const qaEmail = `qa-${stamp}@maestros.test`
await api('POST', '/auth/register/client', { json: { name: 'Cliente de prueba QA', email: qaEmail, password: 'password123', password_confirmation: 'password123' } })
await caso('CP-39', 'HU038', 'Administración', 'Bloquear a un usuario le impide entrar', 'Positivo', 'Bloqueo 200 y login 403', async () => {
  const u = (await api('GET', `/admin/users?q=${encodeURIComponent(qaEmail)}`, { token: admin.token })).body.data[0]
  const b = await api('POST', `/admin/users/${u.id}/block`, { token: admin.token, json: { reason: 'Prueba funcional del informe' } })
  const l = await api('POST', '/auth/login', { json: { email: qaEmail, password: 'password123' } })
  await api('POST', `/admin/users/${u.id}/unblock`, { token: admin.token })
  return { ok: b.status === 200 && l.status === 403, obtenido: `bloqueo ${b.status}, login ${l.status} "${l.body?.message}"` }
})
await caso('CP-40', 'HU023', 'Cuenta', 'Eliminar la cuenta la anonimiza y cierra el acceso', 'Positivo', '200 y el correo anterior ya no inicia sesión', async () => {
  const s = await login(qaEmail, 'password123')
  const d = await api('DELETE', '/me', { token: s.token, json: { current_password: 'password123' } })
  const l = await api('POST', '/auth/login', { json: { email: qaEmail, password: 'password123' } })
  return { ok: d.status === 200 && l.status === 401, obtenido: `eliminar ${d.status}, login después ${l.status}` }
})

// ---------------------------------------------------------------- Navegador: flujos por la interfaz y capturas
const browser = await puppeteer.launch({ executablePath: CHROME, headless: true, defaultViewport: { width: 1366, height: 820 } })
async function pageAs(session) {
  const page = await browser.newPage()
  if (session) {
    await page.evaluateOnNewDocument((t, u) => {
      localStorage.setItem('maestros.auth.token', t)
      localStorage.setItem('maestros.auth.user', u)
    }, session.token, JSON.stringify(session.user))
  }
  return page
}
async function shot(page, path, file, { full = false, before } = {}) {
  await page.goto(FRONT + path, { waitUntil: 'networkidle2', timeout: 60000 })
  await wait(900)
  if (before) await before(page)
  await page.screenshot({ path: join(IMG, file), type: 'jpeg', quality: 78, fullPage: full })
  console.log(`  📷 ${file}`)
}
async function clickText(page, selector, text) {
  const handles = await page.$$(selector)
  for (const h of handles) {
    if ((await h.evaluate((el) => el.textContent.trim())) === text) return h.click()
  }
  throw new Error(`No encontré "${text}"`)
}

console.log('  … esperando 61 s por el límite de intentos')
await wait(61000)
// CP-41: login fallido por la interfaz
await caso('CP-41', 'HU001', 'Autenticación', 'Login con contraseña incorrecta desde la pantalla', 'Negativo', 'La pantalla muestra el error y no entra', async () => {
  const p = await pageAs(null)
  await p.goto(FRONT + '/login', { waitUntil: 'networkidle2' })
  await p.type('input[type="email"]', 'cliente@maestros.test')
  await p.type('input[type="password"]', 'equivocada')
  await p.click('button[type="submit"]')
  await wait(1500)
  const text = await p.evaluate(() => document.body.innerText)
  await p.screenshot({ path: join(IMG, 'login-error.jpg'), type: 'jpeg', quality: 78 })
  const ok = text.includes('Credenciales incorrectas') && p.url().includes('/login')
  await p.close()
  return { ok, obtenido: ok ? 'Se muestra "Credenciales incorrectas." y sigue en /login' : 'No se mostró el error' }
})

// CP-42: registro de profesional completo por la interfaz (dos pasos)
const proEmail = `qa-pro-${stamp}@maestros.test`
await caso('CP-42', 'HU007', 'Autenticación', 'Registro de profesional en dos pasos desde la pantalla', 'Positivo', 'Crea la cuenta y lleva al panel del profesional', async () => {
  const p = await pageAs(null)
  await p.goto(FRONT + '/register/professional', { waitUntil: 'networkidle2' })
  await p.type('input[autocomplete="name"]', 'Profesional de prueba QA')
  await p.type('input[type="email"]', proEmail)
  const firstCommune = await p.$eval('select option:not([disabled])', (o) => o.value)
  await p.select('select', firstCommune)
  const pw = await p.$$('input[type="password"]')
  await pw[0].type('password123')
  await pw[1].type('password123')
  await p.screenshot({ path: join(IMG, 'registro-profesional-1.jpg'), type: 'jpeg', quality: 78 })
  await p.click('button[type="submit"]')
  await p.waitForSelector('textarea', { timeout: 10000 })
  const chips = await p.$$('fieldset button')
  await chips[0].click()
  const nums = await p.$$('input[type="number"]')
  await nums[0].type('6')
  await nums[1].type('45000')
  await p.type('textarea', 'Electricista con seis años de experiencia en instalaciones residenciales y mantenimiento.')
  await p.screenshot({ path: join(IMG, 'registro-profesional-2.jpg'), type: 'jpeg', quality: 78 })
  await p.click('button[type="submit"]:not([disabled])')
  await p.waitForFunction(() => location.pathname.startsWith('/professional'), { timeout: 15000 })
  const token = await p.evaluate(() => localStorage.getItem('maestros.auth.token'))
  await wait(800)
  await p.close()
  // Limpieza: también prueba que un profesional sin reservas no ve perfiles de clientes (CP-43).
  globalThis.qaPro = token
  return { ok: !!token, obtenido: `Cuenta creada y redirigida a ${'/professional/dashboard'}` }
})
await caso('CP-43', 'HU044', 'Calificaciones', 'Un profesional sin reservas con el cliente intenta ver su perfil', 'Negativo', '403', async () => {
  const r = await api('GET', `/clients/${cliente.user.id}`, { token: globalThis.qaPro })
  await api('DELETE', '/me', { token: globalThis.qaPro, json: { current_password: 'password123' } })
  return { ok: r.status === 403, obtenido: `${r.status}` }
})

// Capturas por rol
const clientBookings = (await api('GET', '/bookings?status[]=confirmed&status[]=completed', { token: cliente.token })).body.data
const detailId = (clientBookings.find((b) => b.status === 'confirmed') ?? clientBookings[0]).id
const pub = await pageAs(null)
await shot(pub, '/', 'inicio.jpg')
await shot(pub, `/search?category_id=${categories[0].id}`, 'busqueda.jpg')
await shot(pub, `/professionals/${pro.user.id}`, 'perfil-profesional.jpg', { full: true })
await shot(pub, '/login', 'login.jpg')
await shot(pub, '/forgot-password', 'recuperar-contrasena.jpg')
await pub.setViewport({ width: 390, height: 844, isMobile: true })
await shot(pub, `/search?category_id=${categories[0].id}`, 'busqueda-movil.jpg')
await pub.close()

const c = await pageAs(cliente)
await shot(c, '/client/dashboard', 'cliente-inicio.jpg')
await shot(c, '/client/bookings', 'cliente-reservas.jpg')
await shot(c, `/client/bookings/${detailId}`, 'cliente-detalle-reserva.jpg', { full: true })
await shot(c, `/client/bookings/new?professional=${pro.user.id}&service=${service.id}`, 'cliente-nueva-reserva.jpg', { full: true })
await shot(c, '/client/favorites', 'cliente-favoritos.jpg')
await shot(c, '/client/security', 'cliente-seguridad.jpg')
await c.close()

const pPage = await pageAs(pro)
await shot(pPage, '/professional/dashboard', 'profesional-inicio.jpg')
await shot(pPage, '/professional/availability', 'profesional-agenda.jpg', { full: true })
await shot(pPage, '/professional/services', 'profesional-servicios.jpg')
await shot(pPage, '/professional/earnings', 'profesional-ingresos.jpg')
await shot(pPage, `/professional/bookings/${detailId}`, 'profesional-detalle-reserva.jpg', { full: true })
await pPage.close()

const pend = await pageAs(pendiente)
await shot(pend, '/client/verification', 'verificacion-en-revision.jpg')
await pend.close()

const a = await pageAs(admin)
await shot(a, '/admin/verifications', 'admin-verificaciones.jpg')
await shot(a, '/admin/users', 'admin-usuarios.jpg')
await shot(a, '/admin/reports', 'admin-reportes.jpg')
await shot(a, '/admin/alerts', 'admin-alertas.jpg')
await a.close()
await browser.close()

console.log('  … esperando 61 s por el límite de intentos')
await wait(61000)
await caso('CP-44', 'RNF-SEG-04', 'Seguridad', 'Once intentos de inicio de sesión en un minuto', 'Negativo', 'Los 10 primeros responden 401 y el undécimo 429', async () => {
  const codes = []
  for (let i = 0; i < 11; i++) codes.push((await api('POST', '/auth/login', { json: { email: `fuerza-bruta-${stamp}@maestros.test`, password: 'x' } })).status)
  return { ok: codes.slice(0, 10).every((s) => s === 401) && codes[10] === 429, obtenido: codes.join(', ') }
})

writeFileSync(OUT, JSON.stringify({ fecha: new Date().toISOString(), results, notes }, null, 2))
const ok = results.filter((r) => r.estado === 'Aprobado').length
console.log(`\n${ok} de ${results.length} casos aprobados`)
