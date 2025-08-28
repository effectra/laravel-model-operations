<?php


test('its_get_use_trash_config', function () {
    $this->assertTrue(config('model-operations.use_trash'));
});

test('return correct ids array', function () {
    $data = [
        ['id' => 1, 'name' => 'Item 1'],
        ['id' => 2, 'name' => 'Item 2'],
        ['id' => 3, 'name' => 'Item 3'],
    ];

    $ids = array_column($data, 'id');

    $this->assertEquals([1, 2, 3], $ids);
});