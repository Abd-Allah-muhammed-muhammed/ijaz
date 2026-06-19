<?php

use Lib\SMS\SMSManager;
use Tests\TestCase;

uses(TestCase::class);

test('sms manager falls back to testing when the configured driver is blank', function (): void {
    config()->set('sms.driver', '');

    $manager = new SMSManager(app());

    expect($manager->getDefaultDriver())->toBe('testing');
});
