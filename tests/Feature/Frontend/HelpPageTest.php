<?php

beforeEach(function () {
    withoutOrdersLocaleMiddleware();
});

test('help page renders successfully', function () {
    $this->get(route('help'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Frontend/Help')
            ->where('app.shell', 'web')
        );
});
