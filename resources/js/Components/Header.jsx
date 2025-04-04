import { Link } from '@inertiajs/react';
import ApplicationLogo from './ApplicationLogo';
/* import Carrito from './Carrito'; */
import Cuenta from './Cuenta';

export default function Header() {
    return (
        <nav className="border-zinc-200 bg-white dark:bg-zinc-900">
            <div className="mx-auto flex max-w-screen-xl items-center justify-between p-2">
                <Link href="/" className="flex items-center">
                    <ApplicationLogo className="h-12 w-auto text-zinc-800 dark:text-zinc-200" />
                </Link>
                <div className="flex items-center lg:space-x-2">
                    {/* <Carrito /> */}
                    <Cuenta />
                </div>
            </div>
        </nav>
    );
}
