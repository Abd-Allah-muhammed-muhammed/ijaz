<?php

namespace Modules\Opportunity\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Modules\Opportunity\Models\OpportunityOffer;

class OfferController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:delete opportunities', only: ['destroy']),
        ];
    }

    public function destroy(OpportunityOffer $offer): RedirectResponse
    {
        $offer->delete();

        return back()->with('success', __('opportunity.offer_deleted_successfully'));
    }
}
