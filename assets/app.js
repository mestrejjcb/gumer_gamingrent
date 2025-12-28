(function(){
  function getTheme(){ return localStorage.getItem('theme'); }
  function setTheme(t){
    document.documentElement.setAttribute('data-bs-theme', t);
    localStorage.setItem('theme', t);
    const lbl = document.getElementById('themeLabel');
    if (lbl) lbl.textContent = t;
    const btn = document.getElementById('themeToggle');
    if (btn) btn.textContent = (t === 'dark') ? 'Modo claro' : 'Modo oscuro';
  }
  const saved = getTheme();
  const preferred = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
  setTheme(saved || preferred);

  const btn = document.getElementById('themeToggle');
  if (btn){
    btn.addEventListener('click', () => {
      const current = document.documentElement.getAttribute('data-bs-theme') || 'light';
      setTheme(current === 'dark' ? 'light' : 'dark');
    });
  }
})();