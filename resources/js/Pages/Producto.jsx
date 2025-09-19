import Layout from '@/Layouts/Layout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { motion } from 'framer-motion';
import { useState } from 'react';
import Zoom from 'react-medium-image-zoom';
import 'react-medium-image-zoom/dist/styles.css';

export default function Producto({ producto, marcas }) {
    const user = usePage().props.auth.user;
    const [isModalOpen, setIsModalOpen] = useState(false);

    // Función para volver al catálogo preservando los filtros
    const handleVolverCatalogo = () => {
        const filtrosGuardados = sessionStorage.getItem('catalogo_filtros');
        if (filtrosGuardados) {
            const filtros = JSON.parse(filtrosGuardados);
            // Construir la URL con los parámetros de filtros
            const params = new URLSearchParams();
            if (filtros.search) params.append('search', filtros.search);
            if (filtros.bodega) params.append('bodega', filtros.bodega);
            if (filtros.marca) params.append('marca', filtros.marca);
            if (filtros.genero) params.append('genero', filtros.genero);
            if (filtros.tallas && filtros.tallas.length > 0) {
                filtros.tallas.forEach((talla) =>
                    params.append('tallas[]', talla),
                );
            }

            const queryString = params.toString();
            const url = queryString
                ? `${route('catalogo')}?${queryString}`
                : route('catalogo');
            router.visit(url);
        } else {
            router.visit(route('catalogo'));
        }
    };

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
                        {/* Imagen con click para abrir modal */}
                        <div className="space-y-4">
                            <div
                                className="cursor-zoom-in rounded-xl bg-zinc-50 p-4 shadow-md"
                                onClick={() => setIsModalOpen(true)}
                            >
                                <img
                                    className="max-h-[400px] w-full object-contain"
                                    src={producto.imagen}
                                    alt={producto.descripcion}
                                />
                                <p className="mt-2 text-center text-xs text-zinc-500">
                                    Haz clic para ampliar
                                </p>
                            </div>

                            {/* Precio debajo de la imagen */}
                            <div className="rounded-lg border border-green-200 bg-green-50 p-4 text-center">
                                <div className="text-3xl font-extrabold text-green-600">
                                    Q{producto.precio}
                                </div>
                                {producto.es_precio_ofertado && (
                                    <div className="mt-1 text-sm text-orange-600">
                                        🎉 Precio Ofertado por Apertura
                                    </div>
                                )}
                            </div>

                            {/* Existencia destacada debajo del precio */}
                            {producto.bodega_destacada && (
                                <div className="rounded-lg border border-blue-200 bg-blue-50 p-4">
                                    <p className="mb-2 text-sm font-semibold text-blue-700">
                                        📦 Existencia:
                                    </p>
                                    <div className="inline-block rounded-md bg-white px-3 py-1 shadow-sm ring-1 ring-blue-300">
                                        <strong className="text-blue-800">
                                            {producto.bodega_destacada.bodega}
                                        </strong>
                                    </div>
                                </div>
                            )}

                            {/* Bodegas disponibles debajo del precio */}
                            {user &&
                                producto.bodegas &&
                                producto.bodegas.length > 0 && (
                                    <div className="rounded-lg border border-zinc-200 bg-zinc-50 p-4">
                                        <p className="mb-3 text-sm font-semibold text-zinc-700">
                                            🏬 Disponible en:
                                        </p>
                                        <div className="space-y-2">
                                            {producto.bodegas.map(
                                                (bodega, index) => (
                                                    <div
                                                        key={index}
                                                        className="flex justify-between rounded-md bg-white px-3 py-2 shadow-sm ring-1 ring-zinc-300"
                                                    >
                                                        <span className="font-medium text-zinc-800">
                                                            {bodega.bodega}
                                                        </span>
                                                        <span className="text-sm text-zinc-600">
                                                            {bodega.existencia}{' '}
                                                            unidades
                                                        </span>
                                                    </div>
                                                ),
                                            )}
                                        </div>
                                    </div>
                                )}
                        </div>

                        {/* Información del producto */}
                        <div>
                            <motion.h1
                                initial={{ opacity: 0 }}
                                animate={{ opacity: 1 }}
                                transition={{ delay: 0.2 }}
                                className="text-2xl font-bold text-zinc-800"
                            >
                                {producto.descripcion}
                            </motion.h1>

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
                                    <button
                                        onClick={handleVolverCatalogo}
                                        className="inline-block text-sm font-medium text-blue-600 hover:underline"
                                    >
                                        ← Volver al catálogo
                                    </button>
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

            {/* Modal con efecto zoom */}
            {isModalOpen && (
                <div
                    className="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-80"
                    onClick={() => setIsModalOpen(false)}
                >
                    <Zoom>
                        <img
                            src={producto.imagen}
                            alt={producto.descripcion}
                            className="max-h-[90vh] max-w-[90vw] rounded-lg object-contain"
                            onClick={(e) => e.stopPropagation()} // evita cerrar modal al hacer zoom
                        />
                    </Zoom>
                </div>
            )}
        </Layout>
    );
}
