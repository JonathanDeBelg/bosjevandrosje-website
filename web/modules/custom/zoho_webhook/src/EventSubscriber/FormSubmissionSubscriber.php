<?php

namespace Drupal\zoho_webhook\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\zoho_webhook\Service\ZohoApiClient;

class FormSubmissionSubscriber implements EventSubscriberInterface {

  protected $zohoApiClient;

  public function __construct(ZohoApiClient $zohoApiClient) {
    $this->zohoApiClient = $zohoApiClient;
  }

  public static function getSubscribedEvents() {
    $events['webform.webform_submission.insert'] = ['onWebformSubmit'];
    return $events;
  }

  public function onWebformSubmit(WebformSubmissionInterface $webformSubmission) {
    $data = $webformSubmission->getData();

    $subscriptionData = [
      'plan' => [
        'plan_code' => $data['plan_code'],
      ],
      'customer' => [
        'email' => $data['email'],
        'first_name' => $data['first_name'],
        'last_name' => $data['last_name'],
      ],
      'start_date' => $data['delivery_date'],
    ];

    try {
      $response = $this->zohoApiClient->createSubscription($subscriptionData);

      // Store the hosted page URL for the customer to proceed.
      $webformSubmission->setData(['hosted_page_url' => $response['hostedpage']['url']]);
    }
    catch (\Exception $e) {
      \Drupal::logger('zoho_webhook')->error($e->getMessage());
    }
  }
}
