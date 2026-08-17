<?php

use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\SiteURI;
use CodeIgniter\HTTP\UserAgent;
use CodeIgniter\Test\CIUnitTestCase;
use Config\App;
use Modules\Core\Libraries\Maintenance;

/**
 * Maintenance mode.
 *
 * These cover the levers that must work with the database unreachable — the
 * flag file and the .env override — plus the bypass rules, so nobody can
 * accidentally lock the admin panel behind the offline page.
 *
 * Anything that depends on a stored setting is deliberately left out: the
 * settings helper caches per process, and the point of these tests is the
 * behaviour that survives a broken database.
 *
 * @internal
 */
final class MaintenanceTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->clearLevers();
    }

    protected function tearDown(): void
    {
        $this->clearLevers();
        parent::tearDown();
    }

    public function testFlagFileTakesTheSiteOffline(): void
    {
        // Asserted as a transition rather than an absolute state: whether the
        // site starts live depends on a stored setting, and this test is about
        // the lever that works without one.
        file_put_contents(Maintenance::flagPath(), '{}');
        $this->assertTrue(Maintenance::isActive());

        unlink(Maintenance::flagPath());
        $_SERVER['maintenance.enabled'] = 'false';
        $this->assertFalse(Maintenance::isActive());
    }

    public function testDisableRemovesTheFlagFile(): void
    {
        file_put_contents(Maintenance::flagPath(), '{}');

        $this->assertTrue(Maintenance::disable());
        $this->assertFileDoesNotExist(Maintenance::flagPath());
        $this->assertFalse(Maintenance::isActive());
    }

    public function testEnvOverrideBeatsTheFlagFile(): void
    {
        file_put_contents(Maintenance::flagPath(), '{}');
        $this->assertTrue(Maintenance::isActive());

        // An ops override in .env has the final say, in both directions.
        $_SERVER['maintenance.enabled'] = 'false';
        $this->assertFalse(Maintenance::isActive());

        unlink(Maintenance::flagPath());
        $_SERVER['maintenance.enabled'] = 'true';
        $this->assertTrue(Maintenance::isActive());
    }

    /**
     * The admin panel holds the switch. If the offline page ever covered it,
     * getting back up would mean an SSH session.
     */
    public function testAdminPanelIsAlwaysReachable(): void
    {
        $this->assertTrue(Maintenance::allows($this->request('admin')));
        $this->assertTrue(Maintenance::allows($this->request('admin/login')));
        $this->assertTrue(Maintenance::allows($this->request('admin/maintenance')));
    }

    public function testPublicPagesAreNotAllowedThrough(): void
    {
        $this->assertFalse(Maintenance::allows($this->request('en')));
        $this->assertFalse(Maintenance::allows($this->request('en/our-story')));
        $this->assertFalse(Maintenance::allows($this->request('')));

        // A path that merely starts with the same letters is not the panel.
        $this->assertFalse(Maintenance::allows($this->request('administration')));
    }

    public function testApiRequestsAreAnsweredWithJson(): void
    {
        $this->assertTrue(Maintenance::wantsJson($this->request('api/auth/me')));
        $this->assertFalse(Maintenance::wantsJson($this->request('en')));
    }

    public function testLocaleComesFromTheQueryThenThePath(): void
    {
        $this->assertSame('ja', Maintenance::locale($this->request('en', ['lang' => 'ja'])));
        $this->assertSame('es', Maintenance::locale($this->request('es/productos')));
        $this->assertSame('en', Maintenance::locale($this->request('nothing-familiar')));

        // Unsupported values fall back rather than reaching the view.
        $this->assertSame('en', Maintenance::locale($this->request('en', ['lang' => 'de'])));
    }

    public function testOfflinePageIsShippedInEverySupportedLocale(): void
    {
        foreach (config(App::class)->supportedLocales as $locale) {
            $this->assertArrayHasKey(
                $locale,
                Maintenance::DEFAULT_COPY,
                "the offline page has no built-in copy for '{$locale}'",
            );
        }
    }

    public function testOfflinePageRendersInTheRequestedLanguage(): void
    {
        $html = Maintenance::render($this->request('en', ['lang' => 'ja']));

        $this->assertStringContainsString('<html lang="ja"', $html);
        $this->assertStringContainsString('<h1>', $html);
        // Self-contained by design: nothing to fetch, so it draws even when the
        // asset build or the database is broken.
        $this->assertStringNotContainsString('<script src', $html);
    }

    private function request(string $path, array $query = []): IncomingRequest
    {
        $config  = config(App::class);
        $request = new IncomingRequest($config, new SiteURI($config, $path), null, new UserAgent());
        $request->setGlobal('get', $query);

        return $request;
    }

    /** Leave no lever set behind — they are process- and file-scoped. */
    private function clearLevers(): void
    {
        if (is_file(Maintenance::flagPath())) {
            unlink(Maintenance::flagPath());
        }

        unset($_SERVER['maintenance.enabled'], $_ENV['maintenance.enabled']);
    }
}
