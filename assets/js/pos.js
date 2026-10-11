/* JOUD — منطق نقطة البيع (العرض فقط؛ التحقق النهائي والحسابات في الخادم) */
(function () {
  'use strict';
  const $ = s => document.querySelector(s), P = window.POS;
  const f = n => (+n).toLocaleString('en', { maximumFractionDigits: 2 });
  const esc = s => String(s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  let cart = [], cat = 0, tok = crypto.randomUUID(), timer, prods = {};

  async function load() {
    $('#grid').innerHTML = '<div class="text-muted p-3">جارٍ التحميل…</div>';
    try {
      const r = await fetch(P.api + '?q=' + encodeURIComponent($('#q').value) + '&cat=' + cat, { headers: { Accept: 'application/json' } });
      const d = await r.json(); if (!r.ok) throw new Error(d.error || 'خطأ');
      d.products.forEach(p => prods[p.id] = p);
      $('#grid').innerHTML = d.products.map(p => `<button type="button" class="pcard ${+p.stock <= 0 ? 'out' : ''} ${+p.stock <= +p.min_stock ? 'low' : ''}" data-id="${p.id}"><span class="nm">${esc(p.name)}</span><span class="meta">${esc(p.sku)} · <b>${+p.stock}</b> ${esc(p.unit)}</span><span class="pr">${f(p.base_price)} ${esc(P.cur)}</span></button>`).join('')
        || '<div class="empty-state w-100" style="grid-column:1/-1"><i class="bi bi-search"></i><h5>لا توجد منتجات</h5><p>جرّب كلمة بحث أخرى أو تصنيفًا مختلفًا.</p></div>';
    } catch (e) { $('#grid').innerHTML = '<div class="alert alert-danger" style="grid-column:1/-1">تعذر تحميل المنتجات: ' + esc(e.message) + '</div>'; }
  }
  function add(id) {
    const p = prods[id]; if (!p || +p.stock <= 0) return;
    const i = cart.find(x => x.id == p.id);
    if (i) { if (i.qty + 1 <= +p.stock) i.qty++; else return toast('لا توجد كمية أكبر في المخزون'); }
    else cart.push({ id: +p.id, name: p.name, qty: 1, price: +p.base_price, base: +p.base_price, min: +p.min_price, stock: +p.stock, unit: p.unit });
    draw();
  }
  function toast(m) { const e = $('#err'); e.textContent = m; e.classList.remove('d-none'); }
  const sums = () => { let t = 0, b = 0; cart.forEach(c => { t += c.qty * c.price; b += c.qty * Math.max(c.base, c.price); }); return { t: Math.round(t * 100) / 100, b }; };

  function draw() {
    const box = $('#cart');
    box.innerHTML = cart.length ? cart.map((c, i) => `<div class="cart-line"><div><div class="nm">${esc(c.name)}</div><small class="text-muted">المتاح ${c.stock} ${esc(c.unit)} · الأدنى ${f(c.min)}</small></div><button class="btn-act btn danger" data-del="${i}" aria-label="حذف"><i class="bi bi-trash3"></i></button>
      <div class="ctl"><button class="qty-btn" data-q="${i}" data-d="-1">−</button><input type="number" step="any" min="0.001" max="${c.stock}" class="form-control form-control-sm" value="${c.qty}" data-qi="${i}" aria-label="الكمية"><button class="qty-btn" data-q="${i}" data-d="1">+</button>
      <span class="text-muted">×</span><input type="number" step="0.01" min="0" class="form-control form-control-sm ${c.price + 0.004 < c.min ? 'is-invalid' : ''}" value="${c.price}" data-pi="${i}" aria-label="السعر"><b class="ms-auto amt">${f(c.qty * c.price)}</b></div></div>`).join('')
      : '<div class="empty-state py-4"><i class="bi bi-cart"></i><p class="mb-0">اختر منتجات من القائمة لإضافتها.</p></div>';
    totals();
  }
  function totals() {
    const { t, b } = sums(), mode = document.querySelector('input[name=pmode]:checked').value;
    let paid = mode === 'full' ? t : mode === 'credit' ? 0 : Math.min(+$('#paid').value || 0, t);
    $('#sub').textContent = f(b); $('#disc').textContent = f(b - t); $('#total').textContent = f(t);
    $('#paidShow').textContent = f(paid); $('#rem').textContent = f(Math.max(0, t - paid));
    $('#cnt').textContent = cart.length; $('#fabN').textContent = cart.length; $('#fabT').textContent = f(t);
    $('#paidBox').classList.toggle('d-none', mode !== 'part'); $('#methodBox').classList.toggle('d-none', mode === 'credit');
    const below = cart.some(c => c.price + 0.004 < c.min); $('#whyBox').classList.toggle('d-none', !(below && P.canOvr));
  }
  async function submit() {
    const e = $('#err'); e.classList.add('d-none');
    if (!cart.length) return toast('الفاتورة فارغة');
    const mode = document.querySelector('input[name=pmode]:checked').value, { t } = sums();
    if (cart.some(c => !(c.qty > 0) || c.price < 0)) return toast('كمية أو سعر غير صالح');
    if (cart.some(c => c.price + 0.004 < c.min) && !P.canOvr) return toast('يوجد سعر أقل من الحد الأدنى ولا تملك صلاحية التجاوز');
    let paid = mode === 'full' ? t : mode === 'credit' ? 0 : +$('#paid').value;
    if (mode === 'part') { if (!(paid > 0)) return toast('أدخل المبلغ المدفوع'); if (paid >= t) return toast('الدفع الجزئي يجب أن يكون أقل من الإجمالي'); }
    if (paid < t && !$('#cust').value) return toast('اختر العميل للبيع الآجل أو الدفع الجزئي');
    $('#pay').disabled = true; $('#pay').innerHTML = '<span class="spinner-border spinner-border-sm"></span> جارٍ الحفظ…';
    try {
      const r = await fetch(P.api, { method: 'POST', headers: { 'X-CSRF': P.csrf, 'Content-Type': 'application/json', Accept: 'application/json' }, body: JSON.stringify({ items: cart.map(c => ({ id: c.id, qty: c.qty, price: c.price })), method_id: +$('#pm').value, customer_id: +$('#cust').value || null, paid: String(paid), reason: $('#why').value, token: tok }) });
      const d = await r.json();
      if (d.id) { location.href = d.url; return; }
      toast(d.error || 'خطأ غير معروف');
    } catch (x) { toast('تعذر الاتصال بالخادم'); }
    $('#pay').disabled = false; $('#pay').innerHTML = '<i class="bi bi-check2-circle"></i> إتمام البيع';
  }
  $('#q').addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(load, 250); });
  document.querySelectorAll('.chip').forEach(b => b.onclick = () => { document.querySelectorAll('.chip').forEach(x => x.classList.remove('on')); b.classList.add('on'); cat = +b.dataset.c; load(); });
  $('#grid').addEventListener('click', ev => { const b = ev.target.closest('.pcard'); if (b) { $('#err').classList.add('d-none'); add(b.dataset.id); } });
  $('#cart').addEventListener('click', ev => {
    const del = ev.target.closest('[data-del]'); if (del) { cart.splice(+del.dataset.del, 1); draw(); return; }
    const q = ev.target.closest('[data-q]'); if (q) { const c = cart[+q.dataset.q]; c.qty = Math.max(0.001, Math.min(c.stock, +(c.qty + +q.dataset.d).toFixed(3))); draw(); }
  });
  $('#cart').addEventListener('change', ev => {
    const t = ev.target;
    if (t.dataset.qi !== undefined) { const c = cart[+t.dataset.qi]; c.qty = Math.min(c.stock, Math.max(0.001, +t.value || 1)); draw(); }
    if (t.dataset.pi !== undefined) { cart[+t.dataset.pi].price = Math.max(0, +t.value || 0); draw(); }
  });
  document.querySelectorAll('input[name=pmode]').forEach(r => r.onchange = totals);
  $('#paid').addEventListener('input', totals);
  $('#pay').onclick = submit;
  $('#clear').onclick = () => { cart = []; draw(); };
  $('#fab').onclick = () => $('#cartPanel').scrollIntoView({ behavior: 'smooth' });
  load(); draw();
})();
