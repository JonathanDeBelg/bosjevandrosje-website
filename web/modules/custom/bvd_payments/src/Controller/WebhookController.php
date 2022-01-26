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

  }
}
