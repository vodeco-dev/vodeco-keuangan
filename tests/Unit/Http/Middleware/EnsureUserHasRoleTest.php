<?php

namespace Tests\Unit\Http\Middleware;

use App\Enums\Role;
use App\Http\Middleware\EnsureUserHasRole;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class EnsureUserHasRoleTest extends TestCase
{
    private EnsureUserHasRole $middleware;

    protected function setUp(): void
    {
        parent::setUp();

        $this->middleware = new EnsureUserHasRole();
    }

    public function test_allows_accountant_role_enum_instance(): void
    {
        $request = Request::create('/', 'GET');
        $request->setUserResolver(function () {
            return new class {
                public Role $role;

                public function __construct()
                {
                    $this->role = Role::ACCOUNTANT;
                }
            };
        });

        $response = $this->middleware->handle(
            $request,
            fn () => new Response('ok'),
            'admin',
            'accountant',
        );

        $this->assertSame('ok', $response->getContent());
    }

    public function test_allows_accountant_role_string_with_mixed_case(): void
    {
        $request = Request::create('/', 'GET');
        $request->setUserResolver(function () {
            return new class {
                public string $role = 'Accountant';
            };
        });

        $response = $this->middleware->handle(
            $request,
            fn () => new Response('ok'),
            'admin',
            'accountant',
        );

        $this->assertSame('ok', $response->getContent());
    }

    public function test_rejects_user_without_required_role(): void
    {
        $request = Request::create('/', 'GET');
        $request->setUserResolver(function () {
            return new class {
                public string $role = 'customer_service';
            };
        });

        $this->expectException(HttpException::class);

        try {
            $this->middleware->handle(
                $request,
                fn () => new Response('ok'),
                'admin',
                'accountant',
            );
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());

            throw $exception;
        }
    }

    public function test_rejects_when_user_is_missing(): void
    {
        $request = Request::create('/', 'GET');

        $this->expectException(HttpException::class);

        try {
            $this->middleware->handle($request, fn () => new Response('ok'), 'admin');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());

            throw $exception;
        }
    }
}

