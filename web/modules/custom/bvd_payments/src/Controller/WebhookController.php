<?php

namespace Drupal\bvd_payments\Controller;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Url;
use Drupal\mollie\Events\MollieNotificationEvent;
use Drupal\mollie\Events\MollieTransactionChargebackEvent;
use Drupal\mollie\Events\MollieTransactionRefundEvent;
use Drupal\mollie\Events\MollieTransactionStatusChangeEvent;
use Drupal\mollie\Mollie;
use Mollie\Api\Types\PaymentStatus;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class WebhookController.
 *
 * @package Drupal\mollie\Controller
 */
class WebhookController extends ControllerBase {

  /**
   * Request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Event dispatcher.
   *
   * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
   */
  protected $eventDispatcher;

  /**
   * Mollie connector.
   *
   * @var \Drupal\mollie\Mollie
   */
  protected $mollie;

  /**
   * RedirectController constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   Request stack.
   * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
   *   Event dispatcher.
   * @param \Drupal\mollie\Mollie $mollie
   *   Mollie connector.
   */
  public function __construct(
    RequestStack $requestStack,
    EventDispatcherInterface $eventDispatcher,
    Mollie $mollie
  ) {
    $this->requestStack = $requestStack;
    $this->eventDispatcher = $eventDispatcher;
    $this->mollie = $mollie;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('event_dispatcher'),
      $container->get('mollie.mollie')
    );
  }

  /**
   * Allows modules to react to payment status updates.
   *
   * This webhook is called by Mollie when the status of a payment changes. Once
   * the payment reaches the status 'paid' we will change the webhook to invoke
   * invokeAftercareHook() on future calls.
   *
   * @param string $context
   *   The type of context that requested the payment.
   * @param string $context_id
   *   The ID of the context that requested the payment.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Response that will inform Mollie whether the event was processed
   *   correctly.
   */
  public function invokeStatusChangeHook(string $context, string $context_id): Response {
    $transaction_id = $this->requestStack->getCurrentRequest()->get('id');

    // Register invocation of the webhook.
    $this->keyValue(Mollie::LAST_WEBHOOK_INVOCATION_COLLECTION_KEY)->set($transaction_id, date('c'));

    try {
      // Get the transaction for which the hook was invoked.
      /** @var \Drupal\mollie\TransactionInterface $transaction */
      $transaction = $this->entityTypeManager()->getStorage('mollie_payment')
        ->load($transaction_id);

      // If the payment is paid we change the webhook to start "aftercare".
      if ($transaction->getStatus() === PaymentStatus::STATUS_PAID) {
        $payment = $this->mollie->getClient()->payments->get($transaction->id());

        // Set the new webhook URL.
        $payment->webhookUrl = Url::fromRoute(
          'mollie.webhook.aftercare',
          ['context' => $context, 'context_id' => $context_id]
        )->setAbsolute()->toString();

        // Change webhook when using an alternative base URL in test mode.
        $config = $this->config('mollie.config');
        if ($config->get('test_mode') && $config->get('webhook_base_url') !== '') {
          $defaultBaseUrl = Url::fromRoute('<front>')
            ->setAbsolute()->toString();
          $payment->webhookUrl = str_replace(
            $defaultBaseUrl,
            "{$config->get('webhook_base_url')}/",
            $payment->webhookUrl
          );
        }

        // Send the updated webhook URL to Mollie.
        $payment->update();
      }

      if ($transaction) {
        // Create an event to dispatch notification handling to submodules.
        $event = new MollieTransactionStatusChangeEvent(
          $context,
          $context_id,
          $transaction
        );
        // Set the default HTTP code.
        $event->setHttpStatusCode(200);

        // Dispatch event.
        $this->eventDispatcher->dispatch(MollieTransactionStatusChangeEvent::EVENT_NAME, $event);

        // BC: Support for old MollieNotificationEvent.
        // TODO: Remove in Mollie for Drupal 3.0.0.
        $deprecated_event = new MollieNotificationEvent(
          $context,
          $context_id,
          $transaction->id(),
          MollieNotificationEvent::STATUS_CHANGE_EVENT
        );
        // Set the default HTTP code.
        $deprecated_event->setHttpStatusCode(200);

        // Dispatch deprecated event.
        $this->eventDispatcher->dispatch(MollieNotificationEvent::EVENT_NAME, $deprecated_event);
      }
      else {
        // Just return a 200 OK if the transaction could not be loaded. This is
        // advised by Mollie.
        return new Response('', 200);
      }
    }
    catch (PluginNotFoundException | InvalidPluginDefinitionException $e) {
      watchdog_exception('mollie', $e);
      return new Response('', 500);
    }

    // Only if both the new event and the deprecated event have HTTP status code
    // 200 we can assume that all went well. If both events have both the same
    // HTTP status code we can return that code. If only one of the events has
    // HTTP status code 200 we use the other event. In other case we return HTTP
    // status code 500.
    // TODO: Clean up once MollieNotificationEvent has been removed.
    $http_status_code = $event->getHttpStatusCode() === $deprecated_event->getHttpStatusCode()
      ? $event->getHttpStatusCode()
      : 500;
    if ($event->getHttpStatusCode() === 200 && $deprecated_event->getHttpStatusCode() !== 200) {
      $http_status_code = $deprecated_event->getHttpStatusCode();
    }
    if ($deprecated_event->getHttpStatusCode() === 200 && $event->getHttpStatusCode() !== 200) {
      $http_status_code = $event->getHttpStatusCode();
    }
    return new Response('', $http_status_code);
  }
}
