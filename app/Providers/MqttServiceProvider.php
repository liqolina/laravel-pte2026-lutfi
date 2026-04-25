<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use PhpMqtt\Client\ConnectionSettings;
use PhpMqtt\Client\MqttClient;

class MqttServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        foreach (['mqtt_pub', 'mqtt_sub'] as $connectionName) {
            // Kita bind konfigurasi settings agar bisa di-resolve dengan mudah di tempat lain
            $this->app->singleton($connectionName . '_settings', function ($app) use ($connectionName) {
                $config = $app['config']->get("mqtt-client.connections.{$connectionName}");

                if (!$config) {
                    throw new \InvalidArgumentException("MQTT connection configuration [{$connectionName}] is missing.");
                }

                $settings = new ConnectionSettings();
                
                // FIXED: Menggunakan setCredentials() sesuai dokumentasi asli php-mqtt/client
                if (!empty($config['connection_settings']['auth']['username'])) {
                    $settings->setCredentials(
                        (string) $config['connection_settings']['auth']['username'],
                        (string) $config['connection_settings']['auth']['password']
                    );
                }

                // Timeouts
                $settings->setConnectTimeout((int) $config['connection_settings']['connect_timeout']);
                $settings->setSocketTimeout((int) $config['connection_settings']['socket_timeout']);
                $settings->setKeepAliveInterval((int) $config['connection_settings']['keep_alive_interval']);

                // TLS
                $tls = $config['connection_settings']['tls'];
                if ($tls['enabled']) {
                    $settings->setUseTls(true);
                    $settings->setTlsVerifyPeer($tls['verify_peer']);
                    $settings->setTlsVerifyPeerName($tls['verify_peer_name']);
                    if ($tls['ca_file']) $settings->setTlsCertificateAuthorityFile($tls['ca_file']);
                    if ($tls['ca_path']) $settings->setTlsCertificateAuthorityPath($tls['ca_path']);
                    if ($tls['client_certificate_file']) $settings->setTlsClientCertificateFile($tls['client_certificate_file']);
                    if ($tls['client_certificate_key_file']) $settings->setTlsClientCertificateKeyFile($tls['client_certificate_key_file']);
                    if ($tls['client_certificate_key_passphrase']) $settings->setTlsClientCertificateKeyPassphrase($tls['client_certificate_key_passphrase']);
                    if ($tls['alpn']) $settings->setTlsAlpn($tls['alpn']);
                }

                // Last Will
                $will = $config['connection_settings']['last_will'];
                if (!empty($will['topic'])) {
                    $settings->setLastWill($will['topic'], $will['message'], (int)$will['quality_of_service'], $will['retain']);
                }

                return $settings;
            });

            // Bind MqttClient Utama
            $this->app->singleton($connectionName, function ($app) use ($connectionName) {
                $config = $app['config']->get("mqtt-client.connections.{$connectionName}");

                return new MqttClient(
                    $config['host'],
                    (int) $config['port'],
                    $config['client_id'],
                    $config['protocol'],
                    new $config['repository']
                );
            });
        }
    }
}