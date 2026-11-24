<?php

namespace Tests\Feature;

use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

/** The admin area can be moved to a hard-to-guess URL prefix with ADMIN_PATH (production does). */
class AdminPathTest extends TestCase
{
    public function test_the_admin_area_can_live_under_a_secret_prefix(): void
    {
        $this->useAdminPath('kelola-rahasia');
        try {
            $this->assertSame([url('/kelola-rahasia/login'), url('/kelola-rahasia'), url('/kelola-rahasia/accounts')], [route('login'), route('admin.dashboard'), route('admin.accounts')]);
            $routes = app('router')->getRoutes();
            foreach (['/admin', '/admin/login', '/admin/accounts'] as $oldUrl) {
                try {
                    $routes->match(Request::create($oldUrl));
                    $this->fail($oldUrl.' should no longer exist');
                } catch (NotFoundHttpException) {
                    // Expected: the old admin URLs answer 404.
                }
            }
            // Guests opening the secret admin URL are sent to the secret login page.
            $this->get('/kelola-rahasia/accounts')->assertRedirect(route('login'));
        } finally {
            $this->useAdminPath(null);
        }
    }

    private function useAdminPath(?string $path): void
    {
        if ($path === null) {
            putenv('ADMIN_PATH');
            unset($_ENV['ADMIN_PATH'], $_SERVER['ADMIN_PATH']);
        } else {
            putenv('ADMIN_PATH='.$path);
            $_ENV['ADMIN_PATH'] = $_SERVER['ADMIN_PATH'] = $path;
        }
        $this->refreshApplication();
    }
}
