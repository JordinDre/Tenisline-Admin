import Card from '@/Components/Card';
import Layout from '@/Layouts/Layout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FaSearch } from 'react-icons/fa';

export default function Catalogo({ productos, search, bodega }) {
    const { data, setData, get, processing } = useForm({
        search: search || '',
        bodega: bodega || '',
    });

    function submit(e) {
        e.preventDefault();
        get('catalogo', data, { preserveScroll: true });
    }

    return (
        <Layout>
            <Head>
                <title>Catálogo</title>
                <meta name="catalogo" content="Catálogo - Tienda en línea" />
                <link
                    rel="icon"
                    type="image/svg+xml"
                    href="/images/icono.png"
                />
            </Head>

            <form
                onSubmit={submit}
                className="mx-auto flex max-w-screen-xl gap-6 px-4 py-5"
            >
                <input
                    type="text"
                    name="search"
                    value={data.search}
                    onChange={(e) => setData('search', e.target.value)}
                    placeholder="Buscar productos..."
                    className="focus:ring-black-500 w-full rounded-lg border border-zinc-300 px-4 py-2 text-zinc-700 focus:outline-none focus:ring-2"
                />

                <select
                    name="bodega"
                    value={data.bodega}
                    onChange={(e) => setData('bodega', e.target.value)}
                    className="rounded-lg border border-zinc-300 px-4 py-2 text-zinc-700 focus:outline-none focus:ring-2 focus:ring-black"
                >
                        <option value="">Todas las bodegas</option>
                        <option value="1">Zacapa</option>
                        <option value="2">Chiquimula</option>
                </select>


                <button
                    type="submit"
                    disabled={processing}
                    className="hover:bg-black-600 ml-2 rounded-lg bg-black px-4 py-2 text-white"
                >
                    Buscar
                </button>
            </form>

            {productos.data.length > 0 ? (
                <div className="mx-auto grid max-w-screen-xl grid-cols-1 gap-6 px-4 py-5 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:gap-8">
                    {productos.data.map((producto) => (
                        <Card key={producto.id} producto={producto} />
                    ))}
                </div>
            ) : (
                <div className="mx-auto flex max-w-screen-xl flex-col items-center justify-center gap-4 px-4 py-10 text-center">
                    <FaSearch className="text-6xl text-zinc-500" />
                    <p className="text-lg font-semibold text-zinc-500">
                        No se encontró ningún producto
                    </p>
                </div>
            )}

            <ul className="mt-6 flex items-center justify-center gap-1">
                {productos?.links?.map((link, index) => (
                    <li key={index}>
                        {link.url ? (
                            <Link
                                href={link.url + `&search=${data.search}`}
                                className={`min-w-[36px] rounded-md px-3 py-1 text-center text-sm transition-colors duration-200 ${
                                    link.active
                                        ? 'border border-black bg-black text-white shadow'
                                        : 'border border-zinc-300 bg-white text-zinc-700 hover:bg-zinc-100 hover:text-black'
                                }`}
                                dangerouslySetInnerHTML={{
                                    __html: link.label,
                                }}
                            />
                        ) : (
                            <span
                                className="min-w-[36px] rounded-md border border-zinc-200 bg-white px-3 py-1 text-center text-sm text-zinc-400"
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        )}
                    </li>
                ))}
            </ul>
        </Layout>
    );
}
