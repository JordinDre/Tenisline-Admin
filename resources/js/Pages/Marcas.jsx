import LogoMarca from '@/Components/tienda/LogoMarca';
import Layout from '@/Layouts/Layout';
import { capitalizar } from '@/lib/tienda';
import { Head, Link } from '@inertiajs/react';

export default function Marcas({ marcas = [] }) {
    return (
        <Layout>
            <Head title="Marcas" />
            <div className="mx-auto max-w-[1600px] px-4 sm:px-6">
                <h1 className="py-8 text-2xl font-medium tracking-tight sm:text-[28px]">
                    Marcas <span className="text-neutral-500">({marcas.length})</span>
                </h1>
                <div className="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                    {marcas.map((m, n) => (
                        <Link
                            key={m.marca}
                            href={`/catalogo?marca=${encodeURIComponent(m.marca)}`}
                            className="group animate-in fade-in fill-mode-both duration-500"
                            style={{ animationDelay: `${n * 30}ms` }}
                        >
                            <div className="flex aspect-[4/3] items-center justify-center bg-neutral-100 p-8 transition-colors group-hover:bg-neutral-200">
                                <LogoMarca marca={m.marca} logo={m.logo} className="transition-transform duration-500 group-hover:scale-110" />
                            </div>
                            <p className="mt-3 text-[15px] font-medium">{capitalizar(m.marca)}</p>
                            <p className="text-[15px] text-neutral-500">{m.modelos} modelos</p>
                        </Link>
                    ))}
                </div>
            </div>
        </Layout>
    );
}
