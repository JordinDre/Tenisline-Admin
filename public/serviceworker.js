// Service worker mínimo para que la PWA sea instalable.
// No guarda nada en caché: así nunca sirve assets viejos después de un deploy.

self.addEventListener("install", () => {
    self.skipWaiting();
});

// Borra cachés de versiones anteriores del service worker
self.addEventListener("activate", event => {
    event.waitUntil(
        caches.keys()
            .then(cacheNames => Promise.all(
                cacheNames
                    .filter(cacheName => cacheName.startsWith("pwa-"))
                    .map(cacheName => caches.delete(cacheName))
            ))
            .then(() => self.clients.claim())
    );
});

// Sin evento "fetch": el navegador maneja todas las peticiones directo,
// incluidos los envíos de Livewire y los formularios.
