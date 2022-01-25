<?php

namespace Drupal\bvd_payments\Plugin\WebformHandler;

use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\mollie\Mollie;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Mollie\Api\Exceptions\ApiException;
use Mollie\Api\Resources\Subscription;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form submission handler.
 *
 * @WebformHandler(
 *   id = "bvd_subscriptions_webform_handler",
 *   label = @Translation("Bosje van drosje Subscription webform handler"),
 *   category = @Translation("Bosje van drosje"),
 *   description = @Translation("Sends recurring payment to Mollie."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_SINGLE,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_REQUIRED,
 * )
 */
final class BvdSubscriptionWebformHandler extends WebformHandlerBase {
  const SEPA = 'sepa';

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Mollie API client.
   *
   * @var \Mollie\Api\MollieApiClient|null
   */
  protected $mollieApiClient;

  /**
   * @var \Drupal\mollie_customers\Entity\Customer
   */
  private $customer;

  public function __construct(
    array $configuration,
          $plugin_id,
          $plugin_definition,
    Mollie $mollieConnector)
  {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->mollieApiClient = $mollieConnector->getClient();
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('mollie.mollie')
    );

    $instance->loggerFactory = $container->get('logger.factory');
    $instance->configFactory = $container->get('config.factory');
    $instance->renderer = $container->get('renderer');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->conditionsValidator = $container->get('webform_submission.conditions_validator');
    $instance->tokenManager = $container->get('webform.token_manager');

    $instance->setConfiguration($configuration);

    return $instance;
  }

  /**
  }

  /**
   * {@inheritdoc}
   */
  public function confirmForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    $description = $this->getWebform()->label() . ' #' . $webform_submission->id();

    $subscription = $this->createRecurringPayment($webform_submission);

    $webform_submission->setData([
      'mollie_subscription' => $subscription->id,
    ]);
  }

  /**
   * @param WebformSubmissionInterface $webform_submission
   * @return Subscription|void
   */
  private function createRecurringPayment(WebformSubmissionInterface $webform_submission)
  {
    try {
      /** @var \Drupal\mollie_customers\Entity\Customer  */
      $customer = $this->mollieApiClient->customers->get($webform_submission->get('mollie_customer'));

      $mandate = $this->mollieApiClient->mandates->listFor($customer);

      \Drupal::logger('bvd_mollie')->info(var_export($mandate, true));

      /** @var Subscription  */
      $subscription = $customer->createSubscription([
        "amount" => [
          "value" => "10.00",
          "currency" => "EUR",
        ],
        "times" => 12,
        "interval" => "1 month",
        "description" => "Testbetaling",
        "webhookUrl" => "test",
        "metadata" => [
          "subscription_id" => \Drupal::service('uuid')->generate(),
        ]
      ]);

      return $subscription;
    } catch (ApiException $e) {
      watchdog_exception('bvd_payments', $e);
    }
  }
}
