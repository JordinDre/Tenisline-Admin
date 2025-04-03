import Footer from '@/Components/Footer';
import Header from '@/Components/Header';
import Navigation from '@/Components/Navigation';

export default function Layout({ children }) {
    return (
        <div className="flex min-h-screen flex-col">
            <Header />
            <Navigation />
            <main className="flex-1">{children}</main>
            {/* <Footer /> */}
        </div>
    );
}
