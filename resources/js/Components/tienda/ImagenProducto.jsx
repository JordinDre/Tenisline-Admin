import { imagenAncho } from '@/lib/tienda';
import { useState } from 'react';

const ANCHOS = [240, 480, 800, 1200];

/**
 * Foto del producto en tamaños livianos (srcset). Si no tiene (o falla al cargar) muestra el logo de Tenisline.
 * - sizes: ancho real que ocupa en pantalla, para que el navegador pida la versión justa.
 * - prioridad: para la foto principal (carga inmediata, sin lazy).
 */
export default function ImagenProducto({ src, alt, className = '', ajuste = 'cover', sizes = '(min-width: 1024px) 25vw, 50vw', prioridad = false, ancho = 800 }) {
    const [error, setError] = useState(false);

    if (!src || error) {
        return (
            <div className={`flex items-center justify-center bg-neutral-100 ${className}`} role="img" aria-label={alt}>
                <img src="/images/logo.webp" alt="" className="w-2/5 max-w-[220px] opacity-20 mix-blend-multiply" loading="lazy" decoding="async" />
            </div>
        );
    }

    const delS3 = imagenAncho(src, 240) !== src;

    return (
        <img
            src={imagenAncho(src, ancho)}
            srcSet={delS3 ? ANCHOS.map((w) => `${imagenAncho(src, w)} ${w}w`).join(', ') : undefined}
            sizes={delS3 ? sizes : undefined}
            alt={alt}
            width={800}
            height={1000}
            loading={prioridad ? 'eager' : 'lazy'}
            fetchPriority={prioridad ? 'high' : 'auto'}
            decoding="async"
            onError={() => setError(true)}
            className={`${ajuste === 'contain' ? 'object-contain' : 'object-cover'} ${className}`}
        />
    );
}
