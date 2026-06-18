<?php

test('home page renders in Arabic', function () {
    $response = $this->withSession(['locale' => 'ar'])->get('/');

    $response->assertStatus(200);
    $response->assertSee('وكيل مبيعات بالذكاء الاصطناعي', false);
});

test('home page renders in English', function () {
    $response = $this->withSession(['locale' => 'en'])->get('/');

    $response->assertStatus(200);
    $response->assertSee('An AI sales agent', false);
});

test('features page renders in both locales', function () {
    $this->withSession(['locale' => 'ar'])->get('/features')->assertStatus(200)->assertSee('المميزات', false);
    $this->withSession(['locale' => 'en'])->get('/features')->assertStatus(200)->assertSee('Features', false);
});

test('how it works page renders in both locales', function () {
    $this->withSession(['locale' => 'ar'])->get('/how-it-works')->assertStatus(200)->assertSee('رحلة العميل', false);
    $this->withSession(['locale' => 'en'])->get('/how-it-works')->assertStatus(200)->assertSee('buyer journey', false);
});

test('pricing page renders in both locales', function () {
    $this->withSession(['locale' => 'ar'])->get('/pricing')->assertStatus(200)->assertSee('أسعار واضحة', false);
    $this->withSession(['locale' => 'en'])->get('/pricing')->assertStatus(200)->assertSee('Clear pricing', false);
});

test('about page renders in both locales', function () {
    $this->withSession(['locale' => 'ar'])->get('/about')->assertStatus(200)->assertSee('من نحن', false);
    $this->withSession(['locale' => 'en'])->get('/about')->assertStatus(200)->assertSee('About Us', false);
});

test('contact page renders in both locales', function () {
    $this->withSession(['locale' => 'ar'])->get('/contact')->assertStatus(200)->assertSee('تواصل معنا', false);
    $this->withSession(['locale' => 'en'])->get('/contact')->assertStatus(200)->assertSee('Contact Us', false);
});

test('privacy page renders in both locales', function () {
    $this->withSession(['locale' => 'ar'])->get('/privacy')->assertStatus(200)->assertSee('سياسة الخصوصية', false);
    $this->withSession(['locale' => 'en'])->get('/privacy')->assertStatus(200)->assertSee('Privacy Policy', false);
});

test('terms page renders in both locales', function () {
    $this->withSession(['locale' => 'ar'])->get('/terms')->assertStatus(200)->assertSee('الشروط والأحكام', false);
    $this->withSession(['locale' => 'en'])->get('/terms')->assertStatus(200)->assertSee('Terms', false);
});

test('sitemap returns xml', function () {
    $response = $this->get('/sitemap.xml');

    $response->assertStatus(200);
    $response->assertHeader('Content-Type', 'application/xml');
    $response->assertSee('urlset', false);
});

test('robots returns text', function () {
    $response = $this->get('/robots.txt');

    $response->assertStatus(200);
    $response->assertSee('User-agent', false);
    $response->assertSee('Sitemap', false);
});

test('home page hero CTA links to register', function () {
    $response = $this->withSession(['locale' => 'en'])->get('/');

    $response->assertStatus(200);
    $response->assertSee('href="'.route('register').'"', false);
});

test('pricing CTAs link to register with plan param', function () {
    $response = $this->withSession(['locale' => 'en'])->get('/pricing');

    $response->assertStatus(200);
    $response->assertSee('plan=starter', false);
    $response->assertSee('plan=growth', false);
    $response->assertSee('plan=enterprise', false);
});

test('contact form stores message and redirects with success', function () {
    $response = $this->withSession(['locale' => 'en'])->post('/contact', [
        'name' => 'Ahmed Ali',
        'company' => 'Maadi Properties',
        'email' => 'ahmed@example.com',
        'phone' => '+201234567890',
        'message' => 'I would like to know more about Wakeel pricing.',
    ]);

    $response->assertRedirect('/contact');
    $response->assertSessionHas('success');
    $this->assertDatabaseHas('contact_messages', [
        'name' => 'Ahmed Ali',
        'email' => 'ahmed@example.com',
    ]);
});

test('contact form validates required fields', function () {
    $response = $this->post('/contact', []);

    $response->assertSessionHasErrors(['name', 'email', 'message']);
});

test('contact form requires valid email', function () {
    $response = $this->post('/contact', [
        'name' => 'Test User',
        'email' => 'not-an-email',
        'message' => 'Test message with enough characters.',
    ]);

    $response->assertSessionHasErrors(['email']);
});

test('locale switcher switches language and redirects back', function () {
    $response = $this->withSession(['locale' => 'ar'])->get(route('locale.switch', 'en'));

    $response->assertRedirect();
    $this->assertEquals('en', session('locale'));
});

test('ar and en marketing lang files have identical keys', function () {
    $ar = array_keys(require base_path('lang/ar/marketing.php'));
    $en = array_keys(require base_path('lang/en/marketing.php'));

    $missingInEn = array_diff($ar, $en);
    $missingInAr = array_diff($en, $ar);

    expect($missingInEn)->toBeEmpty('Keys in ar but not in en: '.implode(', ', $missingInEn));
    expect($missingInAr)->toBeEmpty('Keys in en but not in ar: '.implode(', ', $missingInAr));
});
