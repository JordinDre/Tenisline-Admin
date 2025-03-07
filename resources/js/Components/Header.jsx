import { Link } from '@inertiajs/react';
import ApplicationLogo from './ApplicationLogo';
/* import Carrito from './Carrito'; */
import Cuenta from './Cuenta';

export default function Header() {
    return (
        <nav className="border-gray-200 bg-white dark:bg-gray-900">
            <div className="mx-auto flex max-w-screen-xl items-center justify-between p-2">
                <Link href="/" className="flex items-center">
                    <ApplicationLogo className="h-12 w-auto text-gray-800 dark:text-gray-200" />
                </Link>
                <div className="flex items-center lg:space-x-2">
                    {/* <Carrito /> */}
                    <Cuenta />
                </div>
            </div>
        </nav>
    );
}
