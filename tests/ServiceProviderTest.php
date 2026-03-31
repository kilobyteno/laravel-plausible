<?php

it('merges plausible config', function () {
    expect(config('plausible.api_url'))->toBeString()
        ->and(config('plausible.api_key'))->not->toBeNull();
});
