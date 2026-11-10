// Lets browsers offer installing ZutoMarket. Pages are never cached: every visit loads the live site,
// and only a failed page load (no connection) gets a small offline notice.
const OFFLINE_PAGE = `<!doctype html><html lang="id"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Sedang offline · ZutoMarket</title></head>
<body style="margin:0;min-height:100vh;display:grid;place-items:center;background:#070b1c;color:#e2e8f0;font-family:system-ui,sans-serif;text-align:center;padding:24px">
<div><h1 style="font-size:22px;margin:0 0 8px">Kamu sedang offline</h1><p style="color:#94a3b8;margin:0 0 20px">Periksa koneksi internet, lalu coba lagi.</p>
<button onclick="location.reload()" style="border:0;border-radius:12px;padding:12px 20px;background:#a9c3ff;color:#020617;font-weight:700">Coba lagi</button></div></body></html>`;

self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', (event) => event.waitUntil(self.clients.claim()));
self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET' || event.request.mode !== 'navigate') return;
    event.respondWith(fetch(event.request).catch(() => new Response(OFFLINE_PAGE, { headers: { 'Content-Type': 'text/html; charset=utf-8' } })));
});
