import { imagenAncho } from '@/lib/tienda';
import { useCallback, useState } from 'react';

const ANCHOS = [240, 480, 800, 1200];

/**
 * Foto del producto en tamaños livianos (srcset).
 * - Mientras carga: fondo con pulso suave; nunca se ve el texto alternativo.
 * - Sin foto (o si falla): logo de Tenisline.
 * - sizes: ancho real que ocupa en pantalla, para que el navegador pida la versión justa.
 * - prioridad: para la foto principal (carga inmediata, sin lazy).
 */
export default function ImagenProducto({ src, alt, className = '', ajuste = 'cover', sizes = '(min-width: 1024px) 25vw, 50vw', prioridad = false, ancho = 800 }) {
    const [estado, setEstado] = useState('cargando'); // cargando | listo | error

    // Si la imagen ya estaba en la caché del navegador, no hay que esperar al evento load
    const ref = useCallback((img) => {
        if (img?.complete && img.naturalWidth > 0) setEstado('listo');
    }, []);

    if (!src || estado === 'error') {
        return (
            <div className={`flex items-center justify-center bg-neutral-100 ${className}`} role="img" aria-label={alt}>
                <img src="/images/logo.webp" alt="" className="w-2/5 max-w-[220px] opacity-20 mix-blend-multiply" loading="lazy" decoding="async" />
            </div>
        );
    }

    const delS3 = imagenAncho(src, 240) !== src;

    return (
        <div className={`relative overflow-hidden bg-neutral-100 ${className}`}>
            {estado === 'cargando' && <div aria-hidden="true" className="absolute inset-0 animate-pulse bg-neutral-200" />}
            <img
                ref={ref}
                src={imagenAncho(src, ancho)}
                srcSet={delS3 ? ANCHOS.map((w) => `${imagenAncho(src, w)} ${w}w`).join(', ') : undefined}
                sizes={delS3 ? sizes : undefined}
                alt={alt}
                width={800}
                height={1000}
                loading={prioridad ? 'eager' : 'lazy'}
                fetchPriority={prioridad ? 'high' : 'auto'}
                decoding="async"
                onLoad={() => setEstado('listo')}
                onError={() => setEstado('error')}
                className={`h-full w-full transition-opacity duration-500 ${ajuste === 'contain' ? 'object-contain' : 'object-cover'} ${estado === 'listo' ? 'opacity-100' : 'opacity-0'}`}
            />
        </div>
    );
}
