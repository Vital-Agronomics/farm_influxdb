<?php

namespace Drupal\farm_influxdb\Plugin\DataStream\DataStreamType;

use Drupal\data_stream\Entity\DataStreamInterface;
use Drupal\farm_influxdb\InfluxdbServerClient;

/**
 * An interface for influx data stream types.
 */
interface InfluxdbDataStreamTypeInterface {

  /**
   * Helper method to retrieve an influxdb client for a given data stream.
   *
   * @param \Drupal\data_stream\Entity\DataStreamInterface $data_stream
   *   The data stream entity.
   * @param array $options
   *   Optional client options. These will override the server config.
   *
   * @return \Drupal\farm_influxdb\InfluxdbServerClient
   *   The InfluxdbServerClient.
   */
  public function getClient(DataStreamInterface $data_stream, array $options = []): InfluxdbServerClient;

}
