const menuButton = document.querySelector('[data-menu-toggle]');
const mobileMenu = document.querySelector('[data-mobile-menu]');

const closeMenu = () => {
    menuButton?.setAttribute('aria-expanded', 'false');
    mobileMenu?.classList.remove('is-open');
    document.body.classList.remove('menu-open');
};

menuButton?.addEventListener('click', () => {
    const isOpen = menuButton.getAttribute('aria-expanded') === 'true';
    menuButton.setAttribute('aria-expanded', String(!isOpen));
    mobileMenu?.classList.toggle('is-open', !isOpen);
    document.body.classList.toggle('menu-open', !isOpen);
});

mobileMenu?.querySelectorAll('a').forEach((link) => link.addEventListener('click', closeMenu));

const header = document.querySelector('[data-header]');
const updateHeader = () => header?.classList.toggle('is-scrolled', window.scrollY > 24);
window.addEventListener('scroll', updateHeader, { passive: true });
updateHeader();

const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
const revealElements = document.querySelectorAll('.reveal');

if (reducedMotion || !('IntersectionObserver' in window)) {
    revealElements.forEach((element) => element.classList.add('is-visible'));
} else {
    const revealObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) return;
            entry.target.classList.add('is-visible');
            observer.unobserve(entry.target);
        });
    }, { threshold: 0.14 });
    revealElements.forEach((element) => revealObserver.observe(element));
}

const counterGroup = document.querySelector('[data-counters]');
const animateCounters = () => {
    document.querySelectorAll('[data-counter]').forEach((counter) => {
        const target = Number(counter.dataset.counter);
        if (reducedMotion) {
            counter.textContent = String(target);
            return;
        }
        const startedAt = performance.now();
        const tick = (now) => {
            const progress = Math.min((now - startedAt) / 1300, 1);
            counter.textContent = String(Math.round(target * (1 - Math.pow(1 - progress, 3))));
            if (progress < 1) requestAnimationFrame(tick);
        };
        requestAnimationFrame(tick);
    });
};

if (counterGroup && 'IntersectionObserver' in window) {
    const counterObserver = new IntersectionObserver(([entry], observer) => {
        if (!entry.isIntersecting) return;
        animateCounters();
        observer.disconnect();
    }, { threshold: 0.4 });
    counterObserver.observe(counterGroup);
} else {
    animateCounters();
}

const sendContact = async (formData) => {
    const endpoint = import.meta.env.VITE_CONTACT_ENDPOINT;
    if (!endpoint) return { demo: true };
    const response = await fetch(endpoint, { method: 'POST', headers: { Accept: 'application/json' }, body: formData });
    if (!response.ok) throw new Error('No se pudo enviar el formulario.');
    return response.json();
};

document.querySelector('[data-contact-form]')?.addEventListener('submit', async (event) => {
    event.preventDefault();
    const form = event.currentTarget;
    const status = form.querySelector('[data-form-status]');
    if (form.elements.website.value) return;
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    const button = form.querySelector('button[type="submit"]');
    button.disabled = true;
    status.textContent = 'Enviando…';
    try {
        const result = await sendContact(new FormData(form));
        status.textContent = result.demo ? '¡Gracias! El formulario está listo. Próximamente conectaremos el canal de contacto.' : '¡Gracias! Recibimos tu consulta y nos pondremos en contacto.';
        form.reset();
    } catch (error) {
        status.textContent = error.message;
    } finally {
        button.disabled = false;
    }
});
