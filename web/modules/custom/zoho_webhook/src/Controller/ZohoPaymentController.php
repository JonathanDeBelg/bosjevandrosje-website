<?php

namespace Drupal\zoho_webhook\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\webform\Entity\WebformSubmission;
use Drupal\webform\Entity\Webform;

/**
 * Controller for handling Zoho payment response.
 */
class ZohoPaymentController extends ControllerBase {

  /**
   * Handle Zoho's redirect after payment.
   */
  public function handlePaymentResponse(Request $request) {
    // Retrieve Zoho response data
    $zohoResponse = $request->query->all();

    // Log the received response
    \Drupal::logger('zoho_webhook')->info('Received Zoho Payment Response: @response', [
      '@response' => json_encode($zohoResponse),
    ]);

    // Get the Webform Submission ID from the URL query parameter
    $submissionId = $request->query->get('submission_id');
    if (!$submissionId) {
      \Drupal::logger('zoho_webhook')->error('Missing submission ID in Zoho response.');
      return new RedirectResponse('/subscription/payment-failed');
    }

    // Load the webform submission
    $submission = WebformSubmission::load($submissionId);
    if (!$submission) {
      \Drupal::logger('zoho_webhook')->error('Webform submission not found. Submission ID: @id', [
        '@id' => $submissionId,
      ]);
      return new RedirectResponse('/subscription/payment-failed');
    }

    // Retrieve the Webform ID
    $webformId = $submission->getWebform()->id();

    // Mark the submission as completed
    $submission->setElementData('payment_status', $zohoResponse['status'] ?? 'unknown');
    $submission->save();

    // Get confirmation URL dynamically from the webform settings
    $webform = Webform::load($webformId);
    if ($webform && $webform->hasSetting('confirmation_url')) {
      $confirmationUrl = $webform->getSetting('confirmation_url');
    } else {
      $confirmationUrl = '/thank-you'; // Fallback confirmation page
    }

    // Redirect user to the confirmation page
    return new RedirectResponse($confirmationUrl);
  }
}
