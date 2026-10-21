<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Combinamos conocimiento, tecnología e inteligencia artificial para modernizar tu organización y llevarla al siguiente nivel.">
    <meta property="og:type" content="website">
    <meta property="og:title" content="NEURIA — Un paso de tu empresa a la nueva era IA">
    <meta property="og:description" content="Estrategia, tecnología e IA aplicada a resultados para tu empresa.">
    <meta property="og:image" content="{{ asset('og.png') }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="NEURIA — Un paso de tu empresa a la nueva era IA">
    <meta name="twitter:description" content="Estrategia, tecnología e IA aplicada a resultados para tu empresa.">
    <meta name="twitter:image" content="{{ asset('og.png') }}">
    <title>NEURIA — Un paso de tu empresa a la nueva era IA</title>
    @vite(['resources/css/public-home.css', 'resources/js/public-home.js'])
</head>
<body>
    <a class="skip-link" href="#contenido">Saltar al contenido</a>
    <header class="site-header" data-header>
        <div class="shell header-inner">
            <a class="brand" href="#inicio" aria-label="NEURIA, volver al inicio"><span class="brand-mark" aria-hidden="true">N</span><span>NEURIA</span></a>
            <nav class="desktop-nav" aria-label="Navegación principal"><a href="#servicios">Qué hacemos</a><a href="#equipo">Equipo</a><a href="#metodo">Metodología</a><a href="#porque">Por qué NEURIA</a></nav>
            <a class="button button-outline header-cta" href="#contacto">Solicitá tu diagnóstico</a>
            <button class="menu-toggle" type="button" aria-label="Abrir menú" aria-expanded="false" aria-controls="mobile-menu" data-menu-toggle><span></span><span></span></button>
        </div>
        <nav class="mobile-menu" id="mobile-menu" aria-label="Navegación móvil" data-mobile-menu><a href="#servicios">Qué hacemos</a><a href="#equipo">Equipo</a><a href="#metodo">Metodología</a><a href="#porque">Por qué NEURIA</a><a class="button button-primary" href="#contacto">Solicitá tu diagnóstico</a></nav>
    </header>

    <main id="contenido">
        <section class="hero" id="inicio">
            <div class="hero-orb hero-orb-one" aria-hidden="true"></div><div class="hero-orb hero-orb-two" aria-hidden="true"></div>
            <div class="shell hero-content reveal">
                <p class="eyebrow eyebrow-light">Estrategia <span>·</span> Tecnología <span>·</span> Inteligencia artificial</p>
                <h1>Un paso de tu empresa <span>a la nueva era IA.</span></h1>
                <p class="hero-copy">Combinamos conocimiento, tecnología e inteligencia artificial para modernizar tu organización y llevarla al siguiente nivel.</p>
                <div class="hero-actions"><a class="button button-primary" href="#contacto">Solicitá tu diagnóstico</a><a class="button button-ghost" href="#metodo">Conocé cómo lo hacemos <span aria-hidden="true">↗</span></a></div>
                <div class="hero-note"><span></span> Inteligencia aplicada. Dirección humana.</div>
            </div>
            <a class="scroll-cue" href="#servicios" aria-label="Ir a Qué hacemos"><span></span></a>
        </section>

        <div class="marquee" aria-label="Principios de NEURIA"><div class="marquee-track">
            @for ($copy = 0; $copy < 2; $copy++)
                <div class="marquee-group" @if ($copy === 1) aria-hidden="true" @endif><span>Estrategia que transforma</span><i>✦</i><span>Tecnología que impulsa</span><i>✦</i><span>IA aplicada a resultados</span><i>✦</i><span>Más productividad y crecimiento</span><i>✦</i><span>Decisiones basadas en datos</span><i>✦</i></div>
            @endfor
        </div></div>

        <section class="section services" id="servicios"><div class="shell">
            <div class="section-intro reveal"><div><p class="eyebrow">Qué hacemos</p><h2>Soluciones integrales para cada etapa de tu <em>evolución digital</em></h2></div><p>Primero entendemos el problema. Después diseñamos la solución. Finalmente, elegimos la tecnología que genera valor real.</p></div>
            <div class="service-grid">
                @foreach (config('neuria_home.services') as $service)
                    <article class="service-card reveal"><span class="service-number">{{ $service['icon'] }}</span><div class="service-symbol" aria-hidden="true"><span></span></div><h3>{{ $service['title'] }}</h3><p>{{ $service['text'] }}</p><span class="card-arrow" aria-hidden="true">↗</span></article>
                @endforeach
            </div>
        </div></section>

        <section class="section team-section" id="equipo"><div class="shell">
            <div class="center-heading reveal"><p class="eyebrow">Nuestro equipo</p><h2>Especialistas Virtuales IA <span>+</span><br>Dirección estratégica</h2><p>Capacidades especializadas que trabajan de forma coordinada, bajo liderazgo humano.</p></div>
            <div class="team-grid">
                @foreach (config('neuria_home.team') as $member)
                    <article class="team-card reveal"><div class="avatar"><span>{{ $member['initials'] }}</span></div><p class="team-label">{{ $member['label'] }}</p><h3>{{ $member['name'] }}</h3><strong>{{ $member['role'] }}</strong><p>{{ $member['text'] }}</p></article>
                @endforeach
            </div>
        </div></section>

        <section class="section augmented"><div class="shell augmented-grid">
            <div class="augmented-copy reveal"><p class="eyebrow eyebrow-light">Capacidad Empresarial Aumentada</p><h2>Más capacidades.<br><span>La misma empresa.</span></h2><p>NEURIA combina personas, tecnología, procesos, datos e Inteligencia Artificial para ampliar las capacidades de una organización.</p><p>La IA se incorpora como un integrante activo que aporta conocimiento y asistencia, no como un reemplazo automático de las personas.</p></div>
            <div class="capability-visual reveal" aria-label="Humano más IA más metodología más conocimiento es igual a Capacidad Empresarial Aumentada"><div class="capability-rings"><span class="ring ring-human">Humano</span><span class="ring ring-ai">IA</span><span class="ring ring-method">Metodología</span><span class="ring ring-knowledge">Conocimiento</span><div class="capability-core"><small>Capacidad</small><strong>Empresarial</strong><small>Aumentada</small></div></div></div>
            <p class="equation reveal"><span>Humano</span><b>+</b><span>IA</span><b>+</b><span>metodología</span><b>+</b><span>conocimiento</span><b>=</b><strong>Capacidad Empresarial Aumentada</strong></p>
        </div></section>

        <section class="section specialists"><div class="shell specialists-grid">
            <div class="reveal"><p class="eyebrow">Especialistas Virtuales IA</p><h2>Sumá nuevas capacidades a tu organización</h2></div>
            <div class="specialist-content reveal"><p>NEURIA puede diseñar asistentes especializados según las necesidades reales de cada empresa.</p><div class="chips">@foreach (config('neuria_home.specialists') as $specialist)<span><i aria-hidden="true"></i>{{ $specialist }}</span>@endforeach</div></div>
        </div></section>

        <section class="section method" id="metodo"><div class="shell">
            <div class="center-heading reveal"><p class="eyebrow">Metodología NEURIA</p><h2>Un camino claro. <span>Resultados reales.</span></h2></div>
            <div class="method-list">
                <article class="method-step reveal"><span class="step-number">01</span><div><p class="step-label">Paso 1</p><h3>Diagnóstico NEURIA.</h3><p>Evaluamos el punto de partida, fortalezas, debilidades y oportunidades de la organización.</p><ul><li>Procesos y personas</li><li>Tecnología, datos e IA</li><li>Comunicación y clientes</li><li>Capacidad de adaptación</li></ul></div></article>
                <article class="method-step reveal"><span class="step-number">02</span><div><p class="step-label">Paso 2</p><h3>Plan de transformación.</h3><p>Diseñamos un plan estratégico con objetivos, prioridades, tecnologías, procesos, plazos e indicadores.</p></div></article>
                <article class="method-step reveal"><span class="step-number">03</span><div><p class="step-label">Paso 3</p><h3>Evolución integral.</h3><p>Implementamos, capacitamos, medimos y acompañamos continuamente a la organización.</p></div></article>
            </div>
        </div></section>

        <section class="section reasons" id="porque"><div class="shell">
            <div class="section-intro reveal"><div><p class="eyebrow">¿Por qué elegir NEURIA?</p><h2>Más que tecnología, <em>socios en tu evolución</em></h2></div></div>
            <div class="reason-list">@foreach (config('neuria_home.reasons') as $reason)<article class="reason reveal"><span>{{ str_pad((string) ($loop->index + 1), 2, '0', STR_PAD_LEFT) }}</span><h3>{{ $reason['title'] }}</h3><p>{{ $reason['text'] }}</p></article>@endforeach</div>
            <div class="metrics reveal" data-counters>@foreach (config('neuria_home.metrics') as $metric)<div class="metric"><strong><span>{{ $metric['prefix'] }}</span><span data-counter="{{ $metric['value'] }}">0</span></strong><p>{{ $metric['label'] }}</p></div>@endforeach</div>
        </div></section>

        <section class="contact" id="contacto"><div class="shell contact-grid">
            <div class="contact-copy reveal"><p class="eyebrow eyebrow-light">Tu próximo paso</p><h2>¿Listo para potenciar <span>tu empresa?</span></h2><p>Solicitá tu Diagnóstico NEURIA y descubrí dónde está hoy tu organización, dónde debería estar y qué camino puede seguir.</p><div class="contact-detail"><span>01</span><p><strong>Contanos tu desafío</strong>Completá el formulario con una breve descripción.</p></div><div class="contact-detail"><span>02</span><p><strong>Coordinamos una conversación</strong>Analizamos juntos el punto de partida.</p></div></div>
            <form class="contact-form reveal" data-contact-form novalidate>
                <div class="form-row"><label>Nombre<input type="text" name="name" autocomplete="name" required></label><label>Empresa<input type="text" name="company" autocomplete="organization" required></label></div>
                <div class="form-row"><label>Email<input type="email" name="email" autocomplete="email" required></label><label>Teléfono<input type="tel" name="phone" autocomplete="tel"></label></div>
                <label>¿Cómo podemos ayudarte?<textarea name="message" rows="4" required></textarea></label><label class="honeypot" aria-hidden="true">No completar<input type="text" name="website" tabindex="-1" autocomplete="off"></label>
                <button class="button button-primary" type="submit">Solicitá tu diagnóstico <span aria-hidden="true">↗</span></button><p class="form-status" role="status" data-form-status></p>
            </form>
        </div></section>
    </main>
    <footer class="site-footer"><div class="shell footer-inner"><a class="brand" href="#inicio"><span class="brand-mark" aria-hidden="true">N</span><span>NEURIA</span></a><p>© {{ date('Y') }} NEURIA · Capacidad Empresarial Aumentada</p><p>Estrategia · Tecnología · IA</p></div></footer>
    <a class="floating-contact" href="#contacto"><span></span>¿Hablamos?</a>
</body>
</html>
