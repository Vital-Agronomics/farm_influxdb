# farm_influxdb

farmOS integration with [InflxuDB](https://www.influxdata.com/)

This module is an add-on for the [farmOS](http://drupal.org/project/farm)
distribution.

## Getting started

### Installation

Install as you would normally install a contributed drupal module.

Add the Github repo as a composer repository:
```json
  # Project composer.json file:
  "repositories": {
      "drupal": {
        "type": "composer",
        "url": "https://packages.drupal.org/8"
      },
      "farm_influxdb": {
        "type": "vcs",
        "url": "https://github.com/Vital-Agronomics/farm_influxdb.git"
      }
  },
  # Dependencies, etc...
```

Require the module:
```
composer require vital-agronomics/farm_influxdb:^2
```

### Configuration

Each InfluxDB server must be configured via the InfluxDB settings form. Example
configuration:

```yaml
# farm_influxdb.settings.yml
servers:
  -
    id: influx_server_1
    label: 'InfluxDB server 1'
    url: 'https://us-central1-1.gcp.cloud2.influxdata.com/'
    # Access token
    token: token
    # Optional bucket
    bucket: bucket
    # Optional org
    org: org_id
    verifySSL: true
    timeout: 60
```

## Features

### Config

Provides a configuration schema and settings form for configuring connections
with InflxuDB servers. Users must have the `administer farm influxdb`
permission to access the configuration form. The form will attempt to validate
the server configuration.

### Data stream

Provides an `abstract` `InfluxDbBase` class that can be used to start building
custom data stream types that are meant to connect to InfluxDB.

The `farm_influxdb` third party setting on the `data_stream_type` may be used
to configure which InfluxDB server to use for data streams of the given type:
```yaml
langcode: en
status: true
dependencies:
  enforced:
    module:
      - my_module
id: influx_data_stream
label: InfluxDB data stream
description: 'InfluxDB data stream'
third_party_settings:
  farm_influxdb:
    server_id: influx_server_1
```

## Maintainers

Current maintainers:
- Paul Weidner [@paul121](https://github.com/paul121)

## Sponsors
This project has been sponsored by:
- [Vital Agronomics](https://vital-agronomics.com)
