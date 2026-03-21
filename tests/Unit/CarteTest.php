<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Tests Unitaires — Application APPCS
 *
 * Ces tests vérifient des fonctions ou classes isolées,
 * SANS dépendance à la base de données ni au framework HTTP.
 *
 * Sur quoi on se base pour écrire ces tests ?
 * ─────────────────────────────────────────────
 * 1. La STRUCTURE du projet : dossiers app/, routes/, config/
 * 2. Les CONVENTIONS Laravel : helpers, config(), env()
 * 3. Les RÈGLES MÉTIER déduites du nom du projet (cartes scolaires)
 *    et de la stack Docker (PHP 8.4, MySQL 8.0, Nginx)
 * 4. Les EXIGENCES DE SÉCURITÉ du pipeline CI/CD
 */
class CarteTest extends TestCase
{
    // ────────────────────────────────────────────
    // Tests des helpers PHP / logique pure
    // ────────────────────────────────────────────

    /**
     * PHP doit fonctionner — test de base pour valider l'environnement CI.
     */
    public function test_php_is_working(): void
    {
        $this->assertTrue(true);
    }

    /**
     * La version PHP doit être >= 8.4 (exigée par le Dockerfile).
     * Base : FROM php:8.4-fpm dans le Dockerfile.
     */
    public function test_php_version_meets_requirement(): void
    {
        $currentVersion = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;

        $this->assertGreaterThanOrEqual(
            8.4,
            (float) $currentVersion,
            'PHP 8.4+ requis (Dockerfile : FROM php:8.4-fpm)'
        );
    }

    // ────────────────────────────────────────────
    // Tests de logique métier pure (sans BDD)
    // ────────────────────────────────────────────

    /**
     * Validation du format d'un identifiant de carte scolaire.
     * Base : règle métier typique — un ID ne peut pas être vide.
     */
    public function test_student_id_cannot_be_empty(): void
    {
        $studentId = '';

        $this->assertEmpty($studentId, 'Un identifiant vide est détecté correctement');
        $this->assertFalse(
            $this->isValidStudentId($studentId),
            'Un ID vide doit être rejeté'
        );
    }

    /**
     * Un identifiant étudiant valide doit être accepté.
     */
    public function test_valid_student_id_is_accepted(): void
    {
        $studentId = 'ETU-2024-001';

        $this->assertTrue(
            $this->isValidStudentId($studentId),
            'Un ID bien formaté doit être accepté'
        );
    }

    /**
     * La concaténation d'un nom et prénom doit produire un nom complet.
     * Base : fonctionnalité de base pour affichage sur cartes scolaires.
     */
    public function test_full_name_concatenation(): void
    {
        $firstName = 'Angela';
        $lastName  = 'Ngassam';

        $fullName = trim($firstName . ' ' . $lastName);

        $this->assertEquals('Angela Ngassam', $fullName);
        $this->assertStringContainsString($firstName, $fullName);
        $this->assertStringContainsString($lastName, $fullName);
    }

    /**
     * Une année scolaire doit être dans un format valide (ex: 2024-2025).
     * Base : entité métier clé d'un système de cartes scolaires.
     */
    public function test_school_year_format_is_valid(): void
    {
        $validYear   = '2024-2025';
        $invalidYear = '2024';

        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{4}$/',
            $validYear,
            'Format année scolaire valide'
        );

        $this->assertDoesNotMatchRegularExpression(
            '/^\d{4}-\d{4}$/',
            $invalidYear,
            'Format année scolaire invalide rejeté'
        );
    }

    // ────────────────────────────────────────────
    // Tests de sécurité — logique pure
    // ────────────────────────────────────────────

    /**
     * Une chaîne avec du HTML ne doit pas être affichée brute.
     * Base : protection XSS — règle de sécurité CI/CD.
     */
    public function test_html_special_chars_are_escaped(): void
    {
        $maliciousInput = '<script>alert("xss")</script>';
        $escaped        = htmlspecialchars($maliciousInput, ENT_QUOTES, 'UTF-8');

        $this->assertStringNotContainsString('<script>', $escaped);
        $this->assertStringContainsString('&lt;script&gt;', $escaped);
    }

    /**
     * Un mot de passe ne doit jamais être stocké en clair.
     * Base : exigence de sécurité fondamentale.
     */
    public function test_password_is_hashed_not_plain(): void
    {
        $plainPassword  = 'monMotDePasse123';
        $hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT);

        $this->assertNotEquals($plainPassword, $hashedPassword);
        $this->assertTrue(password_verify($plainPassword, $hashedPassword));
    }

    /**
     * Un email invalide doit être détecté.
     * Base : validation des formulaires d'inscription aux cartes scolaires.
     */
    public function test_invalid_email_is_detected(): void
    {
        $validEmail   = 'angela@example.com';
        $invalidEmail = 'pas-un-email';

        $this->assertNotFalse(filter_var($validEmail, FILTER_VALIDATE_EMAIL));
        $this->assertFalse(filter_var($invalidEmail, FILTER_VALIDATE_EMAIL));
    }

    // ────────────────────────────────────────────
    // Méthodes utilitaires privées (helpers de test)
    // ────────────────────────────────────────────

    /**
     * Simule une validation d'identifiant étudiant.
     * Dans le vrai projet, cette logique serait dans un Model ou Service.
     */
    private function isValidStudentId(string $id): bool
    {
        return !empty($id) && strlen($id) >= 3;
    }
}
