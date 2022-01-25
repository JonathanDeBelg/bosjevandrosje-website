<?php

namespace Drupal\bvd_payments\Element;

use Drupal\Core\Render\Element\Radios;
use Drupal\Core\Render\Element\FormElement;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Element\WebformEntityTrait;

/**
 * Provides a CompositeExample form.
 *
 * @FormElement("delivery_date_radio")
 */
class DeliveryDateRadio extends Radios {
  use WebformEntityTrait;

  /**
   * {@inheritdoc}
   */
  public static function processRadios(&$element, FormStateInterface $form_state, &$complete_form) {
    $datePositbilities = (date("l") != 'Wednesday') ?
      [
        date("d-m-Y", strtotime("next thursday +1 week")),
        date("d-m-Y", strtotime("next thursday +2 week")),
        date("d-m-Y", strtotime("next thursday +3 week")),
      ] :  [
        date("d-m-Y", strtotime("next thursday")),
        date("d-m-Y", strtotime("next thursday +1 week")),
        date("d-m-Y", strtotime("next thursday +2 week")),
      ];

    static::setOptions($datePositbilities);
    return parent::processRadios($element, $form_state, $complete_form);
  }
}




