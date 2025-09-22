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

    // Guardar filtros en sessionStorage cuando cambien
    useEffect(() => {
        sessionStorage.setItem('catalogo_filtros', JSON.stringify(data));
    }, [data]);

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
                <div className="mb-6 flex flex-col items-center gap-4 md:flex-row md:justify-between">
                    <h2 className="text-3xl font-bold text-zinc-800">
                        Catálogo de Productos
                    </h2>

                    {/* Botón de exportar PDF */}
                    {/*  <a
                        href={route('pdf.catalogo')}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="flex items-center gap-2 rounded-lg bg-gradient-to-r from-green-600 to-green-700 px-6 py-3 font-medium text-white shadow-lg transition-all duration-200 hover:from-green-700 hover:to-green-800 hover:shadow-xl"
                    >
                        <svg className="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Exportar PDF
                    </a> */}
                </div>

                {/* Botón para móviles */}
                <button
                    onClick={() => setMostrarFiltros(true)}
                    className="mb-6 flex w-full items-center justify-center gap-2 rounded-lg bg-gradient-to-r from-zinc-800 to-zinc-900 px-6 py-3 font-medium text-white shadow-lg transition-all duration-200 hover:from-zinc-900 hover:to-black hover:shadow-xl md:hidden"
                >
                    <svg
                        className="h-5 w-5"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            strokeWidth={2}
                            d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"
                        />
                    </svg>
                    Mostrar filtros
                </button>

                {/* Panel móvil */}
                {mostrarFiltros && (
                    <div className="fixed inset-0 z-50 bg-black bg-opacity-50 md:hidden">
                        <div className="absolute left-0 top-0 h-full w-3/4 max-w-sm overflow-y-auto bg-white shadow-2xl">
                            <div className="sticky top-0 border-b border-zinc-200 bg-gradient-to-r from-zinc-50 to-zinc-100 px-6 py-4">
                                <div className="flex items-center justify-between">
                                    <h3 className="flex items-center gap-2 text-lg font-semibold text-zinc-800">
                                        <svg
                                            className="h-5 w-5 text-zinc-600"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                                strokeWidth={2}
                                                d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"
                                            />
                                        </svg>
                                        Filtros
                                    </h3>
                                    <button
                                        className="rounded-full p-2 text-zinc-500 transition-colors hover:bg-zinc-200 hover:text-zinc-700"
                                        onClick={() => setMostrarFiltros(false)}
                                    >
                                        <svg
                                            className="h-5 w-5"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                strokeLinecap="round"
                                                strokeLinejoin="round"
                                                strokeWidth={2}
                                                d="M6 18L18 6M6 6l12 12"
                                            />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <div className="p-6">
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

                                {/* Botones de acción */}
                                <div className="mt-8 space-y-3">
                                    <button
                                        onClick={handleAplicarFiltros}
                                        className="w-full rounded-lg bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-3 font-medium text-white shadow-lg transition-all duration-200 hover:from-blue-700 hover:to-blue-800 hover:shadow-xl"
                                    >
                                        Aplicar filtros
                                    </button>
                                    <button
                                        onClick={() => setMostrarFiltros(false)}
                                        className="w-full rounded-lg border border-zinc-300 bg-white px-6 py-3 font-medium text-zinc-700 transition-colors hover:bg-zinc-50"
                                    >
                                        Cancelar
                                    </button>
                                </div>
                            </div>
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
                        <div className="rounded-2xl border border-zinc-200 bg-white shadow-lg">
                            <div className="border-b border-zinc-100 bg-gradient-to-r from-zinc-50 to-zinc-100 px-6 py-4">
                                <h3 className="flex items-center gap-2 text-lg font-semibold text-zinc-800">
                                    <svg
                                        className="h-5 w-5 text-zinc-600"
                                        fill="none"
                                        stroke="currentColor"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            strokeLinecap="round"
                                            strokeLinejoin="round"
                                            strokeWidth={2}
                                            d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"
                                        />
                                    </svg>
                                    Filtros
                                </h3>
                            </div>
                            <div className="p-6">
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

// Componente Filtros mejorado
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
        <div className="space-y-6">
            {/* Campo de búsqueda */}
            <div className="space-y-2">
                <label className="flex items-center gap-2 text-sm font-medium text-zinc-700">
                    <svg
                        className="h-4 w-4 text-zinc-500"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            strokeWidth={2}
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                        />
                    </svg>
                    Buscar producto
                </label>
                <div className="relative">
                    <input
                        type="text"
                        name="search"
                        placeholder="Escribe el nombre, código o modelo..."
                        value={data.search}
                        onChange={(e) => setData('search', e.target.value)}
                        className="w-full rounded-lg border border-zinc-300 bg-white px-4 py-3 pl-10 text-sm text-zinc-900 placeholder-zinc-500 shadow-sm transition-all duration-200 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                    />
                    <svg
                        className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-400"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            strokeWidth={2}
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                        />
                    </svg>
                </div>
            </div>

            {/* Género */}
            <div className="space-y-2">
                <label className="flex items-center gap-2 text-sm font-medium text-zinc-700">
                    <svg
                        className="h-4 w-4 text-zinc-500"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            strokeWidth={2}
                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                        />
                    </svg>
                    Género
                </label>
                <select
                    name="genero"
                    value={data.genero}
                    onChange={(e) => setData('genero', e.target.value)}
                    className="w-full rounded-lg border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 shadow-sm transition-all duration-200 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                >
                    <option value="">Todos los géneros</option>
                    {generosDisponibles.map((g, index) => (
                        <option key={index} value={g}>
                            {g}
                        </option>
                    ))}
                </select>
            </div>

            {/* Bodega */}
            <div className="space-y-2">
                <label className="flex items-center gap-2 text-sm font-medium text-zinc-700">
                    <svg
                        className="h-4 w-4 text-zinc-500"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            strokeWidth={2}
                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
                        />
                    </svg>
                    Bodega
                </label>
                <select
                    name="bodega"
                    value={data.bodega}
                    onChange={(e) => setData('bodega', e.target.value)}
                    className="w-full rounded-lg border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 shadow-sm transition-all duration-200 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                >
                    <option value="">Todas las bodegas</option>
                    {bodegas.map((b) => (
                        <option key={b.id} value={b.id}>
                            {b.bodega}
                        </option>
                    ))}
                </select>
            </div>

            {/* Marca */}
            <div className="space-y-2">
                <label className="flex items-center gap-2 text-sm font-medium text-zinc-700">
                    <svg
                        className="h-4 w-4 text-zinc-500"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            strokeWidth={2}
                            d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"
                        />
                    </svg>
                    Marca
                </label>
                <select
                    name="marca"
                    value={data.marca}
                    onChange={(e) => setData('marca', e.target.value)}
                    className="w-full rounded-lg border border-zinc-300 bg-white px-4 py-3 text-sm text-zinc-900 shadow-sm transition-all duration-200 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                >
                    <option value="">Todas las marcas</option>
                    {marcasDisponibles.map((m, index) => (
                        <option key={index} value={m}>
                            {m}
                        </option>
                    ))}
                </select>
            </div>

            {/* Tallas */}
            <div className="space-y-3">
                <label className="flex items-center gap-2 text-sm font-medium text-zinc-700">
                    <svg
                        className="h-4 w-4 text-zinc-500"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            strokeWidth={2}
                            d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zM21 5a2 2 0 00-2-2h-4a2 2 0 00-2 2v12a4 4 0 004 4h4a2 2 0 002-2V5z"
                        />
                    </svg>
                    Tallas US
                </label>
                <div className="max-h-32 overflow-y-auto rounded-lg border border-zinc-200 bg-zinc-50 p-3">
                    <div className="grid grid-cols-4 gap-2">
                        {tallasRango.map((t, index) => (
                            <label
                                key={index}
                                className="group flex cursor-pointer items-center gap-2 rounded-md px-2 py-1.5 text-sm transition-colors hover:bg-white hover:shadow-sm"
                            >
                                <input
                                    type="checkbox"
                                    value={t}
                                    checked={data.tallas.includes(t)}
                                    onChange={handleCheckboxChange}
                                    className="h-4 w-4 rounded border-zinc-300 text-blue-600 focus:ring-2 focus:ring-blue-500/20"
                                />
                                <span className="text-zinc-700 group-hover:text-zinc-900">
                                    {t}
                                </span>
                            </label>
                        ))}
                    </div>
                </div>
            </div>

            {/* Botón de reinicio */}
            <div className="pt-4">
                <button
                    type="button"
                    onClick={handleReset}
                    className="w-full rounded-lg border border-zinc-300 bg-white px-4 py-3 text-sm font-medium text-zinc-700 shadow-sm transition-all duration-200 hover:border-zinc-400 hover:bg-zinc-50 hover:shadow-md"
                >
                    <div className="flex items-center justify-center gap-2">
                        <svg
                            className="h-4 w-4"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                strokeLinecap="round"
                                strokeLinejoin="round"
                                strokeWidth={2}
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                            />
                        </svg>
                        Limpiar filtros
                    </div>
                </button>
            </div>
        </div>
    );
}
