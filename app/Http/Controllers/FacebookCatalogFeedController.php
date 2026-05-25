<?php

namespace App\Http\Controllers;

use App\Services\FacebookCatalogFeedService;
use Illuminate\Http\Response;

class FacebookCatalogFeedController extends Controller
{
    public function __invoke(FacebookCatalogFeedService $feedService): Response
    {
        return response($feedService->xml(), 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=1800',
        ]);
    }
}
