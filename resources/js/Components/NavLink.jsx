import { Link } from '@inertiajs/react';

export default function NavLink({
    active = false,
    className = '',
    children,
    ...props
}) {
    return (
        <Link
            {...props}
            className={
                'inline-flex items-center rounded-xl px-3 py-1 text-sm font-medium leading-5 transition duration-150 ease-in-out focus:outline-none ' +
                (active
                    ? 'bg-white text-black'
                    : 'border-transparent text-white hover:rounded-xl hover:bg-sky-900') +
                className
            }
        >
            {children}
        </Link>
    );
}
