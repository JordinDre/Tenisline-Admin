import { cn } from '@/lib/utils';
import { useCallback, useState } from 'react';

/**
 * Logo de la marca dentro de una caja fija, para que logos anchos (Lacoste)
 * y de icono (Nike) tengan el mismo peso visual. Se muestra en negro y, si
 * está dentro de un elemento con clase "group", toma su color al pasar el mouse.
 * Mientras carga se ve un fondo con pulso suave (nunca el texto alternativo);
 * si falla, se muestra el nombre de la marca.
 */
export default function LogoMarca({ marca, logo, className = '', carga = 'lazy' }) {
    const [estado, setEstado] = useState('cargando'); // cargando | listo | error

    // Si la imagen ya estaba en la caché del navegador, no hay que esperar al evento load
    const ref = useCallback((img) => {
        if (img?.complete && img.naturalWidth > 0) setEstado('listo');
    }, []);

    const nombre = <span className="whitespace-nowrap font-display text-lg uppercase tracking-tight">{marca}</span>;

    return (
        <span className={cn('relative flex h-10 w-28 items-center justify-center', className)}>
            {!logo || estado === 'error' ? (
                nombre
            ) : (
                <>
                    {estado === 'cargando' && <span aria-hidden="true" className="absolute inset-x-3 inset-y-2 animate-pulse rounded-lg bg-neutral-200" />}
                    <img
                        ref={ref}
                        src={logo}
                        alt={marca}
                        loading={carga}
                        decoding="async"
                        onLoad={() => setEstado('listo')}
                        onError={() => setEstado('error')}
                        className={cn(
                            'max-h-full max-w-full object-contain brightness-0 transition-[opacity,filter] duration-500 group-hover:brightness-100',
                            estado === 'listo' ? 'opacity-100' : 'opacity-0',
                        )}
                    />
                </>
            )}
        </span>
    );
}
