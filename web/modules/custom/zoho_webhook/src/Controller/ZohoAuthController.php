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
    $state = $request->query->get('state');
    $error = $request->query->get('error');
  
    if ($error) {
      \Drupal::logger('zoho_webhook')->error('Zoho OAuth Error: @error', ['@error' => $error]);
      return new RedirectResponse('/error-page'); // Replace with your error route.
    }
  
    if (!$code) {
      \Drupal::logger('zoho_webhook')->error('Zoho OAuth Error: Missing code.');
      return new RedirectResponse('/error-page'); // Replace with your error route.
    }
  
    try {
      // Exchange the authorization code for tokens.
      $this->zohoApiClient->exchangeAuthorizationCode($code, $state);
  
      \Drupal::logger('zoho_webhook')->info('Zoho OAuth authentication successful.');
      return new RedirectResponse('/success-page'); // Replace with your success route.
    }
    catch (\Exception $e) {
      \Drupal::logger('zoho_webhook')->error('Zoho OAuth Error: @message', ['@message' => $e->getMessage()]);
      return new RedirectResponse('/error-page'); // Replace with your error route.
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
