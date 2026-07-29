<?php

namespace App\Enums;

use Illuminate\Http\Request;

/**
 * Identifies which Inertia frontend shell is serving the current request.
 * Drives `data-app` on <html> and shared `app.shell` for design tokens.
 */
enum FrontendShell: string
{
    case Admin = 'admin';
    case Provider = 'provider';
    case Marketer = 'marketer';
    case Web = 'web';

    public static function fromRequest(Request $request): self
    {
        return match (true) {
            $request->routeIs('dashboard.*') => self::Admin,
            $request->routeIs('provider.*') => self::Provider,
            $request->routeIs('marketer.*') => self::Marketer,
            default => self::Web,
        };
    }
}
