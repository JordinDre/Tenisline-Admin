import { Link } from '@inertiajs/react';
import {
    FaFacebook,
    FaInstagram,
    FaTiktok /* FaYoutube */,
} from 'react-icons/fa';
import ApplicationLogoBlanco from './ApplicationLogoBlanco';

export default function Footer() {
    return (
        <footer className="mt-10 bg-zinc-800 text-white antialiased">
            <div className="mx-auto max-w-screen-xl px-4 2xl:px-0">
                <div className="py-6 md:py-8 lg:py-16">
                    <div className="grid grid-cols-1 gap-8 md:grid-cols-3">
                        {/* Redes Sociales */}
                        <div>
                            <h6 className="mb-4 text-sm font-semibold uppercase">
                                Redes Sociales
                            </h6>
                            <ul className="space-y-3">
                                <li>
                                    <a
                                        href="https://www.facebook.com/profile.php?id=61573374843930"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="flex items-center hover:underline"
                                    >
                                        <FaFacebook className="mr-2" /> Facebook
                                    </a>
                                </li>
                                <li>
                                    <a
                                        href="https://www.tiktok.com/@tenisline.gt?is_from_webapp=1&sender_device=pc"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="flex items-center hover:underline"
                                    >
                                        <FaTiktok className="mr-2" /> TikTok
                                    </a>
                                </li>
                                <li>
                                    <a
                                        href="https://www.instagram.com/tenisline_gt/"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="flex items-center hover:underline"
                                    >
                                        <FaInstagram className="mr-2" />{' '}
                                        Instagram
                                    </a>
                                </li>
                                {/* <li>
                                    <a
                                        href="https://youtube.com"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="flex items-center hover:underline"
                                    >
                                        <FaYoutube className="mr-2" /> YouTube
                                    </a>
                                </li> */}
                            </ul>
                        </div>

                        {/* Contacto */}
                        <div>
                            <h6 className="mb-4 text-sm font-semibold uppercase">
                                Contacto
                            </h6>
                            <ul className="space-y-3">
                                {/* <li>
                                    <span className="block">
                                        PBX: +502 23158518
                                    </span>
                                </li> */}
                                {/* <li>
                                    <a
                                        href="mailto:info@example.com"
                                        className="hover:underline"
                                    >
                                        Correo: info@example.com
                                    </a>
                                </li> */}
                                <li>
                                    <a
                                        href="https://wa.me/50239900606"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="hover:underline"
                                    >
                                        Tienda Zacapa: +502 39900606
                                    </a>
                                </li>
                                <li>
                                    <a
                                        href="https://wa.me/50239910917"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="hover:underline"
                                    >
                                        Tienda Chiquimula: +502 39910917
                                    </a>
                                </li>
                                <li>
                                    <a
                                        href="https://wa.me/50239999952"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="hover:underline"
                                    >
                                        Tienda Esquipulas: +502 39999952
                                    </a>
                                </li>
                            </ul>
                        </div>

                        <div>
                            <h6 className="mb-4 text-sm font-semibold uppercase">
                                Ventas en línea 
                            </h6>
                            <ul className="space-y-3">
                                {/* <li>
                                    <span className="block">
                                        PBX: +502 23158518
                                    </span>
                                </li> */}
                                {/* <li>
                                    <a
                                        href="mailto:info@example.com"
                                        className="hover:underline"
                                    >
                                        Correo: info@example.com
                                    </a>
                                </li> */}
                                <li>
                                    <a
                                        href="https://wa.me/50241490090"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="hover:underline"
                                    >
                                        Ventas en Línea 01: +502 41490090
                                    </a>
                                </li>
                                <li>
                                    <a
                                        href="https://wa.me/50239901122"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="hover:underline"
                                    >
                                        Ventas en Línea 02: +502 39901122
                                    </a>
                                </li>
                            </ul>
                        </div>

                        {/* Términos y Condiciones */}
                        {/* <div>
                            <h6 className="mb-4 text-sm font-semibold uppercase">
                                Visita Nuestro Catálogo Digital
                            </h6>
                            <p className="text-sm">
                                Conoce y Descarga nuestro catálogo de productos
                                servicios en formato digital.
                            </p>
                            <a
                                target="_blank"
                                href="https://www.calameo.com/read/007306379c7275141394a"
                                className="mt-4 inline-block text-sm hover:underline"
                                rel="noreferrer"
                            >
                                Visitar Catálogo
                            </a>
                        </div> */}
                    </div>

                    {/* Logo */}
                    <div className="mt-8 text-center">
                        <Link href="/">
                            <ApplicationLogoBlanco className="inline-block h-14 w-auto fill-current" />
                        </Link>
                    </div>
                </div>
            </div>
        </footer>
    );
}
