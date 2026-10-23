/* (c) 2026 Wizarts BV, wizarts.be. Alle rechten voorbehouden. wzts-gawfozonsd */
const root = document.documentElement;
const $ = <T extends Element = HTMLElement>(s: string, el: ParentNode = document) => el.querySelector<T>(s as any) as T | null;
const $$ = <T extends Element = HTMLElement>(s: string, el: ParentNode = document) => Array.from(el.querySelectorAll<T>(s as any)) as T[];

/* Mobile menu */
const btn = $<HTMLButtonElement>('[data-menu-btn]');
const setMenu = (open: boolean) => {
  root.classList.toggle('menu-open', open);
  btn?.setAttribute('aria-expanded', String(open));
  const l = $('[data-menu-label]');
  if (l) l.textContent = open ? 'Sluiten' : 'Menu';
};
btn?.addEventListener('click', () => setMenu(!root.classList.contains('menu-open')));
document.addEventListener('keydown', (e) => { if (e.key === 'Escape') setMenu(false); });

/* Behandelingen panel: hover on pointer devices, click and keyboard everywhere */
const megaBtn = $<HTMLButtonElement>('[data-dd-btn]');
const mega = $('[data-mega]');
if (megaBtn && mega) {
  let t: number | undefined;
  let hoverOpenedAt = 0;
  const set = (open: boolean) => { mega.classList.toggle('is-open', open); megaBtn.setAttribute('aria-expanded', String(open)); };
  // A click right after hover opened the panel should not close it again.
  megaBtn.addEventListener('click', () => {
    const open = mega.classList.contains('is-open');
    if (open && Date.now() - hoverOpenedAt < 600) return;
    set(!open);
  });
  if (matchMedia('(hover: hover)').matches) {
    [megaBtn, mega].forEach((el) => {
      el.addEventListener('pointerenter', () => { clearTimeout(t); if (!mega.classList.contains('is-open')) hoverOpenedAt = Date.now(); set(true); });
      el.addEventListener('pointerleave', () => { t = window.setTimeout(() => set(false), 200); });
    });
  }
  mega.addEventListener('focusout', (e) => { if (!mega.contains(e.relatedTarget as Node) && e.relatedTarget !== megaBtn) set(false); });
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && mega.classList.contains('is-open')) { set(false); megaBtn.focus(); } });
  document.addEventListener('click', (e) => { if (!mega.contains(e.target as Node) && !megaBtn.contains(e.target as Node)) set(false); });
}

/* Header line once the page scrolls (IntersectionObserver on a sentinel, no scroll listener) */
const hd = $('[data-header]');
const sentinel = document.createElement('div');
sentinel.style.cssText = 'position:absolute;top:0;height:1px;width:1px';
document.body.prepend(sentinel);
new IntersectionObserver(([e]) => hd?.classList.toggle('is-scrolled', !e.isIntersecting)).observe(sentinel);
/* Mobile booking button appears once the hero is out of view */
const dockEl = $('[data-dock]');
const far = document.createElement('div');
far.style.cssText = 'position:absolute;top:70vh;height:1px;width:1px';
document.body.prepend(far);
new IntersectionObserver(([e]) => dockEl?.classList.toggle('is-on', !e.isIntersecting && e.boundingClientRect.top < 0)).observe(far);
$$('[data-sheet] a').forEach((a) => a.addEventListener('click', () => setMenu(false)));

/* Info line: today's opening hours */
const todayEl = $('[data-today]');
if (todayEl) {
  const wdNow = new Intl.DateTimeFormat('en-GB', { timeZone: 'Europe/Brussels', weekday: 'short' }).format(new Date());
  const dNow = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].indexOf(wdNow);
  const h = (JSON.parse(todayEl.dataset.hours || '[]') as { d: number; t: string }[]).find((x) => x.d === dNow);
  const svg = todayEl.querySelector('svg')?.outerHTML ?? '';
  todayEl.innerHTML = `${svg}${h?.t ? `Vandaag open ${h.t}` : 'Vandaag gesloten'}`;
}

/* Reveals: content enters once, in reading order */
const io = new IntersectionObserver(
  (es) => es.forEach((e) => { if (e.isIntersecting) { e.target.classList.add('is-in'); io.unobserve(e.target); } }),
  { rootMargin: '0px 0px -10% 0px' }
);
$$('.rv, .rv-img').forEach((el) => io.observe(el));

/* Opening hours: mark today */
const wd = new Intl.DateTimeFormat('en-GB', { timeZone: 'Europe/Brussels', weekday: 'short' }).format(new Date());
const d = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].indexOf(wd);
$$(`[data-week] [data-day="${d}"]`).forEach((el) => el.classList.add('today'));

/* Treatment switch: keep the current treatment in view */
const sw = $('.tswitch');
const cur = sw?.querySelector<HTMLElement>('[aria-current="page"]');
if (sw && cur) sw.scrollLeft = cur.offsetLeft - (sw.clientWidth - cur.offsetWidth) / 2;

/* Contact form: validate, then hand off to the visitor's mail app */
$$<HTMLFormElement>('[data-form]').forEach((form) => {
  const done = form.parentElement!.querySelector<HTMLElement>('.done');
  const check = (f: HTMLInputElement) => { const ok = f.checkValidity(); f.closest('.field')!.classList.toggle('has-error', !ok); f.setAttribute('aria-invalid', String(!ok)); return ok; };
  form.addEventListener('submit', (e) => {
    e.preventDefault();
    const bad = $$<HTMLInputElement>('input, textarea', form).filter((f) => !check(f));
    if (bad.length) { bad[0].focus(); return; }
    const v = (n: string) => (form.elements.namedItem(n) as HTMLInputElement).value.trim();
    location.href = `mailto:${form.dataset.mailto}?subject=${encodeURIComponent('Vraag via de website')}&body=${encodeURIComponent(`${v('vraag')}\n\n${v('naam')}${v('telefoon') ? `\n${v('telefoon')}` : ''}`)}`;
    form.hidden = true;
    if (done) { done.hidden = false; done.focus(); }
  });
});
