<?php

namespace Drupal\zoho_webhook\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Routing\TrustedRedirectResponse;
use GuzzleHttp\Client;

/**
 * Configure Zoho Webhook settings.
 */
class ZohoWebhookSettingsForm extends ConfigFormBase {
    protected $config;

    /**
     * Constructor.
     */
  public function __construct() {
    $this->config = \Drupal::service('settings')->get('zoho_billing');
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['zoho_webhook.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'zoho_webhook_settings_form';
  }


  /**
   * Build the settings form.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    // Zoho Client ID & Secret Fields
    $form['client_id'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Zoho Client ID'),
      '#default_value' => $this->config['client_id'],
      '#attributes' => array('readonly' => 'readonly'),
      '#required' => TRUE,
    ];

    $form['client_secret'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Zoho Client Secret'),
      '#default_value' => $this->config['client_secret'],
      '#attributes' => array('readonly' => 'readonly'),
      '#required' => TRUE,
    ];

    // Redirect URI Field
    $form['redirect_uri'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Redirect URI'),
      '#default_value' => $this->config['redirect_uri'],
      '#required' => TRUE,
      '#attributes' => array('readonly' => 'readonly'),
      '#description' => $this->t('This should match the Redirect URI configured in Zoho.'),
    ];

    // Button to start OAuth authorization
    $form['authorize'] = [
      '#type' => 'submit',
      '#value' => $this->t('Get Access Token'),
      '#submit' => ['::authorizeZoho'],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * Handle Zoho authorization.
   */
  public function authorizeZoho(array &$form, FormStateInterface $form_state) {
    // Construct Zoho authorization URL
    $authUrl = $this->config['auth_url'] . http_build_query([
      'response_type' => 'code',
      'client_id' => $this->config['client_id'],
      'redirect_uri' => $this->config['redirect_uri'],
      'scope' => 'ZohoSubscriptions.fullaccess.all',
      'access_type' => 'offline',
    ]);

    // Use TrustedRedirectResponse for external redirect
    $form_state->setResponse(new TrustedRedirectResponse($authUrl));
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('zoho_webhook.settings')
      ->set('client_id', $form_state->getValue('client_id'))
      ->set('client_secret', $form_state->getValue('client_secret'))
      ->set('redirect_uri', $form_state->getValue('redirect_uri'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
