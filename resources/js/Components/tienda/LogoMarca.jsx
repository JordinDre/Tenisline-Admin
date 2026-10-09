import { cn } from '@/lib/utils';

/**
 * Logo de la marca en negro dentro de una caja fija, para que logos anchos
 * (Lacoste) y de icono (Nike) se vean del mismo peso visual.
 */
export default function LogoMarca({ marca, logo, className = '' }) {
    return (
        <span className={cn('flex h-10 w-28 items-center justify-center sm:h-12 sm:w-32', className)}>
            {logo ? (
                <img src={logo} alt={marca} loading="lazy" decoding="async" className="max-h-full max-w-full object-contain brightness-0" />
            ) : (
                <span className="font-display text-xl font-extrabold uppercase tracking-tight">{marca}</span>
            )}
        </span>
    );
}
