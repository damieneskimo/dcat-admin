<?php

namespace Dcat\Admin\Http\Auth;

use Dcat\Admin\Admin;
use Dcat\Admin\Layout\Content;
use Dcat\Admin\Models\Role;
use Dcat\Admin\Support\Helper;
use Illuminate\Contracts\Support\Arrayable;
use Symfony\Component\HttpFoundation\Response;

class Permission
{
    protected static $errorHandler;

    /**
     * Check permission.
     *
     * @param string|array|Arrayable $permission
     *
     * @return true|void
     */
    public static function check($permission)
    {
        if (static::isAdministrator()) {
            return true;
        }

        if (is_array($permission) || $permission instanceof Arrayable) {
            collect($permission)->each(function ($permission) {
                static::check($permission);
            });

            return true;
        }

        if (Admin::user()->hasNoPermission($permission)) {
            static::error();
        }
    }

    /**
     * Roles allowed to access.
     *
     * @param string|array|Arrayable $roles
     *
     * @return true|void
     */
    public static function allow($roles)
    {
        if (static::isAdministrator()) {
            return true;
        }

        if (! Admin::user()->inRoles($roles)) {
            static::error();
        }
    }

    /**
     * Don't check permission.
     *
     * @return bool
     */
    public static function free()
    {
        return true;
    }

    /**
     * Roles denied to access.
     *
     * @param string|array|Arrayable $roles
     *
     * @return true|void
     */
    public static function deny($roles)
    {
        if (static::isAdministrator()) {
            return true;
        }

        if (Admin::user()->inRoles($roles)) {
            static::error();
        }
    }

    /**
     * Send error response page.
     *
     * @throws \Dcat\Admin\Exception\RespondException
     */
    public static function error()
    {
        if ($error = static::$errorHandler) {
            admin_exit($error());
        }

        if (Helper::isAjaxRequest()) {
            abort(403, trans('admin.deny'));
        }

        admin_exit(
            Content::make()->withError(trans('admin.deny'))
        );
    }

    /**
     * If current user is administrator.
     *
     * @return mixed
     */
    public static function isAdministrator()
    {
        return ! config('admin.permission.enable') || Admin::user()->isRole(Role::ADMINISTRATOR);
    }

    /**
     * @param \Closure $callback
     *
     * @return void
     */
    public static function registerErrorHandler(\Closure $callback)
    {
        static::$errorHandler = $callback;
    }
}
