export default function Select({
    label, // Etiqueta para mostrar encima del select
    name, // Nombre del campo (importante para formularios)
    value, // Valor actual del select
    onChange, // Función para manejar cambios
    options = [], // Opciones del select, formato [{ value, label }]
    placeholder = 'Seleccione una opción', // Placeholder para el select
    className = '', // Clases adicionales para estilos personalizados
    error = '', // Mensaje de error
    ...props // Otras props que quieras pasar
}) {
    return (
        <div className={`form-control ${className}`}>
            {label && (
                <label className="label" htmlFor={name}>
                    <span className="label-text cursor-pointer font-bold">
                        {label}
                    </span>
                </label>
            )}
            <select
                name={name}
                id={name}
                value={value}
                onChange={onChange}
                className={`select select-bordered ${error ? 'border-red-500' : ''} ${props.selectClassName || ''}`}
                {...props} // Pasa cualquier otra prop adicional
            >
                <option value="" disabled>
                    {placeholder}
                </option>
                {options.map((option) => (
                    <option key={option.value} value={option.value}>
                        {option.label}
                    </option>
                ))}
            </select>
            {error && (
                <span className="mt-1 text-sm text-red-500">{error}</span>
            )}
        </div>
    );
}
