<?php

namespace Drupal\zoho_webhook\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\Client;
use Drupal\Core\Site\Settings;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\zoho_webhook\Service\ZohoApiClient;


/**
 * Controller for Zoho OAuth authentication.
 */
class ZohoAuthController extends ControllerBase {

  protected $httpClient;
  protected $zohoApiClient;
  protected $config;

  /**
   * Constructor.
   */
  public function __construct(Client $httpClient, ZohoApiClient $zohoApiClient, Settings $settings) {
    $this->httpClient = $httpClient;
    $this->zohoApiClient = $zohoApiClient;
    $this->config = $settings->get('zoho_billing');
  }

  /**
  * Handle Zoho OAuth callback.
  */
 public function handleAuthCallback(Request $request) {
   $code = $request->query->get('code');
   if (!$code) {
    \Drupal::messenger()->addError($this->t('Authorization failed. Missing code.'));
     return $this->redirect('zoho_webhook.settings');
   }

   try {
     $response = $this->httpClient->post($this->config['token_url'], [
       'form_params' => [
         'code' => $code,
         'client_id' => $this->config['client_id'],
         'client_secret' => $this->config['client_secret'],
         'redirect_uri' => $this->config['redirect_uri'],
         'grant_type' => 'authorization_code',
       ],
     ]);

     $data = json_decode($response->getBody(), TRUE);
     
     if (isset($data['access_token'])) {
      \Drupal::state()->set('zoho_access_token', $data['access_token']);
      \Drupal::state()->set('zoho_token_expires', $data['expires_in']);
      if(isset($data['refresh_token'])){
        \Drupal::state()->set('zoho_refresh_token', $data['refresh_token']);
      }
       \Drupal::messenger()->addStatus($this->t('Zoho authorization successful.'));
     } else {
      \Drupal::messenger()->addError($this->t('Authorization response is missing expected parameters.'));
     }
   } catch (\Exception $e) {
     \Drupal::logger('zoho_webhook')->error('Zoho OAuth failed: @message', ['@message' => $e->getMessage()]);
     \Drupal::messenger()->addError($this->t('Zoho authentication failed.'));
   }

   return $this->redirect('zoho_webhook.settings');
 }

 /**
   * {@inheritdoc}
   */
  public static function create(\Symfony\Component\DependencyInjection\ContainerInterface $container) {
    return new static(
      $container->get('http_client'),
      $container->get('zoho_webhook.zoho_api_client'),
      $container->get('settings')
    );
  }
}
