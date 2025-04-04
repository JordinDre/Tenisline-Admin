import Layout from '@/Layouts/Layout';
import { Head, Link, useForm, usePage } from '@inertiajs/react';

export default function Producto({ producto }) {
    const user = usePage().props.auth.user;
    const { data, post, processing } = useForm({
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
                        producto.genero +
                        ', ' +
                        producto.talla}
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

            <section className="bg-white py-8 antialiased dark:bg-gray-900 md:py-16">
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
                            <h1 className="text-xl font-semibold text-gray-900 dark:text-white sm:text-2xl">
                            {producto.codigo +
                                ', ' +
                                producto.descripcion +
                                ', ' +
                                producto.marca +
                                ', ' +
                                producto.genero +
                                ', ' +
                                producto.talla}
                            </h1>
                            {/* <div className="mt-4 sm:flex sm:items-center sm:gap-4">
                                <p className="text-2xl font-extrabold text-gray-900 dark:text-white sm:text-3xl">
                                    {producto.precio
                                        ? 'Q' + producto.precio
                                        : ''}
                                </p>
                            </div> */}

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
                            <Link
                                href={route('catalogo')}
                                disabled={processing}
                                className="btn-neutral-content btn mt-2 w-full rounded-lg py-2 font-semibold"
                            >
                                Buscar Más Productos
                            </Link>

                            <hr className="my-6 border-gray-200 dark:border-gray-800 md:my-8" />

                            <div
                                className="text-gray-500 dark:text-gray-400"
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
