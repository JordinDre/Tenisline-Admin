import LogoMarca from '@/Components/tienda/LogoMarca';
import Layout from '@/Layouts/Layout';
import { capitalizar } from '@/lib/tienda';
import { Head, Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';

export default function Marcas({ marcas = [] }) {
    return (
        <Layout>
            <Head title="Marcas" />
            <section className="relative overflow-hidden border-b bg-neutral-50">
                <div className="pointer-events-none absolute -right-24 -top-24 h-64 w-64 rounded-full bg-brand/10 blur-3xl" />
                <div className="relative mx-auto max-w-7xl px-4 py-10 sm:px-6 sm:py-12 lg:px-8">
                    <h1 className="animate-fade-up font-display text-4xl uppercase leading-none sm:text-5xl">Marcas</h1>
                    <p className="mt-3 text-neutral-500">Las mejores marcas en un solo lugar.</p>
                </div>
            </section>
            <div className="mx-auto grid max-w-7xl grid-cols-2 gap-4 px-4 py-10 sm:grid-cols-3 sm:px-6 lg:grid-cols-4 lg:px-8">
                {marcas.map((m, n) => (
                    <Link key={m.marca} href={`/catalogo?marca=${encodeURIComponent(m.marca)}`}
                        className="group flex animate-fade-up flex-col rounded-3xl bg-white p-5 ring-1 ring-neutral-200 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:ring-ink sm:p-6"
                        style={{ animationDelay: `${Math.min(n, 12) * 40}ms` }}>
                        <div className="flex h-24 items-center justify-center rounded-2xl bg-neutral-50 transition-colors duration-300 group-hover:bg-white">
                            <LogoMarca marca={m.marca} logo={m.logo} className="h-11 w-32 transition-transform duration-500 group-hover:scale-110" />
                        </div>
                        <div className="mt-4 flex items-center justify-between">
                            <div>
                                <p className="font-semibold">{capitalizar(m.marca)}</p>
                                <p className="text-sm text-neutral-500">{m.modelos} modelos</p>
                            </div>
                            <span className="flex h-9 w-9 items-center justify-center rounded-full bg-neutral-100 transition-colors duration-300 group-hover:bg-brand group-hover:text-white">
                                <ArrowRight className="h-4 w-4 transition-transform duration-300 group-hover:translate-x-0.5" />
                            </span>
                        </div>
                    </Link>
                ))}
            </div>
        </Layout>
    );
}
