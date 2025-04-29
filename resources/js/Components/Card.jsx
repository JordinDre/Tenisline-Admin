import { Link } from '@inertiajs/react';

export default function Card({ producto }) {
    return (
        <div className="rounded-lg border border-zinc-200 bg-white p-6 uppercase shadow-sm dark:border-zinc-700 dark:bg-zinc-800">
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
                    className="text-lg font-semibold leading-tight text-zinc-900 hover:underline dark:text-white"
                >
                    <div>
                        <span className="font-semibold">CÓDIGO:</span>{' '}
                        {producto.codigo}
                    </div>
                    <div>MARCA: {producto.marca}</div>
                    <div>
                        TALLA: US {producto.talla} {producto.genero}
                    </div>
                    <div>COLOR: {producto.color}</div>
                    <div>NOMBRE: {producto.descripcion}</div>
                </Link>

                {/* <div className="mt-4 flex items-center justify-between gap-4">
                    <p className="text-2xl font-extrabold leading-tight text-zinc-700 dark:text-white">
                        {producto.precio ? 'Q' + producto.precio : ''}
                    </p>
                </div> */}
            </div>
        </div>
    );
}
