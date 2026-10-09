// Utilidades compartidas del sitio público de Tenisline.

const formato = new Intl.NumberFormat('es-GT', {
    style: 'currency',
    currency: 'GTQ',
    minimumFractionDigits: 2,
});

// Foto de S3 -> versión liviana generada por /img/{ancho}/... (si no es de S3, se deja igual)
export function imagenAncho(src, ancho) {
    if (!src) return src;
    try {
        const { hostname, pathname } = new URL(src);
        if (!hostname.includes('amazonaws.com')) return src;
        return `/img/${ancho}${pathname}`;
    } catch {
        return src;
    }
}

// Color del tenis (en español) -> muestras para el círculo de color. Si trae varios (BLANCO/NEGRO) se parte en tramos.
const COLORES = {
    blanco: '#ffffff', negro: '#161616', gris: '#9ca3af', plomo: '#9ca3af', azul: '#2563eb', marino: '#1e3a8a', celeste: '#7dd3fc',
    rojo: '#dc2626', rosa: '#f9a8d4', rosado: '#f9a8d4', morado: '#7c3aed', lila: '#c4b5fd', violeta: '#7c3aed', verde: '#16a34a',
    amarillo: '#facc15', naranja: '#fb923c', cafe: '#7c4a2d', marron: '#7c4a2d', beige: '#e7d8c0', hueso: '#f3eee3', crema: '#f5ecd6',
    dorado: '#d4af37', oro: '#d4af37', plateado: '#c0c0c0', plata: '#c0c0c0', corinto: '#7f1d1d', mostaza: '#d19a1c', coral: '#fb7185',
    turquesa: '#14b8a6', aqua: '#5eead4', salmon: '#fda4af', colorado: '#b45309', vino: '#7f1d1d', oliva: '#6b7a3a', menta: '#99f6c4',
};

export function coloresDe(nombre = '') {
    const limpio = String(nombre).toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    if (/multicolor|multi/.test(limpio)) return ['#ef4444', '#facc15', '#22c55e', '#3b82f6'];
    return [...new Set(limpio.split(/[\s/,\-+]+/).map((t) => COLORES[t]).filter(Boolean))].slice(0, 3);
}

/** Estilo CSS del círculo de color (relleno sólido o en tramos). */
export function fondoColor(nombre) {
    const c = coloresDe(nombre);
    if (!c.length) return null;
    if (c.length === 1) return c[0];
    const paso = 100 / c.length;
    return `linear-gradient(135deg, ${c.map((h, n) => `${h} ${n * paso}% ${(n + 1) * paso}%`).join(', ')})`;
}

export const quetzales = (valor) => formato.format(Number(valor || 0));

/** Precio a cobrar: el de oferta si existe, si no el normal. */
export const precioFinal = (p) => Number(p?.precio_oferta || p?.precio || 0);

export const descuento = (p) =>
    p?.precio_oferta && p?.precio
        ? Math.round((1 - p.precio_oferta / p.precio) * 100)
        : 0;

export const capitalizar = (texto = '') =>
    String(texto)
        .toLowerCase()
        .replace(/(^|\s|\/)\S/g, (c) => c.toUpperCase());

/** Enlace de WhatsApp con el pedido del carrito: cada producto con su talla, código, subtotal y enlace. */
export function enlaceWhatsApp(telefono, items, sucursal) {
    const origen = typeof window !== 'undefined' ? window.location.origin : '';
    const piezas = items.reduce((s, i) => s + i.cantidad, 0);

    const lineas = items.map((i, n) => {
        const nombre = `${i.marca ? `${capitalizar(i.marca)} ` : ''}${capitalizar(i.descripcion)}${i.color ? ` (${capitalizar(i.color)})` : ''}`;
        const unitario = precioFinal(i);
        return [
            `${n + 1}. *${nombre}*`,
            `   Talla ${i.talla} · Cód. ${i.codigo}`,
            `   ${i.cantidad} x ${quetzales(unitario)} = ${quetzales(unitario * i.cantidad)}`,
            i.slug ? `   ${origen}/producto/${i.slug}` : null,
        ].filter(Boolean).join('\n');
    });

    const total = items.reduce((s, i) => s + precioFinal(i) * i.cantidad, 0);
    const texto = [
        `¡Hola Tenisline ${sucursal}! 👋 Quiero hacer este pedido (${piezas} ${piezas === 1 ? 'par' : 'pares'}):`,
        '',
        lineas.join('\n\n'),
        '',
        `*Total aproximado: ${quetzales(total)}*`,
        '¿Me confirman disponibilidad y formas de pago?',
    ].join('\n');

    return `https://wa.me/${telefono}?text=${encodeURIComponent(texto)}`;
}
