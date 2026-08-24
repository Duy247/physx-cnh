import * as THREE from '../vendor/three.module.min.js';
document.documentElement.dataset.planetaryLoaded = '1';

const host = document.querySelector('[data-planetary]');
if (host) {
  const variant = host.dataset.planetary;
  const reduced = matchMedia('(prefers-reduced-motion: reduce)').matches;
  const labels = [...host.querySelectorAll('[data-satellite]')];
  const relayBoard = document.querySelector('[data-relay]');
  let renderer;
  try {
    const scene = new THREE.Scene();
    scene.fog = new THREE.FogExp2(variant === 'hub' ? 0xf1eee5 : 0xe8f0ea, 0.055);
    const camera = new THREE.PerspectiveCamera(42, 1, 0.1, 100);
    renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true, powerPreference: 'high-performance' });
    renderer.setPixelRatio(Math.min(devicePixelRatio, 1.65));
    renderer.outputColorSpace = THREE.SRGBColorSpace;
    renderer.toneMapping = THREE.ACESFilmicToneMapping;
    renderer.toneMappingExposure = 1.12;
    renderer.setClearColor(0x000000, 0);
    renderer.domElement.className = 'canvas';
    renderer.domElement.setAttribute('aria-hidden', 'true');
    host.prepend(renderer.domElement);

    const world = new THREE.Group();
    scene.add(world, new THREE.HemisphereLight(0xffffff, 0x8aa8a2, 2.4));
    const light = new THREE.PointLight(0xffcf62, variant === 'hub' ? 48 : 14, 24, 1.5);
    light.position.set(variant === 'hub' ? 0 : 3.5, variant === 'hub' ? 0.4 : 3, variant === 'hub' ? 2 : 5);
    scene.add(light);
    const fill = new THREE.DirectionalLight(0x8fcbd0, 3.2); fill.position.set(-5, 4, 6); scene.add(fill);
    const updates = [];
    const projectors = [];
    const pointer = { x: 0, y: 0 };
    let pageVisible = !document.hidden;
    let inView = true;
    const speedFactor = reduced ? 0.18 : 1;

    const dustPositions = new Float32Array(620 * 3);
    for (let i = 0; i < 620; i += 1) {
      dustPositions[i * 3] = (Math.random() - 0.5) * 16;
      dustPositions[i * 3 + 1] = (Math.random() - 0.5) * 11.5;
      dustPositions[i * 3 + 2] = (Math.random() - 0.5) * 8;
    }
    const dustGeometry = new THREE.BufferGeometry();
    dustGeometry.setAttribute('position', new THREE.BufferAttribute(dustPositions, 3));
    const dust = new THREE.Points(dustGeometry, new THREE.PointsMaterial({ color: 0x477d82, size: 0.025, transparent: true, opacity: 0.3, depthWrite: false }));
    world.add(dust); updates.push((time) => { dust.rotation.y = time * 0.006; });
    const orbit = (radius, opacity = .28, parent = world, color = 0x517a80) => {
      const curve = new THREE.EllipseCurve(0, 0, radius, radius * .58, 0, Math.PI * 2);
      const points = curve.getPoints(220).map((point) => new THREE.Vector3(point.x, 0, point.y));
      const line = new THREE.LineLoop(new THREE.BufferGeometry().setFromPoints(points), new THREE.LineBasicMaterial({ color, transparent: true, opacity }));
      parent.add(line); return line;
    };

    if (variant === 'hub') {
      const sun = new THREE.Mesh(new THREE.SphereGeometry(.72, 48, 48), new THREE.MeshPhysicalMaterial({ color: 0xf2bd43, emissive: 0xe49a16, emissiveIntensity: 1.15, roughness: .22, clearcoat: .7 }));
      sun.position.y = .06; world.add(sun);
      const corona = new THREE.Mesh(new THREE.SphereGeometry(.96, 32, 32), new THREE.MeshBasicMaterial({ color: 0xf3c95d, transparent: true, opacity: .1, wireframe: true }));
      corona.position.copy(sun.position); world.add(corona);
      [
        [1.45,.18,0xb88b68,.22,.7,false], [2.2,.32,0x178398,.15,2.4,true],
        [3.05,.25,0x777b79,.105,4.2,false], [4.05,.42,0x8d8b86,.075,5.45,false],
      ].forEach(([radius,size,color,speed,phase,physics]) => {
        orbit(radius, physics ? .48 : .23);
        const planet = new THREE.Mesh(new THREE.SphereGeometry(size, 32, 32), new THREE.MeshPhysicalMaterial({ color, roughness: .46, clearcoat: .3 }));
        world.add(planet);
        if (physics) { const marker = new THREE.Mesh(new THREE.TorusGeometry(size * 1.55,.018,8,64), new THREE.MeshBasicMaterial({ color:0x0d687c,transparent:true,opacity:.65 })); marker.rotation.x=Math.PI/2.8; planet.add(marker); }
        updates.push((time) => { const angle=phase+time*speed; planet.position.set(Math.cos(angle)*radius,Math.sin(angle*.72)*.08,Math.sin(angle)*radius*.58); planet.rotation.y=time*(.18+speed); });
      });
      updates.push((time) => { sun.rotation.y=time*.08; corona.rotation.y=-time*.04; corona.rotation.z=time*.025; });
      world.rotation.z = -.08;
    } else {
      const planet = new THREE.Group(); world.add(planet);
      const globe = new THREE.Mesh(new THREE.SphereGeometry(1.62,64,64), new THREE.MeshPhysicalMaterial({ color:0x76c4c7,emissive:0x1e6670,emissiveIntensity:.16,roughness:.5,clearcoat:.5 })); planet.add(globe);
      const grid = new THREE.Mesh(new THREE.SphereGeometry(1.635,24,16), new THREE.MeshBasicMaterial({ color:0xf7f3e8,wireframe:true,transparent:true,opacity:.14 })); planet.add(grid);
      const atmosphere = new THREE.Mesh(new THREE.SphereGeometry(1.82,40,40), new THREE.MeshBasicMaterial({ color:0x63cad0,transparent:true,opacity:.075,side:THREE.BackSide })); planet.add(atmosphere);
      const settings = [[2.42,2.55,[.2,.18,-.18],0x247f8b,.13],[2.62,.62,[-.18,.12,.24],0xd4a844,.105],[2.5,-.55,[.16,-.2,-.28],0x3f9297,.115],[2.72,3.72,[-.14,-.16,.2],0xb8664d,.095]];
      const satellites = settings.map(([radius,phase,tilt,color,speed], index) => {
        const plane = new THREE.Group(); plane.rotation.set(...tilt); planet.add(plane);
        const path = new THREE.Mesh(new THREE.TorusGeometry(radius,.018,8,180), new THREE.MeshBasicMaterial({ color,transparent:true,opacity:.42 })); plane.add(path);
        const satellite = new THREE.Mesh(new THREE.SphereGeometry(.105,24,24),new THREE.MeshPhysicalMaterial({color,roughness:.28,clearcoat:.8})); plane.add(satellite);
        const projected = new THREE.Vector3();
        projectors.push(() => {
          const label=labels[index]; if (!label) return;
          satellite.getWorldPosition(projected).project(camera);
          const width=host.clientWidth,height=host.clientHeight,edge=width<600?74:105,bottom=width<600?190:115;
          let x=Math.max(edge,Math.min(width-edge,(projected.x*.5+.5)*width));
          let y=Math.max(width<600?62:36,Math.min(height-bottom,(-projected.y*.5+.5)*height));
          // The relay occupies the upper-left telemetry bay. Keep projected labels
          // attached to their orbit while nudging them below the board when paths cross.
          if (relayBoard && width >= 760) {
            const hostRect=host.getBoundingClientRect(),relayRect=relayBoard.getBoundingClientRect();
            const halfWidth=Math.max(label.offsetWidth/2,72),halfHeight=Math.max(label.offsetHeight/2,15);
            const relayLeft=relayRect.left-hostRect.left,relayRight=relayRect.right-hostRect.left;
            const relayTop=relayRect.top-hostRect.top,relayBottom=relayRect.bottom-hostRect.top;
            if (x+halfWidth>relayLeft-10&&x-halfWidth<relayRight+10&&y+halfHeight>relayTop-10&&y-halfHeight<relayBottom+10) {
              y=Math.min(height-bottom,relayBottom+halfHeight+14);
            }
          }
          label.style.transform=`translate3d(${x}px,${y}px,0) translate(-50%,-50%)`;
          const show=projected.z<1&&scrollY<height*.72; label.style.opacity=show?'1':'0'; label.style.pointerEvents=show?'auto':'none';
        });
        return { satellite,radius,phase,speed };
      });
      updates.push((time) => {
        globe.rotation.y=time*.08; grid.rotation.y=time*.035; atmosphere.scale.setScalar(1+Math.sin(time*1.2)*.012);
        satellites.forEach(({satellite,radius,phase,speed}) => { const angle=phase+time*speed; satellite.position.set(Math.cos(angle)*radius,Math.sin(angle)*radius,0); satellite.rotation.y=time*.8; });
      });
    }

    const resize = () => {
      if (!host.clientWidth || !host.clientHeight) return;
      renderer.setSize(host.clientWidth,host.clientHeight,false); camera.aspect=host.clientWidth/host.clientHeight;
      const portrait=camera.aspect<.72,wide=camera.aspect>1.35;
      camera.position.z=variant==='physics'?(portrait?10.4:wide?6.9:7.8):(portrait?12:wide?7.25:9.2);
      camera.position.y=variant==='hub'?(portrait?5.4:wide?3.55:4.8):.2; camera.updateProjectionMatrix();
    };
    new ResizeObserver(resize).observe(host); resize();
    new IntersectionObserver(([entry]) => { inView=entry.isIntersecting; },{threshold:.02}).observe(host);
    document.addEventListener('visibilitychange',()=>{ pageVisible=!document.hidden; });
    host.addEventListener('pointermove',(event)=>{ if(reduced)return; const rect=host.getBoundingClientRect(); pointer.x=((event.clientX-rect.left)/rect.width-.5)*2; pointer.y=((event.clientY-rect.top)/rect.height-.5)*2; },{passive:true});
    const started=performance.now();
    let lastStamp = -1;
    const render = (now) => {
      if (pageVisible && inView) {
        const time=(now-started)/1000*speedFactor; updates.forEach((update)=>update(time));
        const stamp = Math.floor(time * 4);
        if (stamp !== lastStamp) { host.dataset.animationFrame = String(stamp); lastStamp = stamp; }
        if(!reduced){world.rotation.y+=(pointer.x*.11-world.rotation.y)*.022;world.rotation.x+=(-pointer.y*.07-world.rotation.x)*.022;camera.position.x+=(pointer.x*.18-camera.position.x)*.02;}
        camera.lookAt(0,0,0); world.updateMatrixWorld(true); projectors.forEach((project)=>project()); renderer.render(scene,camera);
      }
      requestAnimationFrame(render);
    };
    requestAnimationFrame(render);
  } catch (error) {
    host.classList.add('isFallback');
    console.warn('WebGL unavailable; using the planetary fallback.', error);
  }
}
