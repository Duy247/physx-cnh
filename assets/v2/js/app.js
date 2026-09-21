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
    });
  });

  const fieldOrbit = document.querySelector('[data-field-orbit]');
  if (fieldOrbit) {
    const wheel = fieldOrbit.querySelector('.fieldGrid');
    const cards = [...fieldOrbit.querySelectorAll('[data-field-card]')];
    const steps = [...fieldOrbit.querySelectorAll('.fieldSteps i')];
    let ticking = false;
    const normalize = (angle) => ((angle + 180) % 360 + 360) % 360 - 180;
    const render = () => {
      ticking = false;
      const available = Math.max(1, fieldOrbit.offsetHeight - innerHeight);
      const progress = Math.min(1, Math.max(0, -fieldOrbit.getBoundingClientRect().top / available));
      const turn = progress * -270;
      const mobile = innerWidth <= 760;
      const radius = mobile ? Math.min(270, Math.max(235, innerWidth * .66)) : Math.min(650, Math.max(430, innerWidth * .44));
      wheel.style.setProperty('--orbit-radius', `${radius}px`);
      let current = 0;
      let closest = Infinity;
      cards.forEach((card, index) => {
        const angle = turn + index * 90;
        const facing = Math.cos(angle * Math.PI / 180);
        const distance = Math.abs(normalize(angle));
        if (distance < closest) { closest = distance; current = index; }
        const prominence = Math.max(0, (facing + 1) / 2);
        card.style.transform = `translate(-50%,-50%) rotate(${angle}deg) translateX(${radius}px) rotate(${-angle}deg) scale(${.82 + prominence * .18})`;
        card.style.opacity = `${.14 + prominence * .86}`;
        card.style.zIndex = `${Math.round(prominence * 20) + 2}`;
      });
      cards.forEach((card, index) => card.classList.toggle('isCurrent', index === current));
      steps.forEach((step, index) => step.classList.toggle('isCurrent', index === current));
      fieldOrbit.dataset.fieldIndex = String(current);
    };
    const requestRender = () => {
      if (!ticking) { ticking = true; requestAnimationFrame(render); }
    };
    addEventListener('scroll', requestRender, { passive: true });
    addEventListener('resize', requestRender);
    fieldOrbit.classList.add('isOrbitReady');
    render();
  }

})();
