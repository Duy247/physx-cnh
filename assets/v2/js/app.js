(() => {
  document.querySelectorAll('.mobile-menu nav a').forEach((link) => {
    link.addEventListener('click', () => link.closest('details')?.removeAttribute('open'));
  });

  const sources = ['K2-18 b', 'LHS 1140 b', 'TRAPPIST-1 e', 'TRAPPIST-1 f', 'TRAPPIST-1 g', 'Proxima Centauri b'];
  const sourceNode = document.querySelector('[data-relay-source]');
  if (sourceNode) {
    let source = sessionStorage.getItem('physx-relay-source');
    if (!source || !sources.includes(source)) {
      source = sources[Math.floor(Math.random() * sources.length)];
      sessionStorage.setItem('physx-relay-source', source);
    }
    sourceNode.textContent = source;
  }

  document.querySelectorAll('.fieldCard[href]').forEach((card) => {
    card.addEventListener('pointermove', (event) => {
      const rect = card.getBoundingClientRect();
      const x = event.clientX - rect.left;
      const y = event.clientY - rect.top;
      card.style.setProperty('--mx', `${x}px`);
      card.style.setProperty('--my', `${y}px`);
      card.style.setProperty('--ry', `${((x / rect.width) - 0.5) * 4}deg`);
      card.style.setProperty('--rx', `${-((y / rect.height) - 0.5) * 4}deg`);
    });
    card.addEventListener('pointerleave', () => {
      card.style.setProperty('--rx', '0deg');
      card.style.setProperty('--ry', '0deg');
    });
  });

  const showcase = document.querySelector('[data-showcase]');
  if (showcase) {
    const images = JSON.parse(showcase.dataset.images || '[]');
    const image = showcase.querySelector('img');
    const reduced = matchMedia('(prefers-reduced-motion: reduce)').matches;
    let index = 0;
    let timer = null;
    const show = (next) => {
      if (!images.length || !image) return;
      index = (next + images.length) % images.length;
      showcase.classList.add('isChanging');
      const source = images[index];
      const swap = () => {
        image.src = source;
        image.onload = () => showcase.classList.remove('isChanging');
      };
      setTimeout(swap, reduced ? 0 : 160);
    };
    const stop = () => { if (timer) clearInterval(timer); timer = null; };
    const start = () => { if (!document.hidden) { stop(); timer = setInterval(() => show(index + 1), 8000); } };
    document.addEventListener('visibilitychange', () => document.hidden ? stop() : start());
    start();
  }
})();
