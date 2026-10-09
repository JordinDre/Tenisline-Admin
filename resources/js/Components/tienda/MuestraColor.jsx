import { fondoColor } from '@/lib/tienda';
import { cn } from '@/lib/utils';

/** Círculo con el color del tenis (o tramos si tiene varios). Si no se reconoce el color, no muestra nada. */
export default function MuestraColor({ color, className }) {
    const fondo = fondoColor(color);
    if (!fondo) return null;
    return <span aria-hidden="true" className={cn('inline-block h-3.5 w-3.5 shrink-0 rounded-full border border-neutral-300', className)} style={{ background: fondo }} />;
}
