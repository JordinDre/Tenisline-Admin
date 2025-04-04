import Baner from '@/Components/Baner';
import Carrusel from '@/Components/Carrusel';
import Imagen from '@/Components/Imagen';
import Productos from '@/Components/Productos';
import Seccion from '@/Components/Seccion';
import Layout from '@/Layouts/Layout';
import { Head } from '@inertiajs/react';
import { FaTools } from 'react-icons/fa';

export default function Inicio({ contenido, url }) {
    const renderBlock = (block) => {
        switch (block.type) {
            case 'carrusel':
                return <Carrusel data={block.data} url={url} />;
            case 'banner':
                return <Baner data={block.data} />;
            case 'productos':
                return <Productos data={block.data} url={url} />;
            case 'seccion':
                return <Seccion data={block.data} url={url} />;
            case 'imagen':
                return <Imagen data={block.data} url={url} />;
            default:
                return null;
        }
    };

    return (
        <Layout>
            <Head>
                <title>Tienda</title>
                <meta name="tienda" content="Tienda en línea" />
                <link
                    rel="icon"
                    type="image/svg+xml"
                    href="/images/icono.png"
                />
            </Head>
            <div>
                {contenido && contenido.contenido ? (
                    contenido.contenido.map((block, index) => (
                        <div key={index}>{renderBlock(block)}</div>
                    ))
                ) : (
                    <p></p>
                )}
            </div>
            <div className="flex flex-col items-center justify-center px-4 text-center">
                <FaTools className="my-6 text-6xl text-zinc-700" />
                <h1 className="text-2xl font-bold text-zinc-800">
                    ¡Estamos trabajando para ti!
                </h1>
                <p className="mt-2 text-zinc-600">
                    Muy pronto estaremos de vuelta con una{' '}
                    <strong>nueva tienda en línea</strong> para brindarte una
                    mejor experiencia de compra.
                </p>
                <p className="mt-2 text-zinc-600">
                    Mientras tanto, te invitamos a{' '}
                    <strong>explorar nuestro catálogo</strong> y conocer
                    nuestros productos. 📦✨
                </p>
                <p className="mt-4 text-zinc-600">
                    Gracias por tu paciencia y confianza. 😊
                </p>
            </div>
        </Layout>
    );
}
