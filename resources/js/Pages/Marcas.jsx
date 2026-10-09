import LogoMarca from '@/Components/tienda/LogoMarca';
import Layout from '@/Layouts/Layout';
import { Head, Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';

export default function Marcas({ marcas = [] }) {
    return (
        <Layout>
            <Head title="Marcas" />
            <section className="border-b bg-neutral-50">
                <div className="mx-auto max-w-7xl px-4 py-12 sm:px-6">
                    <h1 className="font-display text-4xl uppercase sm:text-5xl">Marcas</h1>
                    <p className="mt-2 text-neutral-500">Las mejores marcas, 100% originales, en un solo lugar.</p>
                </div>
            </section>
            <div className="mx-auto grid max-w-7xl grid-cols-2 gap-4 px-4 py-10 sm:grid-cols-3 sm:px-6 lg:grid-cols-4">
                {marcas.map((m, n) => (
                    <Link
                        key={m.marca}
                        href={`/catalogo?marca=${encodeURIComponent(m.marca)}`}
                        className="group flex animate-fade-up flex-col rounded-3xl p-6 ring-1 ring-neutral-200 transition hover:-translate-y-1 hover:shadow-xl hover:ring-neutral-900"
                        style={{ animationDelay: `${n * 40}ms` }}
                    >
                        <div className="flex h-20 items-center justify-center">
                            <LogoMarca marca={m.marca} logo={m.logo} className="h-12" />
                        </div>
                        <div className="mt-6 flex items-center justify-between border-t pt-4">
                            <span className="text-sm text-neutral-500">{m.modelos} modelos</span>
                            <ArrowRight className="h-4 w-4 transition-transform group-hover:translate-x-1" />
                        </div>
                    </Link>
                ))}
            </div>
        </Layout>
    );
}
