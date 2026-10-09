import ImagenProducto from '@/Components/tienda/ImagenProducto';
import ProductoCard from '@/Components/tienda/ProductoCard';
import { useCarrito } from '@/Contexts/CarritoContext';
import Layout from '@/Layouts/Layout';
import { capitalizar, descuento, enlaceWhatsApp, quetzales } from '@/lib/tienda';
import { Head, Link, usePage } from '@inertiajs/react';
import { Check, MapPin, MessageCircle, ShieldCheck, ShoppingBag, Store } from 'lucide-react';
import { useMemo, useState } from 'react';
import Zoom from 'react-medium-image-zoom';
import 'react-medium-image-zoom/dist/styles.css';

function Contenido({ producto, variantes, mostrarExistencia, relacionados }) {
    const { agregar } = useCarrito();
    const sucursales = usePage().props.tienda?.sucursales ?? [];
    const inicial = variantes.find((v) => v.slug === producto.slug) ?? variantes[0];
    const [seleccion, setSeleccion] = useState(inicial?.id ?? null);
    const [foto, setFoto] = useState(0);
    const [agregado, setAgregado] = useState(false);

    const variante = variantes.find((v) => v.id === seleccion) ?? inicial;
    const imagenes = producto.imagenes?.length ? producto.imagenes : [null];
    const pct = descuento(variante);

    const item = useMemo(
        () =>
            variante && {
                id: variante.id,
                slug: variante.slug,
                codigo: variante.codigo,
                talla: variante.talla,
                precio: variante.precio,
                precio_oferta: variante.precio_oferta,
                descripcion: producto.descripcion,
                marca: producto.marca,
                color: producto.color,
                imagen: producto.imagenes?.[0] ?? null,
            },
        [variante, producto],
    );

    const alAgregar = () => {
        if (!item) return;
        agregar(item);
        setAgregado(true);
        setTimeout(() => setAgregado(false), 1800);
    };

    return (
        <>
            <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6">
                <nav className="text-sm text-neutral-500">
                    <Link href="/" className="hover:text-ink">Inicio</Link> /{' '}
                    <Link href="/catalogo" className="hover:text-ink">Catálogo</Link> /{' '}
                    {producto.marca && (
                        <>
                            <Link href={`/catalogo?marca=${encodeURIComponent(producto.marca)}`} className="hover:text-ink">{capitalizar(producto.marca)}</Link> /{' '}
                        </>
                    )}
                    <span className="text-neutral-800">{capitalizar(producto.descripcion)}</span>
                </nav>

                <div className="mt-6 grid gap-10 lg:grid-cols-2 lg:gap-16">
                    {/* Galería */}
                    <div className="lg:sticky lg:top-28 lg:self-start">
                        <div className="relative aspect-[4/5] overflow-hidden rounded-3xl bg-neutral-100">
                            {imagenes[foto] ? (
                                <Zoom>
                                    <ImagenProducto src={imagenes[foto]} alt={producto.descripcion} className="h-full w-full" />
                                </Zoom>
                            ) : (
                                <ImagenProducto src={null} alt={producto.descripcion} className="h-full w-full" />
                            )}
                            {pct > 0 && (
                                <span className="absolute left-4 top-4 rounded-full bg-brand px-3 py-1 text-sm font-bold text-white">-{pct}%</span>
                            )}
                        </div>
                        {imagenes.length > 1 && (
                            <div className="mt-3 flex gap-3 overflow-x-auto">
                                {imagenes.map((img, n) => (
                                    <button key={n} onClick={() => setFoto(n)}
                                        className={`h-20 w-16 shrink-0 overflow-hidden rounded-xl ring-2 transition ${foto === n ? 'ring-ink' : 'ring-transparent opacity-70 hover:opacity-100'}`}>
                                        <ImagenProducto src={img} alt="" className="h-full w-full" />
                                    </button>
                                ))}
                            </div>
                        )}
                    </div>

                    {/* Información */}
                    <div>
                        <p className="text-sm font-bold uppercase tracking-widest text-neutral-500">{producto.marca}</p>
                        <h1 className="mt-1 font-display text-3xl uppercase leading-tight sm:text-4xl">{producto.descripcion}</h1>
                        <div className="mt-2 flex flex-wrap gap-2 text-sm text-neutral-600">
                            {producto.color && <span className="rounded-full bg-neutral-100 px-3 py-1">{capitalizar(producto.color)}</span>}
                            {producto.genero && <span className="rounded-full bg-neutral-100 px-3 py-1">{capitalizar(producto.genero)}</span>}
                        </div>

                        {variante && (
                            <div className="mt-6 flex items-baseline gap-3">
                                <span className={`text-3xl font-bold ${pct ? 'text-brand' : ''}`}>{quetzales(variante.precio_oferta || variante.precio)}</span>
                                {pct > 0 && <span className="text-lg text-neutral-400 line-through">{quetzales(variante.precio)}</span>}
                            </div>
                        )}

                        <div className="mt-8">
                            <div className="mb-3 flex items-center justify-between">
                                <h2 className="font-semibold">Elige tu talla (US)</h2>
                                {variante && <span className="text-sm text-neutral-500">Código {variante.codigo}</span>}
                            </div>
                            <div className="grid grid-cols-4 gap-2 sm:grid-cols-5">
                                {variantes.map((v) => (
                                    <button key={v.id} onClick={() => setSeleccion(v.id)}
                                        className={`rounded-xl border-2 py-3 text-sm font-semibold transition ${v.id === seleccion ? 'border-ink bg-ink text-white' : 'border-neutral-200 hover:border-neutral-500'}`}>
                                        {v.talla}
                                    </button>
                                ))}
                            </div>
                        </div>

                        {variante && (
                            <p className="mt-4 flex items-center gap-2 text-sm font-medium text-emerald-700">
                                <Check className="h-4 w-4" /> Disponible en tienda
                            </p>
                        )}

                        {mostrarExistencia && variante?.sucursales?.length > 0 && (
                            <div className="mt-3 flex flex-wrap gap-2">
                                {variante.sucursales.map((s) => (
                                    <span key={s.sucursal} className="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-sm text-emerald-800">
                                        <MapPin className="h-3.5 w-3.5" /> {s.sucursal}: {s.existencia}
                                    </span>
                                ))}
                            </div>
                        )}

                        <div className="mt-8 flex flex-col gap-3 sm:flex-row">
                            <button onClick={alAgregar} disabled={!variante}
                                className="flex flex-1 items-center justify-center gap-2 rounded-full bg-ink py-4 font-semibold text-white transition hover:bg-neutral-800 disabled:opacity-40">
                                {agregado ? <><Check className="h-5 w-5" /> Agregado</> : <><ShoppingBag className="h-5 w-5" /> Agregar al carrito</>}
                            </button>
                            {item && sucursales[0] && (
                                <a href={enlaceWhatsApp(sucursales[0].telefono, [{ ...item, cantidad: 1 }], sucursales[0].nombre)}
                                    target="_blank" rel="noopener noreferrer"
                                    className="flex flex-1 items-center justify-center gap-2 rounded-full border-2 border-[#25D366] py-4 font-semibold text-[#128C7E] transition hover:bg-[#25D366] hover:text-white">
                                    <MessageCircle className="h-5 w-5" /> Preguntar por WhatsApp
                                </a>
                            )}
                        </div>

                        <ul className="mt-10 space-y-4 rounded-3xl bg-neutral-50 p-6 text-sm">
                            <li className="flex gap-3"><ShieldCheck className="h-5 w-5 shrink-0 text-brand" /><span><b>Producto original.</b> Todos nuestros tenis son 100% originales.</span></li>
                            <li className="flex gap-3"><Store className="h-5 w-5 shrink-0 text-brand" /><span><b>Visítanos</b> en Zacapa, Chiquimula y Esquipulas, o pide por WhatsApp.</span></li>
                            <li className="flex gap-3"><MessageCircle className="h-5 w-5 shrink-0 text-brand" /><span><b>Sin pagos en línea.</b> Confirmamos disponibilidad y forma de pago por WhatsApp.</span></li>
                        </ul>
                    </div>
                </div>
            </div>

            {relacionados.length > 0 && (
                <section className="mx-auto mt-16 max-w-7xl px-4 sm:px-6">
                    <h2 className="mb-8 font-display text-3xl uppercase">También de {capitalizar(producto.marca)}</h2>
                    <div className="grid grid-cols-2 gap-x-4 gap-y-10 md:grid-cols-4">
                        {relacionados.map((p, n) => <ProductoCard key={p.id} producto={p} indice={n} />)}
                    </div>
                </section>
            )}
        </>
    );
}

export default function Producto(props) {
    return (
        <Layout>
            <Head title={`${capitalizar(props.producto.marca ?? '')} ${capitalizar(props.producto.descripcion)}`} />
            <Contenido key={props.producto.slug} {...props} variantes={props.variantes ?? []} relacionados={props.relacionados ?? []} />
        </Layout>
    );
}
