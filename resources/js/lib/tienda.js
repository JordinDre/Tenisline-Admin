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
