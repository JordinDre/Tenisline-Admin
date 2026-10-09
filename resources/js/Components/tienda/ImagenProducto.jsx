import Logo from '@/Components/tienda/Logo';
import { imagenAncho } from '@/lib/tienda';
import { cn } from '@/lib/utils';
import { useCallback, useState } from 'react';
import Zoom from 'react-medium-image-zoom';
import 'react-medium-image-zoom/dist/styles.css';

const ANCHOS = [240, 480, 800, 1200];

/**
 * Foto del producto, siempre completa dentro de su marco (sin recortes ni huecos):
 * la foto se ajusta al marco y el resto se rellena con la misma foto difuminada, así
 * las fotos horizontales, verticales o cuadradas que suben los asesores se ven bien.
 * - Mientras carga: fondo con pulso suave; nunca se ve el texto alternativo.
 * - Sin foto (o si falla): logo de Tenisline.
 * - sizes: ancho real que ocupa en pantalla, para que el navegador pida la versión justa.
 * - prioridad: para la foto principal (carga inmediata, sin lazy).
 * - zoom: la foto se puede ampliar al hacer clic.
 */
export default function ImagenProducto({ src, alt, className = '', sizes = '(min-width: 1024px) 25vw, 50vw', prioridad = false, ancho = 800, zoom = false }) {
    const [estado, setEstado] = useState('cargando'); // cargando | listo | error
    const listo = estado === 'listo';

    // Si la imagen ya estaba en la caché del navegador, no hay que esperar al evento load
    const ref = useCallback((img) => {
        if (img?.complete && img.naturalWidth > 0) setEstado('listo');
    }, []);

    if (!src || estado === 'error') {
        return (
            <div className={cn('flex items-center justify-center bg-neutral-100', className)} role="img" aria-label={alt}>
                <Logo alt="" className="w-2/5 max-w-[220px] opacity-20" loading="lazy" decoding="async" />
            </div>
        );
    }

    const delS3 = imagenAncho(src, 240) !== src;
    const comun = {
        src: imagenAncho(src, ancho),
        srcSet: delS3 ? ANCHOS.map((w) => `${imagenAncho(src, w)} ${w}w`).join(', ') : undefined,
        sizes: delS3 ? sizes : undefined,
        decoding: 'async',
    };

    const frente = (
        <img
            ref={ref}
            {...comun}
            alt={alt}
            width={800}
            height={1000}
            loading={prioridad ? 'eager' : 'lazy'}
            fetchPriority={prioridad ? 'high' : 'auto'}
            onLoad={() => setEstado('listo')}
            onError={() => setEstado('error')}
            className={cn('relative h-full w-full object-contain transition-opacity duration-700', listo ? 'opacity-100' : 'opacity-0')}
        />
    );

    return (
        <div className={cn('relative overflow-hidden bg-neutral-100', className)}>
            {!listo && <div aria-hidden="true" className="absolute inset-0 animate-pulse bg-neutral-200" />}
            {/* Relleno: la misma foto, ampliada y difuminada (el navegador no la vuelve a descargar) */}
            <img aria-hidden="true" alt="" {...comun} loading="lazy"
                className={cn('absolute inset-0 h-full w-full scale-125 object-cover blur-2xl transition-opacity duration-700', listo ? 'opacity-70' : 'opacity-0')} />
            {zoom ? <Zoom zoomImg={{ src: imagenAncho(src, 1200) }}>{frente}</Zoom> : frente}
        </div>
    );
}
