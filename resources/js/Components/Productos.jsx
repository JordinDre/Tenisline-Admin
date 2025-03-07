import { useEffect, useState } from 'react';

export default function Productos({ data, url }) {
    const [isVisible, setIsVisible] = useState(false);

    const images = data.imagen.map((img) => `${url + img}`);
    const duplicatedImages = [...images, ...images]; // Duplicar las imágenes para un efecto infinito

    useEffect(() => {
        const timer = setTimeout(() => {
            setIsVisible(true); // Hacer visible las imágenes después de un tiempo
        }, 500); // Retraso de 300ms

        return () => clearTimeout(timer);
    }, []);

    return (
        <div className="relative w-full overflow-hidden">
            <div
                className={`animate-marquee flex w-[200%] space-x-4 transition-opacity duration-1000 ${isVisible ? 'opacity-100' : 'opacity-0'}`}
            >
                {duplicatedImages.map((src, index) => (
                    <div
                        key={index}
                        className="h-40 w-40 flex-shrink-0 md:h-48 md:w-48 lg:h-56 lg:w-56"
                    >
                        <img
                            src={src}
                            alt={`Producto ${index + 1}`}
                            loading="lazy"
                            className="h-full w-full rounded-lg object-cover"
                        />
                    </div>
                ))}
            </div>
        </div>
    );
}
