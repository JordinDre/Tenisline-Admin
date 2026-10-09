import { cn } from '@/lib/utils';

const ARCHIVOS = { simple: 'logo', completo: 'logo-completo', eslogan: 'eslogan' };

/** Logo original de Tenisline: versión negra en modo claro y blanca en modo oscuro (se cambia solo con CSS). */
export default function Logo({ variante = 'simple', alt = 'Tenisline', className = '', ...props }) {
    const base = `/images/${ARCHIVOS[variante]}`;
    const ancho = variante === 'eslogan' ? 830 : 977;
    const alto = { simple: 506, completo: 558, eslogan: 269 }[variante];

    return (
        <>
            <img src={`${base}-negro.webp`} alt={alt} width={ancho} height={alto} className={cn('dark:hidden', className)} {...props} />
            <img src={`${base}-blanco.webp`} alt={alt} width={ancho} height={alto} className={cn('hidden dark:block', className)} {...props} />
        </>
    );
}
