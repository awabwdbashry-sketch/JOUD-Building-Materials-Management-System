/* JOUD — سلوك الواجهة المشترك: نوافذ التأكيد، فتح النماذج تلقائيًا، بحث فوري بالجداول */
(function () {
  'use strict';
  // نوافذ تأكيد بدل confirm() الافتراضي: <form data-confirm="نص"> أو <a data-confirm>
  let pending = null;
  const modalEl = document.getElementById('confirmModal');
  const modal = modalEl ? new bootstrap.Modal(modalEl) : null;
  document.addEventListener('submit', function (ev) {
    const f = ev.target;
    if (!f.dataset || !f.dataset.confirm || f.dataset.confirmed) return;
    ev.preventDefault();
    ask(f.dataset.confirm, function () { f.dataset.confirmed = '1'; f.requestSubmit ? f.requestSubmit() : f.submit(); }, f.dataset.confirmTitle);
  }, true);
  document.addEventListener('click', function (ev) {
    const a = ev.target.closest('a[data-confirm]');
    if (!a) return;
    ev.preventDefault();
    ask(a.dataset.confirm, function () { location.href = a.href; });
  });
  function ask(text, cb, title) {
    if (!modal) { if (confirm(text)) cb(); return; }
    document.getElementById('confirmText').textContent = text;
    document.getElementById('confirmTitle').textContent = title || 'تأكيد الإجراء';
    pending = cb; modal.show();
  }
  const ok = document.getElementById('confirmOk');
  if (ok) ok.addEventListener('click', function () { modal.hide(); if (pending) { const c = pending; pending = null; c(); } });

  // نوافذ تُفتح تلقائيًا بعد تحميل الصفحة (تعديل ?edit=ID أو أخطاء التحقق)
  document.querySelectorAll('.modal[data-auto-open]').forEach(function (el) {
    const m = new bootstrap.Modal(el); m.show();
    // عند الإغلاق نزيل ?edit من الرابط حتى لا يُعاد فتحها
    el.addEventListener('hidden.bs.modal', function () {
      const u = new URL(location.href); if (u.searchParams.has('edit')) { u.searchParams.delete('edit'); history.replaceState(null, '', u); }
    });
  });
  // فتح نافذة "إضافة" بزر: <button data-open-modal="id">
  document.addEventListener('click', function (ev) {
    const b = ev.target.closest('[data-open-modal]');
    if (!b) return;
    const el = document.getElementById(b.dataset.openModal);
    if (el) bootstrap.Modal.getOrCreateInstance(el).show();
  });
  // منع الضغط المزدوج على أزرار الحفظ
  document.addEventListener('submit', function (ev) {
    if (ev.defaultPrevented) return;
    const b = ev.target.querySelector('button[type=submit][data-once],button.btn-gold:not([type=button])');
    if (b && !ev.target.hasAttribute('data-confirm')) setTimeout(function () { b.disabled = true; }, 0);
  });
})();
