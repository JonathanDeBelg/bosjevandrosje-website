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

    try {
      $url = $globalSettings->get('webapp_url') . $globalSettings->get('webapp_subscription_url');
      $response = $client->post($url, [
        'json' => $submission->getData()
      ]);

      $result = json_decode($response->getBody(), TRUE);

      $submission->setElementData('customer_no', $result['customer_no']);
      $submission->setElementData('registration_url', $result['registration_url']);
      $submission->resave();

      return $response->getStatusCode();
    } catch (RequestException $e) {
      \Drupal::logger('bvd_mollie_subscrip_error')->error($e->getMessage());
    }
  }

}
