import Layout from '@/Layouts/Layout';
import { Head, Link, useForm, usePage } from '@inertiajs/react';

export default function Producto({ producto }) {
    const user = usePage().props.auth.user;
    const { data, post } = useForm({
        producto_id: producto.id,
    });

    function submit(e) {
        e.preventDefault();
        post('/agregar-carrito', data);
    }

    return (
        <Layout>
            <Head>
                <title>
                    {producto.codigo +
                        ', ' +
                        producto.descripcion +
                        ', ' +
                        producto.marca +
                        ', ' +
                        producto.talla +
                        ', ' +
                        producto.genero}
                </title>
                <meta
                    name={producto.slug}
                    content="Producto - Tienda en línea"
                />
                <link
                    rel="icon"
                    type="image/svg+xml"
                    href="/images/icono.png"
                />
            </Head>

            <section className="bg-white py-8 antialiased dark:bg-zinc-900 md:py-16">
                <div className="mx-auto max-w-screen-xl px-4 2xl:px-0">
                    <div className="lg:grid lg:grid-cols-2 lg:gap-8 xl:gap-16">
                        <div className="mx-auto max-w-md shrink-0 lg:max-w-lg">
                            <img
                                className="w-full dark:hidden"
                                src={producto.imagen}
                                alt=""
                            />
                        </div>

                        <div className="mt-6 sm:mt-8 lg:mt-0">
                            <h1 className="text-xl font-semibold text-zinc-900 dark:text-white sm:text-2xl">
                                <div className="mt-4">
                                    <div
                                        className={`flex items-center gap-2 rounded-xl px-4 py-2 text-sm font-semibold shadow-sm ${
                                            producto.stock > 0
                                                ? 'bg-green-100 text-green-700 ring-1 ring-green-300'
                                                : 'bg-red-100 text-red-700 ring-1 ring-red-300'
                                        }`}
                                    >
                                        <svg
                                            className="h-5 w-5"
                                            fill="none"
                                            stroke="currentColor"
                                            strokeWidth={2}
                                            viewBox="0 0 24 24"
                                        >
                                            {producto.stock > 0 ? (
                                                <path
                                                    strokeLinecap="round"
                                                    strokeLinejoin="round"
                                                    d="M5 13l4 4L19 7"
                                                />
                                            ) : (
                                                <path
                                                    strokeLinecap="round"
                                                    strokeLinejoin="round"
                                                    d="M6 18L18 6M6 6l12 12"
                                                />
                                            )}
                                        </svg>
                                        {producto.stock > 0
                                            ? `En existencia: ${producto.stock} unidades`
                                            : 'Sin stock disponible'}
                                    </div>
                                </div>
                            </h1>
                            <div className="mt-4 space-y-1 text-sm text-zinc-800 dark:text-white sm:text-base">
                                {[
                                    `CÓDIGO: ${producto.codigo}`,
                                    `MARCA: ${producto.marca}`,
                                    `TALLA: US ${producto.talla} ${producto.genero}`,
                                    `COLOR: ${producto.color}`,
                                    `NOMBRE: ${producto.descripcion}`,
                                ].map((linea, index) => (
                                    <div key={index}>
                                        {index === 0 ? (
                                            <span className="font-semibold">
                                                {linea}
                                            </span>
                                        ) : (
                                            linea
                                        )}
                                    </div>
                                ))}
                            </div>
                            <div className="mt-4 space-y-1 text-sm text-zinc-800 dark:text-white sm:text-base">
                                {[
                                    `PRECIO: Q.${producto.precio}`,
                                    `PRECIO OFERTA: `,
                                ].map((linea, index) => (
                                    <div key={index}>
                                        {index === 0 ? (
                                            <span className="font-semibold text-blue-600">
                                                {linea}
                                            </span>
                                        ) : (
                                            <span className="font-semibold text-green-600">
                                                {linea}
                                            </span>
                                        )}
                                    </div>
                                ))}
                            </div>

                            {user && producto.precio && (
                                <form
                                    onSubmit={submit}
                                    className="mt-6 sm:mt-8 sm:flex sm:items-center sm:gap-4"
                                >
                                    {/* <button
                                        type="submit"
                                        disabled={processing}
                                        className="btn mt-6 w-full rounded-lg bg-green-600 py-2 font-semibold text-white hover:bg-green-800"
                                    >
                                        Añadir al carrito
                                    </button> */}
                                </form>
                            )}
                            <div className="mt-4 flex gap-4">
                                {['ADIDAS', 'HOKA', 'NIKE', 'REEBOK'].map(
                                    (marca, index) => (
                                        <Link
                                            key={index}
                                            href={route('catalogo', {
                                                search: marca.toLowerCase(), // establece como search directamente
                                            })}
                                            className="btn-zinc-content btn rounded-lg px-4 py-2 text-sm font-semibold"
                                        >
                                            {marca}
                                        </Link>
                                    ),
                                )}
                            </div>

                            <hr className="my-6 border-zinc-200 dark:border-zinc-800 md:my-8" />

                            <div
                                className="text-zinc-500 dark:text-zinc-400"
                                dangerouslySetInnerHTML={{
                                    __html: producto.detalle,
                                }}
                            />
                        </div>
                    </div>
                </div>
            </section>
        </Layout>
    );
}
