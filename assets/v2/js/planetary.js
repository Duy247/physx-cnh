import * as THREE from '../vendor/three.module.min.js';
document.documentElement.dataset.planetaryLoaded = '1';

const host = document.querySelector('[data-planetary]');
if (host) {
  const variant = host.dataset.planetary;
  const reduced = matchMedia('(prefers-reduced-motion: reduce)').matches;
  const labels = [...host.querySelectorAll('[data-satellite]')];
  const hubLabels = [...host.querySelectorAll('[data-hub-planet]')];
  const geoLabels = [...host.querySelectorAll('[data-map-label]')];
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
    const setLeader = (label, offsetX, offsetY) => {
      label.style.setProperty('--label-offset-x', `${offsetX}px`);
      label.style.setProperty('--label-offset-y', `${offsetY}px`);
      label.style.setProperty('--leader-length', `${Math.hypot(offsetX, offsetY)}px`);
      label.style.setProperty('--leader-angle', `${Math.atan2(offsetY, offsetX)}rad`);
    };
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
      const curve = new THREE.EllipseCurve(0, 0, radius, radius, 0, Math.PI * 2);
      const points = curve.getPoints(220).map((point) => new THREE.Vector3(point.x, 0, point.y));
      const line = new THREE.LineLoop(new THREE.BufferGeometry().setFromPoints(points), new THREE.LineBasicMaterial({ color, transparent: true, opacity }));
      parent.add(line); return line;
    };
    const projectedBox = new THREE.Box3();
    const projectedCorner = new THREE.Vector3();
    const projectedObjectSupport = (object,x,y,dx,dy,width,height) => {
      projectedBox.setFromObject(object);
      let support=0;
      for(const px of [projectedBox.min.x,projectedBox.max.x]) for(const py of [projectedBox.min.y,projectedBox.max.y]) for(const pz of [projectedBox.min.z,projectedBox.max.z]) {
        projectedCorner.set(px,py,pz).project(camera);
        const cornerX=(projectedCorner.x*.5+.5)*width,cornerY=(-projectedCorner.y*.5+.5)*height;
        support=Math.max(support,(cornerX-x)*dx+(cornerY-y)*dy);
      }
      return Math.max(4,support);
    };
    const metal = new THREE.MeshStandardMaterial({ color:0xd6d2c5,metalness:.72,roughness:.3 });
    const gold = new THREE.MeshStandardMaterial({ color:0xb99036,metalness:.62,roughness:.36 });
    const dark = new THREE.MeshStandardMaterial({ color:0x26343a,metalness:.48,roughness:.38 });
    const panelMaterial = new THREE.MeshStandardMaterial({ color:0x245a82,metalness:.36,roughness:.42,emissive:0x123050,emissiveIntensity:.14 });
    const addSolarPanel = (group, x, width=.42, height=.22) => {
      const panel = new THREE.Mesh(new THREE.BoxGeometry(width,height,.025),panelMaterial); panel.position.x=x; group.add(panel);
      for (const offset of [-.25,0,.25]) { const rib=new THREE.Mesh(new THREE.BoxGeometry(.008,height,.032),metal); rib.position.set(x+offset*width,0,.003); group.add(rib); }
      const boom=new THREE.Mesh(new THREE.BoxGeometry(Math.max(.08,Math.abs(x)-width/2),.018,.018),metal); boom.position.x=x>0?(Math.abs(x)-width/2)/2:-(Math.abs(x)-width/2)/2; group.add(boom);
    };
    const makeCommSatellite = (accent, twinDish=false) => {
      const craft=new THREE.Group();
      const bus=new THREE.Mesh(new THREE.BoxGeometry(.28,.24,.26),new THREE.MeshStandardMaterial({color:accent,metalness:.55,roughness:.32})); craft.add(bus);
      addSolarPanel(craft,-.43,.42,.24); addSolarPanel(craft,.43,.42,.24);
      const mast=new THREE.Mesh(new THREE.CylinderGeometry(.012,.012,.25,8),metal); mast.rotation.x=Math.PI/2; mast.position.z=.23; craft.add(mast);
      const dish=new THREE.Mesh(new THREE.SphereGeometry(.15,24,12,0,Math.PI*2,0,.55),gold); dish.scale.z=.32; dish.position.z=.34; dish.rotation.x=Math.PI; craft.add(dish);
      if(twinDish){const smallDish=dish.clone();smallDish.scale.multiplyScalar(.58);smallDish.position.set(.12,.11,.25);craft.add(smallDish);}
      return craft;
    };
    const makeObservationSatellite = () => {
      const craft=new THREE.Group();
      const bus=new THREE.Mesh(new THREE.BoxGeometry(.28,.34,.25),metal); craft.add(bus);
      addSolarPanel(craft,-.42,.38,.32); addSolarPanel(craft,.42,.38,.32);
      const lens=new THREE.Mesh(new THREE.CylinderGeometry(.075,.12,.19,20),dark); lens.rotation.x=Math.PI/2; lens.position.z=.21; craft.add(lens);
      const antenna=new THREE.Mesh(new THREE.CylinderGeometry(.008,.008,.35,8),gold); antenna.position.y=.3; craft.add(antenna);
      return craft;
    };
    const makeCubeSat = () => {
      const craft=new THREE.Group();
      const cube=new THREE.Mesh(new THREE.BoxGeometry(.25,.25,.25),panelMaterial); craft.add(cube);
      const frame=new THREE.LineSegments(new THREE.EdgesGeometry(cube.geometry),new THREE.LineBasicMaterial({color:0xd8b64d})); craft.add(frame);
      [[.13,.13,0],[-.13,.13,0],[.13,-.13,0],[-.13,-.13,0]].forEach(([x,y])=>{const antenna=new THREE.Mesh(new THREE.CylinderGeometry(.006,.006,.32,6),gold);antenna.position.set(x,y,.22);antenna.rotation.x=Math.PI/5;craft.add(antenna);});
      return craft;
    };
    const surfacePoint = (latitude, longitude, radius) => {
      const lat=THREE.MathUtils.degToRad(latitude),lon=THREE.MathUtils.degToRad(longitude);
      return new THREE.Vector3(radius*Math.cos(lat)*Math.cos(lon),radius*Math.sin(lat),-radius*Math.cos(lat)*Math.sin(lon));
    };
    if (variant === 'hub') {
      host.dataset.planetCount='8';
      const hubLabelMap=new Map(hubLabels.map((label)=>[Number(label.dataset.hubPlanet),label]));
      const sun = new THREE.Mesh(new THREE.SphereGeometry(.72, 48, 48), new THREE.MeshPhysicalMaterial({ color: 0xf2bd43, emissive: 0xe49a16, emissiveIntensity: 1.15, roughness: .22, clearcoat: .7 }));
      sun.position.y = .06; world.add(sun);
      const corona = new THREE.Mesh(new THREE.SphereGeometry(.96, 32, 32), new THREE.MeshBasicMaterial({ color: 0xf3c95d, transparent: true, opacity: .1, wireframe: true }));
      corona.position.copy(sun.position); world.add(corona);
      [
        [1.16,.09,0x8b8174,.34,.2,false,'Mercury'],
        [1.54,.16,0xd4a260,.26,1.35,false,'Venus'],
        [2.03,.2,0x278a9a,.205,2.35,true,'Earth'],
        [2.49,.12,0xb85f43,.17,3.16,false,'Mars'],
        [3.18,.37,0xc49160,.108,4.02,false,'Jupiter'],
        [3.93,.3,0xd1b477,.084,4.74,false,'Saturn'],
        [4.62,.22,0x73c8c8,.066,5.3,false,'Uranus'],
        [5.28,.21,0x527bb6,.053,5.84,false,'Neptune'],
      ].forEach(([radius,size,color,speed,phase,physics,name], index) => {
        orbit(radius, physics ? .48 : .23);
        const planet = new THREE.Mesh(new THREE.SphereGeometry(size, 32, 32), new THREE.MeshPhysicalMaterial({ color, roughness: .46, clearcoat: .3 }));
        planet.name=name; world.add(planet);
        if (physics) { const marker = new THREE.Mesh(new THREE.TorusGeometry(size * 1.55,.018,8,64), new THREE.MeshBasicMaterial({ color:0x0d687c,transparent:true,opacity:.65 })); marker.rotation.x=Math.PI/2.8; planet.add(marker); }
        if(name==='Saturn'){const rings=new THREE.Mesh(new THREE.RingGeometry(size*1.35,size*2.05,72),new THREE.MeshBasicMaterial({color:0xbba46f,transparent:true,opacity:.62,side:THREE.DoubleSide}));rings.rotation.x=Math.PI/2.45;planet.add(rings);}
        if(name==='Uranus'){const rings=new THREE.Mesh(new THREE.RingGeometry(size*1.28,size*1.6,64),new THREE.MeshBasicMaterial({color:0x9bd4d1,transparent:true,opacity:.42,side:THREE.DoubleSide}));rings.rotation.x=Math.PI/2.08;planet.add(rings);}
        const projected = new THREE.Vector3();
        const sunProjected = new THREE.Vector3();
        projectors.push(() => {
          const label=hubLabelMap.get(index); if (!label) return;
          planet.getWorldPosition(projected).project(camera);
          sun.getWorldPosition(sunProjected).project(camera);
          const width=host.clientWidth,height=host.clientHeight;
          const naturalX=(projected.x*.5+.5)*width,naturalY=(-projected.y*.5+.5)*height;
          const sunX=(sunProjected.x*.5+.5)*width,sunY=(-sunProjected.y*.5+.5)*height;
          const dx=naturalX-sunX,dy=naturalY-sunY,length=Math.hypot(dx,dy)||1;
          const ux=dx/length,uy=dy/length;
          const text=label.querySelector('span');
          const halfWidth=Math.max(text?.offsetWidth||70,70)/2,halfHeight=Math.max(text?.offsetHeight||30,30)/2;
          const tangentLength=Math.hypot(-uy-.16*ux,ux-.16*uy)||1;
          const tx=(-uy-.16*ux)/tangentLength,ty=(ux-.16*uy)/tangentLength;
          const objectSupport=Math.max(4,size*(width<600?64:72));
          const textSupport=halfWidth*Math.abs(tx)+halfHeight*Math.abs(ty);
          const baseDistance=objectSupport+textSupport+2;
          const distance=baseDistance*.5;
          const labelX=naturalX+tx*distance,labelY=naturalY+ty*distance;
          setLeader(label,labelX-naturalX,labelY-naturalY);
          label.style.transform=`translate3d(${naturalX}px,${naturalY}px,0) translate(-50%,-50%)`;
          label.dataset.anchorX=naturalX.toFixed(2); label.dataset.anchorY=naturalY.toFixed(2);
          label.dataset.labelX=labelX.toFixed(2); label.dataset.labelY=labelY.toFixed(2);
          label.dataset.objectSupport=objectSupport.toFixed(2);
          label.dataset.baseDistance=baseDistance.toFixed(2);
          const show=projected.z<1&&scrollY<height*.72;
          label.style.opacity=show?'1':'0'; label.style.pointerEvents=show?'auto':'none';
        });
        updates.push((time) => { const angle=phase+time*speed; planet.position.set(Math.cos(angle)*radius,Math.sin(angle*.72)*.08,Math.sin(angle)*radius); planet.rotation.y=time*(.18+speed); });
      });
      updates.push((time) => { sun.rotation.y=time*.08; corona.rotation.y=-time*.04; corona.rotation.z=time*.025; });
      world.rotation.z = -.08;
    } else {
      host.dataset.spacecraftCount='4';
      const planet = new THREE.Group(); world.add(planet);
      const earthSurface=new THREE.Group(); planet.add(earthSurface);
      const earthTexture=new THREE.TextureLoader().load('/assets/v2/images/earth-vietnam-map.webp?v=1',()=>{host.dataset.earthMap='loaded';});
      earthTexture.colorSpace=THREE.SRGBColorSpace; earthTexture.anisotropy=Math.min(renderer.capabilities.getMaxAnisotropy(),8);
      const globe = new THREE.Mesh(new THREE.SphereGeometry(1.62,64,64), new THREE.MeshPhysicalMaterial({ map:earthTexture,color:0xffffff,emissive:0x174b56,emissiveIntensity:.08,roughness:.62,clearcoat:.28 })); earthSurface.add(globe);
      const grid = new THREE.Mesh(new THREE.SphereGeometry(1.635,24,16), new THREE.MeshBasicMaterial({ color:0xf7f3e8,wireframe:true,transparent:true,opacity:.11 })); earthSurface.add(grid);
      const atmosphere = new THREE.Mesh(new THREE.SphereGeometry(1.82,40,40), new THREE.MeshBasicMaterial({ color:0x63cad0,transparent:true,opacity:.075,side:THREE.BackSide })); planet.add(atmosphere);
      const mapLocations=[
        {lat:16,lon:108.2},{lat:16.5,lon:112},{lat:10,lon:114},
        {lat:48.8566,lon:2.3522,ipho:true},{lat:32.6546,lon:51.668,ipho:true},
        {lat:35.6762,lon:139.6503,ipho:true},{lat:54.6872,lon:25.2797,ipho:true},
        {lat:32.0853,lon:34.7818,ipho:true},
      ];
      host.dataset.iphoCityCount='5';
      const mapMarkers=[];
      mapLocations.forEach(({lat,lon,ipho},index)=>{
        const marker=new THREE.Group();
        const point=surfacePoint(lat,lon,1.65); marker.position.copy(point);
        const dotRadius=ipho ? .035 : (index ? .028 : .04);
        const dotColor=ipho ? 0x45b9c4 : (index ? 0xf0c44e : 0xe83d2f);
        const dot=new THREE.Mesh(new THREE.SphereGeometry(dotRadius,16,16),new THREE.MeshBasicMaterial({color:dotColor})); marker.add(dot);
        if(ipho){
          const ring=new THREE.Mesh(new THREE.TorusGeometry(.072,.008,8,36),new THREE.MeshBasicMaterial({color:0xbdebe8,transparent:true,opacity:.82})); marker.add(ring);
          marker.quaternion.setFromUnitVectors(new THREE.Vector3(0,0,1),point.clone().normalize());
        }
        earthSurface.add(marker); mapMarkers.push(marker);
      });
      const mapProjected=new THREE.Vector3(),mapWorld=new THREE.Vector3(),earthWorld=new THREE.Vector3(),toCamera=new THREE.Vector3(),surfaceNormal=new THREE.Vector3();
      projectors.push(()=>{
        const width=host.clientWidth,height=host.clientHeight,compact=width<600;
        const offsets=compact
          ?[[-64,26],[72,-52],[74,48],[74,55],[78,-54],[72,38],[-74,-55],[-78,54]]
          :[[-92,30],[108,-66],[112,62],[-106,35],[112,-66],[108,42],[-110,-58],[-116,68]];
        earthSurface.getWorldPosition(earthWorld);
        mapMarkers.forEach((marker,index)=>{
          const label=geoLabels[index]; if(!label)return;
          marker.getWorldPosition(mapWorld); mapProjected.copy(mapWorld).project(camera);
          const x=(mapProjected.x*.5+.5)*width,y=(-mapProjected.y*.5+.5)*height;
          let [offsetX,offsetY]=offsets[index];
          const text=label.querySelector('b');
          const halfWidth=Math.max(text?.offsetWidth||64,64)/2,halfHeight=Math.max(text?.offsetHeight||24,24)/2;
          const edge=compact?7:12;
          const labelX=Math.min(width-halfWidth-edge,Math.max(halfWidth+edge,x+offsetX));
          const labelY=Math.min(height-halfHeight-edge,Math.max(halfHeight+edge,y+offsetY));
          offsetX=labelX-x; offsetY=labelY-y;
          setLeader(label,offsetX,offsetY);
          label.style.transform=`translate3d(${x}px,${y}px,0) translate(-50%,-50%)`;
          label.dataset.anchorX=x.toFixed(2); label.dataset.anchorY=y.toFixed(2);
          label.dataset.labelX=labelX.toFixed(2); label.dataset.labelY=labelY.toFixed(2);
          surfaceNormal.copy(mapWorld).sub(earthWorld).normalize(); toCamera.copy(camera.position).sub(earthWorld).normalize();
          const facing=surfaceNormal.dot(toCamera)>.05;
          const onScreen=x>-20&&x<width+20&&y>-20&&y<height+20;
          label.style.opacity=facing&&mapProjected.z<1&&onScreen?'1':'0';
        });
      });
      const settings = [
        [2.42,2.55,[.2,.18,-.18],0x247f8b,.13,()=>makeCommSatellite(0xc7a13c,false),'VINASAT-1'],
        [2.62,.62,[-.18,.12,.24],0xd4a844,.105,()=>makeCommSatellite(0xc9c4b8,true),'VINASAT-2'],
        [2.5,-.55,[.16,-.2,-.28],0x3f9297,.115,makeObservationSatellite,'VNREDSat-1'],
        [2.72,3.72,[-.14,-.16,.2],0xb8664d,.095,makeCubeSat,'PicoDragon'],
      ];
      const satellites = settings.map(([radius,phase,tilt,color,speed,factory,name], index) => {
        const plane = new THREE.Group(); plane.rotation.set(...tilt); planet.add(plane);
        const path = new THREE.Mesh(new THREE.TorusGeometry(radius,.018,8,180), new THREE.MeshBasicMaterial({ color,transparent:true,opacity:.42 })); plane.add(path);
        const satellite = factory(); satellite.name=name; satellite.scale.setScalar(index===3?.78:.92); plane.add(satellite);
        const projected = new THREE.Vector3();
        const planetProjected = new THREE.Vector3();
        projectors.push(() => {
          const label=labels[index]; if (!label) return;
          satellite.getWorldPosition(projected).project(camera);
          planet.getWorldPosition(planetProjected).project(camera);
          const width=host.clientWidth,height=host.clientHeight;
          const x=(projected.x*.5+.5)*width,y=(-projected.y*.5+.5)*height;
          const centerX=(planetProjected.x*.5+.5)*width,centerY=(-planetProjected.y*.5+.5)*height;
          const dx=x-centerX,dy=y-centerY,length=Math.hypot(dx,dy)||1;
          const ux=dx/length,uy=dy/length;
          const text=label.querySelector('span');
          const halfWidth=Math.max(text?.offsetWidth||92,92)/2,halfHeight=Math.max(text?.offsetHeight||44,44)/2;
          const tangentLength=Math.hypot(-uy-.12*ux,ux-.12*uy)||1;
          const tx=(-uy-.12*ux)/tangentLength,ty=(ux-.12*uy)/tangentLength;
          const objectSupport=projectedObjectSupport(satellite,x,y,tx,ty,width,height);
          const textSupport=halfWidth*Math.abs(tx)+halfHeight*Math.abs(ty);
          const baseDistance=objectSupport+textSupport+2;
          const distance=baseDistance*.5;
          const labelX=x+tx*distance,labelY=y+ty*distance;
          setLeader(label,labelX-x,labelY-y);
          label.style.transform=`translate3d(${x}px,${y}px,0) translate(-50%,-50%)`;
          label.dataset.anchorX=x.toFixed(2); label.dataset.anchorY=y.toFixed(2);
          label.dataset.labelX=labelX.toFixed(2); label.dataset.labelY=labelY.toFixed(2);
          label.dataset.objectSupport=objectSupport.toFixed(2);
          label.dataset.baseDistance=baseDistance.toFixed(2);
          const onScreen=x>-20&&x<width+20&&y>-20&&y<height+20;
          const show=projected.z<1&&onScreen&&scrollY<height*.72; label.style.opacity=show?'1':'0'; label.style.pointerEvents=show?'auto':'none';
        });
        return { satellite,radius,phase,speed };
      });
      updates.push((time) => {
        earthSurface.rotation.y=Math.PI+time*.026; atmosphere.scale.setScalar(1+Math.sin(time*1.2)*.012);
        satellites.forEach(({satellite,radius,phase,speed}) => { const angle=phase+time*speed; satellite.position.set(Math.cos(angle)*radius,Math.sin(angle)*radius,0); satellite.rotation.y=time*.55; satellite.rotation.z=time*.18; });
      });
    }

    const resize = () => {
      if (!host.clientWidth || !host.clientHeight) return;
      renderer.setSize(host.clientWidth,host.clientHeight,false); camera.aspect=host.clientWidth/host.clientHeight;
      const portrait=camera.aspect<.72,wide=camera.aspect>1.35;
      if (variant === 'hub') {
        // Keep the orbital plane legible: mostly overhead, with just enough
        // perspective to preserve depth without letting planets hide behind the Sun.
        camera.position.y=portrait?16.2:wide?11.5:13.4;
        camera.position.z=portrait?5.8:wide?4.3:4.9;
      } else {
        camera.position.y=.2;
        camera.position.z=portrait?10.4:wide?6.9:7.8;
      }
      camera.updateProjectionMatrix();
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
        camera.lookAt(0,0,0); camera.updateMatrixWorld(true); world.updateMatrixWorld(true); projectors.forEach((project)=>project()); renderer.render(scene,camera);
      }
      requestAnimationFrame(render);
    };
    requestAnimationFrame(render);
  } catch (error) {
    host.classList.add('isFallback');
    console.warn('WebGL unavailable; using the planetary fallback.', error);
  }
}
