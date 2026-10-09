import { cn } from '@/lib/utils';

/**
 * Logo de la marca dentro de una caja fija, para que logos anchos (Lacoste)
 * y de icono (Nike) tengan el mismo peso visual. Se muestra en negro y, si
 * está dentro de un elemento con clase "group", toma su color al pasar el mouse.
 */
export default function LogoMarca({ marca, logo, className = '', carga = 'lazy' }) {
    return (
        <span className={cn('flex h-10 w-28 items-center justify-center', className)}>
            {logo ? (
                <img
                    src={logo}
                    alt={marca}
                    loading={carga}
                    decoding="async"
                    className="max-h-full max-w-full object-contain brightness-0 transition-[filter] duration-300 group-hover:brightness-100"
                />
            ) : (
                <span className="truncate font-display text-lg uppercase tracking-tight">{marca}</span>
            )}
        </span>
    );
}
