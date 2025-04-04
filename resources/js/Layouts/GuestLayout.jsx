import ApplicationLogo from '@/Components/ApplicationLogo';
import { Link } from '@inertiajs/react';

export default function GuestLayout({ children }) {
    return (
        <div className="flex min-h-screen flex-col items-center bg-zinc-100 pt-6 dark:bg-zinc-900 sm:justify-center sm:pt-0">
            <div>
                <Link href="/">
                    <ApplicationLogo className="h-auto w-64 fill-current text-zinc-500" />
                </Link>
            </div>

            <div className="mt-6 w-full overflow-hidden bg-white px-6 py-4 shadow-md dark:bg-zinc-800 sm:max-w-md sm:rounded-lg">
                {children}
            </div>
        </div>
    );
}
