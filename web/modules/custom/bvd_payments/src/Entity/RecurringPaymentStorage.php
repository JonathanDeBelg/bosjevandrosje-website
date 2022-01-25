<?php

namespace Drupal\bvd_payments\Entity;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Url;
use Drupal\mollie\Entity\TransactionStorageBase;
use Mollie\Api\Exceptions\ApiException;
use Mollie\Api\Resources\BaseResource;

/**
 * Class PaymentStorage.
 *
 * @package Drupal\mollie\Entity
 */
class RecurringPaymentStorage extends TransactionStorageBase {

  const RESOURCE_NAME = 'recurring_payments';

  /**
   * {@inheritdoc}
   */
  protected function createEntityFromTransaction(BaseResource $payment): ?EntityInterface {
    \Drupal::logger('bvd_handler')->info('1');

    if ($payment instanceof RecurringPayment) {
      $metadata = Json::decode($payment->metadata);

      $values = [
        'id' => $payment->id,
        'created' => $payment->createdAt,
        'changed' => $this->getPaymentChangedDate($payment),
        'status' => $payment->status,
        'amount' => $payment->amount->value,
        'currency' => $payment->amount->currency,
        'refunded_amount' => $payment->amountRefunded ? $payment->amountRefunded->value : 0,
        'refunded_currency' => $payment->amountRefunded ? $payment->amountRefunded->currency : 'EUR',
        'refundable_amount' => $payment->amountRemaining ? $payment->amountRemaining->value : 0,
        'refundable_currency' => $payment->amountRemaining ? $payment->amountRemaining->currency : 'EUR',
        'captured_amount' => $payment->amountCaptured ? $payment->amountCaptured->value : 0,
        'captured_currency' => $payment->amountCaptured ? $payment->amountCaptured->currency : 'EUR',
        'charged_back_amount' => $payment->amountChargedBack ? $payment->amountChargedBack->value : 0,
        'charged_back_currency' => $payment->amountChargedBack ? $payment->amountChargedBack->value : 'EUR',
        'description' => $payment->description,
        'mode' => $payment->mode,
        'method' => $payment->method,
        'context' => $metadata['context'],
        'context_id' => $metadata['context_id'],
      ];
      if (isset($metadata['issuer'])) {
        $values['issuer'] = $metadata['issuer'];
      }
      if (isset($payment->_links->checkout)) {
        $values['checkout_url'] = $payment->_links->checkout->href;
      }

      return RecurringPayment::create($values);
    }

    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  protected function createTransactionFromEntity(EntityInterface $entity): void {
    if ($entity instanceof RecurringPayment) {
      $redirectUrl = Url::fromRoute(
        'mollie.redirect',
        ['context' => $entity->getContext(), 'context_id' => $entity->getContextId()]
      )->setAbsolute()->toString();
      $webhookUrl = Url::fromRoute(
        'mollie.webhook.status_change',
        ['context' => $entity->getContext(), 'context_id' => $entity->getContextId()]
      )->setAbsolute()->toString();

      // Change webhook when using an alternative base URL in test mode.
      $config = $this->configFactory->get('mollie.config');
      if ($config->get('test_mode') && $config->get('webhook_base_url') !== '') {
        $defaultBaseUrl = Url::fromRoute('<front>')
          ->setAbsolute()->toString();
        $webhookUrl = str_replace(
          $defaultBaseUrl,
          "{$config->get('webhook_base_url')}/",
          $webhookUrl
        );
      }

      $metadata = [
        'context' => $entity->getContext(),
        'context_id' => $entity->getContextId(),
      ];

      $values = [
        'description' => $entity->getDescription(),
        'amount' => [
          'value' => number_format($entity->getAmount(), 2, '.', ''),
          'currency' => $entity->getCurrency(),
        ],
        'method' => $entity->getMethod(),
        'redirectUrl' => $redirectUrl,
        'webhookUrl' => $webhookUrl,
        'metadata' => Json::encode($metadata),
        'locale' => $this->getLocaleByCurrentContentLanguage(),
        'sequenceType' => $entity->getSequenceType(),
        'customerId' => $entity->getCustomer()
      ];

      if (in_array($entity->getMethod(), ['ideal']) && $entity->getIssuer()) {
        $values['issuer'] = $entity->getIssuer();
      }

      try {
        // Create a payment on the Mollie side.
        $payment = $this->mollieApiClient->payments->create($values);
        // Update the entity with the information added by Mollie.
        // TODO: This might be incomplete. Look for a way to replace $entity.
        $entity->setOriginalId($payment->id);
        $entity->set('id', $payment->id);
        if (isset($payment->_links->checkout)) {
          $entity->set('checkout_url', $payment->_links->checkout->href);
        }
      }
      catch (ApiException $e) {
        watchdog_exception('mollie', $e);
        throw new EntityStorageException('An error occurred while creating the payment.');
      }
    }
  }

  /**
   * Returns the date the payment last changed in ISO 8601 format.
   *
   * @param RecurringPayment $payment
   *   Payment.
   *
   * @return string
   *   Date the payment last changed in ISO 8601 format.
   */
  protected function getPaymentChangedDate(RecurringPayment $payment): string {
    $changedDate = $payment->createdAt;

    foreach ($this->getPaymentDateFields() as $dateField) {
      if (property_exists($payment, $dateField)
        && $payment->{$dateField} > $changedDate) {
        $changedDate = $payment->{$dateField};
      }
    }

    return $changedDate;
  }

  /**
   * Returns the date fields known for payments.
   *
   * @return array
   *   Array of date field names.
   */
  protected function getPaymentDateFields(): array {
    return [
      'authorizedAt',
      'paidAt',
      'canceledAt',
      'expiredAt',
      'failedAt',
    ];
  }

  /**
   * Returns an ISO locale code based on the current content language.
   *
   * Best effort to get a locale code to pass to Mollie. Drupal does not have
   * a country for (anonymous) visitors by default so we cannot construct the
   * locale from language code and country code. This implementation is based
   * on the locales currently supported by Mollie.
   *
   * @return string
   */
  protected function getLocaleByCurrentContentLanguage(): string {
    $langcode = $this->languageManager
      ->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)
      ->getId();

    switch ($langcode) {
      case 'en':
        return 'en_US';

      case 'ca':
        return 'ca_ES';

      case 'nb':
        return 'nb_NO';

      case 'sv':
        return 'sv_SE';

      case 'da':
        return 'da_DK';

      default:
        $countryCode = strtoupper($langcode);
        return "{$langcode}_{$countryCode}";
    }
  }

}
