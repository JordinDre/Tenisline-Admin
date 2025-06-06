import Layout from '@/Layouts/Layout';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { motion } from 'framer-motion';

export default function Producto({ producto, marcas }) {
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

            <section className="bg-white py-10 md:py-16">
                <div className="mx-auto max-w-screen-xl px-4 md:px-8">
                    <motion.div
                        initial={{ opacity: 0, y: 20 }}
                        animate={{ opacity: 1, y: 0 }}
                        transition={{ duration: 0.5 }}
                        className="grid gap-8 md:grid-cols-2 md:items-start"
                    >
                        <div className="rounded-xl bg-zinc-50 p-4 shadow-md">
                            <img
                                className="max-h-[400px] w-full object-contain"
                                src={producto.imagen}
                                alt={producto.descripcion}
                            />
                        </div>

                        <div>
                            <motion.h1
                                initial={{ opacity: 0 }}
                                animate={{ opacity: 1 }}
                                transition={{ delay: 0.2 }}
                                className="text-2xl font-bold text-zinc-800"
                            >
                                {producto.descripcion}
                            </motion.h1>

                            <div className="mt-3 flex items-center gap-2 text-sm">
                                <span
                                    className={`rounded-full px-3 py-1 text-sm font-medium ring-1 ${
                                        producto.stock > 0
                                            ? 'bg-green-100 text-green-700 ring-green-300'
                                            : 'bg-red-100 text-red-700 ring-red-300'
                                    }`}
                                >
                                    {producto.stock > 0
                                        ? 'En existencia'
                                        : 'Sin stock disponible'}
                                </span>
                            </div>

                            <div className="mt-5 space-y-2 text-sm text-zinc-700">
                                <p>
                                    <strong>Código:</strong> {producto.codigo}
                                </p>
                                <p>
                                    <strong>Marca:</strong> {producto.marca}
                                </p>
                                <p>
                                    <strong>Talla:</strong> US {producto.talla}{' '}
                                    ({producto.genero})
                                </p>
                            </div>

                            <div className="mt-4 text-xl font-bold text-green-600">
                                Q{producto.precio}
                            </div>

                            {user && producto.precio && (
                                <form
                                    onSubmit={submit}
                                    className="mt-6 sm:flex sm:items-center sm:gap-4"
                                >
                                    <button
                                        type="submit"
                                        className="w-full rounded-lg bg-green-600 px-4 py-2 font-semibold text-white transition hover:bg-green-700"
                                    >
                                        Añadir al carrito
                                    </button>
                                </form>
                            )}

                            <div className="mt-8">
                                <h2 className="mb-2 text-sm font-semibold text-zinc-500">
                                    Otras marcas:
                                </h2>
                                <div className="flex flex-wrap gap-2">
                                    {marcas.map((marca, index) => (
                                        <Link
                                            key={index}
                                            href={route('catalogo', { marca })}
                                            className="rounded-full bg-zinc-100 px-4 py-1.5 text-sm font-medium text-zinc-800 shadow hover:bg-zinc-200"
                                        >
                                            {marca}
                                        </Link>
                                    ))}
                                </div>
                                <div className="mt-4">
                                    <Link
                                        href={route('catalogo')}
                                        className="inline-block text-sm font-medium text-blue-600 hover:underline"
                                    >
                                        ← Volver al catálogo
                                    </Link>
                                </div>
                            </div>

                            <hr className="my-6 border-zinc-200" />

                            <div
                                className="prose-sm prose max-w-none text-zinc-700"
                                dangerouslySetInnerHTML={{
                                    __html: producto.detalle,
                                }}
                            />
                        </div>
                    </motion.div>
                </div>
            </section>
        </Layout>
    );
}
