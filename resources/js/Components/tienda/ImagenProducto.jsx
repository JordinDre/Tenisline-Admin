import { useState } from 'react';

/** Foto del producto; si no tiene (o falla al cargar) muestra el logo de Tenisline. */
export default function ImagenProducto({ src, alt, className = '', ajuste = 'cover' }) {
    const [error, setError] = useState(false);

    if (!src || error) {
        return (
            <div
                className={`flex items-center justify-center bg-neutral-100 ${className}`}
                aria-label={alt}
            >
                <img
                    src="/images/logo.png"
                    alt="Tenisline"
                    className="w-3/5 opacity-25 mix-blend-multiply"
                    loading="lazy"
                />
            </div>
        );
    }

    return (
        <img
            src={src}
            alt={alt}
            loading="lazy"
            onError={() => setError(true)}
            className={`${ajuste === 'contain' ? 'object-contain' : 'object-cover'} ${className}`}
        />
    );
}
