<?php


test('its_get_use_trash_config', function () {
    $this->assertTrue(config('model-operations.use_trash'));
});

test('return correct ids array', function () {
    $data = [
        ['id' => 94646116, 'name' => 'Item 1'],
        ['id' => 9544964, 'name' => 'Item 2'],
        ['id' => 461646, 'name' => 'Item 3'],
    ];

    $ids = array_column($data, 'id');

    $this->assertEquals([94646116, 9544964, 461646], $ids);
});