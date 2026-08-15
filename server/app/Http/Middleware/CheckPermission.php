<?php

namespace App\Http\Middleware;

use App\Enums\ModuleEnum;
use App\Enums\PermissionAction;
use App\Guard\AuthGuard;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(
        Request $request,
        Closure $next,
        string $module
    ): Response {
        $action = match ($request->route()->getActionMethod()) {
            'index', 'show' => PermissionAction::Read,
            'store' => PermissionAction::Create,
            'update' => PermissionAction::Update,
            default => null,
        };

        if (!$action) {
            abort(403, 'Invalid permission action.');
        }

        $moduleEnum = ModuleEnum::from($module);

        AuthGuard::requireModule(
            $request->user(),
            false,
            $moduleEnum,
            $action
        );

        return $next($request);
    }
}
