import { Link } from '@inertiajs/react';
import { HiArrowRight } from 'react-icons/hi'; // Importando el ícono de flecha

export default function Seccion({ data, url }) {
    return (
        <section className="bg-gradient-to-r from-zinc-200 to-zinc-600 px-4 py-12 antialiased dark:bg-gradient-to-r dark:from-zinc-800 dark:to-zinc-900 md:py-16">
            <div className="mx-auto grid max-w-screen-xl rounded-lg bg-white shadow-xl dark:bg-zinc-800 lg:grid-cols-12 lg:gap-8 xl:gap-16">
                {/* Imagen */}
                <div className="mb-6 flex justify-center lg:col-span-5 lg:mb-0">
                    <Link href={route('catalogo')}>
                        <div className="transform overflow-hidden rounded-lg shadow-lg transition-all duration-500 ease-in-out hover:scale-105 hover:shadow-2xl">
                            <img
                                className="h-80 w-full object-cover md:h-full"
                                src={`${url + data.imagen}`}
                                alt="Sección"
                            />
                        </div>
                    </Link>
                </div>

                {/* Contenido */}
                <div className="animate__animated animate__fadeIn animate__delay-1s animate__fadeInUp flex flex-col justify-center pr-0 text-center md:pr-12 lg:col-span-7 lg:text-left">
                    <h1 className="mb-4 text-lg font-semibold leading-tight text-zinc-900 dark:text-white md:text-2xl">
                        {data.contenido}
                    </h1>
                    <Link
                        href={route('catalogo')}
                        className="btn inline-flex items-center justify-center rounded-lg bg-amber-600 px-6 py-3 text-base font-medium text-white shadow-md transition-all duration-300 hover:bg-amber-700"
                    >
                        <span className="mr-2">Catálogo</span>
                        <HiArrowRight className="text-xl" /> {/* Flecha */}
                    </Link>
                </div>
            </div>
        </section>
    );
}
