// ============================================
// FINOVA — Main JS
// ============================================

document.addEventListener('DOMContentLoaded', function () {

  // Page loader
  const loader = document.querySelector('.page-loader');
  if (loader) setTimeout(() => loader.classList.add('hidden'), 600);

  // Burger menu
  const burger = document.getElementById('burger');
  const navLinks = document.getElementById('navLinks');
  if (burger && navLinks) {
    burger.addEventListener('click', () => {
      navLinks.classList.toggle('open');
      burger.classList.toggle('open');
    });
  }

  // Language dropdown
  const langBtn = document.getElementById('langBtn');
  const langDropdown = document.getElementById('langDropdown');
  if (langBtn && langDropdown) {
    langBtn.addEventListener('click', (e) => {
      e.stopPropagation();
      langDropdown.classList.toggle('open');
    });
    document.addEventListener('click', () => langDropdown.classList.remove('open'));
  }

  // FAQ accordion
  document.querySelectorAll('.faq-question').forEach(q => {
    q.addEventListener('click', () => {
      const item = q.closest('.faq-item');
      const wasOpen = item.classList.contains('open');
      document.querySelectorAll('.faq-item').forEach(i => i.classList.remove('open'));
      if (!wasOpen) item.classList.add('open');
    });
  });

  // Scroll reveal
  const reveals = document.querySelectorAll('.reveal');
  if (reveals.length) {
    const obs = new IntersectionObserver((entries) => {
      entries.forEach((e, i) => {
        if (e.isIntersecting) {
          setTimeout(() => e.target.classList.add('visible'), i * 80);
          obs.unobserve(e.target);
        }
      });
    }, { threshold: 0.1 });
    reveals.forEach(r => obs.observe(r));
  }

  // Active nav link
  const currentPage = window.location.pathname.split('/').pop() || 'index.php';
  document.querySelectorAll('.nav-link').forEach(link => {
    if (link.getAttribute('href') && link.getAttribute('href').includes(currentPage.replace('.php', ''))) {
      link.classList.add('active');
    }
  });

  // Animated counters
  function animateCounter(el) {
    const target = parseInt(el.dataset.target || el.textContent.replace(/\D/g, ''));
    const prefix = el.dataset.prefix || '';
    const suffix = el.dataset.suffix || '';
    const duration = 1800;
    const start = performance.now();
    function update(now) {
      const progress = Math.min((now - start) / duration, 1);
      const eased = 1 - Math.pow(1 - progress, 3);
      el.textContent = prefix + Math.floor(eased * target).toLocaleString() + suffix;
      if (progress < 1) requestAnimationFrame(update);
    }
    requestAnimationFrame(update);
  }
  const counterObs = new IntersectionObserver(entries => {
    entries.forEach(e => {
      if (e.isIntersecting) {
        animateCounter(e.target);
        counterObs.unobserve(e.target);
      }
    });
  });
  document.querySelectorAll('[data-counter]').forEach(el => counterObs.observe(el));

  // File upload labels
  document.querySelectorAll('.file-upload input').forEach(input => {
    input.addEventListener('change', function () {
      const label = this.closest('.file-upload').querySelector('.file-name');
      if (label && this.files[0]) {
        label.textContent = '✓ ' + this.files[0].name;
        label.style.display = 'block';
      }
    });
  });

  // Program selector on form
  document.querySelectorAll('.program-option').forEach(opt => {
    opt.addEventListener('click', function () {
      document.querySelectorAll('.program-option').forEach(o => o.classList.remove('selected'));
      this.classList.add('selected');
      const radio = this.querySelector('input');
      if (radio) radio.checked = true;
      const amountMin = this.dataset.min;
      const amountMax = this.dataset.max;
      const amountField = document.getElementById('requested_amount');
      if (amountField) {
        amountField.min = amountMin;
        amountField.max = amountMax;
        amountField.placeholder = amountMin + ' – ' + amountMax;
      }
    });
  });

  // Application form submission
  const appForm = document.getElementById('applicationForm');
  if (appForm) {
    appForm.addEventListener('submit', async function (e) {
      e.preventDefault();
      if (!validateForm(appForm)) return;
      const submitBtn = appForm.querySelector('.form-submit-btn');
      const origText = submitBtn.innerHTML;
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<div class="loader-ring" style="width:18px;height:18px;border-width:2px;margin:0 auto"></div>';
      const formData = new FormData(appForm);
      try {
        const res = await fetch('api/submit_application.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
          appForm.style.display = 'none';
          const successCard = document.getElementById('successCard');
          if (successCard) {
            successCard.style.display = 'block';
            const refEl = successCard.querySelector('.success-ref');
            if (refEl) refEl.textContent = data.reference;
          }
          window.scrollTo({ top: 0, behavior: 'smooth' });
        } else {
          showAlert(data.message || 'Une erreur est survenue.', 'danger');
          submitBtn.disabled = false;
          submitBtn.innerHTML = origText;
        }
      } catch (err) {
        showAlert('Erreur réseau. Veuillez réessayer.', 'danger');
        submitBtn.disabled = false;
        submitBtn.innerHTML = origText;
      }
    });
  }

  // Contact form
  const contactForm = document.getElementById('contactForm');
  if (contactForm) {
    contactForm.addEventListener('submit', async function (e) {
      e.preventDefault();
      const btn = contactForm.querySelector('[type="submit"]');
      btn.disabled = true;
      const formData = new FormData(contactForm);
      try {
        const res = await fetch('api/contact.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (data.success) {
          showAlert(data.message, 'success');
          contactForm.reset();
        } else {
          showAlert(data.message, 'danger');
        }
      } catch (e) {
        showAlert('Erreur. Veuillez réessayer.', 'danger');
      }
      btn.disabled = false;
    });
  }

  // Tracking form
  const trackForm = document.getElementById('trackForm');
  if (trackForm) {
    trackForm.addEventListener('submit', async function (e) {
      e.preventDefault();
      const btn = trackForm.querySelector('[type="submit"]');
      btn.disabled = true;
      const result = document.getElementById('trackResult');
      if (result) result.innerHTML = '<div style="text-align:center;padding:20px"><div class="loader-ring"></div></div>';
      const formData = new FormData(trackForm);
      try {
        const res = await fetch('api/track.php', { method: 'POST', body: formData });
        const data = await res.json();
        if (result) result.innerHTML = data.html || '<p style="color:var(--danger)">Aucun résultat.</p>';
      } catch (e) {
        if (result) result.innerHTML = '<p style="color:var(--danger)">Erreur réseau.</p>';
      }
      btn.disabled = false;
    });
  }
});

// ---- FORM VALIDATION ----
function validateForm(form) {
  let valid = true;
  form.querySelectorAll('[required]').forEach(field => {
    const group = field.closest('.form-group');
    if (!field.value.trim()) {
      group.classList.add('has-error');
      valid = false;
    } else {
      group.classList.remove('has-error');
    }
    if (field.type === 'email' && field.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(field.value)) {
      group.classList.add('has-error');
      valid = false;
    }
  });
  if (!valid) {
    const firstError = form.querySelector('.has-error');
    if (firstError) firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }
  return valid;
}

// ---- ALERT ----
function showAlert(msg, type = 'info') {
  const el = document.createElement('div');
  el.className = `alert alert-${type}`;
  el.innerHTML = `<span>${msg}</span>`;
  document.body.prepend(el);
  el.style.cssText = 'position:fixed;top:80px;left:50%;transform:translateX(-50%);z-index:9999;min-width:320px;max-width:560px;text-align:center;';
  setTimeout(() => el.remove(), 5000);
}
