<?php

namespace Drupal\zoho_webhook\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\Client;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\zoho_webhook\Service\ZohoApiClient;


/**
 * Controller for Zoho OAuth authentication.
 */
class ZohoAuthController extends ControllerBase {

  protected $httpClient;
  protected $zohoApiClient;

  /**
   * Constructor.
   */
  public function __construct(Client $httpClient, ZohoApiClient $zohoApiClient) {
    $this->httpClient = $httpClient;
    $this->zohoApiClient = $zohoApiClient;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_client'),
      $container->get('zoho_webhook.zoho_api_client'),
    );
  }

  /**
   * Handle Zoho OAuth callback.
   */
  public function callback(Request $request) {
    $code = $request->query->get('code');
    $state = $request->query->get('state'); // Optional state parameter

    if (!$code) {
      \Drupal::messenger()->addError($this->t('Authorization failed. No authorization code received.'));
      return $this->redirect('zoho_webhook.settings');
    }

    try {
      // Use the modular function to exchange the authorization code
      $accessToken = $this->zohoApiClient->exchangeAuthorizationCode($code, $state);

      // Success message and redirect to settings
      \Drupal::messenger()->addStatus($this->t('Zoho authentication successful. Access token obtained.'));
      return $this->redirect('zoho_webhook.settings');
    } catch (\Exception $e) {
      // Error message and redirect back
      \Drupal::messenger()->addError($this->t('Failed to exchange authorization code: @message', [
        '@message' => $e->getMessage(),
      ]));
      return $this->redirect('zoho_webhook.settings');
    }
  }
  

  /**
   * Redirect the user to Zoho's OAuth authorization page.
   */
  public function redirectToZoho() {
    /** @var \Drupal\zoho_webhook\Service\ZohoApiClient $zohoApiClient */
    $zohoApiClient = \Drupal::service('zoho_webhook.zoho_api_client');

    // Get the authorization URL.
    $authorizationUrl = $this->zohoApiClient->getAuthorizationUrl();
    // Redirect the user to the Zoho authorization page.
    return new RedirectResponse($authorizationUrl);
  }
}
