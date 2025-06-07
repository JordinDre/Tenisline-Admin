import React from 'react';
import { Head, Link, useForm, usePage } from '@inertiajs/react';

export default function Catalogo({ auth, tenis, marcasDisponibles }) {
    const { data, setData, get } = useForm({
        marca: '',
        talla: '',
        genero: '',
        modelo: '',
    });

    const handleFilterChange = (name, value) => {
        setData((prevData) => ({
            ...prevData,
            [name]: value,
        }));

        get(route('catalogo'), {
            data: { ...data, [name]: value, page: 1 },
            preserveScroll: true,
            preserveState: true,
            replace: true,
        });
    };

    return (
        <>
            <Head title="Catálogo" />

            <div className="mx-auto max-w-7xl px-4 py-8">
                <h1 className="mb-4 text-3xl font-bold">Catálogo de Tenis</h1>

                {/* Filtros */}
                <div className="mb-6 grid grid-cols-2 gap-4 md:grid-cols-4">
                    <select
                        name="marca"
                        value={data.marca}
                        onChange={(e) => handleFilterChange('marca', e.target.value)}
                        className="w-full rounded border border-zinc-300 px-4 py-2"
                    >
                        <option value="">Todas las marcas</option>
                        {marcasDisponibles.map((m, index) => (
                            <option key={index} value={m}>
                                {m}
                            </option>
                        ))}
                    </select>

                    <input
                        type="text"
                        name="modelo"
                        value={data.modelo}
                        onChange={(e) => handleFilterChange('modelo', e.target.value)}
                        placeholder="Modelo"
                        className="w-full rounded border border-zinc-300 px-4 py-2"
                    />

                    <select
                        name="talla"
                        value={data.talla}
                        onChange={(e) => handleFilterChange('talla', e.target.value)}
                        className="w-full rounded border border-zinc-300 px-4 py-2"
                    >
                        <option value="">Todas las tallas</option>
                        {[...Array(10)].map((_, i) => {
                            const talla = (35 + i).toString();
                            return (
                                <option key={talla} value={talla}>
                                    {talla}
                                </option>
                            );
                        })}
                    </select>

                    <select
                        name="genero"
                        value={data.genero}
                        onChange={(e) => handleFilterChange('genero', e.target.value)}
                        className="w-full rounded border border-zinc-300 px-4 py-2"
                    >
                        <option value="">Todos los géneros</option>
                        <option value="masculino">Masculino</option>
                        <option value="femenino">Femenino</option>
                        <option value="unisex">Unisex</option>
                    </select>
                </div>

                {/* Catálogo */}
                <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
                    {tenis.data.map((item) => (
                        <div
                            key={item.id}
                            className="rounded-lg border border-zinc-200 bg-white p-4 shadow-sm"
                        >
                            <img
                                src={item.imagen_url || '/placeholder.jpg'}
                                alt={item.modelo}
                                className="mb-2 h-48 w-full object-cover"
                            />
                            <h2 className="text-lg font-semibold">{item.modelo}</h2>
                            <p className="text-sm text-zinc-500">{item.marca}</p>
                            <p className="mt-1 font-bold text-zinc-800">Q{item.precio1}</p>
                        </div>
                    ))}
                </div>

                {/* Paginación */}
                <div className="mt-6 flex flex-wrap items-center justify-center gap-2">
                    {tenis.links.map((link, index) => (
                        <Link
                            key={index}
                            href={link.url || '#'}
                            preserveScroll
                            preserveState
                            className={`min-w-[36px] rounded px-3 py-1 text-sm ${
                                link.active
                                    ? 'border border-black bg-black text-white'
                                    : 'border border-zinc-300 bg-white text-zinc-700 hover:bg-zinc-100'
                            }`}
                            dangerouslySetInnerHTML={{
                                __html: link.label,
                            }}
                        />
                    ))}
                </div>
            </div>
        </>
    );
}
