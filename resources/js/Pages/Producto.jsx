import { IconoWhatsApp } from '@/Components/tienda/CarritoDrawer';
import ImagenProducto from '@/Components/tienda/ImagenProducto';
import ProductoCard from '@/Components/tienda/ProductoCard';
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@/Components/ui/accordion';
import { Button } from '@/Components/ui/button';
import { Carousel, CarouselContent, CarouselItem, CarouselNext, CarouselPrevious } from '@/Components/ui/carousel';
import { useCarrito } from '@/Contexts/CarritoContext';
import Layout from '@/Layouts/Layout';
import { capitalizar, descuento, enlaceWhatsApp, quetzales } from '@/lib/tienda';
import { cn } from '@/lib/utils';
import { Head, Link, usePage } from '@inertiajs/react';
import { Check, MapPin } from 'lucide-react';
import { useMemo, useState } from 'react';
import Zoom from 'react-medium-image-zoom';
import 'react-medium-image-zoom/dist/styles.css';

const GENERO = { CABALLERO: 'Tenis para hombre', DAMA: 'Tenis para mujer', 'NIÑO': 'Tenis para niño', INFANTE: 'Tenis para infante' };

function Contenido({ producto, variantes, mostrarExistencia, relacionados }) {
    const { agregar } = useCarrito();
    const sucursales = usePage().props.tienda?.sucursales ?? [];
    const inicial = variantes.find((v) => v.slug === producto.slug) ?? variantes[0];
    const [seleccion, setSeleccion] = useState(inicial?.id ?? null);
    const [foto, setFoto] = useState(0);
    const [agregado, setAgregado] = useState(false);
    const [aviso, setAviso] = useState(false);

    const variante = variantes.find((v) => v.id === seleccion);
    const imagenes = producto.imagenes?.length ? producto.imagenes : [null];
    const precioRef = variante ?? inicial;
    const pct = descuento(precioRef);

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
        if (!item) return setAviso(true);
        agregar(item);
        setAgregado(true);
        setTimeout(() => setAgregado(false), 1800);
    };

    return (
        <>
            <div className="mx-auto max-w-[1280px] px-4 pt-6 sm:px-6 lg:pt-12">
                <div className="grid gap-8 lg:grid-cols-[minmax(0,1fr)_400px] lg:gap-14">
                    {/* Galería */}
                    <div className="lg:sticky lg:top-24 lg:self-start">
                        <div className="flex gap-4">
                            {imagenes.length > 1 && (
                                <div className="hidden w-16 shrink-0 flex-col gap-2 lg:flex">
                                    {imagenes.map((img, n) => (
                                        <button key={n} onMouseEnter={() => setFoto(n)} onClick={() => setFoto(n)}
                                            className={cn('aspect-square overflow-hidden rounded-md bg-neutral-100 transition-opacity', foto === n ? 'opacity-100 ring-1 ring-black' : 'opacity-60 hover:opacity-100')}>
                                            <ImagenProducto src={img} alt="" className="h-full w-full" />
                                        </button>
                                    ))}
                                </div>
                            )}
                            <div className="relative min-w-0 flex-1">
                                {/* Celular: carrusel deslizable; computadora: foto grande con zoom */}
                                <Carousel className="lg:hidden" opts={{ loop: imagenes.length > 1 }}>
                                    <CarouselContent className="-ml-0">
                                        {imagenes.map((img, n) => (
                                            <CarouselItem key={n} className="pl-0">
                                                <div className="aspect-square bg-neutral-100"><ImagenProducto src={img} alt={producto.descripcion} className="h-full w-full" /></div>
                                            </CarouselItem>
                                        ))}
                                    </CarouselContent>
                                    {imagenes.length > 1 && (
                                        <>
                                            <CarouselPrevious className="left-3 border-0 bg-white/90" />
                                            <CarouselNext className="right-3 border-0 bg-white/90" />
                                        </>
                                    )}
                                </Carousel>
                                <div className="hidden aspect-square overflow-hidden rounded-lg bg-neutral-100 lg:block">
                                    {imagenes[foto] ? (
                                        <Zoom><ImagenProducto src={imagenes[foto]} alt={producto.descripcion} className="h-full w-full" /></Zoom>
                                    ) : (
                                        <ImagenProducto src={null} alt={producto.descripcion} className="h-full w-full" />
                                    )}
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Información */}
                    <div className="lg:pt-2">
                        <h1 className="text-2xl font-medium leading-tight tracking-tight sm:text-[28px]">
                            {capitalizar(producto.marca ?? '')} {capitalizar(producto.descripcion)}
                        </h1>
                        <p className="mt-1 text-[15px] font-medium text-neutral-600">{GENERO[producto.genero] ?? 'Tenis'}</p>

                        {precioRef && (
                            <div className="mt-4 flex flex-wrap items-baseline gap-x-3 gap-y-1">
                                <span className="text-xl font-medium">{quetzales(precioRef.precio_oferta || precioRef.precio)}</span>
                                {pct > 0 && (
                                    <>
                                        <span className="text-neutral-500 line-through">{quetzales(precioRef.precio)}</span>
                                        <span className="font-medium text-emerald-700">{pct}% de descuento</span>
                                    </>
                                )}
                            </div>
                        )}

                        {producto.color && (
                            <div className="mt-6 flex items-center gap-3">
                                {imagenes[0] && (
                                    <div className="h-16 w-16 overflow-hidden rounded-md bg-neutral-100 ring-1 ring-black">
                                        <ImagenProducto src={imagenes[0]} alt="" className="h-full w-full" />
                                    </div>
                                )}
                                <p className="text-[15px] text-neutral-600">Color: <span className="text-black">{capitalizar(producto.color)}</span></p>
                            </div>
                        )}

                        <div className="mt-8">
                            <div className="mb-3 flex items-center justify-between text-[15px]">
                                <span className={cn('font-medium', aviso && !variante && 'text-red-600')}>Selecciona la talla (US)</span>
                                {variante && <span className="text-neutral-500">Cód. {variante.codigo}</span>}
                            </div>
                            <div className={cn('grid grid-cols-3 gap-2 rounded-lg sm:grid-cols-4', aviso && !variante && 'ring-1 ring-red-600 ring-offset-4')}>
                                {variantes.map((v) => (
                                    <button key={v.id} onClick={() => { setSeleccion(v.id); setAviso(false); }}
                                        className={cn('h-12 rounded-md border text-[15px] transition-colors', v.id === seleccion ? 'border-black ring-1 ring-black' : 'border-neutral-300 hover:border-black')}>
                                        {v.talla}
                                    </button>
                                ))}
                            </div>
                            {aviso && !variante && <p className="mt-3 text-sm text-red-600">Selecciona una talla para continuar.</p>}
                        </div>

                        <div className="mt-8 space-y-3">
                            <Button onClick={alAgregar} size="lg" className="h-14 w-full rounded-full text-base">
                                {agregado ? <><Check className="h-5 w-5" /> Agregado a tu bolsa</> : 'Agregar a la bolsa'}
                            </Button>
                            {sucursales[0] && (
                                <Button asChild variant="outline" size="lg" className="h-14 w-full rounded-full border-neutral-300 text-base hover:border-black hover:bg-white">
                                    <a href={item ? enlaceWhatsApp(sucursales[0].telefono, [{ ...item, cantidad: 1 }], sucursales[0].nombre)
                                        : `https://wa.me/${sucursales[0].telefono}?text=${encodeURIComponent(`¡Hola Tenisline! Me interesan los ${producto.marca ?? ''} ${capitalizar(producto.descripcion)}.`)}`}
                                        target="_blank" rel="noopener noreferrer">
                                        <IconoWhatsApp className="h-5 w-5 text-[#25D366]" /> Preguntar por WhatsApp
                                    </a>
                                </Button>
                            )}
                        </div>

                        {variante && mostrarExistencia && variante.sucursales?.length > 0 && (
                            <div className="mt-5 flex flex-wrap gap-2">
                                {variante.sucursales.map((s) => (
                                    <span key={s.sucursal} className="inline-flex items-center gap-1.5 rounded-full bg-neutral-100 px-3 py-1 text-sm">
                                        <MapPin className="h-3.5 w-3.5" /> {s.sucursal}: {s.existencia}
                                    </span>
                                ))}
                            </div>
                        )}

                        <Accordion type="multiple" defaultValue={['detalles']} className="mt-10 border-t">
                            <AccordionItem value="detalles">
                                <AccordionTrigger className="text-lg font-medium hover:no-underline">Detalles del producto</AccordionTrigger>
                                <AccordionContent className="space-y-1.5 text-[15px] text-neutral-600">
                                    <p>Marca: {capitalizar(producto.marca ?? '—')}</p>
                                    {producto.color && <p>Color: {capitalizar(producto.color)}</p>}
                                    <p>Tallas disponibles: {variantes.map((v) => v.talla).join(', ')}</p>
                                </AccordionContent>
                            </AccordionItem>
                            <AccordionItem value="compra">
                                <AccordionTrigger className="text-lg font-medium hover:no-underline">Cómo comprar</AccordionTrigger>
                                <AccordionContent className="text-[15px] text-neutral-600">
                                    Agrega tu talla a la bolsa y envíanos el pedido por WhatsApp. Te confirmamos disponibilidad y forma de pago; no hay pagos en línea.
                                </AccordionContent>
                            </AccordionItem>
                            <AccordionItem value="tiendas">
                                <AccordionTrigger className="text-lg font-medium hover:no-underline">Tiendas</AccordionTrigger>
                                <AccordionContent className="space-y-2 text-[15px] text-neutral-600">
                                    {sucursales.map((s) => (
                                        <p key={s.nombre}><span className="font-medium text-black">{s.nombre}</span>{s.direccion ? ` — ${capitalizar(s.direccion)}` : ''}</p>
                                    ))}
                                </AccordionContent>
                            </AccordionItem>
                        </Accordion>
                    </div>
                </div>
            </div>

            {relacionados.length > 0 && (
                <section className="mx-auto mt-24 max-w-[1600px] px-4 sm:px-6">
                    <Carousel opts={{ align: 'start', dragFree: true }}>
                        <div className="mb-6 flex items-end justify-between">
                            <h2 className="text-2xl font-medium tracking-tight">También te puede gustar</h2>
                            <div className="relative hidden h-11 w-24 sm:block">
                                <CarouselPrevious className="static h-11 w-11 translate-y-0 border-0 bg-neutral-100 hover:bg-neutral-200" />
                                <CarouselNext className="absolute right-0 top-0 h-11 w-11 translate-y-0 border-0 bg-neutral-100 hover:bg-neutral-200" />
                            </div>
                        </div>
                        <CarouselContent className="-ml-3">
                            {relacionados.map((p, n) => (
                                <CarouselItem key={p.id} className="basis-[72%] pl-3 sm:basis-1/2 md:basis-1/3 lg:basis-1/4">
                                    <ProductoCard producto={p} indice={n} />
                                </CarouselItem>
                            ))}
                        </CarouselContent>
                    </Carousel>
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
