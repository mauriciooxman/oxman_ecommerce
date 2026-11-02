(function (root, factory) {
    const api = factory();
    if (typeof module === 'object' && module.exports) module.exports = api;
    else {
        const element = root.document.getElementById('owner-live-orders');
        if (element) api.mount(element, root);
    }
})(globalThis, function () {
    const INTERVAL = 20000;
    const validCursor = value => typeof value === 'string' && /^(0|[1-9][0-9]{0,18})$/.test(value);
    const greater = (a, b) => BigInt(a) > BigInt(b);

    class Alerts {
        constructor(options) {
            Object.assign(this, options);
            this.floor = this.cursor;
            this.stopped = false;
            this.audioReady = false;
            this.memory = {};
        }
        read(suffix) {
            try { return this.storage.getItem(this.key + suffix) ?? this.memory[suffix] ?? null; }
            catch { this.coordinated = false; return this.memory[suffix] ?? null; }
        }
        write(suffix, value) {
            this.memory[suffix] = value;
            try { this.storage.setItem(this.key + suffix, value); }
            catch { this.coordinated = false; }
        }
        state() {
            try {
                const state = JSON.parse(this.read(''));
                if (state && validCursor(state.cursor) && Number.isInteger(state.count) && state.count >= 0) return state;
            } catch { /* Una preferencia dañada no debe crear alarmas históricas. */ }
            return null;
        }
        save(state) { this.write('', JSON.stringify(state)); }
        enabled() { return this.read(':sound') === 'on'; }
        async initialize() {
            await this.lock(this.key, async () => {
                let state = this.state();
                if (!state || this.now() - state.activeAt > 60000) {
                    state = { cursor: this.floor, count: this.count, activeAt: this.now(), nextDue: 0, errors: 0, alert: null };
                    this.save(state);
                }
                this.update(state, false);
            });
        }
        update(state, sound) {
            const dismissed = this.read(':dismissed') || '0';
            const candidate = state.alert && validCursor(state.alert.cursor) && Array.isArray(state.alert.orders)
                && state.alert.orders.length > 0 && state.alert.orders.every(order => validCursor(order.id) && typeof order.reference === 'string')
                && greater(state.alert.cursor, this.floor)
                && (!validCursor(dismissed) || greater(state.alert.cursor, dismissed)) ? state.alert : null;
            const orders = candidate?.orders.filter(order => greater(order.id, this.floor)) || [];
            const alert = orders.length ? { cursor: candidate.cursor, orders } : null;
            this.onUpdate(state.count, alert);
            if (sound && alert) return this.sound(alert.cursor);
        }
        async sound(cursor) {
            if (this.stopped || !this.coordinated || !this.audioReady || !this.enabled() || (this.canPlay && !this.canPlay())) return;
            await this.lock(this.key + ':audio', async () => {
                const heard = this.read(':heard') || '0';
                if (this.stopped || !this.coordinated || !this.audioReady || !this.enabled() || (this.canPlay && !this.canPlay()) || (validCursor(heard) && !greater(cursor, heard))) return;
                // Reclamar antes de tocar audio; otra pestaña nunca reproduce el mismo lote.
                this.write(':heard', cursor);
                if (!this.coordinated) return;
                try { await this.play(); }
                catch { this.onStatus('No se pudo reproducir el sonido. Las alertas visuales siguen activas.'); }
            });
        }
        async activate() {
            if (!this.coordinated) {
                this.onStatus('Este navegador no permite coordinar el sonido entre pestañas. Las alertas visuales siguen activas.');
                return;
            }
            try {
                // unlock se llama únicamente desde el clic del dueño.
                await this.unlock();
                this.audioReady = true;
                await this.lock(this.key + ':audio', async () => {
                    const cursor = this.state()?.cursor || this.floor;
                    const heard = this.read(':heard') || '0';
                    const baseline = greater(cursor, this.floor) ? cursor : this.floor;
                    if (!validCursor(heard) || greater(baseline, heard)) this.write(':heard', baseline);
                    this.write(':sound', 'on');
                });
                this.onStatus('Sonido activado para nuevas llegadas en esta pestaña. Puedes probarlo o silenciarlo.');
            } catch { this.audioReady = false; this.onStatus('No se pudo activar el sonido. Vuelve a pulsar Activar sonido.'); }
            this.onControls();
        }
        async test() {
            if (this.audioReady && this.enabled()) {
                try { await this.unlock(); await this.play(); }
                catch { this.onStatus('No se pudo reproducir la prueba de sonido.'); }
            }
        }
        mute() {
            this.write(':sound', 'off');
            this.onStatus('Sonido silenciado en este navegador. Las alertas visuales siguen activas.');
            this.onControls();
        }
        dismiss() {
            const state = this.state();
            if (state?.alert) this.write(':dismissed', state.alert.cursor);
            if (state) this.update(state, false);
        }
        receive(suffix) {
            if (suffix === ':expired') { this.stop(); return; }
            if (suffix === ':sound') {
                this.onControls();
                if (!this.enabled()) this.onStatus('Sonido silenciado en este navegador.');
            }
            if (!this.stopped) {
                const state = this.state();
                if (state) return this.update(state, suffix === '');
            }
        }
        stop() {
            this.stopped = true;
            this.onStatus('La sesión terminó. Ingresa de nuevo para recibir avisos.');
            this.onStop();
        }
        async poll() {
            if (this.stopped) return;
            await this.lock(this.key, async () => {
                if (this.stopped) return;
                const state = this.state();
                if (!state) return;
                if (this.now() < state.nextDue) { await this.update(state, true); return; }
                try {
                    const response = await this.request(state.cursor);
                    if ([401, 403, 419].includes(response.status)) {
                        this.write(':expired', String(this.now()));
                        this.stop();
                        return;
                    }
                    if (response.status !== 200) throw new Error('HTTP');
                    const data = response.data;
                    if (!validCursor(data.cursor) || greater(state.cursor, data.cursor)
                        || !Number.isInteger(data.unreviewed_count) || data.unreviewed_count < 0 || !Array.isArray(data.orders)) throw new Error('Formato');
                    const orders = data.orders.filter(order => validCursor(order.id) && typeof order.reference === 'string' && greater(order.id, state.cursor));
                    const next = { cursor: data.cursor, count: data.unreviewed_count, errors: 0,
                        activeAt: this.now(), nextDue: this.now() + INTERVAL,
                        alert: orders.length ? { cursor: data.cursor, orders } : state.alert };
                    this.save(next);
                    await this.update(next, true);
                } catch {
                    state.errors = Math.min((state.errors || 0) + 1, 4);
                    state.nextDue = this.now() + Math.min(INTERVAL * 2 ** state.errors, 300000);
                    state.activeAt = this.now();
                    this.save(state);
                    this.onStatus('No se pudo comprobar pedidos. Reintentaremos con menos frecuencia.');
                }
            });
        }
    }

    function mount(element, win) {
        const find = selector => element.querySelector(selector);
        const baseTitle = win.document.title;
        const status = find('[data-live-status]');
        const banner = find('[data-new-order]');
        let announced = '0';
        let context;
        let timer;
        let storage;
        let coordinated = !!win.navigator.locks;
        try { storage = win.localStorage; storage.setItem('oxman-alert-storage-check', '1'); storage.removeItem('oxman-alert-storage-check'); }
        catch { storage = { getItem: () => null, setItem: () => { throw new Error('Sin almacenamiento'); } }; coordinated = false; }
        const alerts = new Alerts({
            key: element.dataset.key, cursor: element.dataset.cursor, count: Number(element.dataset.count), storage, coordinated,
            now: () => Date.now(),
            lock: (key, callback) => win.navigator.locks ? win.navigator.locks.request(key, callback) : callback(),
            request: async cursor => {
                const controller = new AbortController();
                const timeout = win.setTimeout(() => controller.abort(), 8000);
                try {
                    const url = new URL(element.dataset.url);
                    url.searchParams.set('after', cursor);
                    const response = await win.fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin', cache: 'no-store', signal: controller.signal });
                    return { status: response.status, data: response.ok ? await response.json() : null };
                } finally { win.clearTimeout(timeout); }
            },
            unlock: async () => {
                const Audio = win.AudioContext || win.webkitAudioContext;
                if (!Audio) throw new Error('Audio no disponible');
                context ??= new Audio();
                await context.resume();
                if (context.state !== 'running') throw new Error('Audio bloqueado');
            },
            play: () => {
                if (context?.state !== 'running') throw new Error('Audio suspendido');
                const oscillator = context.createOscillator();
                const gain = context.createGain();
                const start = context.currentTime;
                oscillator.frequency.value = 740;
                gain.gain.setValueAtTime(0, start);
                gain.gain.linearRampToValueAtTime(0.12, start + 0.015);
                gain.gain.exponentialRampToValueAtTime(0.001, start + 0.3);
                oscillator.connect(gain); gain.connect(context.destination);
                oscillator.start(start); oscillator.stop(start + 0.32);
                oscillator.onended = () => { oscillator.disconnect(); gain.disconnect(); };
            },
            canPlay: () => context?.state === 'running',
            onStatus: message => { status.textContent = message; },
            onControls: () => {
                find('[data-test-sound]').disabled = !alerts.audioReady || !alerts.enabled();
                find('[data-mute-sound]').disabled = !alerts.enabled();
                find('[data-enable-sound]').setAttribute('aria-pressed', String(alerts.audioReady && alerts.enabled()));
            },
            onStop: () => { win.clearTimeout(timer); },
            onUpdate: (count, arrival) => {
                win.document.querySelectorAll('[data-order-count]').forEach(node => { node.textContent = `${count} sin revisar`; });
                find('[data-pending-summary]').textContent = `${count} ${count === 1 ? 'pedido sin revisar' : 'pedidos sin revisar'}`;
                win.document.title = count ? `(${count}) ${baseTitle}` : baseTitle;
                banner.hidden = !arrival;
                if (arrival) {
                    const latest = arrival.orders[arrival.orders.length - 1];
                    find('[data-preview-label]').hidden = !latest.preview;
                    const message = `${latest.reference}${arrival.orders.length > 1 ? ` y ${arrival.orders.length - 1} más` : ''}`;
                    find('[data-arrival-reference]').textContent = message;
                    find('[data-arrival-summary]').textContent = Number.isInteger(latest.units)
                        ? `${latest.units} ${latest.units === 1 ? 'unidad' : 'unidades'} · ${latest.delivery === 'retiro' ? 'Retiro en tienda' : 'Despacho'}` : '';
                    find('[data-arrival-subtotal]').textContent = Number.isInteger(latest.product_subtotal)
                        ? `Subtotal productos: $${new Intl.NumberFormat('es-CL').format(latest.product_subtotal)}${latest.delivery === 'despacho' ? ' · Sin incluir envío' : ''}` : '';
                    find('[data-open-order]').href = latest.preview ? element.dataset.panel : element.dataset.panel.replace(/\/$/, '') + '/' + latest.id;
                    find('[data-open-order]').textContent = latest.preview ? 'Ver pedidos' : 'Ver pedido';
                    if (arrival.cursor !== announced) {
                        find('[data-arrival-announcement]').textContent = `${latest.preview ? 'Vista previa, no es una compra real. ' : ''}Tienes una compra nueva. ${message}. ${find('[data-arrival-summary]').textContent}. ${find('[data-arrival-subtotal]').textContent}`;
                        announced = arrival.cursor;
                    }
                }
                alerts.onControls();
            },
        });
        const preview = () => alerts.onUpdate(alerts.state()?.count ?? Number(element.dataset.count), {
            cursor: 'preview', orders: [{ id: '0', reference: 'EJEMPLO-001', units: 3, product_subtotal: 9000, delivery: 'retiro', preview: true }],
        });
        find('[data-preview-order]').addEventListener('click', preview);
        find('[data-enable-sound]').addEventListener('click', () => alerts.activate());
        find('[data-test-sound]').addEventListener('click', () => alerts.test());
        find('[data-mute-sound]').addEventListener('click', () => alerts.mute());
        find('[data-dismiss-order]').addEventListener('click', () => alerts.dismiss());
        banner.addEventListener('keydown', event => {
            if (event.key === 'Escape') { alerts.dismiss(); find('[data-enable-sound]').focus({ preventScroll: true }); }
        });
        win.addEventListener('storage', event => {
            if (event.key === alerts.key || event.key?.startsWith(alerts.key + ':')) alerts.receive(event.key.slice(alerts.key.length));
        });
        win.document.addEventListener('visibilitychange', () => { if (!win.document.hidden && !alerts.stopped) alerts.poll(); });
        const tick = async () => {
            await alerts.poll();
            if (!alerts.stopped) {
                const delay = Math.max(INTERVAL, (alerts.state()?.nextDue || 0) - Date.now());
                timer = win.setTimeout(tick, delay);
            }
        };
        alerts.initialize().then(async () => {
            await tick();
            if (new URL(win.location?.href || element.dataset.url).searchParams.get('vista_previa_aviso') === '1') preview();
        }).catch(() => { status.textContent = 'No se pudieron iniciar los avisos automáticos. Recarga el panel.'; });
    }
    return { Alerts, mount };
});
