import { useState } from 'react';

export default function WhatsAppModal({ isOpen, onClose }) {
    const bodegas = [
        {
            nombre: 'Zacapa',
            telefono: '+50239900606',
            descripcion: 'Bodega de Zacapa'
        },
        {
            nombre: 'Chiquimula',
            telefono: '+50239910917',
            descripcion: 'Bodega de Chiquimula'
        },
        {
            nombre: 'Esquipulas',
            telefono: '+50239999952',
            descripcion: 'Bodega de Esquipulas'
        }
    ];

    const handleBodegaClick = (telefono) => {
        const mensaje = encodeURIComponent('¡Hola! Me interesa conocer más sobre sus productos.');
        const url = `https://wa.me/${telefono.replace('+', '')}?text=${mensaje}`;
        window.open(url, '_blank', 'noopener,noreferrer');
        onClose();
    };

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
            <div className="mx-4 w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
                <div className="mb-4 flex items-center justify-between">
                    <h3 className="text-xl font-semibold text-gray-900">
                        Selecciona una Bodega
                    </h3>
                    <button
                        onClick={onClose}
                        className="text-gray-400 hover:text-gray-600"
                    >
                        <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                
                <p className="mb-6 text-sm text-gray-600">
                    Elige la bodega más cercana a tu ubicación para comunicarte por WhatsApp
                </p>

                <div className="space-y-3">
                    {bodegas.map((bodega, index) => (
                        <button
                            key={index}
                            onClick={() => handleBodegaClick(bodega.telefono)}
                            className="w-full rounded-lg border border-gray-200 p-4 text-left transition-colors hover:bg-green-50 hover:border-green-300"
                        >
                            <div className="flex items-center justify-between">
                                <div>
                                    <h4 className="font-medium text-gray-900">
                                        {bodega.nombre}
                                    </h4>
                                    <p className="text-sm text-gray-600">
                                        {bodega.descripcion}
                                    </p>
                                    <p className="text-sm font-medium text-green-600">
                                        {bodega.telefono}
                                    </p>
                                </div>
                                <div className="flex h-10 w-10 items-center justify-center rounded-full bg-green-100">
                                    <svg className="h-6 w-6 text-green-600" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.885 3.488"/>
                                    </svg>
                                </div>
                            </div>
                        </button>
                    ))}
                </div>

                <div className="mt-6 flex justify-end">
                    <button
                        onClick={onClose}
                        className="rounded-lg bg-gray-100 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-200"
                    >
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    );
}
