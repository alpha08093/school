/* ===== PRELOADER : attendre le chargement complet avant d'afficher ===== */
(function(){
  const MIN_DISPLAY = 900; // durée minimale d'affichage pour éviter un flash
  const start = Date.now();
  function reveal(){
    const elapsed = Date.now() - start;
    const wait = Math.max(0, MIN_DISPLAY - elapsed);
    setTimeout(function(){
      document.getElementById('preloader').classList.add('hide');
      document.body.classList.remove('is-loading');
    }, wait);
  }
  if(document.readyState === 'complete'){ reveal(); }
  else { window.addEventListener('load', reveal); }
  // Filet de sécurité : ne jamais bloquer l'affichage plus de 6s
  setTimeout(reveal, 6000);
})();

/* ===== Compteurs de pourcentages animés (0 -> valeur réelle) ===== */
(function(){
  function formatNumber(value, decimals){
    return value.toFixed(decimals).replace('.', ',') + '%';
  }
  function animateCounter(el){
    if(el.dataset.counted) return;
    el.dataset.counted = '1';
    const target = parseFloat(el.getAttribute('data-target'));
    const decimals = parseInt(el.getAttribute('data-decimals') || '0', 10);
    const duration = 1400;
    const startTime = performance.now();
    function step(now){
      const progress = Math.min(1, (now - startTime) / duration);
      const eased = 1 - Math.pow(1 - progress, 3); // ease-out
      const current = target * eased;
      el.textContent = formatNumber(current, decimals);
      if(progress < 1){
        requestAnimationFrame(step);
      } else {
        el.textContent = formatNumber(target, decimals);
      }
    }
    requestAnimationFrame(step);
  }
  const counters = document.querySelectorAll('.counter');
  if('IntersectionObserver' in window){
    const observer = new IntersectionObserver(function(entries){
      entries.forEach(function(entry){
        if(entry.isIntersecting){
          animateCounter(entry.target);
          observer.unobserve(entry.target);
        }
      });
    }, {threshold:0.4});
    counters.forEach(function(c){ observer.observe(c); });
  } else {
    counters.forEach(animateCounter);
  }
})();

/* ===== Mode clair / sombre ===== */
(function(){
  const btn = document.getElementById('themeToggle');
  const saved = localStorage.getItem('site-theme');
  if(saved === 'dark'){
    document.documentElement.setAttribute('data-theme','dark');
    if(btn) btn.textContent = '☀️';
  }
  if(btn){
    btn.addEventListener('click', function(){
      const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
      if(isDark){
        document.documentElement.removeAttribute('data-theme');
        localStorage.setItem('site-theme','light');
        btn.textContent = '🌙';
      } else {
        document.documentElement.setAttribute('data-theme','dark');
        localStorage.setItem('site-theme','dark');
        btn.textContent = '☀️';
      }
    });
  }
})();

/* ===== Navigation entre pages ===== */
function showPage(id){
  document.querySelectorAll('.page-section').forEach(p=>p.classList.remove('active'));
  document.getElementById('page-'+id).classList.add('active');
  document.querySelectorAll('#navList .nav-link').forEach(l=>l.classList.remove('active'));
  const link = document.querySelector('#navList .nav-link[data-page="'+id+'"]');
  if(link) link.classList.add('active');
  window.scrollTo({top:0,behavior:'smooth'});
  const collapse = document.getElementById('navMain');
  if(collapse.classList.contains('show')) bootstrap.Collapse.getOrCreateInstance(collapse).hide();
}
document.querySelector('#navList .nav-link[data-page="accueil"]').classList.add('active');

function showCycle(which){
  document.getElementById('cycleCollege').style.display = which==='college' ? 'flex' : 'none';
  document.getElementById('cycleLycee').style.display = which==='lycee' ? 'flex' : 'none';
  document.getElementById('btnCollege').classList.toggle('active', which==='college');
  document.getElementById('btnLycee').classList.toggle('active', which==='lycee');
}

/* ===== Bandeau défilant (contenu fourni par PHP via window.SITE_DATA) ===== */
function renderTicker(){
  const track = document.getElementById('tickerTrack');
  const items = (window.SITE_DATA && window.SITE_DATA.tickerItems) || [];
  if(!track || !items.length) return;
  const html = items.map(t=>`<span>${tr(t)}</span>`).join('');
  track.innerHTML = html + html;
}
renderTicker();

document.getElementById('announceClose').addEventListener('click', function(){
  document.getElementById('announceBar').style.display = 'none';
  document.getElementById('pageBody').classList.remove('has-announce');
});

window.addEventListener('scroll', function(){
  document.getElementById('scrollTopBtn').classList.toggle('show', window.scrollY > 420);
});

document.getElementById('newsletterForm').addEventListener('submit', function(e){
  e.preventDefault();
  document.getElementById('newsletterMsg').classList.remove('d-none');
  this.reset();
});

/* ===== Formulaire de préinscription : envoi réel au serveur ===== */
(function(){
  const form = document.getElementById('contactForm');
  if(!form) return;
  const msg = document.getElementById('formMsg');
  form.addEventListener('submit', function(e){
    e.preventDefault();
    const submitBtn = form.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    msg.classList.remove('d-none', 'text-danger');
    msg.classList.add('text-ciel');
    msg.textContent = tr('Envoi en cours…');

    const formData = new FormData(form);
    fetch('submit-preinscription.php', { method: 'POST', body: formData })
      .then(function(res){ return res.json(); })
      .then(function(data){
        msg.textContent = tr(data.message);
        if(data.ok){
          msg.classList.remove('text-danger');
          msg.classList.add('text-ciel');
          form.reset();
        } else {
          msg.classList.remove('text-ciel');
          msg.classList.add('text-danger');
        }
      })
      .catch(function(){
        msg.classList.remove('text-ciel');
        msg.classList.add('text-danger');
        msg.textContent = tr("Une erreur est survenue. Merci de réessayer ou de nous contacter par téléphone.");
      })
      .finally(function(){
        submitBtn.disabled = false;
      });
  });
})();

/* ===== Actualités : rendues côté serveur (PHP) ; POSTS alimente juste la fenêtre "Voir plus" ===== */
function openPostModal(i){
  const posts = (window.SITE_DATA && window.SITE_DATA.posts) || [];
  const p = posts[i];
  if(!p) return;
  const modalImg = document.getElementById('postModalImg');
  modalImg.className = 'post-img ' + p.cat_bg + (p.image_url ? ' has-photo' : '');
  modalImg.style.backgroundImage = p.image_url ? "url('"+p.image_url+"')" : '';
  document.getElementById('postModalIcon').className = 'bi ' + p.icon + ' post-icon';
  document.getElementById('postModalTag').className = 'tag ' + p.tag_class;
  document.getElementById('postModalTag').textContent = tr(p.label);
  const dateEl = document.getElementById('postModalDate');
  const d = new Date(p.date_iso + 'T00:00:00');
  const locale = (window.SiteLang && window.SiteLang.current === 'en') ? 'en-US' : 'fr-FR';
  dateEl.textContent = isNaN(d.getTime()) ? p.date_label : d.toLocaleDateString(locale, {day:'2-digit', month:'long', year:'numeric'});
  document.getElementById('postModalTitle').textContent = p.title;
  document.getElementById('postModalDesc').textContent = p.desc;
  new bootstrap.Modal(document.getElementById('postModal')).show();
}

window.addEventListener('sitelangchange', function(){
  renderTicker();
});
