<?php

namespace Drupal\farm_influxdb;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\data_stream\Entity\DataStreamInterface;
use Drupal\data_stream\Entity\DataStreamType;
use Drupal\farm_influxdb\Form\InfluxdbSettingsForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Factory service to create influxdb clients.
 */
class InfluxdbServerClientFactory implements ContainerInjectionInterface {

  const THIRD_PARTY_PROVIDER = 'farm_influxdb';

  /**
   * An array of server config.
   *
   * @var array
   *
   * @see \Drupal\farm_influxdb\Form\InfluxdbSettingsForm
   */
  protected array $serverConfig;

  /**
   * Constructs the InfluxServerClientFactory object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->serverConfig = $config_factory->get(InfluxdbSettingsForm::SETTINGS)->get('servers');
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
    );
  }

  /**
   * Create an InfluxdbServerClient from an existing server configuration.
   *
   * @param string $server_id
   *   The server id to load.
   * @param array $options
   *   Optional client options. These will override the server config.
   *
   * @return \Drupal\farm_influxdb\InfluxdbServerClient
   *   The Influxdb client.
   */
  public function createClientFromServerConfig(string $server_id, array $options = []): InfluxdbServerClient {
    $servers = array_filter($this->serverConfig, function ($config) use ($server_id) {
      return isset($config['id']) && $server_id === $config['id'];
    });

    // Get the first server with the matching ID.
    $server = reset($servers);
    if (empty($server)) {
      throw new \Exception("The influxdb server id \"$server_id\" does not exist. Check the farm_influxdb configuration.");
    }

    // Build an InfluxdbServerClient.
    // Optional options override the server config.
    $final_options = $server + $options;
    return new InfluxdbServerClient($final_options);
  }

  /**
   * Create an InfluxdbServerClient from a given data stream entity.
   *
   * @param \Drupal\data_stream\Entity\DataStreamInterface $data_stream
   *   The data stream entity.
   * @param array $options
   *   Optional client options. These will override the server config.
   *
   * @return \Drupal\farm_influxdb\InfluxdbServerClient
   *   The Influxdb client.
   */
  public function createClientFromDataStream(DataStreamInterface $data_stream, array $options = []): InfluxdbServerClient {

    /** @var \Drupal\data_stream\Entity\DataStreamTypeInterface $data_stream_type */
    $data_stream_type = $data_stream->get('type')->entity;
    return $this->createClientFromDataStreamType($data_stream_type->id(), $options);
  }

  /**
   * Create an InfluxdbServerClietn from a given data stream type ID.
   *
   * @param string $data_stream_type
   *   The data stream type ID.
   * @param array $options
   *   Optional client options. These will override the server config.
   *
   * @return \Drupal\farm_influxdb\InfluxdbServerClient
   *   The Influxdb client.
   */
  public function createClientFromDataStreamType(string $data_stream_type, array $options = []): InfluxdbServerClient {
    $data_stream_type = DataStreamType::load($data_stream_type);

    // Get the configured server_id.
    $server_id = $data_stream_type->getThirdPartySetting(static::THIRD_PARTY_PROVIDER, 'server_id');
    if (empty($server_id)) {
      $data_stream_type_label = $data_stream_type->label();
      throw new \Exception("The \"${data_stream_type_label}\" data stream type does not have an influxdb server id configured. Check the farm_influxdb configuration.");
    }

    return $this->createClientFromServerConfig($server_id, $options);
  }

}
