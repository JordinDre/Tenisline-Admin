import React from 'react';
import Card from '@/Components/Card';
import Layout from '@/Layouts/Layout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FaSearch } from 'react-icons/fa';
import { useState } from 'react';

export default function Catalogo({ 
    productos,
    search,
    bodega,
    bodegas,
    marca,
    tallas,
    precioMin,
    precioMax,
    genero,
    tallasDisponibles,
    marcasDisponibles,
    generosDisponibles, }) {

    const [mostrarFiltros, setMostrarFiltros] = useState(false);

    const { data, setData, get, processing } = useForm({
        search: search || '',
        bodega: bodega || '',
        marca: marca || '',
        genero: genero || '',
        tallas: tallas || [],
        precioMin: precioMin || '',
        precioMax: precioMax || '',
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        get(route('catalogo'), {
            preserveScroll: true,
            preserveState: true,
        });
    };

    const handleReset = () => {
        setData({
            search: '',
            marca: '',
            bodega: '',
            tallas: [],
            color: '',
            genero: '',
            precioMin: '',
            precioMax: '',
        });
    
        get(route('catalogo'), {
            preserveScroll: true,
            preserveState: true,
        });
    };

    const handleCheckboxChange = (e) => {
        const value = e.target.value;
        if (data.tallas.includes(value)) {
            setData('tallas', data.tallas.filter((t) => t !== value));
        } else {
            setData('tallas', [...data.tallas, value]);
        }
    };

    return (
        <div className="container mx-auto px-4">
        {/* Botón para mostrar filtros en móvil */}
        <div className="md:hidden flex justify-end mb-4">
            <button onClick={() => setMostrarFiltros(!mostrarFiltros)}>
                {mostrarFiltros ? 'Ocultar filtros' : 'Mostrar filtros'}
            </button>
        </div>

        <div className="flex flex-col md:flex-row gap-4">
            
        <Layout>
            <div className="max-w-screen-xl mx-auto flex px-4 py-6 gap-6">
                {/* Sidebar de filtros */}
                <form onSubmit={handleSubmit} className="w-full max-w-xs space-y-4">
                    <div className="bg-white p-4 rounded shadow space-y-4 border border-zinc-200">
                        <input
                            type="text"
                            name="search"
                            placeholder="Buscar..."
                            value={data.search}
                            onChange={(e) => setData('search', e.target.value)}
                            className="w-full rounded border border-zinc-300 px-4 py-2"
                        />

                        <select
                            name="genero"
                            value={data.genero}
                            onChange={(e) => setData('genero', e.target.value)}
                            className="w-full rounded border border-zinc-300 px-4 py-2"
                        >
                            <option value="">Todos los géneros</option>
                            {generosDisponibles.map((g, index) => (
                                <option key={index} value={g}>{g}</option>
                            ))}
                        </select>

                        <select
                            name="bodega"
                            value={data.bodega}
                            onChange={(e) => setData('bodega', e.target.value)}
                            className="w-full rounded border border-zinc-300 px-4 py-2"
                        >
                            <option value="">Todas las tiendas</option>
                            {bodegas.map((b) => (
                                <option key={b.id} value={b.id}>
                                    {b.bodega}
                                </option>
                            ))}
                        </select>

                        <select
                            name="marca"
                            value={data.marca}
                            onChange={(e) => setData('marca', e.target.value)}
                            className="w-full rounded border border-zinc-300 px-4 py-2"
                        >
                            <option value="">Todas las marcas</option>
                            {marcasDisponibles.map((m, index) => (
                                <option key={index} value={m}>{m}</option>
                            ))}
                        </select>

                        <div className="flex gap-2">
                            <input
                                type="number"
                                placeholder="Precio mínimo"
                                value={data.precioMin}
                                onChange={(e) => setData('precioMin', e.target.value)}
                                className="w-full rounded border border-zinc-300 px-2 py-2"
                            />
                            <input
                                type="number"
                                placeholder="Precio máximo"
                                value={data.precioMax}
                                onChange={(e) => setData('precioMax', e.target.value)}
                                className="w-full rounded border border-zinc-300 px-2 py-2"
                            />
                        </div>

                        <div>
                            <h3 className="font-semibold text-sm mb-1">Tallas:</h3>
                            <div className="flex flex-wrap gap-2">
                                {tallasDisponibles.map((t, index) => (
                                    <label key={index} className="flex items-center space-x-1">
                                        <input
                                            type="checkbox"
                                            value={t}
                                            checked={data.tallas.includes(t)}
                                            onChange={handleCheckboxChange}
                                        />
                                        <span>{t}</span>
                                    </label>
                                ))}
                            </div>
                        </div>

                        <div className="flex gap-2">
                            <button
                                type="submit"
                                disabled={processing}
                                className="w-full bg-black text-white px-4 py-2 rounded hover:bg-zinc-800"
                            >
                                Aplicar filtros
                            </button>
                            <button
                                type="button"
                                onClick={handleReset}
                                className="w-full border border-zinc-400 text-zinc-700 px-4 py-2 rounded hover:bg-zinc-100"
                            >
                                Reiniciar
                            </button>
                        </div>
                    </div>
                </form>

                {/* Sección principal: productos */}
                <div className="flex-1">
                    {productos.data.length > 0 ? (
                        <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                            {productos.data.map((producto) => (
                                <Card key={producto.id} producto={producto} />
                            ))}
                        </div>
                    ) : (
                        <div className="text-center text-zinc-500 py-10">
                            <FaSearch className="mx-auto text-5xl mb-4" />
                            <p>No se encontró ningún producto</p>
                        </div>
                    )}

                    {/* Paginación */}
                    <ul className="mt-6 flex items-center justify-center gap-1">
                        {productos.links.map((link, index) => (
                            <li key={index}>
                                {link.url ? (
                                    <Link
                                        href={link.url}
                                        className={`min-w-[36px] px-3 py-1 rounded text-sm ${
                                            link.active
                                                ? 'bg-black text-white border border-black'
                                                : 'bg-white text-zinc-700 border border-zinc-300 hover:bg-zinc-100'
                                        }`}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                ) : (
                                    <span
                                        className="min-w-[36px] px-3 py-1 rounded text-sm text-zinc-400"
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                )}
                            </li>
                        ))}
                    </ul>
                </div>
            </div>
        </Layout>
        </div>
    </div>
    );
}
