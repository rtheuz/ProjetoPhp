(function () {
  'use strict';

  const canvas = document.getElementById('particle-canvas');
  if (canvas) {
    const ctx = canvas.getContext('2d');
    let w, h, particles;
    const count = 80;

    function resize() {
      w = canvas.width = window.innerWidth;
      h = canvas.height = window.innerHeight;
      particles = Array.from({ length: count }, () => ({
        x: Math.random() * w,
        y: Math.random() * h,
        vx: (Math.random() - 0.5) * 0.4,
        vy: (Math.random() - 0.5) * 0.4,
        r: Math.random() * 1.5 + 0.5,
      }));
    }

    function drawGrid() {
      ctx.strokeStyle = 'rgba(0, 245, 255, 0.04)';
      ctx.lineWidth = 1;
      const step = 48;
      for (let x = 0; x < w; x += step) {
        ctx.beginPath();
        ctx.moveTo(x, 0);
        ctx.lineTo(x, h);
        ctx.stroke();
      }
      for (let y = 0; y < h; y += step) {
        ctx.beginPath();
        ctx.moveTo(0, y);
        ctx.lineTo(w, y);
        ctx.stroke();
      }
    }

    function tick() {
      ctx.clearRect(0, 0, w, h);
      drawGrid();
      particles.forEach((p, i) => {
        p.x += p.vx;
        p.y += p.vy;
        if (p.x < 0 || p.x > w) p.vx *= -1;
        if (p.y < 0 || p.y > h) p.vy *= -1;
        ctx.beginPath();
        ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2);
        ctx.fillStyle = 'rgba(0, 245, 255, 0.6)';
        ctx.fill();
        for (let j = i + 1; j < particles.length; j++) {
          const q = particles[j];
          const dx = p.x - q.x;
          const dy = p.y - q.y;
          const dist = Math.sqrt(dx * dx + dy * dy);
          if (dist < 120) {
            ctx.beginPath();
            ctx.moveTo(p.x, p.y);
            ctx.lineTo(q.x, q.y);
            ctx.strokeStyle = `rgba(0, 245, 255, ${0.15 * (1 - dist / 120)})`;
            ctx.stroke();
          }
        }
      });
      requestAnimationFrame(tick);
    }

    resize();
    window.addEventListener('resize', resize);
    tick();
  }

  const container = document.getElementById('hero-3d');
  if (container && typeof THREE !== 'undefined') {
    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(45, 1, 0.1, 100);
    camera.position.z = 5;

    const renderer = new THREE.WebGLRenderer({ alpha: true, antialias: true });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    container.appendChild(renderer.domElement);

    const group = new THREE.Group();
    scene.add(group);

    const cyan = 0x00f5ff;
    const magenta = 0xff00aa;

    const boxGeo = new THREE.BoxGeometry(1.2, 0.7, 1.8);
    const boxMat = new THREE.MeshBasicMaterial({
      color: cyan,
      wireframe: true,
      transparent: true,
      opacity: 0.85,
    });
    const cargo = new THREE.Mesh(boxGeo, boxMat);
    group.add(cargo);

    const ringGeo = new THREE.TorusGeometry(1.6, 0.02, 8, 64);
    const ringMat = new THREE.MeshBasicMaterial({ color: magenta, transparent: true, opacity: 0.7 });
    const ring1 = new THREE.Mesh(ringGeo, ringMat);
    const ring2 = ring1.clone();
    ring2.rotation.x = Math.PI / 2;
    group.add(ring1, ring2);

    const icoGeo = new THREE.IcosahedronGeometry(0.35, 0);
    const icoMat = new THREE.MeshBasicMaterial({ color: 0xffffff, wireframe: true });
    const core = new THREE.Mesh(icoGeo, icoMat);
    core.position.y = 0.9;
    group.add(core);

    const amb = new THREE.AmbientLight(0xffffff, 0.5);
    scene.add(amb);

    let mx = 0;
    let my = 0;
    document.addEventListener('mousemove', (e) => {
      mx = (e.clientX / window.innerWidth - 0.5) * 0.6;
      my = (e.clientY / window.innerHeight - 0.5) * 0.4;
    });

    function resize3d() {
      const size = Math.min(container.clientWidth, container.clientHeight, 320);
      renderer.setSize(size, size);
      camera.aspect = 1;
      camera.updateProjectionMatrix();
    }

    function animate() {
      requestAnimationFrame(animate);
      group.rotation.y += 0.008;
      group.rotation.x += 0.003;
      ring1.rotation.z += 0.012;
      ring2.rotation.y += 0.015;
      group.rotation.y += mx * 0.02;
      group.rotation.x += my * 0.02;
      renderer.render(scene, camera);
    }

    resize3d();
    window.addEventListener('resize', resize3d);
    animate();
  }

  const sidebar = document.querySelector('.sidebar');
  const toggle = document.querySelector('.sidebar-toggle');
  if (sidebar && toggle) {
    toggle.addEventListener('click', () => sidebar.classList.toggle('open'));
  }

  document.querySelectorAll('.panel, .stat-card, .glass-form').forEach((el, i) => {
    el.style.animationDelay = `${i * 0.05}s`;
  });
})();
