<?php

test('the API health endpoint returns application status', function () {
    $response = $this->getJson('/api/health');

    $response->assertOk()
        ->assertJson([
            'status' => 'ok',
            'app' => 'PatchNotesPublisher',
        ]);
});
