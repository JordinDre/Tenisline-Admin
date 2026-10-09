import { Moon, Sun } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Button } from '@/Components/ui/button';

const aplicar = (oscuro) => {
    document.documentElement.classList.toggle('dark', oscuro);
    document.querySelector('meta[name="theme-color"]')?.setAttribute('content', oscuro ? '#0a0a0a' : '#ffffff');
};

/** Cambia entre modo claro y oscuro y recuerda la elección en el navegador. */
export default function BotonTema({ className = '' }) {
    const [oscuro, setOscuro] = useState(false);

    useEffect(() => {
        setOscuro(document.documentElement.classList.contains('dark'));
        aplicar(document.documentElement.classList.contains('dark'));
        return () => document.documentElement.classList.remove('dark'); // las páginas internas no usan modo oscuro
    }, []);

    const alternar = () => {
        const nuevo = !oscuro;
        setOscuro(nuevo);
        aplicar(nuevo);
        try {
            localStorage.setItem('tema', nuevo ? 'oscuro' : 'claro');
        } catch {}
    };

    return (
        <Button variant="ghost" size="icon" className={`h-10 w-10 rounded-full ${className}`} onClick={alternar}
            aria-label={oscuro ? 'Activar modo claro' : 'Activar modo oscuro'}>
            {oscuro ? <Sun className="h-[22px] w-[22px]" strokeWidth={1.9} /> : <Moon className="h-[22px] w-[22px]" strokeWidth={1.9} />}
        </Button>
    );
}
