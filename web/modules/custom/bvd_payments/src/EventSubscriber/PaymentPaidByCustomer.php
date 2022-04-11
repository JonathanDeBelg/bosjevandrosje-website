<?php

namespace Drupal\bvd_payments\EventSubscriber;

use Drupal\bvd_payments\Controller\BvdApiController;
use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Drupal\mollie\Entity\Payment;
use Drupal\mollie\Events\MollieTransactionStatusChangeEvent;
use Drupal\webform\WebformSubmissionInterface;
use Mollie\Api\Exceptions\ApiException;
use Mollie\Api\MollieApiClient;
use Mollie\Api\Resources\Subscription;
use Mollie\Api\Types\PaymentStatus;
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
    $bvdApiController = new BvdApiController();
    $httpStatusCode = 200;
    /** @var WebformSubmissionInterface $submission */
    $submission = $this->entityTypeManager->getStorage('webform_submission')
      ->load($event->getContextId());

    if($submission->getWebform()->id() != 'inschrijfformulier') {
      return;
    }

    try {
      $transaction = $event->getTransaction();
      if (!($transaction instanceof Payment)) {
        return;
      }

      if($transaction->getStatus() == PaymentStatus::STATUS_PAID) {
        $subscription = $this->createSubscription($transaction, $submission, $event);

        $submission->setElementData('mollie_customer', $subscription->customerId);
        $submission->setElementData('mollie_subscription', $subscription->id);
        $submission->resave();
      }

      try {
        $httpStatusCode = $bvdApiController->sendSubscription($submission);
        \Drupal::logger('bvd_subscriptions')->info('Subscription & customer saved to API');
      } catch (\Exception $e) {
        watchdog_exception('bvd_subscriptions_error', $e);
        $httpStatusCode = 500;
      }
    } catch (InvalidPluginDefinitionException | PluginNotFoundException | EntityStorageException | ApiException $e) {
      watchdog_exception('bvd_subscriptions_error', $e);
      $httpStatusCode = 500;
    }

    $event->setHttpStatusCode($httpStatusCode);

    $handler = $submission->getWebform()->getHandler('email');
    $message = $handler->getMessage($submission);
    $handler->sendMessage($submission, $message);
  }

  /**
   * @param MollieTransactionStatusChangeEvent $event
   * @return array|\Drupal\Core\GeneratedUrl|string|string[]
   */
  protected function getWebhookUrl(MollieTransactionStatusChangeEvent $event)
  {
    $webhookUrl = Url::fromRoute(
      'mollie.subscription.webhook.status_change',
      ['context' => $event->getContext(), 'context_id' => $event->getContextId()],
    )->setAbsolute()->toString();

    $config = \Drupal::config('mollie.config');
    if ($config->get('test_mode') && $config->get('webhook_base_url') !== '') {
      $defaultBaseUrl = Url::fromRoute('<front>')
        ->setAbsolute()->toString();
      $webhookUrl = str_replace(
        $defaultBaseUrl,
        "{$config->get('webhook_base_url')}/",
        $webhookUrl
      );
    }
    return $webhookUrl;
  }

  /**
   * @param WebformSubmissionInterface $submission
   * @return string
   */
  protected function getInterval(WebformSubmissionInterface $submission): string
  {
    if ($submission->getElementData('frequentie') == 2) {
      return "14 days";
    } else {
      return "7 days";
    }
  }

  /**
   * @param $transaction
   * @param WebformSubmissionInterface $submission
   * @param MollieTransactionStatusChangeEvent $event
   * @return Subscription
   * @throws ApiException
   */
  protected function createSubscription($transaction, WebformSubmissionInterface $submission, MollieTransactionStatusChangeEvent $event): Subscription
  {
    $payment = $this->mollieApiClient->payments->get($transaction->id());
    $customer = $this->mollieApiClient->customers->get($payment->customerId);

    /** @var Subscription */
    $subscription = $customer->createSubscription([
      "amount" => [
        "value" => (string) $submission->getElementData('payment_amount'),
        "currency" => "EUR",
      ],
      "interval" => $this->getInterval($submission),
      "webhookUrl" => $this->getWebhookUrl($event),
      "description" => $submission->id(),
      "metadata" => [
        "subscription_id" => \Drupal::service('uuid')->generate(),
      ]
    ]);
    return $subscription;
  }
}
