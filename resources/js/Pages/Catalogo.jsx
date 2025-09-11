import Layout from '@/Layouts/Layout';
import { Head, useForm } from '@inertiajs/react';
import { motion } from 'framer-motion';
import { useEffect, useState } from 'react';
import { FaSearch } from 'react-icons/fa';

export default function Catalogo({
    productos,
    search,
    bodega,
    bodegas,
    marca,
    tallas,
    genero,
    marcasDisponibles,
    generosDisponibles,
}) {
    const [mostrarFiltros, setMostrarFiltros] = useState(false);

    const { data, setData, get } = useForm({
        search: search || '',
        bodega: bodega || '',
        marca: marca || '',
        genero: genero || '',
        tallas: tallas || [],
    });

    useEffect(() => {
        const delayDebounce = setTimeout(() => {
            get(route('catalogo'), {
                preserveScroll: true,
                preserveState: true,
            });
        }, 300);

        return () => clearTimeout(delayDebounce);
    }, [data]);

    const handleCheckboxChange = (e) => {
        const value = e.target.value;
        if (data.tallas.includes(value)) {
            setData(
                'tallas',
                data.tallas.filter((t) => t !== value),
            );
        } else {
            setData('tallas', [...data.tallas, value]);
        }
    };

    const handleReset = () => {
        setData({
            search: '',
            marca: '',
            bodega: '',
            tallas: [],
            genero: '',
        });
    };

    const handleAplicarFiltros = () => {
        setMostrarFiltros(false); // cerrar modal
        get(route('catalogo'), {
            preserveScroll: true,
            preserveState: true,
        });
    };

    const tallasRango = Array.from({ length: 19 }, (_, i) =>
        (4 + i * 0.5).toFixed(1),
    );

    return (
        <Layout>
            <Head>
                <title>Catálogo</title>
                <meta
                    name="Catalogo"
                    content="Catálogo de productos - Tienda en línea"
                />
                <link
                    rel="icon"
                    type="image/svg+xml"
                    href="/images/icono.png"
                />
            </Head>
            <div className="container mx-auto px-4 py-6">
                <h2 className="mb-6 text-center text-3xl font-bold text-zinc-800">
                    Catálogo de Productos
                </h2>

                {/* Botón para móviles */}
                <button
                    onClick={() => setMostrarFiltros(true)}
                    className="mb-4 block w-full rounded bg-black px-4 py-2 text-white md:hidden"
                >
                    Mostrar filtros
                </button>

                {/* Panel móvil */}
                {mostrarFiltros && (
                    <div className="fixed inset-0 z-50 bg-black bg-opacity-50 md:hidden">
                        <div className="absolute left-0 top-0 h-full w-3/4 max-w-xs overflow-y-auto bg-white p-4 shadow-lg">
                            <div className="mb-4 flex items-center justify-between">
                                <h3 className="text-lg font-semibold">
                                    Filtros
                                </h3>
                                <button
                                    className="font-bold text-red-600"
                                    onClick={() => setMostrarFiltros(false)}
                                >
                                    ✕
                                </button>
                            </div>

                            {/* FORMULARIO MOVIL */}
                            <Filtros
                                data={data}
                                setData={setData}
                                handleCheckboxChange={handleCheckboxChange}
                                handleReset={handleReset}
                                bodegas={bodegas}
                                marcasDisponibles={marcasDisponibles}
                                generosDisponibles={generosDisponibles}
                                tallasRango={tallasRango}
                            />

                            {/* ✅ Botón aplicar */}
                            <button
                                onClick={handleAplicarFiltros}
                                className="mt-6 w-full rounded bg-green-600 px-4 py-2 text-white hover:bg-green-700"
                            >
                                Aplicar filtros
                            </button>
                        </div>
                    </div>
                )}

                <div className="flex flex-col gap-6 md:flex-row">
                    {/* FORMULARIO ESCRITORIO */}
                    <motion.form
                        initial={{ opacity: 0, x: -20 }}
                        animate={{ opacity: 1, x: 0 }}
                        transition={{ duration: 0.5 }}
                        className="hidden w-full space-y-6 md:block md:w-1/4"
                    >
                        <div className="space-y-5 border-l border-r border-black bg-gradient-to-b from-white to-zinc-50 px-6 py-4">
                            <h3 className="mb-4 text-lg font-semibold text-zinc-800">
                                Filtrar por
                            </h3>
                            <Filtros
                                data={data}
                                setData={setData}
                                handleCheckboxChange={handleCheckboxChange}
                                handleReset={handleReset}
                                bodegas={bodegas}
                                marcasDisponibles={marcasDisponibles}
                                generosDisponibles={generosDisponibles}
                                tallasRango={tallasRango}
                            />
                        </div>
                    </motion.form>

                    <div className="w-full md:w-3/4">
                        {productos.data.length > 0 ? (
                            <motion.div
                                initial={{ opacity: 0, y: 20 }}
                                animate={{ opacity: 1, y: 0 }}
                                transition={{ duration: 0.5 }}
                                className="grid grid-cols-1 gap-6 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-4"
                            >
                                {productos.data.map((producto) => (
                                    <motion.div
                                        key={producto.id}
                                        whileHover={{ scale: 1.03 }}
                                        whileTap={{ scale: 0.97 }}
                                        className="rounded-2xl border border-zinc-200 bg-white p-0 shadow-lg transition duration-300 ease-in-out hover:shadow-2xl"
                                    >
                                        <a
                                            href={`/producto/${producto.slug}`}
                                            className="block"
                                        >
                                            <img
                                                src={producto.imagen}
                                                alt={producto.descripcion}
                                                className="h-52 w-full object-contain p-4"
                                            />
                                            <div className="px-4 pb-4">
                                                {/* Identificador de Precio Ofertado por Apertura */}
                                                {producto.es_precio_ofertado && (
                                                    <div className="mb-2 inline-block rounded-full bg-orange-100 px-3 py-1 text-xs font-semibold text-orange-800">
                                                        🎉 Precio Ofertado por Apertura
                                                    </div>
                                                )}
                                                
                                                <h3 className="text-md whitespace-normal font-semibold text-zinc-800">
                                                    {producto.descripcion}
                                                </h3>
                                                <p className="mt-1 text-xs text-zinc-500">
                                                    Marca:{' '}
                                                    <span className="font-medium text-indigo-600">
                                                        {producto.marca}
                                                    </span>
                                                </p>
                                                <p className="text-xs text-zinc-500">
                                                    Talla:{' '}
                                                    <span className="font-medium text-emerald-600">
                                                        {producto.talla}
                                                    </span>
                                                </p>
                                                <p className="text-xs text-zinc-500">
                                                    Género:{' '}
                                                    <span className="font-medium text-blue-600">
                                                        {producto.genero}
                                                    </span>
                                                </p>
                                                <p className="mt-2 text-lg font-bold text-green-600">
                                                    Q{producto.precio}
                                                </p>
                                            </div>
                                        </a>
                                    </motion.div>
                                ))}
                            </motion.div>
                        ) : (
                            <div className="py-10 text-center text-zinc-500">
                                <FaSearch className="mx-auto mb-4 text-5xl" />
                                <p>No se encontró ningún producto</p>
                            </div>
                        )}

                        <ul className="mt-6 flex flex-wrap justify-center gap-1">
                            {productos.links.map((link, index) => (
                                <li key={index}>
                                    {link.url ? (
                                        <button
                                            onClick={() => {
                                                get(link.url, {
                                                    preserveState: true,
                                                    preserveScroll: true,
                                                    onSuccess: () => {
                                                        window.scrollTo({
                                                            top: 0,
                                                            behavior: 'smooth',
                                                        });
                                                    },
                                                });
                                            }}
                                            className={`min-w-[36px] rounded px-3 py-1 text-sm ${
                                                link.active
                                                    ? 'border border-black bg-black text-white'
                                                    : 'border border-zinc-300 bg-white text-zinc-700 hover:bg-zinc-100'
                                            }`}
                                            dangerouslySetInnerHTML={{
                                                __html: link.label,
                                            }}
                                        />
                                    ) : (
                                        <span
                                            className="min-w-[36px] rounded px-3 py-1 text-sm text-zinc-400"
                                            dangerouslySetInnerHTML={{
                                                __html: link.label,
                                            }}
                                        />
                                    )}
                                </li>
                            ))}
                        </ul>
                    </div>
                </div>
            </div>
        </Layout>
    );
}

