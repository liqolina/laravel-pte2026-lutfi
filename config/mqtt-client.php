<?php

declare(strict_types=1);

use PhpMqtt\Client\MqttClient;
use PhpMqtt\Client\Repositories\MemoryRepository;

return [

    'default_connection' => env('MQTT_DEFAULT_CONNECTION', 'mosquitto1'),

    'connections' => [

        'mosquitto1' => [
            'host' => env('MQTT1_HOST', 'mosquitto1'),
            'port' => env('MQTT1_PORT', 1883),
            'protocol' => MqttClient::MQTT_3_1,
            'client_id' => env('MQTT1_CLIENT_ID'),
            'use_clean_session' => env('MQTT1_CLEAN_SESSION', true),
            'enable_logging' => env('MQTT1_ENABLE_LOGGING', true),
            'log_channel' => env('MQTT1_LOG_CHANNEL', null),
            'repository' => MemoryRepository::class,

            'connection_settings' => [
                'tls' => [
                    'enabled' => env('MQTT1_TLS_ENABLED', false),
                    'allow_self_signed_certificate' => env('MQTT1_TLS_ALLOW_SELF_SIGNED_CERT', false),
                    'verify_peer' => env('MQTT1_TLS_VERIFY_PEER', true),
                    'verify_peer_name' => env('MQTT1_TLS_VERIFY_PEER_NAME', true),
                    'ca_file' => env('MQTT1_TLS_CA_FILE'),
                    'ca_path' => env('MQTT1_TLS_CA_PATH'),
                    'client_certificate_file' => env('MQTT1_TLS_CLIENT_CERT_FILE'),
                    'client_certificate_key_file' => env('MQTT1_TLS_CLIENT_CERT_KEY_FILE'),
                    'client_certificate_key_passphrase' => env('MQTT1_TLS_CLIENT_CERT_KEY_PASSPHRASE'),
                    'alpn' => env('MQTT1_TLS_ALPN'),
                ],
                'auth' => [
                    'username' => env('MQTT1_AUTH_USERNAME'),
                    'password' => env('MQTT1_AUTH_PASSWORD'),
                ],
                'last_will' => [
                    'topic' => env('MQTT1_LAST_WILL_TOPIC'),
                    'message' => env('MQTT1_LAST_WILL_MESSAGE'),
                    'quality_of_service' => env('MQTT1_LAST_WILL_QUALITY_OF_SERVICE', 0),
                    'retain' => env('MQTT1_LAST_WILL_RETAIN', false),
                ],
                'connect_timeout' => env('MQTT1_CONNECT_TIMEOUT', 60),
                'socket_timeout' => env('MQTT1_SOCKET_TIMEOUT', 5),
                'resend_timeout' => env('MQTT1_RESEND_TIMEOUT', 10),
                'keep_alive_interval' => env('MQTT1_KEEP_ALIVE_INTERVAL', 10),
                'auto_reconnect' => [
                    'enabled' => env('MQTT1_AUTO_RECONNECT_ENABLED', false),
                    'max_reconnect_attempts' => env('MQTT1_AUTO_RECONNECT_MAX_RECONNECT_ATTEMPTS', 3),
                    'delay_between_reconnect_attempts' => env('MQTT1_AUTO_RECONNECT_DELAY_BETWEEN_RECONNECT_ATTEMPTS', 0),
                ],
            ],
        ],

        'mosquitto2' => [
            'host' => env('MQTT2_HOST', 'mosquitto2'),
            'port' => env('MQTT2_PORT', 1883),
            'protocol' => MqttClient::MQTT_3_1,
            'client_id' => env('MQTT2_CLIENT_ID'),
            'use_clean_session' => env('MQTT2_CLEAN_SESSION', true),
            'enable_logging' => env('MQTT2_ENABLE_LOGGING', true),
            'log_channel' => env('MQTT2_LOG_CHANNEL', null),
            'repository' => MemoryRepository::class,

            'connection_settings' => [
                'tls' => [
                    'enabled' => env('MQTT2_TLS_ENABLED', false),
                    'allow_self_signed_certificate' => env('MQTT2_TLS_ALLOW_SELF_SIGNED_CERT', false),
                    'verify_peer' => env('MQTT2_TLS_VERIFY_PEER', true),
                    'verify_peer_name' => env('MQTT2_TLS_VERIFY_PEER_NAME', true),
                    'ca_file' => env('MQTT2_TLS_CA_FILE'),
                    'ca_path' => env('MQTT2_TLS_CA_PATH'),
                    'client_certificate_file' => env('MQTT2_TLS_CLIENT_CERT_FILE'),
                    'client_certificate_key_file' => env('MQTT2_TLS_CLIENT_CERT_KEY_FILE'),
                    'client_certificate_key_passphrase' => env('MQTT2_TLS_CLIENT_CERT_KEY_PASSPHRASE'),
                    'alpn' => env('MQTT2_TLS_ALPN'),
                ],
                'auth' => [
                    'username' => env('MQTT2_AUTH_USERNAME'),
                    'password' => env('MQTT2_AUTH_PASSWORD'),
                ],
                'last_will' => [
                    'topic' => env('MQTT2_LAST_WILL_TOPIC'),
                    'message' => env('MQTT2_LAST_WILL_MESSAGE'),
                    'quality_of_service' => env('MQTT2_LAST_WILL_QUALITY_OF_SERVICE', 0),
                    'retain' => env('MQTT2_LAST_WILL_RETAIN', false),
                ],
                'connect_timeout' => env('MQTT2_CONNECT_TIMEOUT', 60),
                'socket_timeout' => env('MQTT2_SOCKET_TIMEOUT', 5),
                'resend_timeout' => env('MQTT2_RESEND_TIMEOUT', 10),
                'keep_alive_interval' => env('MQTT2_KEEP_ALIVE_INTERVAL', 10),
                'auto_reconnect' => [
                    'enabled' => env('MQTT2_AUTO_RECONNECT_ENABLED', false),
                    'max_reconnect_attempts' => env('MQTT2_AUTO_RECONNECT_MAX_RECONNECT_ATTEMPTS', 3),
                    'delay_between_reconnect_attempts' => env('MQTT2_AUTO_RECONNECT_DELAY_BETWEEN_RECONNECT_ATTEMPTS', 0),
                ],
            ],
        ],

    ],

];