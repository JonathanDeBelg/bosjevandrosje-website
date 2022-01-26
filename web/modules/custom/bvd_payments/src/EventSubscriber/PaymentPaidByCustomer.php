<?php

namespace Drupal\bvd_payments\EventSubscriber;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Drupal\mollie\Entity\Payment;
use Drupal\mollie\Events\MollieTransactionStatusChangeEvent;
use Mollie\Api\Exceptions\ApiException;
use Mollie\Api\MollieApiClient;
use Mollie\Api\Resources\Subscription;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class EntityTypeSubscriber.
 *
 * @package Drupal\custom_events\EventSubscriber
 */
class PaymentPaidByCustomer implements EventSubscriberInterface {
  /**
   * Mollie API client.
   *
   * @var \Mollie\Api\MollieApiClient|null
   */
  protected $mollieApiClient;

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  private $entityTypeManager;

  /**
   * @throws ApiException
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager) {
    $this->mollieApiClient = new MollieApiClient();
    $this->mollieApiClient->setApiKey(Settings::get('mollie.settings')['test_key']);
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   *
   * @return array
   *   The event names to listen for, and the methods that should be executed.
   */
  public static function getSubscribedEvents() {
    return [
      MollieTransactionStatusChangeEvent::EVENT_NAME => 'updateOrderStatus',
    ];
  }

  /**
   * Updates the status of the order when the status of a payment has changed.
   *
   * @param \Drupal\mollie\Events\MollieTransactionStatusChangeEvent $event
   *   Event.
   */
  public function updateOrderStatus(MollieTransactionStatusChangeEvent $event): void {
    // Return if the event context is not this module.
    if ($event->getContext() !== 'mollie_webform') {
      return;
    }
    // Default HTTP status code.
    $httpStatusCode = 200;

    // Fetch the transaction.
    try {
      // Get the transaction and check whether it is a payment.
      $transaction = $event->getTransaction();
      if (!($transaction instanceof Payment)) {
        return;
      }

      if($transaction->getStatus() == 'paid') {
        $payment = $this->mollieApiClient->payments->get($transaction->id());
        $customer = $this->mollieApiClient->customers->get($payment->customerId);

        $defaultBaseUrl = Url::fromRoute('<front>')
          ->setAbsolute()->toString();
        $webhookUrl = Url::fromRoute(
          'mollie.subscription.webhook.status_change',
          ['context' => $event->getContext(), 'context_id' => $event->getContextId()],
        )->setAbsolute()->toString();

        $webhookUrl = str_replace(
          $defaultBaseUrl,
          "http://1d1c-83-83-28-183.ngrok.io",
          $webhookUrl
        );

        /** @var Subscription  */
        $subscription = $customer->createSubscription([
          "amount" => [
            "value" => "10.00",
            "currency" => "EUR",
          ],
          "times" => 12,
          "interval" => "1 month",
          "description" => "Testbetaling",
          "webhookUrl" => $webhookUrl,
          "metadata" => [
            "subscription_id" => \Drupal::service('uuid')->generate(),
          ]
        ]);

        \Drupal::logger('bvd_subscriptions')->info(var_export($subscription, true));

        // Load the webform and save the payment status.
        /** @var \Drupal\webform\WebformSubmissionInterface $submission */
        $submission = $this->entityTypeManager->getStorage('webform_submission')
          ->load($event->getContextId());
        $submission->setElementData(
          'mollie_customer', $subscription->customerId
        );
        $submission->setElementData(
          'mollie_subscription', $subscription->id
        );
        $submission->resave();
      }

    } catch (InvalidPluginDefinitionException | PluginNotFoundException | EntityStorageException $e) {
      watchdog_exception('bvd_payments', $e);
      $httpStatusCode = 500;
    } catch (ApiException $e) {
      watchdog_exception('bvd_subscriptions_error', $e);

    }

    // Set the HTTP status code to return to Mollie.
    $event->setHttpStatusCode($httpStatusCode);
  }
}
