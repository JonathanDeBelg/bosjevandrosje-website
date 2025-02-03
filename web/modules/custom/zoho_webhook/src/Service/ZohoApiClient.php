<?php

namespace Drupal\zoho_webhook\Service;

use GuzzleHttp\Client;
use Drupal\Core\Site\Settings;
use GuzzleHttp\Exception\RequestException;

class ZohoApiClient {

  protected $httpClient;
  protected $config;

  /**
   * Constructor.
   */
  public function __construct(Client $httpClient, Settings $settings) {
    $this->httpClient = $httpClient;
    $this->config = $settings->get('zoho_billing');
  }

  /**
   * Generate Zoho authorization URL.
   *
   * @return string
   *   The authorization URL.
   */
  public function getAuthorizationUrl() {
    return $this->config['auth_url'] . '?' . http_build_query([
      'response_type' => 'code',
      'client_id' => $this->config['client_id'],
      'scope' => 'ZohoSubscriptions.fullaccess.all',
      'redirect_uri' => $this->config['redirect_uri'],
      'access_type' => 'offline',
      'prompt' => 'consent',
    ]);
  }

  /**
   * Exchange authorization code for access and refresh tokens.
   *x
   * @param string $authorizationCode
   *   The authorization code received from Zoho.
   *
   * @return string
   *   The access token.
   *
   * @throws \Exception
   *   When unable to exchange the authorization code for tokens.
   */
  public function exchangeAuthorizationCode($code, $state) {
    try {
      $response = $this->httpClient->post($this->config['token_url'], [
        'form_params' => [
          'grant_type' => 'authorization_code',
          'client_id' => $this->config['client_id'],
          'client_secret' => $this->config['client_secret'],
          'redirect_uri' => $this->config['redirect_uri'],
          'code' => $code,
          'scope' => 'ZohoSubscriptions.fullaccess.all'
        ],
      ]);

      $data = json_decode($response->getBody(), TRUE);

      if (isset($data['access_token'], $data['refresh_token'])) {
        // Store tokens and expiration time.
        \Drupal::state()->set('zoho_access_token', $data['access_token']);
        \Drupal::state()->set('zoho_refresh_token', $data['refresh_token']);
        \Drupal::state()->set('zoho_access_token_expires_at', time() + $data['expires_in']);

        \Drupal::logger('zoho_webhook')->info('Zoho access token obtained successfully.');
        return $data['access_token'];
      }
      else {
        throw new \Exception('Failed to exchange authorization code: Invalid response from Zoho.');
      }
    }
    catch (RequestException $e) {
      \Drupal::logger('zoho_webhook')->error('Failed to exchange authorization code: @message', [
        '@message' => $e->getMessage(),
      ]);
      throw $e;
    }
  }

  /**
   * Get or refresh the access token.
   *
   * @return string
   *   The current or newly refreshed access token.
   *
   * @throws \Exception
   *   When unable to obtain an access token.
   */
  public function getAccessToken() {
    $accessToken = \Drupal::state()->get('zoho_access_token');
    $expiresAt = \Drupal::state()->get('zoho_access_token_expires_at');
  
    if (!$accessToken || ($expiresAt && $expiresAt <= time())) {
      $refreshToken = \Drupal::state()->get('zoho_refresh_token');
      if ($refreshToken) {
        return $this->refreshAccessToken($refreshToken);
      }
      else {
        // No access or refresh token available. User must authenticate.
        throw new \Exception('No access or refresh token available. Redirect user to Zoho authorization URL.');
      }
    }
  
    return $accessToken;
  }


  /**
   * Refresh the access token using the refresh token.
   *
   * @param string $refreshToken
   *   The refresh token.
   *
   * @return string
   *   The refreshed access token.
   *
   * @throws \Exception
   *   When unable to refresh the access token.
   */
  public function refreshAccessToken($refreshToken) {
    try {
      $response = $this->httpClient->post($this->config['token_url'], [
        'form_params' => [
          'refresh_token' => $refreshToken,
          'client_id' => $this->config['client_id'],
          'client_secret' => $this->config['client_secret'],
          'grant_type' => 'refresh_token',
        ],
      ]);

      $data = json_decode($response->getBody(), TRUE);

      if (isset($data['access_token'])) {
        \Drupal::state()->set('zoho_access_token', $data['access_token']);
        \Drupal::state()->set('zoho_access_token_expires_at', time() + $data['expires_in']);
        \Drupal::logger('zoho_webhook')->info('Zoho access token refreshed successfully.');
        return $data['access_token'];
      }
      else {
        throw new \Exception('Failed to refresh Zoho access token: Missing access token in response.');
      }
    }
    catch (RequestException $e) {
      \Drupal::logger('zoho_webhook')->error('Zoho OAuth Error during refresh: @message', [
        '@message' => $e->getMessage(),
      ]);
      throw $e;
    }
  }

