<?php

use App\Http\Controllers\Marketing\ContactController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:60,1')->group(function () {
    Route::view('/', 'marketing.home')->name('home');
    Route::redirect('/home-page', '/')->name('marketing.home');
    Route::view('/features', 'marketing.features')->name('marketing.features');
    Route::view('/how-it-works', 'marketing.how-it-works')->name('marketing.how-it-works');
    Route::view('/pricing', 'marketing.pricing')->name('marketing.pricing');
    Route::view('/about', 'marketing.about')->name('marketing.about');
    Route::view('/privacy', 'marketing.privacy')->name('marketing.privacy');
    Route::view('/terms', 'marketing.terms')->name('marketing.terms');

    Route::get('/contact', function () {
        return view('marketing.contact');
    })->name('marketing.contact');

    Route::post('/contact', [ContactController::class, 'store'])
        ->name('marketing.contact.store')
        ->middleware('throttle:5,1');
});

Route::get('/sitemap.xml', function () {
    $locale = app()->getLocale();
    $routes = [
        ['name' => 'marketing.home',        'priority' => '1.0',  'freq' => 'weekly'],
        ['name' => 'marketing.features',    'priority' => '0.9',  'freq' => 'monthly'],
        ['name' => 'marketing.how-it-works', 'priority' => '0.8',  'freq' => 'monthly'],
        ['name' => 'marketing.pricing',     'priority' => '0.9',  'freq' => 'weekly'],
        ['name' => 'marketing.about',       'priority' => '0.7',  'freq' => 'monthly'],
        ['name' => 'marketing.contact',     'priority' => '0.6',  'freq' => 'monthly'],
        ['name' => 'marketing.privacy',     'priority' => '0.3',  'freq' => 'yearly'],
        ['name' => 'marketing.terms',       'priority' => '0.3',  'freq' => 'yearly'],
    ];

    $xml = '<?xml version="1.0" encoding="UTF-8"?>';
    $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">';

    foreach ($routes as $r) {
        foreach (['ar', 'en'] as $lang) {
            $url = url(route($r['name'], [], false));
            $xml .= '<url>';
            $xml .= '<loc>'.$url.'</loc>';
            $xml .= '<xhtml:link rel="alternate" hreflang="ar" href="'.$url.'"/>';
            $xml .= '<xhtml:link rel="alternate" hreflang="en" href="'.$url.'"/>';
            $xml .= '<changefreq>'.$r['freq'].'</changefreq>';
            $xml .= '<priority>'.$r['priority'].'</priority>';
            $xml .= '</url>';
            break; // same URL for both locales (session-based)
        }
    }

    $xml .= '</urlset>';

    return response($xml, 200)->header('Content-Type', 'application/xml');
})->name('sitemap');

Route::get('/robots.txt', function () {
    return response(
        "User-agent: *\nAllow: /\nDisallow: /dashboard\nDisallow: /onboarding\nSitemap: ".route('sitemap'),
        200
    )->header('Content-Type', 'text/plain');
})->name('robots');
