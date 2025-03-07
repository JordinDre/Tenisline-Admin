import { Banner } from 'flowbite-react';
import {
    HiOutlineArrowRight,
    HiOutlineCheckCircle,
    HiOutlineExclamation,
    HiOutlineSun,
} from 'react-icons/hi'; // Íconos para cada color

export default function Baner({ data }) {
    // Clases CSS según el color seleccionado
    const colorClasses = {
        blue: 'bg-blue-100 text-blue-800 border-blue-200 dark:bg-blue-900 dark:text-blue-200 dark:border-blue-600',
        green: 'bg-green-100 text-green-800 border-green-200 dark:bg-green-900 dark:text-green-200 dark:border-green-600',
        red: 'bg-red-100 text-red-800 border-red-200 dark:bg-red-900 dark:text-red-200 dark:border-red-600',
        yellow: 'bg-yellow-100 text-yellow-800 border-yellow-200 dark:bg-yellow-900 dark:text-yellow-200 dark:border-yellow-600',
    };

    const selectedColor = colorClasses[data.color] || colorClasses.blue; // Default: azul

    // Determinar el ícono según el color
    const icon = {
        blue: <HiOutlineArrowRight className="text-xl" />,
        green: <HiOutlineCheckCircle className="text-xl" />,
        red: <HiOutlineExclamation className="text-xl" />,
        yellow: <HiOutlineSun className="text-xl" />,
    };

    return (
        <Banner>
            <div
                className={`flex w-full items-center border-b p-4 ${selectedColor}`}
            >
                {/* Ícono */}
                <div className="mr-4 flex items-center justify-center">
                    {icon[data.color] || icon.blue}{' '}
                    {/* Mostrar ícono basado en el color */}
                </div>

                {/* Información principal */}
                <div className="mb-0 flex items-center justify-center">
                    <p className="text-md font-bold md:text-lg">
                        {data.contenido}
                    </p>
                </div>
            </div>
        </Banner>
    );
}
