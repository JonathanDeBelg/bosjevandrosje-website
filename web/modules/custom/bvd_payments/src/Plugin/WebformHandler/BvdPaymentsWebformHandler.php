<?php

namespace Drupal\bvd_payments\Plugin\WebformHandler;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use Drupal\mollie\Events\MollieTransactionStatusChangeEvent;
use Drupal\mollie\Mollie;
use Drupal\mollie_customers\Entity\Customer;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Form submission handler.
 *
 * @WebformHandler(
 *   id = "bvd_payments_webform_handler",
 *   label = @Translation("Bosje van drosje Payments webform handler"),
 *   category = @Translation("Bosje van drosje"),
 *   description = @Translation("Sends recurring payment to Mollie."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_SINGLE,
 *   
 * 
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_REQUIRED,
 * )
 */
final class BvdPaymentsWebformHandler extends WebformHandlerBase {
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
   * @var Customer
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
   * {@inheritdoc}
   */
  public function confirmForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    if($webform_submission->getWebform()->id() == 'inschrijfformulier' &&
       $webform_submission->getElementData('betaalopties') == 'sepa') {
        $description = $this->getWebform()->label() . ' #' . $webform_submission->id();

      try {
        $response = $this->createFirstPayment($webform_submission, $description, $form_state);

        \Drupal::logger('bvd_mollie')->info(var_export($response, true));
      } catch (InvalidPluginDefinitionException|PluginNotFoundException $e) {
        \Drupal::logger('bvd_mollie_error')->info(var_export($e, true));
      }
    }
  }

  /**
   * @param WebformSubmissionInterface $webform_submission
   * @param string $description
   * @param FormStateInterface $form_state
   * @throws InvalidPluginDefinitionException
   * @throws PluginNotFoundException
   */
  public function createFirstPayment(WebformSubmissionInterface $webform_submission, string $description, FormStateInterface $form_state): TrustedRedirectResponse
  {
    /** @var Customer  */
    $customer = $this->entityTypeManager->getStorage('mollie_customer')->create(
      [
        'name' => $webform_submission->getElementData('voornaam') . ' ' . $webform_submission->getElementData('achternaam'),
        'email' => $webform_submission->getElementData('e_mailadres'),
      ]
    );

    try {

      $customer->save();
      $this->customer = $customer;

      $transaction = $this->entityTypeManager->getStorage('mollie_recurring_payment')->create(
        [
          'amount' => '0.01',
          'currency' => 'EUR',
          'description' => $description,
          'context' => 'mollie_webform',
          'context_id' => $webform_submission->id(),
          'method' => [],
          'customerId' => $customer->id(),
          'sequenceType' => "first",
        ]
      );
      $transaction->save();

      $response = new TrustedRedirectResponse($transaction->getCheckoutUrl(), '303');
    } catch (EntityStorageException $e) {
      watchdog_exception('mollie', $e);
      $response = new TrustedRedirectResponse('/er-ging-iets-mis-met-het-betalen', '303');
    }
    $form_state->setResponse($response);
    return $response;

  }
}
