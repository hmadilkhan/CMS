<?php

namespace App\Http\Controllers\Concerns;

use App\Services\OperationsConsoleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * A save made inside the Operations console comes back to the console.
 *
 * The screens are reached two ways - on their own URL and as a console panel -
 * and a redirect that always went to the screen's own page would throw the user
 * out of the console on every save. The form says which section it was drawn
 * in; `returnUrl()` only trusts a key the console really has.
 */
trait ReturnsToOperationsConsole
{
    protected function backToOperations(Request $request, string $fallbackRoute): RedirectResponse
    {
        return redirect(app(OperationsConsoleService::class)->returnUrl(
            $request->user(),
            $request->input('ops_section'),
            $fallbackRoute,
        ));
    }
}
