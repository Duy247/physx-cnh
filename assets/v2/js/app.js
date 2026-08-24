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

})();
