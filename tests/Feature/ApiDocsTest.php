<?php

/*
| La documentación interactiva (/docs/api) la genera Scramble leyendo el código.
| Este test falla si algún cambio impide generarla.
*/

it('generates the OpenAPI document for every v1 route', function () {
    $path = tempnam(sys_get_temp_dir(), 'openapi');

    $this->artisan('scramble:export', ['--path' => $path])->assertSuccessful();

    $document = json_decode(file_get_contents($path), true);
    unlink($path);

    expect($document['paths'])->toHaveKeys(['/auth/login', '/bookings', '/professionals'])
        ->and($document['paths']['/auth/me']['get']['responses']['200']['content']['application/json']['schema']['properties'])
        ->toHaveKeys(['success', 'message', 'data']);
});