// Componente Filtros (sin cambios)
function Filtros({
    data,
    setData,
    handleCheckboxChange,
    handleReset,
    bodegas,
    marcasDisponibles,
    generosDisponibles,
    tallasRango,
}) {
    return (
        <>
            <input
                type="text"
                name="search"
                placeholder="Buscar producto..."
                value={data.search}
                onChange={(e) => setData('search', e.target.value)}
                className="w-full rounded border border-zinc-300 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-black"
            />

            <div>
                <label className="mb-1 block text-sm font-medium text-zinc-700">
                    Género
                </label>
                <select
                    name="genero"
                    value={data.genero}
                    onChange={(e) => setData('genero', e.target.value)}
                    className="w-full rounded border border-zinc-300 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-black"
                >
                    <option value="">Todos</option>
                    {generosDisponibles.map((g, index) => (
                        <option key={index} value={g}>
                            {g}
                        </option>
                    ))}
                </select>
            </div>

            <div>
                <label className="mb-1 block text-sm font-medium text-zinc-700">
                    Bodega
                </label>
                <select
                    name="bodega"
                    value={data.bodega}
                    onChange={(e) => setData('bodega', e.target.value)}
                    className="w-full rounded border border-zinc-300 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-black"
                >
                    <option value="">Todas</option>
                    {bodegas.map((b) => (
                        <option key={b.id} value={b.id}>
                            {b.bodega}
                        </option>
                    ))}
                </select>
            </div>

            <div>
                <label className="mb-1 block text-sm font-medium text-zinc-700">
                    Marca
                </label>
                <select
                    name="marca"
                    value={data.marca}
                    onChange={(e) => setData('marca', e.target.value)}
                    className="w-full rounded border border-zinc-300 px-4 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-black"
                >
                    <option value="">Todas</option>
                    {marcasDisponibles.map((m, index) => (
                        <option key={index} value={m}>
                            {m}
                        </option>
                    ))}
                </select>
            </div>

            <div>
                <label className="mb-1 block text-sm font-medium text-zinc-700">
                    Tallas US
                </label>
                <div className="flex flex-wrap gap-2">
                    {tallasRango.map((t, index) => (
                        <label
                            key={index}
                            className="flex items-center gap-1 text-sm text-zinc-600"
                        >
                            <input
                                type="checkbox"
                                value={t}
                                checked={data.tallas.includes(t)}
                                onChange={handleCheckboxChange}
                            />
                            {t}
                        </label>
                    ))}
                </div>
            </div>

            <button
                type="button"
                onClick={handleReset}
                className="mt-4 w-full rounded border border-zinc-400 bg-white px-4 py-2 text-sm text-zinc-700 hover:bg-zinc-100"
            >
                Reiniciar filtros
            </button>
        </>
    );
}
