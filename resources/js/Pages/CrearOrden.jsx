import Checkbox from '@/Components/Checkbox';
import Resumen from '@/Components/Resumen';
import Select from '@/Components/Select';
import Layout from '@/Layouts/Layout';
import { Head, useForm, usePage } from '@inertiajs/react';
import { MdShoppingCart } from 'react-icons/md'; // Importamos un ícono de react-icons

export default function CrearOrden({ direcciones, tipoPagos }) {
    const carrito = usePage().props.auth.carrito;
    const envioGratis = usePage().props.auth.envio_gratis;

    const { data, setData, post, processing, errors } = useForm({
        direccion: '',
        tipoPago: '',
        facturarCF: false,
    });

    function handleChange(e) {
        const { name, value, type, checked } = e.target;
        setData(name, type === 'checkbox' ? checked : value);
    }

    function submit(e) {
        e.preventDefault();
        post('/guardar-orden');
    }

    const subtotal = carrito.reduce(
        (total, item) => total + item.precio * item.cantidad,
        0,
    );

    const faltaParaEnvioGratis = envioGratis - subtotal;

    return (
        <Layout>
            <Head>
                <title>Crear Orden</title>
                <meta
                    name="crear-orden"
                    content="Crear Orden - Tienda en línea"
                />
                <link
                    rel="icon"
                    type="image/svg+xml"
                    href="/images/icono.png"
                />
            </Head>

            {carrito.length > 0 ? (
                <div className="mx-auto mt-10 max-w-screen-xl p-4">
                    <div className="grid grid-cols-1 gap-8 lg:grid-cols-3">
                        <Resumen
                            subtotal={subtotal}
                            faltaParaEnvioGratis={faltaParaEnvioGratis}
                            ruta={route('carrito')}
                            botonTexto="Volver al Carrito"
                            processing={processing}
                        />

                        <form
                            onSubmit={submit}
                            className="col-span-2 flex flex-col gap-4"
                        >
                            <Select
                                label="Dirección*"
                                name="direccion"
                                value={data.direccion}
                                onChange={handleChange}
                                options={direcciones.map((direccion) => ({
                                    value: direccion.id,
                                    label: `${direccion.direccion}${direccion.referencia ? `, ${direccion.referencia}` : ''}${direccion.zona ? `, Zona ${direccion.zona}` : ''}${direccion.municipio ? `, ${direccion.municipio.municipio}` : ''}${direccion.departamento ? `, ${direccion.departamento.departamento}` : ''}`,
                                }))}
                                placeholder="Escoge una Dirección"
                                error={errors.direccion}
                            />

                            <Select
                                label="Tipo de Pago*"
                                name="tipoPago"
                                value={data.tipoPago}
                                onChange={handleChange}
                                options={tipoPagos.map((tipo) => ({
                                    value: tipo.id,
                                    label: tipo.tipo_pago,
                                }))}
                                placeholder="Selecciona un Tipo de Pago"
                                error={errors.tipoPago}
                            />

                            <div className="mt-4">
                                <Checkbox
                                    name="facturarCF"
                                    id="facturarCF"
                                    className="h-6 w-6 text-xl"
                                    checked={data.facturarCF}
                                    onChange={handleChange}
                                />
                                <label
                                    htmlFor="facturarCF"
                                    className="ml-2 cursor-pointer font-bold"
                                >
                                    Facturar CF
                                </label>
                            </div>

                            <button
                                type="submit"
                                className="btn btn-success mt-4 w-full text-white"
                                disabled={processing}
                            >
                                Crear Orden
                            </button>
                        </form>
                    </div>
                </div>
            ) : (
                <div className="mt-20 flex flex-col items-center justify-center">
                    <MdShoppingCart className="text-9xl text-zinc-400" />
                    <p className="mt-4 text-xl font-semibold text-zinc-600">
                        Tu carrito está vacío. ¡Agrega productos para continuar!
                    </p>
                </div>
            )}
        </Layout>
    );
}
