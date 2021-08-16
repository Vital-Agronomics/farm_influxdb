<?php

namespace Drupal\farm_influxdb\Plugin\DataStream\DataStreamType;

use Drupal\data_stream\Entity\DataStreamInterface;
use Drupal\data_stream\Plugin\DataStream\DataStreamType\DataStreamTypeBase;
use Drupal\farm_influxdb\InfluxdbServerClient;
use Drupal\farm_influxdb\InfluxdbServerClientFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * A base class for influxdb data streams.
 */
abstract class InfluxdbBase extends DataStreamTypeBase implements InfluxdbDataStreamTypeInterface {

  /**
   * The influxdb server client factory service.
   *
   * @var \Drupal\farm_influxdb\InfluxdbServerClientFactory
   */
  protected $influxdbServerClientFactory;

  /**
   * Constructs an InfluxBase object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\farm_influxdb\InfluxdbServerClientFactory $influx_server_client_factory
   *   The influxdb server client factory service.
   */
  public function __construct(array $configuration, string $plugin_id, $plugin_definition, InfluxdbServerClientFactory $influx_server_client_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->influxdbServerClientFactory = $influx_server_client_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('farm_influxdb.influxdb_server_client_factory'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getClient(DataStreamInterface $data_stream, array $options = []): InfluxdbServerClient {
    return $this->influxdbServerClientFactory->createClientFromDataStream($data_stream);
  }

}
