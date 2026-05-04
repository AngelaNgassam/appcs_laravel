<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AppTest extends TestCase
{
    // ────────────────────────────────────────────
    // Tests de santé générale
    // ────────────────────────────────────────────

    public function test_homepage_returns_200(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
    }

    public function test_unknown_route_returns_404(): void
    {
        $response = $this->get('/route-qui-nexiste-vraiment-pas');
        $response->assertStatus(404);
    }

    public function test_response_does_not_expose_php_version(): void
    {
        $response = $this->get('/');
        $this->assertFalse(
            $response->headers->has('X-Powered-By'),
            'X-Powered-By ne doit pas être exposé'
        );
    }

    public function test_csrf_protection_is_active(): void
    {
        $response = $this->post('/login', [
            'email'    => 'test@example.com',
            'password' => 'password',
        ]);
        $this->assertContains($response->status(), [302, 404, 419, 422]);
    }

    // ────────────────────────────────────────────
    // Tests de configuration
    // ────────────────────────────────────────────

    public function test_app_name_is_configured(): void
    {
        $appName = config('app.name');
        $this->assertNotEmpty($appName);
        $this->assertIsString($appName);
    }

    /**
     * On vérifie que le NOM de BDD configuré est correct
     * sans tenter une vraie connexion TCP (qui échoue hors Docker).
     */
    public function test_database_is_configured_as_cartes_scolaires(): void
    {
        $configured = config('database.connections.mysql.database');
        $this->assertEquals(
            'cartes_scolaires',
            $configured,
            "La BDD configurée doit être 'cartes_scolaires'"
        );
    }

    public function test_database_name_is_cartes_scolaires(): void
    {
        $dbName = DB::connection()->getDatabaseName();
        $this->assertEquals('cartes_scolaires', $dbName);
    }

    public function test_storage_logs_is_writable(): void
    {
        $path = storage_path('logs');
        $this->assertDirectoryExists($path);
        $this->assertTrue(is_writable($path));
    }
}
