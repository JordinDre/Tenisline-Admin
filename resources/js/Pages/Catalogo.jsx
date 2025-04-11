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
                    className="focus:ring-black-500 w-full rounded-lg border border-zinc-300 px-4 py-2 text-zinc-700 focus:outline-none focus:ring-2"
                />
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

            {/* Paginación */}
            {productos.data.length > 0 && (
                <nav className="mt-6 flex w-full justify-center">
                    <ul className="inline-flex flex-wrap items-center justify-center gap-2 text-sm">
                        {productos.links.map((link, index) => (
                            <li key={index}>
                               {/* <Link
                                    href={link.url}
                                    className={`flex h-8 items-center justify-center rounded-lg px-4 py-1 text-center leading-tight transition-colors duration-200 ${
                                        link.active
                                            ? 'border-black-300 bg-black-50 text-black-600 hover:bg-black-100 border'
                                            : 'border border-zinc-300 bg-white text-zinc-500 hover:bg-zinc-100 hover:text-zinc-700'
                                    }`}
                                    dangerouslySetInnerHTML={{
                                        __html: link.label,
                                    }}
                                /> */}
                            </li>
                        ))}
                    </ul>
                </nav>
            )}
        </Layout>
    );
}
