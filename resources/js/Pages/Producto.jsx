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

                        {/* Información del producto */}
                        <div>
                            {/* Identificador de Precio Ofertado por Apertura */}
                            {producto.es_precio_ofertado && (
                                <div className="mb-4 inline-block rounded-full bg-orange-100 px-4 py-2 text-sm font-semibold text-orange-800">
                                    🎉 Precio Ofertado por Apertura
                                </div>
                            )}

                            <motion.h1
                                initial={{ opacity: 0 }}
                                animate={{ opacity: 1 }}
                                transition={{ delay: 0.2 }}
                                className="text-2xl font-bold text-zinc-800"
                            >
                                {producto.descripcion}
                            </motion.h1>

                            {producto.bodega_destacada && (
                                <div className="mt-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3">
                                    <p className="mb-2 text-sm font-semibold text-green-700">
                                        📦 Existencia:
                                    </p>
                                    <div className="inline-block rounded-md bg-white px-3 py-1 shadow-sm ring-1 ring-green-300">
                                        <strong>
                                            {producto.bodega_destacada.bodega}
                                        </strong>
                                    </div>
                                </div>
                            )}

                            <div className="mt-5 space-y-2 text-sm text-zinc-700">
                                {user &&
                                    producto.bodegas &&
                                    producto.bodegas.length > 0 && (
                                        <div className="mt-6 rounded-lg border border-zinc-200 bg-zinc-50 px-4 py-3">
                                            <p className="mb-2 text-sm font-semibold text-zinc-700">
                                                🏬 Ubicación en bodegas:
                                            </p>
                                            <ul className="flex flex-wrap gap-2 text-sm text-zinc-700">
                                                {producto.bodegas.map(
                                                    (b, i) => (
                                                        <li
                                                            key={i}
                                                            className="rounded-md bg-white px-3 py-1 shadow-sm ring-1 ring-zinc-300"
                                                        >
                                                            <strong>
                                                                {b.bodega}
                                                            </strong>
                                                            : {b.existencia}
                                                        </li>
                                                    ),
                                                )}
                                            </ul>
                                        </div>
                                    )}

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
