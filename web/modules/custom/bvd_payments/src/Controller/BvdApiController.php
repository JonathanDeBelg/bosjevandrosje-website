<?php

namespace Drupal\bvd_payments\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Site\Settings;
use Drupal\webform\WebformSubmissionInterface;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

/**
 * Defines HelloController class.
 */
class BvdApiController extends ControllerBase {

  /**
   * Display the markup.
   *
   * @return int
   *   Return markup array.
   */
  public function sendSubscription(WebformSubmissionInterface $submission) {
    $client = new Client();
    $globalSettings = \Drupal::service('settings');

    $data = [
      'json' => $submission->getData()
    ];
    \Drupal::logger('bvd_mollie_subscrip')->info('<pre><code>' . print_r($data, TRUE) . '</code></pre>');

    $httpStatusCode = 500;

    try {
      $url = $globalSettings->get('webapp_url') .
        $globalSettings->get('webapp_subscription_url') .
        '?api_token=' .
        $globalSettings->get('webapp_auth_token');


      $response = $client->post($url, $data);

      \Drupal::logger('bvd_mollie_subscrip')->info($url);


      $result = json_decode($response->getBody(), TRUE);

      $submission->setElementData('customer_no', $result['customer_no']);
      $submission->setElementData('registration_url', $result['registration_url']);
      $submission->resave();

      $httpStatusCode = $response->getStatusCode();
    } catch (RequestException $e) {
      \Drupal::logger('bvd_mollie_subscrip_error')->error($e->getMessage());
      $httpStatusCode = $e->getCode();
    }

    return $httpStatusCode;
  }

  public function sendGiftcard(WebformSubmissionInterface $submission) {
    $client = new Client();
    $globalSettings = \Drupal::service('settings');

    $data = [
      'json' => $submission->getData()
    ];
    \Drupal::logger('bvd_api_giftcard')->info('<pre><code>' . print_r($data, TRUE) . '</code></pre>');

    try {
      $url = $globalSettings->get('webapp_url') .
        $globalSettings->get('webapp_giftcard_url') .
        '?api_token=' .
        $globalSettings->get('webapp_auth_token');

      $response = $client->post($url, $data);

      \Drupal::logger('bvd_api_giftcard')->info($url);
      \Drupal::logger('bvd_api_giftcard')->info(var_export($response, true));

      $result = json_decode($response->getBody(), TRUE);

//      $submission->setElementData('customer_no', $result['customer_no']);
//      $submission->setElementData('registration_url', $result['registration_url']);
      $submission->resave();

      return $response->getStatusCode();
    } catch (RequestException $e) {
      \Drupal::logger('bvd_api_giftcard_error')->error($e->getMessage());
    }
  }
}
