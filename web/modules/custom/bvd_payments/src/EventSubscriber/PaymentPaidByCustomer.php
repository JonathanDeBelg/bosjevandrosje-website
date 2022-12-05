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
use Drupal\mollie\Events\MollieTransactionEventBase;
use Drupal\mollie\Events\MollieTransactionStatusChangeEvent;
use Drupal\webform\WebformSubmissionInterface;
use GuzzleHttp\Exception\ClientException;
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
    $this->mollieApiClient->setApiKey(Settings::get('mollie.settings')['live_key']);
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
   * @param MollieTransactionStatusChangeEvent $event
   *   Event.
   * @throws ApiException
   */
  public function updateOrderStatus(MollieTransactionStatusChangeEvent $event): void {
    \Drupal::logger('bvd_subscription')->info("Kaaas");

    $bvdApiController = new BvdApiController();
    $httpStatusCode = 500;
    /** @var WebformSubmissionInterface $submission */
    $submission = $this->entityTypeManager->getStorage('webform_submission')->load($event->getContextId());

    if($submission->getWebform()->id() == 'inschrijfformulier') {
      if ($submission->getElementData('betaalopties') == 'sepa') {
        $transactionPaid = $this->getTransactionState($event->getTransaction());

        if ($transactionPaid) {
          $subscription = $this->createSubscription($submission, $event);

          $submission->setElementData('mollie_customer', $subscription->customerId);
          $submission->setElementData('mollie_subscription', $subscription->id);
          $submission->resave();
        } else {
          return;
        }
      }

      $httpStatusCode = $bvdApiController->sendSubscription($submission);
      \Drupal::logger('bvd_subscription')->info('Subscription & customer saved to API');
    } else if($submission->getWebform()->id() == 'cadeaukaartformulier') {
      if ($submission->getElementData('betaalopties') == 'sepa') {
        $transactionPaid = $this->getTransactionState($event->getTransaction());
        if($transactionPaid) {
          \Drupal::logger('bvd_giftcard')->info('Hey');

          //TODO Add transaction paid result.
        }
      }

      $httpStatusCode = $bvdApiController->sendGiftcard($submission);
      \Drupal::logger('bvd_giftcard')->info('Giftcard & customer saved to API');
    }

    $event->setHttpStatusCode($httpStatusCode);
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
  protected function createSubscription(WebformSubmissionInterface $submission, MollieTransactionStatusChangeEvent $event): Subscription
  {
    $transaction = $event->getTransaction();
    $payment = $this->mollieApiClient->payments->get($transaction->id());
    $customer = $this->mollieApiClient->customers->get($payment->customerId);

    $firstDate = explode (",", $submission->getElementData('bezorgdatum'));
    $firstDate = strtotime($firstDate[0]);
    $firstDate = date('Y-m-d', $firstDate);

    /** @var Subscription */
    return $customer->createSubscription([
      "amount" => [
        "value" => (string) $submission->getElementData('payment_amount'),
        "currency" => "EUR",
      ],
      "interval" => $this->getInterval($submission),
      "startDate" => $firstDate,
      "description" => $submission->id(),
      "metadata" => [
        "subscription_id" => \Drupal::service('uuid')->generate(),
      ]
    ]);
  }

  private function getTransactionState(\Drupal\mollie\TransactionInterface $transaction): bool
  {
    if (!($transaction instanceof Payment)) {
      return false;
    }
    if($transaction->getStatus() != PaymentStatus::STATUS_PAID) {
      return false;
    }
    return true;
  }
}
