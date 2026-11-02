const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const sandbox = { module: { exports: {} }, URL, AbortController };
vm.runInNewContext(fs.readFileSync(require('node:path').join(__dirname, '../app/public/js/owner-order-alerts.js'), 'utf8'), sandbox);
const { Alerts, mount } = sandbox.module.exports;

function harness() {
    const values = new Map();
    const queues = new Map();
    const storage = { getItem: key => values.get(key) ?? null, setItem: (key, value) => values.set(key, value) };
    let time = 1000000;
    let requests = 0;
    let response = { status: 200, data: { cursor: '0', unreviewed_count: 0, orders: [] } };
    const lock = async (key, fn) => {
        const previous = queues.get(key) || Promise.resolve();
        let release;
        const next = new Promise(resolve => { release = resolve; });
        queues.set(key, next);
        await previous;
        try { return await fn(); } finally { release(); }
    };
    async function tab(cursor = '0', count = 0, key = 'store-1', coordinated = true) {
        const observed = { sounds: 0, unlocks: 0, updates: [], statuses: [], stops: 0 };
        const alerts = new Alerts({ key, cursor, count, storage, coordinated, lock, now: () => time,
            request: async () => { requests++; if (response instanceof Error) throw response; return response; },
            unlock: async () => { observed.unlocks++; }, play: async () => { observed.sounds++; },
            onUpdate: (count, alert) => observed.updates.push({ count, alert }),
            onStatus: message => observed.statuses.push(message), onControls: () => {}, onStop: () => observed.stops++,
        });
        await alerts.initialize();
        return { alerts, observed };
    }
    return { tab, values, storage, requests: () => requests, advance: ms => { time += ms; },
        reply: (cursor, count, ids = []) => { response = { status: 200, data: { cursor, unreviewed_count: count,
            orders: ids.map(id => ({ id, reference: `REF-${id}` })) } }; },
        fail: status => { response = { status }; }, networkError: () => { response = new Error('Offline'); },
    };
}

test('históricos sin alarma; no se desbloquea ni reproduce audio sin interacción', async () => {
    const h = harness(); const t = await h.tab('9', 4);
    h.reply('9', 4);
    await t.alerts.test(); await t.alerts.poll();
    assert.equal(t.observed.unlocks, 0); assert.equal(t.observed.sounds, 0);
    assert.equal(t.observed.updates.at(-1).count, 4); assert.equal(t.observed.updates.at(-1).alert, null);
    await t.alerts.activate(); assert.equal(t.observed.sounds, 0);
    await t.alerts.test(); assert.equal(t.observed.sounds, 1);
});
test('dos pestañas: una consulta cada 20s y una señal agrupada, sin duplicados', async () => {
    const h = harness(); const a = await h.tab(); const b = await h.tab();
    await a.alerts.activate(); await b.alerts.activate();
    h.reply('2', 2, ['1', '2']);
    await Promise.all([a.alerts.poll(), b.alerts.poll(), b.alerts.receive('')]);
    assert.equal(h.requests(), 1); assert.equal(a.observed.sounds + b.observed.sounds, 1);
    assert.equal(a.observed.updates.at(-1).alert.orders.length, 2);
    h.advance(20000); await Promise.all([a.alerts.poll(), b.alerts.poll()]);
    assert.equal(h.requests(), 2); assert.equal(a.observed.sounds + b.observed.sounds, 1);
    const reload = await h.tab('2', 2); await reload.alerts.activate();
    await reload.alerts.poll(); assert.equal(reload.observed.sounds, 0);
});
test('cursor detecta llegada aun con contador igual; cerrar no cambia contador ni pide al servidor', async () => {
    const h = harness(); const t = await h.tab('1', 1); await t.alerts.activate();
    h.reply('2', 1, ['2']); await t.alerts.poll();
    assert.equal(t.observed.sounds, 1); assert.equal(t.observed.updates.at(-1).count, 1);
    t.alerts.dismiss(); assert.equal(t.observed.updates.at(-1).alert, null);
    assert.equal(t.observed.updates.at(-1).count, 1); assert.equal(h.requests(), 1);
    h.advance(20000); h.reply('2', 0); await t.alerts.poll();
    assert.equal(t.observed.updates.at(-1).count, 0); assert.equal(t.observed.sounds, 1);
});
test('silencio compartido, prueba deshabilitada y reactivación sin alarmas atrasadas', async () => {
    const h = harness(); const a = await h.tab(); const b = await h.tab();
    await a.alerts.activate(); await b.alerts.activate(); a.alerts.mute();
    h.reply('1', 1, ['1']); await b.alerts.poll(); await b.alerts.test();
    assert.equal(a.observed.sounds + b.observed.sounds, 0);
    await b.alerts.activate(); assert.equal(b.observed.sounds, 0);
    h.advance(20000); h.reply('2', 2, ['2']); await b.alerts.poll();
    assert.equal(b.observed.sounds, 1);
});
test('sesión vencida detiene consultas en todas las pestañas', async () => {
    const h = harness(); const a = await h.tab(); const b = await h.tab();
    h.fail(401); await a.alerts.poll(); b.alerts.receive(':expired');
    h.advance(300000); await a.alerts.poll(); await b.alerts.poll();
    assert.equal(h.requests(), 1); assert.equal(a.observed.stops, 1); assert.equal(b.observed.stops, 1);
});
test('errores espacian reintentos sin repetir avisos; recuperación vuelve a 20s', async () => {
    const h = harness(); const t = await h.tab(); await t.alerts.activate();
    h.networkError(); await t.alerts.poll();
    h.advance(20000); await t.alerts.poll(); assert.equal(h.requests(), 1);
    h.advance(20000); await t.alerts.poll(); assert.equal(h.requests(), 2);
    h.advance(40000); await t.alerts.poll(); assert.equal(h.requests(), 2);
    h.advance(40000); h.reply('1', 1, ['1']); await t.alerts.poll();
    assert.equal(h.requests(), 3); assert.equal(t.observed.sounds, 1);
    h.advance(20000); await t.alerts.poll(); assert.equal(h.requests(), 4); assert.equal(t.observed.sounds, 1);
});
test('preferencias separadas por tienda y sin sonido si no hay coordinación segura', async () => {
    const h = harness(); const a = await h.tab(); const other = await h.tab('0', 0, 'store-2');
    await a.alerts.activate(); assert.equal(other.alerts.enabled(), false);
    const fallback = await h.tab('0', 0, 'store-3', false);
    await fallback.alerts.activate(); assert.equal(fallback.observed.unlocks, 0);
    h.reply('1', 1, ['1']); await fallback.alerts.poll();
    assert.equal(fallback.observed.sounds, 0); assert.equal(fallback.observed.updates.at(-1).count, 1);
});

