<?php

namespace Drupal\zoho_webhook\Plugin\WebformHandler;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\Plugin\WebformHandlerBase;
use Drupal\webform\WebformSubmissionInterface;
use Drupal\zoho_webhook\Service\ZohoApiClient;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Handles webform submissions to integrate with Zoho.
 *
 * @WebformHandler(
 *   id = "zoho_webhook_handler",
 *   label = @Translation("Zoho Webhook Handler"),
 *   category = @Translation("Zoho"),
 *   description = @Translation("Handles webform submissions to create Zoho subscriptions."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = TRUE
 * )
 */
class ZohoWebhookHandler extends WebformHandlerBase {

  protected $config;

    /**
   * Constructor.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->config = \Drupal::service('settings')->get('zoho_billing');
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
      return new static($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function confirmForm(array &$form, FormStateInterface $form_state, WebformSubmissionInterface $webform_submission) {
    // Get the data from the form submission.
    $data = $webform_submission->getData();

    // Format customer data for Zoho.
    $customerData = [
        'display_name' => $data['voornaam'] . ' ' . $data['achternaam'],
        'email' => $data['e_mailadres'],
        'first_name' => $data['voornaam'],
        'last_name' => $data['achternaam'],
        'billing_address' => [
            'address' => $data['adres'] . ' ' . $data['huisnummer'],
            'city' => json_decode($data['woonplaats'], TRUE)['value'], // Extract city.
            'zip' => $data['postcode'],
        ],
    ];

    try {
        /** @var \Drupal\zoho_webhook\Service\ZohoApiClient $zohoApiClient */
        $zohoApiClient = \Drupal::service('zoho_webhook.zoho_api_client');

        // 1️⃣ Create a new customer in Zoho.
        $customerResponse = $zohoApiClient->createCustomer($customerData);
        if (!isset($customerResponse['customer']['customer_id'])) {
            throw new \Exception('Failed to create customer.');
        }

        $customerId = $customerResponse['customer']['customer_id'];

        // 2️⃣ Determine payment method & configure subscription.
        $isCashPayment = ($data['betaalopties'] === 'cash');
        $dateParts = explode(',', $data['bezorgdatum']);
        $cleanDate = trim($dateParts[0]); // "7-02-2025"

        // Convert it to YYYY-MM-DD format correctly
        $formattedStartDate = \DateTime::createFromFormat('j-m-Y', $cleanDate)->format('Y-m-d');

        $subscriptionData = [
            'customer_id' => $customerId,
            'plan' => [
                'plan_code' => $this->getPlanCode($data['frequentie'], $data['pakketkeuze']),
            ],
            'starts_at' => $formattedStartDate,
        ];

        if ($isCashPayment) {
            $subscriptionData['auto_collect'] = FALSE;
            // 🟢 Cash Payment: Create subscription directly (no Hosted Payment Page).
            $response = $zohoApiClient->createDirectSubscription($subscriptionData);

            if (isset($response['subscription']['subscription_id'])) {
                \Drupal::logger('zoho_webhook')->info('Cash subscription created successfully. Subscription ID: @id', [
                    '@id' => $response['subscription']['subscription_id'],
                ]);
                return;
            } else {
                throw new \Exception('Failed to create cash subscription.');
            }
        } else {
          $subscriptionData['auto_collect'] = TRUE;
          $subscriptionData['redirect_url'] = $this->config['webform_redirect_url'] . '?submission_id=' . $webform_submission->id();
          \Drupal::logger('zoho_webhook')->info('Subscription created successfully. Data: @data', [
            '@data' => json_encode($subscriptionData),
          ]);
            // 💳 Online Payment: Create subscription via Hosted Payment Page.
            $response = $zohoApiClient->createSubscription($subscriptionData);
            
            if (isset($response['hostedpage']['url'])) {
                $hostedPageUrl = $response['hostedpage']['url'];
                
                // Store hosted page URL in the submission results.
                $webform_submission->setElementData('hosted_page_url', $hostedPageUrl);
                \Drupal::logger('zoho_webhook')->info('Subscription created successfully. Hosted page URL: @url', [
                  '@url' => $hostedPageUrl,
                ]);
                
                $response = new RedirectResponse($hostedPageUrl); 
                $response->send();
            } else {
                \Drupal::logger('zoho_webhook')->info('Subscription not created. Hosted page URL: @url, Data: @data', [
                  '@data' => json_encode($response),
                ]);
                throw new \Exception('Failed to retrieve hosted page URL from Zoho.');
            }
        }
    } catch (\Exception $e) {
        \Drupal::logger('zoho_webhook')->error('Zoho API error: @error', [
            '@error' => $e->getMessage(),
        ]);
    }
  }

  private function getPlanCode($frequentie, $pakketkeuze) {
    // Normalize pakketkeuze to uppercase
    $pakketkeuze = strtolower($pakketkeuze);

      // Determine the correct plan code based on frequency
      if ($frequentie == 4) {
          return "{$pakketkeuze}_week"; // M_week, L_week, XL_week
      } elseif ($frequentie == 2) {
          return "{$pakketkeuze}_biweekly"; // M_biweekly, L_biweekly, XL_biweekly
      }

      throw new \Exception("Invalid frequentie or pakketkeuze: frequentie=$frequentie, pakketkeuze=$pakketkeuze");
  }
}
