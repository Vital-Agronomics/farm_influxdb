<?php

namespace Drupal\farm_influxdb\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\farm_influxdb\InfluxdbServerClient;
use InfluxDB2\Service\OrganizationsService;

/**
 * Configuration form for influxdb settings.
 */
class InfluxdbSettingsForm extends ConfigFormBase {

  /**
   * Influxdb settings name.
   *
   * @var string
   */
  const SETTINGS = 'farm_influxdb.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'farm_influxdb_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    // Get the config.
    $config = $this->config(static::SETTINGS);

    // Init servers in the form state.
    if ($form_state->getValue('servers') === NULL) {
      $form_state->setValue('servers', $config->get('servers') ?? []);
    }

    $form['servers'] = [
      '#type' => 'details',
      '#title' => $this->t('Influxdb Servers'),
      '#open' => TRUE,
      '#tree' => TRUE,
      '#prefix' => '<div id="servers-fieldset-wrapper">',
      '#suffix' => '</div>',
    ];

    $form['servers']['add_server'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add server'),
      '#submit' => ['::addServer'],
      '#name' => 'add-server',
      '#ajax' => [
        'callback' => '::updateServers',
        'wrapper' => 'servers-fieldset-wrapper',
      ],
      '#tree' => FALSE,
      '#weight' => 15,
    ];

    $servers = $form_state->getValue('servers');
    foreach ($servers as $delta => $server_config) {

      // Build a details label that includes the server label and ID.
      $label = $server_config['label'];
      $details_title = $label;
      if (!empty($server_config['id'])) {
        $details_title = "$label (${server_config['id']})";
      }

      // Fieldset for the server.
      $form['servers'][$delta] = [
        '#type' => 'details',
        '#title' => $details_title,
        '#open' => TRUE,
      ];

      $form['servers'][$delta]['id'] = [
        '#type' => 'machine_name',
        '#title' => $this->t('Server ID'),
        '#default_value' => $server_config['id'],
      ];

      $form['servers'][$delta]['label'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Label'),
        '#default_value' => $server_config['label'],
        '#required' => TRUE,
      ];

      $form['servers'][$delta]['url'] = [
        '#type' => 'url',
        '#title' => $this->t('URL'),
        '#default_value' => $server_config['url'],
        '#required' => TRUE,
      ];

      $form['servers'][$delta]['token'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Token'),
        '#default_value' => $server_config['token'],
        '#required' => TRUE,
      ];

      $form['servers'][$delta]['org'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Organization'),
        '#default_value' => $server_config['org'],
      ];

      $form['servers'][$delta]['bucket'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Bucket'),
        '#default_value' => $server_config['bucket'],
      ];

      $form['servers'][$delta]['timeout'] = [
        '#type' => 'number',
        '#step' => 1,
        '#title' => $this->t('Timeout'),
        '#default_value' => $server_config['timeout'] ?? 10,
      ];

      $form['servers'][$delta]['verifySSL'] = [
        '#type' => 'checkbox',
        '#title' => $this->t('Verify SSL'),
        '#default_value' => $server_config['verifySSL'] ?? TRUE,
      ];

      // Add button to remove the server.
      $form['servers'][$delta]['test'] = [
        '#type' => 'submit',
        '#value' => $this->t('Test server'),
        '#name' => "test-server-$delta",
        '#submit' => ['::testServer'],
        '#ajax' => [
          'callback' => '::updateServers',
          'wrapper' => 'servers-fieldset-wrapper',
        ],
        '#tree' => FALSE,
      ];

      // Add button to remove the server.
      $form['servers'][$delta]['remove'] = [
        '#type' => 'submit',
        '#value' => $this->t('Remove server'),
        '#submit' => ['::removeServer'],
        '#name' => "remove-server-$delta",
        '#attributes' => [
          'class' => ['button--danger'],
        ],
        '#ajax' => [
          'callback' => '::updateServers',
          'wrapper' => 'servers-fieldset-wrapper',
        ],
        '#tree' => FALSE,
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * Submit handler to add a server.
   */
  public function addServer(array &$form, FormStateInterface $form_state) {

    // Append a server.
    $servers = $form_state->getValue('servers');
    $servers[] = [
      'label' => $this->t('Server @number', ['@number' => count($servers) + 1]),
      'verifySSL' => TRUE,
    ];

    // Update the form.
    $form_state->setValue('servers', $servers);
    $form_state->setRebuild();
  }

  /**
   * Submit handler to remove a server.
   */
  public function removeServer(array &$form, FormStateInterface $form_state) {

    // Determine which server to remove.
    $parents = $form_state->getTriggeringElement()['#array_parents'];
    $delta = $parents[1];

    // Remove the delta from the existing servers.
    $servers = $form_state->getValue('servers');
    unset($servers[$delta]);

    // Update the form.
    $form_state->setValue('servers', $servers);
    $form_state->setRebuild();
  }

  /**
   * Submit handler to test a server configuration.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public function testServer(array &$form, FormStateInterface $form_state) {

    // Determine which server to test.
    $parents = $form_state->getTriggeringElement()['#array_parents'];
    $delta = $parents[1];

    // Remove the delta from the existing servers.
    $servers = $form_state->getValue('servers');
    $test_server = $servers[$delta];

    // Try and query the organizations on the server.
    // The $client->health() method only works for OSS, not influx cloud.
    $client = new InfluxdbServerClient($test_server);
    $org_service = $client->createService(OrganizationsService::class);
    try {
      $org_service->getOrgs()->getOrgs();
      $this->messenger()->addMessage($this->t(
        '@server_name (@server_id): success',
        [
          '@server_name' => $test_server['label'],
          '@server_id' => $test_server['id'],
        ]
      ));
    }
    catch (\Exception $e) {
      $this->messenger()->addError($this->t(
        '@server_name (@server_id): @reason',
        [
          '@server_name' => $test_server['label'],
          '@server_id' => $test_server['id'],
          '@reason' => $e->getMessage(),
        ]
      ));
    }
  }

  /**
   * Callback to update the servers fieldset.
   */
  public function updateServers(array &$form, FormStateInterface $form_state) {
    return $form['servers'];
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->configFactory->getEditable(static::SETTINGS)
      ->set('servers', $form_state->getValue('servers') ?? [])
      ->save();

    parent::submitForm($form, $form_state);
  }

}
