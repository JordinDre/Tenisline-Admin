<?php

namespace Tests\Feature;

use Tests\TestCase;

/** Sitio público de Tenisline (solo lectura). */
class TiendaPublicaTest extends TestCase
{
    public function test_portada_trae_seo_en_el_html_del_servidor(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('<title inertia>Tenisline | Tenis de marca en Zacapa, Chiquimula y Esquipulas</title>', false)
            ->assertSee('property="og:title"', false)
            ->assertSee('rel="canonical"', false)
            ->assertSee('application/ld+json', false);
    }

    public function test_catalogo_indexa_categorias_pero_no_busquedas(): void
    {
        $this->get('/catalogo?categoria=dama')->assertOk()->assertSee('content="index,follow,max-image-preview:large"', false);
        $this->get('/catalogo?search=puma')->assertOk()->assertSee('content="noindex,follow"', false);
    }

    public function test_marcas_y_sitemap(): void
    {
        $this->get('/marcas')->assertOk();

        $sitemap = $this->get('/sitemap.xml')->assertOk();
        $this->assertStringContainsString('application/xml', $sitemap->headers->get('Content-Type'));
        $this->assertStringContainsString('<urlset', $sitemap->getContent());
        $this->assertStringContainsString('/catalogo', $sitemap->getContent());
    }

    public function test_producto_existente_tiene_datos_estructurados(): void
    {
        preg_match('#<loc>([^<]*/producto/[^<]*)</loc>#', $this->get('/sitemap.xml')->getContent(), $m);
        if (empty($m)) {
            $this->markTestSkipped('La base de pruebas no tiene productos con existencia.');
        }

        $this->get(parse_url($m[1], PHP_URL_PATH))
            ->assertOk()
            ->assertSee('"@type":"Product"', false)
            ->assertSee('"priceCurrency":"GTQ"', false);
    }

    public function test_producto_inexistente_da_404(): void
    {
        $this->get('/producto/no-existe-xyz')->assertNotFound();
    }

    public function test_redimensionador_rechaza_tamanos_y_rutas_invalidas(): void
    {
        $this->get('/img/999/local/abc.webp')->assertNotFound();           // tamaño no permitido
        $this->get('/img/480/otra-carpeta/abc.webp')->assertNotFound();    // carpeta no permitida
        $this->get('/img/480/local/..%2F..%2F.env')->assertNotFound();      // intento de salir de la carpeta
        $this->get('/img/480/local/no-existe-en-s3.webp')->assertNotFound();
    }

    public function test_vista_previa_usa_imagen_liviana_de_marca_y_la_ruta_og_valida_entradas(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('og-tenisline.jpg', false)
            ->assertSee('property="og:image:width" content="1200"', false);

        $this->assertLessThan(100 * 1024, filesize(public_path('images/og-tenisline.jpg')), 'La imagen de vista previa debe pesar menos de 100 KB');
        $this->assertLessThan(30 * 1024, filesize(public_path('images/logo.webp')), 'El logo del sitio debe pesar menos de 30 KB');

        $this->get('/img/og/otra-carpeta/abc.webp')->assertNotFound();
        $this->get('/img/og/local/no-existe-en-s3.webp')->assertNotFound();
    }
}
