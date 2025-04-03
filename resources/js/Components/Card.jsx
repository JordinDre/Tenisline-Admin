import { Link } from '@inertiajs/react';

export default function Card({ producto }) {
    return (
        <div className="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div className="h-56 w-full">
                <Link href={`/producto/${producto.slug}`}>
                    <img
                        className="mx-auto h-full dark:hidden"
                        src={producto.imagen}
                        alt=""
                    />
                </Link>
            </div>
            <div className="pt-6">
                <Link
                    href={`/producto/${producto.slug}`}
                    className="text-lg font-semibold leading-tight text-gray-900 hover:underline dark:text-white"
                >
                    {producto.codigo +
                        ', ' +
                        producto.descripcion +
                        ', ' +
                        producto.marca +
                        ', ' +
                        producto.genero +
                        ', ' +
                        producto.talla}
                </Link>

                {/* <div className="mt-4 flex items-center justify-between gap-4">
                    <p className="text-2xl font-extrabold leading-tight text-sky-700 dark:text-white">
                        {producto.precio ? 'Q' + producto.precio : ''}
                    </p>
                </div> */}
            </div>
        </div>
    );
}