test('adaptador actualiza título, contador, aviso y controles sin reproducir al iniciar', async () => {
    const handlers = new Map();
    const nodes = new Map();
    const node = selector => {
        if (!nodes.has(selector)) nodes.set(selector, { textContent: '', hidden: true, disabled: false,
            addEventListener: (event, fn) => handlers.set(selector + ':' + event, fn), setAttribute: () => {} });
        return nodes.get(selector);
    };
    const values = new Map();
    const callbacks = new Map(); let timerId = 0; let audioStarts = 0;
    const win = {
        navigator: { locks: { request: async (name, fn) => fn() } },
        localStorage: { getItem: key => values.get(key) ?? null, setItem: (key, value) => values.set(key, value), removeItem: key => values.delete(key) },
        document: { title: 'Catálogo · Oxman', querySelectorAll: () => [node('counter')], addEventListener: () => {} },
        addEventListener: () => {},
        setTimeout: (callback, delay) => { callbacks.set(++timerId, { callback, delay }); return timerId; }, clearTimeout: id => callbacks.delete(id),
        fetch: async () => ({ status: 200, ok: true, json: async () => ({ cursor: '1', unreviewed_count: 1, orders: [{ id: '1', reference: 'OXM-PRUEBA', units: 3, product_subtotal: 9000, delivery: 'despacho' }] }) }),
        AudioContext: class { constructor() { audioStarts++; } },
    };
    mount({ dataset: { key: 'adapter', cursor: '0', count: '0', url: 'http://127.0.0.1/dueno/pedidos/avisos', panel: '/dueno/pedidos' }, querySelector: node }, win);
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(win.document.title, '(1) Catálogo · Oxman');
    assert.equal(node('counter').textContent, '1 sin revisar');
    assert.equal(node('[data-new-order]').hidden, false);
    assert.equal(node('[data-arrival-reference]').textContent, 'OXM-PRUEBA');
    assert.equal(node('[data-arrival-summary]').textContent, '3 unidades · Despacho');
    assert.equal(node('[data-arrival-subtotal]').textContent, 'Subtotal productos: $9.000 · Sin incluir envío');
    assert.equal(node('[data-open-order]').href, '/dueno/pedidos/1');
    assert.equal(node('[data-test-sound]').disabled, true);
    assert.equal(audioStarts, 0);
    assert.ok([...callbacks.values()].some(timer => timer.delay === 20000));
    handlers.get('[data-dismiss-order]:click')();
    assert.equal(node('[data-new-order]').hidden, true);
    assert.equal(node('counter').textContent, '1 sin revisar');
    handlers.get('[data-preview-order]:click')();
    assert.equal(node('[data-new-order]').hidden, false);
    assert.equal(node('[data-preview-label]').hidden, false);
    assert.equal(node('[data-arrival-reference]').textContent, 'EJEMPLO-001');
    assert.equal(node('[data-open-order]').href, '/dueno/pedidos');
    assert.equal(node('counter').textContent, '1 sin revisar');
    assert.equal(audioStarts, 0);
});

test('audio suspendido en una pestaña no reclama el sonido de otra activa', async () => {
    const h = harness(); const a = await h.tab(); const b = await h.tab();
    await a.alerts.activate(); await b.alerts.activate();
    a.alerts.canPlay = () => false;
    h.reply('1', 1, ['1']);
    await a.alerts.poll(); await b.alerts.receive('');
    assert.equal(a.observed.sounds, 0); assert.equal(b.observed.sounds, 1);
});
