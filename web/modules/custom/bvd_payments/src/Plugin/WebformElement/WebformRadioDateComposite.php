<?php

namespace Drupal\bvd_payments\Plugin\WebformElement;

use Drupal\Core\Form\FormState;
use Drupal\webform\Plugin\WebformElement\Radios;
use Drupal\webform\Plugin\WebformElement\WebformCompositeBase;
use Drupal\WebformCompositeExample\Element\CompositeExample;

/**
 * Provides WebformRadioDateComposite webform composite element.
 *
 * @WebformElement(
 *   id = "WebformRadioDateComposite",
 *   label = @Translation("WebformRadioDateComposite"),
 *   description = @Translation("Provides a custom date-recommendation for first delivery date."),
 *   category = @Translation("Composite elements"),
 *   multiline = TRUE,
 *   composite = TRUE,
 *   states_wrapper = TRUE,
 * )
 */
class WebformRadioDateComposite extends Radios {


  /**
   * {@inheritdoc}
   */
  protected function getInitializedCompositeElement(array &$element) {
    $form_state = new FormState();
    $form_completed = [];

    return CompositeExample::processWebformComposite($element, $form_state, $form_completed);
  }

  /**
   * {@inheritdoc}
   */
  protected function formatTextItemValue(array $element, array $value) {
    $lines = [];

    if (!empty($value['title'])) {
      $lines['title'] = $value['title'];
    }

    if (!empty($value['name'])) {
      $lines['name'] = $value['name'];
    }

    return $lines;
  }

}

