import { usePage } from '@inertiajs/react';
import { FaRegUser } from 'react-icons/fa';

export default function Cuenta() {
    const user = usePage().props.auth.user;
    const allowedToAccess = user?.roles?.some(
        (role) => role.name !== 'cliente' && role.name !== 'proveedor',
    );
    const userInitials = user ? user.name.substring(0, 1) : null;
    return (
        <div className="navbar bg-base-100">
            <div className="dropdown dropdown-end">
                <div
                    tabIndex={0}
                    role="button"
                    className="avatar btn btn-circle btn-ghost"
                >
                    <div className="avatar placeholder">
                        <div className="text-zinc-content w-10 rounded-full bg-zinc-700">
                            <span className="text-lg text-white">
                                {userInitials || <FaRegUser />}
                            </span>
                        </div>
                    </div>
                </div>

                <ul
                    tabIndex={0}
                    className="menu dropdown-content menu-sm z-[1] mt-3 w-52 rounded-box bg-base-100 p-2 shadow"
                >
                    {user ? (
                        <div>
                            {/* <div className="my-3 p-3 text-center font-bold">
                                <h1>{user.name}</h1>
                            </div>
                            <li>
                                <Link
                                    className="p-3 font-bold"
                                    href={route('profile.edit')}
                                >
                                    Perfil
                                </Link>
                            </li> */}
                            {allowedToAccess && (
                                <li>
                                    <a href="/admin" className="p-3 font-bold">
                                        Administración
                                    </a>
                                </li>
                            )}
                            {/* <li>
                                <Link
                                    className="p-3 font-bold"
                                    method="post"
                                    href={route('logout')}
                                    as="button"
                                >
                                    Cerrar Sesión
                                </Link>
                            </li> */}
                        </div>
                    ) : (
                        <div>
                            {/* <li>
                                <Link
                                    className="p-3 font-bold"
                                    href={route('login')}
                                >
                                    Iniciar Sesión
                                </Link>
                            </li> */}
                        </div>
                    )}
                </ul>
            </div>
        </div>
    );
}
