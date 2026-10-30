/* Instituut Soleil. Gebouwd met wordpress/bin/build-js.mjs uit src/scripts/main.ts en src/components/Newsletter.astro. Niet met de hand aanpassen. */
(() => {
  // src/scripts/main.ts
  var root = document.documentElement;
  var $ = (s, el = document) => el.querySelector(s);
  var $$ = (s, el = document) => Array.from(el.querySelectorAll(s));
  var btn = $("[data-menu-btn]");
  var setMenu = (open) => {
    root.classList.toggle("menu-open", open);
    btn?.setAttribute("aria-expanded", String(open));
    const l = $("[data-menu-label]");
    if (l) l.textContent = open ? "Sluiten" : "Menu";
  };
  btn?.addEventListener("click", () => setMenu(!root.classList.contains("menu-open")));
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") setMenu(false);
  });
  var megaBtn = $("[data-dd-btn]");
  var mega = $("[data-mega]");
  if (megaBtn && mega) {
    let t;
    let hoverOpenedAt = 0;
    const set = (open) => {
      mega.classList.toggle("is-open", open);
      megaBtn.setAttribute("aria-expanded", String(open));
    };
    megaBtn.addEventListener("click", () => {
      const open = mega.classList.contains("is-open");
      if (open && Date.now() - hoverOpenedAt < 600) return;
      set(!open);
    });
    if (matchMedia("(hover: hover)").matches) {
      [megaBtn, mega].forEach((el) => {
        el.addEventListener("pointerenter", () => {
          clearTimeout(t);
          if (!mega.classList.contains("is-open")) hoverOpenedAt = Date.now();
          set(true);
        });
        el.addEventListener("pointerleave", () => {
          t = window.setTimeout(() => set(false), 200);
        });
      });
    }
    mega.addEventListener("focusout", (e) => {
      if (!mega.contains(e.relatedTarget) && e.relatedTarget !== megaBtn) set(false);
    });
    document.addEventListener("keydown", (e) => {
      if (e.key === "Escape" && mega.classList.contains("is-open")) {
        set(false);
        megaBtn.focus();
      }
    });
    document.addEventListener("click", (e) => {
      if (!mega.contains(e.target) && !megaBtn.contains(e.target)) set(false);
    });
  }
  var hd = $("[data-header]");
  var sentinel = document.createElement("div");
  sentinel.style.cssText = "position:absolute;top:0;height:1px;width:1px";
  document.body.prepend(sentinel);
  new IntersectionObserver(([e]) => hd?.classList.toggle("is-scrolled", !e.isIntersecting)).observe(sentinel);
  var dockEl = $("[data-dock]");
  var far = document.createElement("div");
  far.style.cssText = "position:absolute;top:70vh;height:1px;width:1px";
  document.body.prepend(far);
  new IntersectionObserver(([e]) => dockEl?.classList.toggle("is-on", !e.isIntersecting && e.boundingClientRect.top < 0)).observe(far);
  $$("[data-sheet] a").forEach((a) => a.addEventListener("click", () => setMenu(false)));
  var todayEl = $("[data-today]");
  if (todayEl) {
    const wdNow = new Intl.DateTimeFormat("en-GB", { timeZone: "Europe/Brussels", weekday: "short" }).format(/* @__PURE__ */ new Date());
    const dNow = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"].indexOf(wdNow);
    const h = JSON.parse(todayEl.dataset.hours || "[]").find((x) => x.d === dNow);
    const svg = todayEl.querySelector("svg")?.outerHTML ?? "";
    todayEl.innerHTML = `${svg}${h?.t ? `Vandaag open ${h.t}` : "Vandaag gesloten"}`;
  }
  var io = new IntersectionObserver(
    (es) => es.forEach((e) => {
      if (e.isIntersecting) {
        e.target.classList.add("is-in");
        io.unobserve(e.target);
      }
    }),
    { rootMargin: "0px 0px -10% 0px" }
  );
  $$(".rv, .rv-img").forEach((el) => io.observe(el));
  var wd = new Intl.DateTimeFormat("en-GB", { timeZone: "Europe/Brussels", weekday: "short" }).format(/* @__PURE__ */ new Date());
  var d = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"].indexOf(wd);
  $$(`[data-week] [data-day="${d}"]`).forEach((el) => el.classList.add("today"));
  var sw = $(".tswitch");
  var cur = sw?.querySelector('[aria-current="page"]');
  if (sw && cur) sw.scrollLeft = cur.offsetLeft - (sw.clientWidth - cur.offsetWidth) / 2;
  $$("[data-form]").forEach((form) => {
    const done = form.parentElement.querySelector(".done");
    const check = (f) => {
      const ok = f.checkValidity();
      f.closest(".field").classList.toggle("has-error", !ok);
      f.setAttribute("aria-invalid", String(!ok));
      return ok;
    };
    form.addEventListener("submit", (e) => {
      e.preventDefault();
      const bad = $$("input, textarea", form).filter((f) => !check(f));
      if (bad.length) {
        bad[0].focus();
        return;
      }
      const v = (n) => form.elements.namedItem(n).value.trim();
      location.href = `mailto:${form.dataset.mailto}?subject=${encodeURIComponent("Vraag via de website")}&body=${encodeURIComponent(`${v("vraag")}

${v("naam")}${v("telefoon") ? `
${v("telefoon")}` : ""}`)}`;
      form.hidden = true;
      if (done) {
        done.hidden = false;
        done.focus();
      }
    });
  });

  // main-and-newsletter.ts
  {
    const KEY = "soleil-nieuwsbrief";
    const box = document.querySelector("[data-nl]");
    const form = box?.querySelector("[data-nl-form]");
    const msg = box?.querySelector("[data-nl-msg]");
    const get = () => {
      try {
        return localStorage.getItem(KEY);
      } catch {
        return "x";
      }
    };
    const set = (v) => {
      try {
        localStorage.setItem(KEY, v);
      } catch {
      }
    };
    const seen = get();
    const quiet = seen === "ingeschreven" || seen && Date.now() - Number(seen) < 60 * 864e5;
    if (box && form && !quiet) {
      let shown = false;
      const show = () => {
        if (shown || document.documentElement.classList.contains("menu-open")) return;
        shown = true;
        box.hidden = false;
        requestAnimationFrame(() => box.classList.add("is-on"));
        removeEventListener("scroll", onScroll);
      };
      const hide = () => {
        box.classList.remove("is-on");
        setTimeout(() => box.hidden = true, 400);
      };
      const onScroll = () => {
        if (scrollY > (document.documentElement.scrollHeight - innerHeight) * 0.5) show();
      };
      setTimeout(show, 2e4);
      addEventListener("scroll", onScroll, { passive: true });
      box.querySelector("[data-nl-close]")?.addEventListener("click", () => {
        set(String(Date.now()));
        hide();
      });
      document.addEventListener("keydown", (e) => {
        if (e.key === "Escape" && !box.hidden) {
          set(String(Date.now()));
          hide();
        }
      });
      form.addEventListener("submit", (e) => {
        e.preventDefault();
        set("ingeschreven");
        form.querySelector(".nl-row")?.remove();
        msg.textContent = "Bedankt, je bent ingeschreven.";
        setTimeout(hide, 3e3);
      });
    }
  }
})();
