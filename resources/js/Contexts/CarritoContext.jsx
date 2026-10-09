import { createContext, useContext, useEffect, useState } from 'react';

// Carrito del sitio público: vive en el navegador del cliente (localStorage)
// y se envía como mensaje de WhatsApp; no hay pagos en línea.
const CarritoContext = createContext(null);
const CLAVE = 'tenisline_carrito_v1';

function leer() {
    try {
        return JSON.parse(window.localStorage.getItem(CLAVE)) || [];
    } catch {
        return [];
    }
}

export function CarritoProvider({ children }) {
    const [items, setItems] = useState([]);
    const [abierto, setAbierto] = useState(false);
    const [listo, setListo] = useState(false);

    useEffect(() => {
        setItems(leer());
        setListo(true);
    }, []);

    useEffect(() => {
        if (!listo) return;
        try {
            window.localStorage.setItem(CLAVE, JSON.stringify(items));
        } catch {
            // Sin almacenamiento disponible: el carrito funciona solo en esta visita
        }
    }, [items, listo]);

    const agregar = (producto, cantidad = 1) => {
        setItems((actual) => {
            const existe = actual.find((i) => i.id === producto.id);
            // Cada par es único: si ya está en el carrito, no se repite
            if (existe) return actual;
            return [...actual, { ...producto, cantidad: 1 }];
        });
        setAbierto(true);
    };

    const cambiarCantidad = (id, cantidad) =>
        setItems((actual) =>
            actual
                .map((i) =>
                    i.id === id
                        ? { ...i, cantidad: Math.max(0, Math.min(cantidad, 10)) }
                        : i,
                )
                .filter((i) => i.cantidad > 0),
        );

    const quitar = (id) => setItems((a) => a.filter((i) => i.id !== id));
    const vaciar = () => setItems([]);
    const cantidad = items.reduce((s, i) => s + i.cantidad, 0);

    return (
        <CarritoContext.Provider
            value={{
                items,
                cantidad,
                agregar,
                cambiarCantidad,
                quitar,
                vaciar,
                abierto,
                setAbierto,
            }}
        >
            {children}
        </CarritoContext.Provider>
    );
}

export const useCarrito = () => useContext(CarritoContext);
