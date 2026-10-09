/** Logo de la marca (SVG en /images/marcas) o su nombre con estilo si no hay logo. */
export default function LogoMarca({ marca, logo, className = 'h-8' }) {
    if (logo) {
        return (
            <img
                src={logo}
                alt={marca}
                loading="lazy"
                className={`w-auto max-w-full object-contain ${className}`}
            />
        );
    }

    return (
        <span className="font-display text-lg uppercase tracking-tight text-neutral-900">
            {marca}
        </span>
    );
}
