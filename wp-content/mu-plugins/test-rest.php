<?php

add_action('rest_api_init', function () {

    register_rest_route(
        'test/v1',
        '/ping',
        [
            'methods' => 'GET',
            'callback' => function () {
                return 'pong';
            },
            'permission_callback' => '__return_true'
        ]
    );

});