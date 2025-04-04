/* import Checkbox from '@/Components/Checkbox'; */
import Checkbox from '@/Components/Checkbox';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import Layout from '@/Layouts/Layout';
import { Head, /* Link, */ useForm } from '@inertiajs/react';

export default function Login({ status /* , canResetPassword  */ }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit = (e) => {
        e.preventDefault();

        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <Layout>
            <Head>
                <title>Iniciar Sesión</title>
                <meta name="login" content="Iniciar Sesión" />
                <link
                    rel="icon"
                    type="image/svg+xml"
                    href="/images/icono.png"
                />
            </Head>

            {status && (
                <div className="mb-4 rounded-lg bg-green-100 p-4 text-sm font-medium text-green-600">
                    {status}
                </div>
            )}

            <form
                onSubmit={submit}
                className="mx-auto mt-10 max-w-md rounded-lg bg-white p-8 shadow-md dark:bg-zinc-800 dark:text-zinc-200"
            >
                <h2 className="mb-6 text-center text-2xl font-extrabold uppercase text-zinc-700 dark:text-zinc-100">
                    Iniciar Sesión
                </h2>

                <div className="mb-4">
                    <InputLabel
                        htmlFor="email"
                        value="Email"
                        className="text-sm font-medium"
                    />
                    <TextInput
                        id="email"
                        type="email"
                        name="email"
                        value={data.email}
                        className="mt-2 block w-full rounded-md border-zinc-300 p-3 shadow-sm focus:border-zinc-500 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-900"
                        autoComplete="username"
                        isFocused={true}
                        onChange={(e) => setData('email', e.target.value)}
                    />
                    <InputError
                        message={errors.email}
                        className="mt-2 text-sm text-red-500"
                    />
                </div>

                <div className="mb-4">
                    <InputLabel
                        htmlFor="password"
                        value="Contraseña"
                        className="text-sm font-medium"
                    />
                    <TextInput
                        id="password"
                        type="password"
                        name="password"
                        value={data.password}
                        className="mt-2 block w-full rounded-md border-zinc-300 p-3 shadow-sm focus:border-zinc-500 focus:ring-zinc-500 dark:border-zinc-700 dark:bg-zinc-900"
                        autoComplete="current-password"
                        onChange={(e) => setData('password', e.target.value)}
                    />
                    <InputError
                        message={errors.password}
                        className="mt-2 text-sm text-red-500"
                    />
                </div>

                <div className="mb-6 flex items-center">
                    <Checkbox
                        name="remember"
                        checked={data.remember}
                        onChange={(e) => setData('remember', e.target.checked)}
                        className="h-4 w-4 rounded border-zinc-300 text-zinc-600 focus:ring-zinc-500"
                    />
                    <span className="ms-2 text-sm text-zinc-600 dark:text-zinc-400">
                        Recordar Credenciales
                    </span>
                </div>

                <div className="flex items-center justify-end">
                    <button
                        className="w-full rounded-lg bg-zinc-600 px-4 py-2 text-sm font-bold uppercase text-white transition hover:bg-zinc-700 focus:outline-none focus:ring-2 focus:ring-zinc-500 focus:ring-offset-2 dark:focus:ring-offset-zinc-800"
                        disabled={processing}
                    >
                        Ingresar
                    </button>
                </div>
            </form>
        </Layout>
    );
}