  /**
   * Create a new subscription in Zoho using the Hosted Payment Page.
   *
   * @param array $subscriptionData
   *   The subscription data to send to Zoho.
   *
   * @return array
   *   The response from Zoho.
   *
   * @throws \Exception
   *   When unable to create the subscription.
   */
  public function createSubscription($subscriptionData) {
    $accessToken = $this->getAccessToken();

    try {
      \Drupal::logger('zoho_webhook')->info('Creating Zoho subscription: @data', [
        '@data' => json_encode($subscriptionData),
      ]);
      $response = $this->httpClient->post($this->config['hosted_page_url'], [
        'headers' => [
          'Authorization' => "Zoho-oauthtoken $accessToken",
          'X-com-zoho-subscriptions-organizationid' => $this->config['organization_id'],
          'Content-Type' => 'application/json',
        ],
        'json' => $subscriptionData,
      ]);

      return json_decode($response->getBody(), TRUE);
    }
    catch (RequestException $e) {
      $responseBody = $e->getResponse() ? $e->getResponse()->getBody()->getContents() : 'No response';
      \Drupal::logger('zoho_webhook')->error('Failed to create Zoho subscription: @error. Response: @response', [
        '@error' => $e->getMessage(),
        '@response' => $responseBody,
      ]);
      throw new \Exception('Failed to create Zoho subscription.');
    }
  }

  public function createDirectSubscription($subscriptionData) {
    $accessToken = $this->getAccessToken();
    \Drupal::logger('zoho_webhook')->info('Creating Zoho direct subscription: @data', [
      '@data' => json_encode($subscriptionData),
    ]);
    try {
        $response = $this->httpClient->post($this->config['subscription_url'] , [
            'headers' => [
                'Authorization' => "Zoho-oauthtoken $accessToken",
                'X-com-zoho-subscriptions-organizationid' => $this->config['organization_id'],
                'Content-Type' => 'application/json',
            ],
            'json' => $subscriptionData,
        ]);

        return json_decode($response->getBody(), TRUE);
    } catch (RequestException $e) {
        $responseBody = $e->getResponse() ? $e->getResponse()->getBody()->getContents() : 'No response';
        \Drupal::logger('zoho_webhook')->error('Failed to create direct Zoho subscription: @error. Response: @response, Data: @data', [
            '@error' => $e->getMessage(),
            '@response' => $responseBody,
            '@data' => print_r($subscriptionData, TRUE),
        ]);
        throw new \Exception('Failed to create direct Zoho subscription.');
    }
  }

  public function createCustomer($customerData) {
    $accessToken = $this->getAccessToken();

    try {
        $response = $this->httpClient->post($this->config['customer_url'] , [
            'headers' => [
                'Authorization' => "Zoho-oauthtoken $accessToken",
                'X-com-zoho-subscriptions-organizationid' => $this->config['organization_id'],
                'Content-Type' => 'application/json',
            ],
            'json' => $customerData,
        ]);

        return json_decode($response->getBody(), TRUE);
    } catch (RequestException $e) {
        $responseBody = $e->getResponse() ? $e->getResponse()->getBody()->getContents() : 'No response';
        \Drupal::logger('zoho_webhook')->error('Failed to create Zoho customer: @error. Response: @response. Data: @data', [
            '@error' => $e->getMessage(),
            '@response' => $responseBody,
            '@data' => print_r([
                'headers' => [
                    'Authorization' => "Zoho-oauthtoken $accessToken",
                    'X-com-zoho-subscriptions-organizationid: ' . $this->config['organization_id'],
                    'Content-Type' => 'application/json',
                ],
                'json' => print_r($customerData),
            ], TRUE),
        ]);
        throw new \Exception('Failed to create Zoho customer.');
    }
  }
}