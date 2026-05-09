<?php

declare(strict_types=1);

use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\Repositories\MemoryRepository;

return [

    'default_connection' => env('MQTT_DEFAULT_CONNECTION', 'mqtt_pub'),

    'connections' => [

        'mqtt_pub' => [
            'host' => env('PUB_MQTT_HOST'),
            'port' => env('PUB_MQTT_PORT', 1883),
            'protocol' => MqttClient::MQTT_3_1,
            'client_id' => env('PUB_MQTT_CLIENT_ID'),
            'use_clean_session' => env('PUB_MQTT_CLEAN_SESSION', true),
            'enable_logging' => env('PUB_MQTT_ENABLE_LOGGING', true),
            'log_channel' => env('PUB_MQTT_LOG_CHANNEL', null),
            'repository' => MemoryRepository::class,
            'connection_settings' => [
                'tls' => [
                    'enabled' => env('PUB_MQTT_TLS_ENABLED', false),
                    'allow_self_signed_certificate' => env('PUB_MQTT_TLS_ALLOW_SELF_SIGNED_CERT', false),
                    'verify_peer' => env('PUB_MQTT_TLS_VERIFY_PEER', true),
                    'verify_peer_name' => env('PUB_MQTT_TLS_VERIFY_PEER_NAME', true),
                    'ca_file' => env('PUB_MQTT_TLS_CA_FILE'),
                    'ca_path' => env('PUB_MQTT_TLS_CA_PATH'),
                    'client_certificate_file' => env('PUB_MQTT_TLS_CLIENT_CERT_FILE'),
                    'client_certificate_key_file' => env('PUB_MQTT_TLS_CLIENT_CERT_KEY_FILE'),
                    'client_certificate_key_passphrase' => env('PUB_MQTT_TLS_CLIENT_CERT_KEY_PASSPHRASE'),
                    'alpn' => env('PUB_MQTT_TLS_ALPN'),
                ],
                'auth' => [
                    'username' => env('PUB_MQTT_AUTH_USERNAME'),
                    'password' => env('PUB_MQTT_AUTH_PASSWORD'),
                ],
                'last_will' => [
                    'topic' => env('PUB_MQTT_LAST_WILL_TOPIC'),
                    'message' => env('PUB_MQTT_LAST_WILL_MESSAGE'),
                    'quality_of_service' => env('PUB_MQTT_LAST_WILL_QUALITY_OF_SERVICE', 0),
                    'retain' => env('PUB_MQTT_LAST_WILL_RETAIN', false),
                ],
                'connect_timeout' => env('PUB_MQTT_CONNECT_TIMEOUT', 60),
                'socket_timeout' => env('PUB_MQTT_SOCKET_TIMEOUT', 5),
                'resend_timeout' => env('PUB_MQTT_RESEND_TIMEOUT', 10),
                'keep_alive_interval' => env('PUB_MQTT_KEEP_ALIVE_INTERVAL', 10),
                'auto_reconnect' => [
                    'enabled' => env('PUB_MQTT_AUTO_RECONNECT_ENABLED', false),
                    'max_reconnect_attempts' => env('PUB_MQTT_AUTO_RECONNECT_MAX_RECONNECT_ATTEMPTS', 3),
                    'delay_between_reconnect_attempts' => env('PUB_MQTT_AUTO_RECONNECT_DELAY_BETWEEN_RECONNECT_ATTEMPTS', 0),
                ],
            ],
        ],

        'mqtt_sub' => [
            'host' => env('SUB_MQTT_HOST'),
            'port' => env('SUB_MQTT_PORT', 1883),
            'protocol' => MqttClient::MQTT_3_1,
            'client_id' => env('SUB_MQTT_CLIENT_ID'),
            'use_clean_session' => env('SUB_MQTT_CLEAN_SESSION', true),
            'enable_logging' => env('SUB_MQTT_ENABLE_LOGGING', true),
            'log_channel' => env('SUB_MQTT_LOG_CHANNEL', null),
            'repository' => MemoryRepository::class,
            'connection_settings' => [
                'tls' => [
                    'enabled' => env('SUB_MQTT_TLS_ENABLED', false),
                    'allow_self_signed_certificate' => env('SUB_MQTT_TLS_ALLOW_SELF_SIGNED_CERT', false),
                    'verify_peer' => env('SUB_MQTT_TLS_VERIFY_PEER', true),
                    'verify_peer_name' => env('SUB_MQTT_TLS_VERIFY_PEER_NAME', true),
                    'ca_file' => env('SUB_MQTT_TLS_CA_FILE'),
                    'ca_path' => env('SUB_MQTT_TLS_CA_PATH'),
                    'client_certificate_file' => env('SUB_MQTT_TLS_CLIENT_CERT_FILE'),
                    'client_certificate_key_file' => env('SUB_MQTT_TLS_CLIENT_CERT_KEY_FILE'),
                    'client_certificate_key_passphrase' => env('SUB_MQTT_TLS_CLIENT_CERT_KEY_PASSPHRASE'),
                    'alpn' => env('SUB_MQTT_TLS_ALPN'),
                ],
                'auth' => [
                    'username' => env('SUB_MQTT_AUTH_USERNAME'),
                    'password' => env('SUB_MQTT_AUTH_PASSWORD'),
                ],
                'last_will' => [
                    'topic' => env('SUB_MQTT_LAST_WILL_TOPIC'),
                    'message' => env('SUB_MQTT_LAST_WILL_MESSAGE'),
                    'quality_of_service' => env('SUB_MQTT_LAST_WILL_QUALITY_OF_SERVICE', 0),
                    'retain' => env('SUB_MQTT_LAST_WILL_RETAIN', false),
                ],
                'connect_timeout' => env('SUB_MQTT_CONNECT_TIMEOUT', 60),
                'socket_timeout' => env('SUB_MQTT_SOCKET_TIMEOUT', 5),
                'resend_timeout' => env('SUB_MQTT_RESEND_TIMEOUT', 10),
                'keep_alive_interval' => env('SUB_MQTT_KEEP_ALIVE_INTERVAL', 10),
                'auto_reconnect' => [
                    'enabled' => env('SUB_MQTT_AUTO_RECONNECT_ENABLED', false),
                    'max_reconnect_attempts' => env('SUB_MQTT_AUTO_RECONNECT_MAX_RECONNECT_ATTEMPTS', 3),
                    'delay_between_reconnect_attempts' => env('SUB_MQTT_AUTO_RECONNECT_DELAY_BETWEEN_RECONNECT_ATTEMPTS', 0),
                ],
            ],
        ],
    ],
];