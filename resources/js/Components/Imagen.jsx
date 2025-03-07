export default function Imagen({ data, url }) {
    return (
        <div className="mx-auto my-5 max-w-screen-lg px-4">
            <img
                src={`${url + data.imagen}`}
                alt="Imagen"
                className="h-auto w-full transform rounded-xl object-contain shadow-lg transition-transform duration-500 ease-in-out hover:scale-105"
            />
        </div>
    );
}
