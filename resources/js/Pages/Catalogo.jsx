import Card from '@/Components/Card';
import Layout from '@/Layouts/Layout';
import { Link, useForm } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { FaSearch } from 'react-icons/fa';

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
    generosDisponibles,
}) {
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

    // Ejecuta la búsqueda automáticamente al cambiar filtros
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
            color: '',
            genero: '',
            precioMin: '',
            precioMax: '',
        });
    };

    return (
        <Layout>
            <div className="container mx-auto px-4">
                {/* Botón mostrar/ocultar filtros en móvil */}
                <div className="mb-4 flex justify-end md:hidden">
                    <button
                        onClick={() => setMostrarFiltros(!mostrarFiltros)}
                        className="rounded border bg-black px-4 py-2 text-sm text-white"
                    >
                        {mostrarFiltros ? 'Ocultar filtros' : 'Mostrar filtros'}
                    </button>
                </div>

                <div className="flex flex-col gap-6 md:flex-row">
                    {(mostrarFiltros || window.innerWidth >= 768) && (
                        <form className="w-full space-y-4 md:w-1/4">
                            <div className="space-y-4 rounded border border-zinc-200 bg-white p-4 shadow">
                                <input
                                    type="text"
                                    name="search"
                                    placeholder="Buscar..."
                                    value={data.search}
                                    onChange={(e) =>
                                        setData('search', e.target.value)
                                    }
                                    className="w-full rounded border border-zinc-300 px-4 py-2"
                                />

                                <select
                                    name="genero"
                                    value={data.genero}
                                    onChange={(e) =>
                                        setData('genero', e.target.value)
                                    }
                                    className="w-full rounded border border-zinc-300 px-4 py-2"
                                >
                                    <option value="">Todos los géneros</option>
                                    {generosDisponibles.map((g, index) => (
                                        <option key={index} value={g}>
                                            {g}
                                        </option>
                                    ))}
                                </select>

                                <select
                                    name="bodega"
                                    value={data.bodega}
                                    onChange={(e) =>
                                        setData('bodega', e.target.value)
                                    }
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
                                    onChange={(e) =>
                                        setData('marca', e.target.value)
                                    }
                                    className="w-full rounded border border-zinc-300 px-4 py-2"
                                >
                                    <option value="">Todas las marcas</option>
                                    {marcasDisponibles.map((m, index) => (
                                        <option key={index} value={m}>
                                            {m}
                                        </option>
                                    ))}
                                </select>

                                <div className="flex gap-2">
                                    <input
                                        type="number"
                                        placeholder="Precio mínimo"
                                        value={data.precioMin}
                                        onChange={(e) =>
                                            setData('precioMin', e.target.value)
                                        }
                                        className="w-full rounded border border-zinc-300 px-2 py-2"
                                    />
                                    <input
                                        type="number"
                                        placeholder="Precio máximo"
                                        value={data.precioMax}
                                        onChange={(e) =>
                                            setData('precioMax', e.target.value)
                                        }
                                        className="w-full rounded border border-zinc-300 px-2 py-2"
                                    />
                                </div>

                                <div>
                                    <h3 className="mb-1 text-sm font-semibold">
                                        Tallas:
                                    </h3>
                                    <div className="flex flex-wrap gap-2">
                                        {tallasDisponibles.map((t, index) => (
                                            <label
                                                key={index}
                                                className="flex items-center space-x-1"
                                            >
                                                <input
                                                    type="checkbox"
                                                    value={t}
                                                    checked={data.tallas.includes(
                                                        t,
                                                    )}
                                                    onChange={
                                                        handleCheckboxChange
                                                    }
                                                />
                                                <span>{t}</span>
                                            </label>
                                        ))}
                                    </div>
                                </div>

                                <button
                                    type="button"
                                    onClick={handleReset}
                                    className="w-full rounded border border-zinc-400 px-4 py-2 text-zinc-700 hover:bg-zinc-100"
                                >
                                    Reiniciar
                                </button>
                            </div>
                        </form>
                    )}

                    {/* Productos */}
                    <div className="w-full md:w-3/4">
                        {productos.data.length > 0 ? (
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                                {productos.data.map((producto) => (
                                    <Card
                                        key={producto.id}
                                        producto={producto}
                                    />
                                ))}
                            </div>
                        ) : (
                            <div className="py-10 text-center text-zinc-500">
                                <FaSearch className="mx-auto mb-4 text-5xl" />
                                <p>No se encontró ningún producto</p>
                            </div>
                        )}

                        {/* Paginación */}
                        <ul className="mt-6 flex flex-wrap justify-center gap-1">
                            {productos.links.map((link, index) => (
                                <li key={index}>
                                    {link.url ? (
                                        <Link
                                            href={link.url}
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
