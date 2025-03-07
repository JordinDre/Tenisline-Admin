import Card from '@/Components/Card';
import Layout from '@/Layouts/Layout';
import { Head, Link, useForm } from '@inertiajs/react';
import { FaSearch } from 'react-icons/fa';

export default function Catalogo({ productos, search }) {
    const { data, setData, get, processing } = useForm({
        search: search || '',
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
                    className="w-full rounded-lg border border-gray-300 px-4 py-2 text-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                />
                <button
                    type="submit"
                    disabled={processing}
                    className="ml-2 rounded-lg bg-blue-500 px-4 py-2 text-white hover:bg-blue-600"
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
                    <FaSearch className="text-6xl text-gray-500" />
                    <p className="text-lg font-semibold text-gray-500">
                        No se encontró ningún producto
                    </p>
                </div>
            )}

            {/* Paginación */}
            {productos.data.length > 0 && (
                <nav className="mt-6 flex w-full justify-center">
                    <ul className="inline-flex flex-wrap items-center justify-center gap-2 text-sm">
                        {productos.links.map((link, index) => (
                            <li key={index}>
                                <Link
                                    href={link.url}
                                    className={`flex h-8 items-center justify-center rounded-lg px-4 py-1 text-center leading-tight transition-colors duration-200 ${
                                        link.active
                                            ? 'border border-blue-300 bg-blue-50 text-blue-600 hover:bg-blue-100'
                                            : 'border border-gray-300 bg-white text-gray-500 hover:bg-gray-100 hover:text-gray-700'
                                    }`}
                                    dangerouslySetInnerHTML={{
                                        __html: link.label,
                                    }}
                                />
                            </li>
                        ))}
                    </ul>
                </nav>
            )}
        </Layout>
    );
}
