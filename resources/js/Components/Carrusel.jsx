import { Carousel } from 'flowbite-react';

export default function Carrusel({ data, url }) {
    return (
        <div className="h-[200px] bg-zinc-700 sm:h-[250px] md:h-[300px] lg:h-[350px]">
            <Carousel>
                {data.imagen.map((img, index) => (
                    <img
                        key={index}
                        src={`${url + img}`}
                        alt={`Carrusel ${index + 1}`}
                        className="h-full w-full rounded-none object-cover" // Agregado rounded-none para quitar el borde redondeado
                    />
                ))}
            </Carousel>
        </div>
    );
}
