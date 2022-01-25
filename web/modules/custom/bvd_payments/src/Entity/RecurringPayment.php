<?php

namespace Drupal\bvd_payments\Entity;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\mollie\Entity\TransactionBase;

/**
 * Class RecurringPayment.
 *
 * @package Drupal\bvd_payments\Entity
 *
 * @ContentEntityType(
 *   id = "mollie_recurring_payment",
 *   label = @Translation("Mollie recurring payment"),
 *   handlers = {
 *     "access" = "Drupal\mollie\PaymentAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\mollie\Form\PaymentForm",
 *     },
 *     "list_builder" = "Drupal\mollie\Controller\PaymentListBuilder",
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "storage" = "Drupal\bvd_payments\Entity\RecurringPaymentStorage",
 *   },
 *   fieldable = FALSE,
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "id",
 *   },
 *   links = {
 *     "canonical" = "/admin/content/mollie/payment/{mollie_payment}",
 *     "collection" = "/admin/content/mollie/payments",
 *     "add-form" = "/admin/content/mollie/payment/add",
 *   },
 * )
 */
class RecurringPayment extends TransactionBase {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entityType) {
    /** @var \Drupal\Core\Field\BaseFieldDefinition[] $fields */
    $fields = parent::baseFieldDefinitions($entityType);

    if (isset($fields['id'])) {
      $fields['id']->setDescription(t('The ID of the Mollie payment.'));
    }

