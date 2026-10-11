/* JOUD — رسوم لوحة التحكم (البيانات تأتي من قاعدة البيانات عبر window.DASH) */
(function () {
  'use strict';
  if (!window.Chart || !window.DASH) return;
  const D = window.DASH, fm = n => (+n).toLocaleString('en', { maximumFractionDigits: 0 });
  Chart.defaults.font.family = 'Cairo, Tahoma, sans-serif';
  const gold = '#b8893a', ink = '#0f1a2e';
  const c1 = document.getElementById('salesChart');
  if (c1) new Chart(c1, {
    data: { labels: D.s.labels, datasets: [
      { type: 'bar', label: 'المبيعات (' + D.cur + ')', data: D.s.totals, backgroundColor: gold, borderRadius: 6, maxBarThickness: 38, yAxisID: 'y' },
      { type: 'line', label: 'عدد الفواتير', data: D.s.counts, borderColor: ink, backgroundColor: ink, tension: .3, pointRadius: 3, yAxisID: 'y1' }] },
    options: { responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
      plugins: { legend: { position: 'bottom', rtl: true, labels: { usePointStyle: true } }, tooltip: { rtl: true, textDirection: 'rtl', callbacks: { label: c => c.dataset.label + ': ' + fm(c.parsed.y) } } },
      scales: { x: { reverse: true, grid: { display: false } }, y: { position: 'right', beginAtZero: true, ticks: { callback: fm } }, y1: { position: 'left', beginAtZero: true, grid: { drawOnChartArea: false }, ticks: { precision: 0 } } } }
  });
  const c2 = document.getElementById('splitChart');
  if (c2 && D.split.length) new Chart(c2, { type: 'doughnut', data: { labels: D.split.map(x => x.label), datasets: [{ data: D.split.map(x => x.value), backgroundColor: ['#0f1a2e', '#b8893a', '#2b6cb0', '#1f8a5b', '#c2413b', '#7a8499'], borderWidth: 2 }] },
    options: { responsive: true, maintainAspectRatio: false, cutout: '62%', plugins: { legend: { position: 'bottom', rtl: true, labels: { usePointStyle: true } }, tooltip: { rtl: true, textDirection: 'rtl', callbacks: { label: c => c.label + ': ' + fm(c.parsed) + ' ' + D.cur } } } } });
})();
