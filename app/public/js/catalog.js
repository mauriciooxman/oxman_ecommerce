// Mejora progresiva: sin JavaScript el POST conserva el contexto del catálogo.
document.querySelectorAll('[data-catalog-add]').forEach(form => {
    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (form.getAttribute('aria-busy') === 'true') return;
        const button = form.querySelector('button');
        const input = form.querySelector('[name="quantity"]');
        const error = document.getElementById(`qty-error-${form.dataset.productId}`);
        const status = document.getElementById(`catalog-added-${form.dataset.productId}`);
        form.setAttribute('aria-busy', 'true');
        button.disabled = true;
        error.textContent = '';
        input.setAttribute('aria-invalid', 'false');
        status.textContent = 'Agregando al carro…';
        try {
            const response = await fetch(form.action, {
                method: 'POST', body: new FormData(form), credentials: 'same-origin',
                headers: { Accept: 'application/json' },
            });
            const result = await response.json();
            if (!response.ok) {
                const messages = result.errors?.[`quantity.${form.dataset.productId}`];
                if (messages) {
                    error.textContent = messages.join(' ');
                    input.setAttribute('aria-invalid', 'true');
                    status.textContent = 'No se agregó el artículo. Revisa la cantidad.';
                    input.focus({ preventScroll: true });
                } else {
                    status.textContent = response.status === 419
                        ? 'La sesión venció. Recarga el catálogo antes de volver a agregar.'
                        : 'No se pudo agregar el artículo. Recarga para comprobar su disponibilidad.';
                }
                return;
            }
            const link = document.createElement('a');
            link.href = result.cart_url;
            link.textContent = 'Ver carro';
            status.replaceChildren(document.createTextNode(`${result.message} `), link);
        } catch {
            // Un fallo de red puede ocurrir después de guardar: no reenviar automáticamente.
            const link = document.createElement('a');
            link.href = document.querySelector('.cart-link').href;
            link.textContent = 'Ver carro';
            status.replaceChildren(document.createTextNode('No pudimos verificar el resultado. Revisa el carro antes de reintentar. '), link);
        } finally {
            form.removeAttribute('aria-busy');
            button.disabled = false;
        }
    });
});
