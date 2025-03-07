import NavLink from './NavLink';

export default function Navigation() {
    return (
        <nav className="bg-sky-700 dark:bg-gray-700">
            <div className="mx-auto max-w-screen-xl px-4 py-3">
                <div className="flex items-center">
                    <ul className="mt-0 flex flex-row space-x-8 text-sm font-medium rtl:space-x-reverse">
                        <NavLink
                            href={route('inicio')}
                            active={route().current('inicio')}
                        >
                            Inicio
                        </NavLink>
                        <NavLink
                            href={route('catalogo')}
                            active={route().current('catalogo')}
                        >
                            Catálogo
                        </NavLink>
                        {/* <NavLink
                            href={route('dashboard')}
                            active={route().current('dashboard')}
                        >
                            Mis Compras
                        </NavLink> */}
                    </ul>
                </div>
            </div>
        </nav>
    );
}