    // Customer
    $fields['customerId'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Customer'))
      ->setRequired(TRUE)
      ->setDisplayOptions(
        'form',
        [
          'type' => 'string_textfield',
          'weight' => 0,
        ]
      );

    // Sequence type
    $fields['sequenceType'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Sequence'))
      ->setRequired(TRUE)
      ->setDisplayOptions(
        'form',
        [
          'type' => 'string_textfield',
          'weight' => 0,
        ]
      );

    // Payment description.
    $fields['description'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Description'))
      ->setRequired(TRUE)
      ->setDisplayOptions(
        'form',
        [
          'type' => 'string_textfield',
          'weight' => 0,
        ]
      );

    // Refunded amount.
    $fields['refunded_amount'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Refunded amount'))
      ->setRequired(TRUE)
      ->setDisplayOptions(
        'form',
        [
          'type' => 'string_textfield',
          'weight' => 20,
        ]
      );

    // Refunded currency.
    $fields['refunded_currency'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Refunded currency'))
      ->setRequired(TRUE)
      ->setSetting(
        'allowed_values',
        ['EUR' => 'Euro', 'USD' => 'USD']
      )
      ->setDisplayOptions(
        'form',
        [
          'type' => 'options_select',
          'weight' => 15,
        ]
      );

    // Refundable amount.
    $fields['refundable_amount'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Refundable amount'))
      ->setRequired(TRUE)
      ->setDisplayOptions(
        'form',
        [
          'type' => 'string_textfield',
          'weight' => 30,
        ]
      );

    // Refundable currency.
    $fields['refundable_currency'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Refundable currency'))
      ->setRequired(TRUE)
      ->setSetting(
        'allowed_values',
        ['EUR' => 'Euro', 'USD' => 'USD']
      )
      ->setDisplayOptions(
        'form',
        [
          'type' => 'options_select',
          'weight' => 25,
        ]
      );

    // Captured amount.
    $fields['captured_amount'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Captured amount'))
      ->setRequired(TRUE)
      ->setDisplayOptions(
        'form',
        [
          'type' => 'string_textfield',
          'weight' => 40,
        ]
      );

    // Captured currency.
    $fields['captured_currency'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Captured currency'))
      ->setRequired(TRUE)
      ->setSetting(
        'allowed_values',
        ['EUR' => 'Euro', 'USD' => 'USD']
      )
      ->setDisplayOptions(
        'form',
        [
          'type' => 'options_select',
          'weight' => 35,
        ]
      );

    // Charged back amount.
    $fields['charged_back_amount'] = BaseFieldDefinition::create('float')
      ->setLabel(t('Charged back amount'))
      ->setRequired(TRUE)
      ->setDisplayOptions(
        'form',
        [
          'type' => 'string_textfield',
          'weight' => 50,
        ]
      );

    // Charged back currency.
    $fields['charged_back_currency'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Charged back currency'))
      ->setRequired(TRUE)
      ->setSetting(
        'allowed_values',
        ['EUR' => 'Euro', 'USD' => 'USD']
      )
      ->setDisplayOptions(
        'form',
        [
          'type' => 'options_select',
          'weight' => 45,
        ]
      );

    // Payment method.
    $fields['method'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Method'))
      ->setDisplayOptions(
        'form',
        [
          'type' => 'opt ions_select',
          'weight' => 55,
        ]
      );

    // Payment method issuer.
    $fields['issuer'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Issuer'))
      ->setDisplayOptions(
        'form',
        [
          'type' => 'options_select',
          'weight' => 60,
        ]
      );

    // Payment checkout URL.
    $fields['checkout_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Checkout URL'));

    return $fields;
  }

  /**
   * Returns the customerId for the payment.
   *
   * @return string
   *   CustomerId for the payment.
   */
  public function getCustomer(): string {
    return $this->get('customerId')->value;
  }

  /**
   * Returns the sequenceType for the payment.
   *
   * @return string
   *   SequenceType for the payment.
   */
  public function getSequenceType(): string {
    return $this->get('sequenceType')->value;
  }

  /**
   * Returns the description for the payment.
   *
   * @return string
   *   Description for the payment.
   */
  public function getDescription(): string {
    return $this->get('description')->value;
  }

  /**
   * Returns the refunded amount for the transaction.
   *
   * @return float
   *   Refunded amount for the transaction.
   */
  public function getRefundedAmount(): float {
    return $this->get('refunded_amount')->value;
  }

  /**
   * Returns the currency of the refunded amount for the transaction.
   *
   * @return string
   *   Currency of the refunded amount for the transaction.
   */
  public function getRefundedCurrency(): string {
    return $this->get('refunded_currency')->value;
  }

  /**
   * Returns the refundable amount for the transaction.
   *
   * @return float
   *   Refundable amount for the transaction.
   */
  public function getRefundableAmount(): float {
    return $this->get('refundable_amount')->value;
  }

  /**
   * Returns the currency of the refundable amount for the transaction.
   *
   * @return string
   *   Currency of the refundable amount for the transaction.
   */
  public function getRefundableCurrency(): string {
    return $this->get('refundable_currency')->value;
  }

  /**
   * Returns the captured amount for the transaction.
   *
   * @return float
   *   Captured amount for the transaction.
   */
  public function getCapturedAmount(): float {
    return $this->get('captured_amount')->value;
  }

  /**
   * Returns the currency of the captured amount for the transaction.
   *
   * @return string
   *   Currency of the captured amount for the transaction.
   */
  public function getCapturedCurrency(): string {
    return $this->get('captured_currency')->value;
  }

  /**
   * Returns the charged back amount for the transaction.
   *
   * @return float
   *   Charged back amount for the transaction.
   */
  public function getChargedBackAmount(): float {
    return $this->get('charged_back_amount')->value;
  }

  /**
   * Returns the currency of the charged back amount for the transaction.
   *
   * @return string
   *   Currency of the charged back amount for the transaction.
   */
  public function getChargedBackCurrency(): string {
    return $this->get('charged_back_currency')->value;
  }

  /**
   * Returns the payment method for the payment.
   *
   * @return string
   *   Payment method for the payment.
   */
  public function getMethod(): ?string {
    return $this->get('method')->value;
  }

  /**
   * Returns the payment method issuer for the payment.
   *
   * @return string
   *   Payment method issuer for the payment.
   */
  public function getIssuer(): ?string {
    return $this->get('issuer')->value;
  }

  /**
   * Returns the checkout URL for the payment.
   *
   * @return string
   *   Checkout URL for the payment.
   */
  public function getCheckoutUrl(): ?string {
    return $this->get('checkout_url')->value;
  }

}
