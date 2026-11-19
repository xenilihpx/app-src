<?php
/**
 * Stripe webhook for Stripe Express Checkout (Apple Pay / Google Pay / Link) orders and upsells.
 *
 * When the PaymentIntent was still "processing" at checkout (asynchronous payment, e.g. Link bank debit),
 * OfferApi::stripeExpressPendingOrder() leaves the Checkout Champ order in QA and flags the PaymentIntent
 * with metadata cocQaPending=1. This webhook finishes it once Stripe knows the outcome:
 *
 *   payment_intent.processing      -> flag cocQaPending=1 (backup, in case the checkout could not flag it)
 *   payment_intent.succeeded       -> order/qa APPROVE
 *   payment_intent.payment_failed  -> order/qa DECLINE
 *   payment_intent.canceled        -> order/qa DECLINE
 *
 * Only PaymentIntents flagged cocQaPending=1 are touched, so orders already approved/declined at checkout
 * are ignored. After handling, the flag becomes approved / declined / decline_manual / error (idempotent).
 *
 * Upsells are NOT imported into Checkout Champ until Stripe has charged them. When an upsell payment is still
 * "processing" at checkout, OfferApi::stripeExpressUpsellImport() does not import it, stores the upsale payload
 * on the PaymentIntent (metadata cocUpsale) and flags it cocImportPending=1. This webhook then:
 *
 *   payment_intent.processing      -> flag cocImportPending=1 (backup)
 *   payment_intent.succeeded       -> upsale/import + order/qa APPROVE (refund if the import fails)
 *   payment_intent.payment_failed  -> nothing imported, customer note only
 *   payment_intent.canceled        -> nothing imported, customer note only
 *
 * After handling, cocImportPending becomes imported / approve_manual / declined / refunded / error (idempotent).
 *
 * Setup:
 *   Stripe Dashboard > Developers > Webhooks > Add endpoint: https://<site>/src/stripe-webhook.php
 *   events: payment_intent.processing, payment_intent.succeeded, payment_intent.payment_failed, payment_intent.canceled
 *   .env: STRIPE_WEBHOOK_SECRET=whsec_...          (live mode endpoint signing secret)
 *         STRIPE_WEBHOOK_SECRET_SANDBOX=whsec_...  (test mode endpoint signing secret, optional)
 */

error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);
header('Content-Type: application/json');

require_once(__DIR__.'/KonnektiveApi.php');
require_once(dirname(__DIR__).'/vendor/autoload.php'); // Stripe SDK

function stripeWebhookRespond($httpCode, $message){
    http_response_code($httpCode);
    echo json_encode(["message" => $message]);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    stripeWebhookRespond(405, "Method not allowed");
}

$kApi = new KonnektiveApi(); // also loads .env into getenv()

// ---------------------------------------------------------------------
// Verify the Stripe signature (live and/or sandbox endpoint secret)
// ---------------------------------------------------------------------
$payload = @file_get_contents('php://input');
$signature = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';
$secrets = array_filter([getenv('STRIPE_WEBHOOK_SECRET'), getenv('STRIPE_WEBHOOK_SECRET_SANDBOX')]);

if (empty($secrets)) {
    error_log("stripe-webhook: STRIPE_WEBHOOK_SECRET is not set in .env");
    stripeWebhookRespond(500, "Webhook secret not configured");
}

$event = null;
foreach ($secrets as $secret) {
    try {
        $event = \Stripe\Webhook::constructEvent($payload, $signature, $secret);
        break;
    } catch (\UnexpectedValueException $e) {
        stripeWebhookRespond(400, "Invalid payload");
    } catch (\Stripe\Exception\SignatureVerificationException $e) {
        // try the next secret
    }
}
if ($event === null) {
    stripeWebhookRespond(400, "Invalid signature");
}

$handledEvents = ['payment_intent.processing', 'payment_intent.succeeded', 'payment_intent.payment_failed', 'payment_intent.canceled'];
if (!in_array($event->type, $handledEvents, true)) {
    stripeWebhookRespond(200, "Ignored event ".$event->type);
}

$secretKey = $event->livemode ? getenv('LIVE_SECRETKEY') : getenv('SANDBOX_SECRETKEY');
if (!$secretKey) {
    error_log("stripe-webhook: Stripe secret key for ".($event->livemode ? "live" : "sandbox")." mode is not set");
    stripeWebhookRespond(500, "Stripe secret key not configured");
}
\Stripe\Stripe::setApiKey($secretKey);

// Re-read the PaymentIntent so the latest status / metadata is used (events can arrive out of order)
try {
    $paymentIntent = \Stripe\PaymentIntent::retrieve($event->data->object->id);
} catch (\Exception $e) {
    error_log("stripe-webhook: failed to retrieve PaymentIntent ".$event->data->object->id.": ".$e->getMessage());
    stripeWebhookRespond(500, "Unable to retrieve PaymentIntent"); // Stripe retries
}

$metadata = $paymentIntent->metadata;
$orderId = $metadata['orderId'] ?? '';
$mainOrderId = $metadata['mainOrderId'] ?? '';
$cocCustomerId = $metadata['cocCustomerId'] ?? '';
$pendingFlag = $metadata['cocQaPending'] ?? '';
$paySource = $metadata['paySource'] ?? '';
$upsalePayload = $metadata['cocUpsale'] ?? '';
$importFlag = $metadata['cocImportPending'] ?? '';

function stripeWebhookSetFlag($paymentIntentId, $value, $key = 'cocQaPending', $extra = []){
    try {
        \Stripe\PaymentIntent::update($paymentIntentId, ['metadata' => array_merge([$key => $value], $extra)]);
    } catch (\Exception $e) {
        error_log("stripe-webhook: failed to set ".$key."=".$value." on ".$paymentIntentId.": ".$e->getMessage());
    }
}

function stripeWebhookNote($kApi, $cocCustomerId, $message){
    if ($cocCustomerId !== '') {
        $kApi->customer_add_note(["customerId" => $cocCustomerId, "message" => $message]);
    }
}

// =====================================================================
// Pending upsells: NOT in Checkout Champ yet, import them only once Stripe has charged them
// =====================================================================
if ($orderId === '' && $upsalePayload !== '') {

    $noteSuffix = " Main order: ".$mainOrderId.
                  ", Stripe Customer id: ".$paymentIntent->customer.
                  ", Payment Type: ".$paySource.
                  ", Payment Intent Id: ".$paymentIntent->id.
                  ", Amount: ".(in_array(strtoupper($paymentIntent->currency), ['BIF','CLP','DJF','GNF','JPY','KMF','KRW','MGA','PYG','RWF','UGX','VND','VUV','XAF','XOF','XPF'], true)
                                    ? $paymentIntent->amount : $paymentIntent->amount / 100)." ".strtoupper($paymentIntent->currency);

    // Backup flag: the upsell payment went asynchronous, the checkout did not import it
    if ($event->type === 'payment_intent.processing') {
        if ($importFlag === '') {
            stripeWebhookSetFlag($paymentIntent->id, '1', 'cocImportPending');
        }
        stripeWebhookRespond(200, "Upsell flagged for import");
    }

    if ($importFlag !== '1') {
        // Imported at checkout (synchronous charge), or already handled by an earlier webhook delivery
        stripeWebhookRespond(200, "Nothing to do (cocImportPending=".($importFlag === '' ? 'none' : $importFlag).")");
    }

    // payment_intent.succeeded -> import the upsell into Checkout Champ, then APPROVE it
    if ($paymentIntent->status === 'succeeded') {
        $upsale = json_decode($upsalePayload, true);
        if (!is_array($upsale) || empty($upsale['orderId'])) {
            stripeWebhookSetFlag($paymentIntent->id, 'error', 'cocImportPending');
            error_log("stripe-webhook: invalid cocUpsale payload on PaymentIntent ".$paymentIntent->id.": ".$upsalePayload);
            stripeWebhookNote($kApi, $cocCustomerId, "Upsell payment SUCCEEDED in Stripe but the upsell could not be imported automatically (invalid payload), import it manually.".$noteSuffix);
            stripeWebhookRespond(200, "Invalid upsale payload, flagged for manual review");
        }
        $upsale['skipQA'] = 0;
        $upsale['externalOrderId'] = $paymentIntent->id;

        $resp = $kApi->import_upsale($upsale);
        $decode = json_decode($resp);

        if (!isset($decode->result)) {
            error_log("stripe-webhook: upsale/import no response for main order ".$mainOrderId.", PaymentIntent ".$paymentIntent->id);
            stripeWebhookRespond(500, "Checkout Champ did not respond"); // Stripe retries
        }

        if ($decode->result === "SUCCESS" && !empty($decode->message->orderId)) {
            $upsellOrderId = $decode->message->orderId;

            $approveResp = $kApi->order_qa(['orderId' => $upsellOrderId, 'action' => 'APPROVE']);
            $approveDecode = json_decode($approveResp);
            $approved = isset($approveDecode->result) && $approveDecode->result === "SUCCESS";

            // orderId is stored so the regular QA flow above never touches this upsell again
            stripeWebhookSetFlag($paymentIntent->id, $approved ? 'imported' : 'approve_manual', 'cocImportPending', ['orderId' => (string)$upsellOrderId]);
            if ($approved) {
                stripeWebhookNote($kApi, $cocCustomerId, "Upsell order: ".$upsellOrderId." IMPORTED and APPROVED automatically: Stripe Express payment succeeded.".$noteSuffix);
                stripeWebhookRespond(200, "Upsell ".$upsellOrderId." imported and approved");
            }
            error_log("stripe-webhook: order_qa APPROVE failed for upsell order ".$upsellOrderId.", PaymentIntent ".$paymentIntent->id.": ".$approveResp);
            stripeWebhookNote($kApi, $cocCustomerId, "Upsell order: ".$upsellOrderId." IMPORTED automatically (Stripe Express payment succeeded) but the QA APPROVE failed, approve it manually. Checkout Champ: ".json_encode($approveDecode->message ?? $approveResp).".".$noteSuffix);
            stripeWebhookRespond(200, "Upsell imported, approve failed, flagged for manual review");
        }

        // Import failed: the customer must not stay charged for an upsell that is not in Checkout Champ -> refund
        error_log("stripe-webhook: upsale/import failed for main order ".$mainOrderId.", PaymentIntent ".$paymentIntent->id.": ".$resp);
        try {
            \Stripe\Refund::create(['payment_intent' => $paymentIntent->id]);
            stripeWebhookSetFlag($paymentIntent->id, 'refunded', 'cocImportPending');
            stripeWebhookNote($kApi, $cocCustomerId, "Upsell NOT completed: Stripe Express payment succeeded but the Checkout Champ import failed, payment REFUNDED. Checkout Champ: ".json_encode($decode->message ?? $resp).".".$noteSuffix);
            stripeWebhookRespond(200, "Upsell import failed, payment refunded");
        } catch (\Exception $e) {
            stripeWebhookSetFlag($paymentIntent->id, 'error', 'cocImportPending');
            error_log("stripe-webhook: refund failed for PaymentIntent ".$paymentIntent->id.": ".$e->getMessage());
            stripeWebhookNote($kApi, $cocCustomerId, "Upsell CHARGED in Stripe, Checkout Champ import AND automatic refund failed, import or refund it manually. Checkout Champ: ".json_encode($decode->message ?? $resp).", Refund error: ".$e->getMessage().".".$noteSuffix);
            stripeWebhookRespond(200, "Upsell import and refund failed, flagged for manual review");
        }
    }

    // payment_intent.payment_failed / payment_intent.canceled -> not charged, nothing is imported
    if (in_array($paymentIntent->status, ['requires_payment_method', 'canceled'], true)) {
        $reason = $paymentIntent->last_payment_error->message
               ?? ($paymentIntent->cancellation_reason ? "canceled (".$paymentIntent->cancellation_reason.")" : $paymentIntent->status);
        stripeWebhookSetFlag($paymentIntent->id, 'declined', 'cocImportPending');
        stripeWebhookNote($kApi, $cocCustomerId, "Upsell declined: Stripe Express payment failed (".$reason."), nothing imported into Checkout Champ.".$noteSuffix);
        stripeWebhookRespond(200, "Upsell payment failed, nothing imported");
    }

    stripeWebhookRespond(200, "PaymentIntent ".$paymentIntent->id." is ".$paymentIntent->status.", waiting");
}

if ($orderId === '') {
    stripeWebhookRespond(200, "Not a Stripe Express Checkout order");
}

// Backup flag: the payment went asynchronous, make sure the final event is handled
if ($event->type === 'payment_intent.processing') {
    if ($pendingFlag === '') {
        stripeWebhookSetFlag($paymentIntent->id, '1');
    }
    stripeWebhookRespond(200, "Flagged as pending");
}

if ($pendingFlag !== '1') {
    // Approved / declined at checkout, or already handled by an earlier webhook delivery
    stripeWebhookRespond(200, "Nothing to do (cocQaPending=".($pendingFlag === '' ? 'none' : $pendingFlag).")");
}

$noteSuffix = " Stripe Customer id: ".$paymentIntent->customer.
              ", Payment Type: ".$paySource.
              ", Payment Intent Id: ".$paymentIntent->id;

// ---------------------------------------------------------------------
// payment_intent.succeeded -> APPROVE the Checkout Champ order
// ---------------------------------------------------------------------
if ($paymentIntent->status === 'succeeded') {
    $resp = $kApi->order_qa(['orderId' => $orderId, 'action' => 'APPROVE']);
    $decode = json_decode($resp);

    if (!isset($decode->result)) {
        error_log("stripe-webhook: order_qa APPROVE no response for order ".$orderId.", PaymentIntent ".$paymentIntent->id);
        stripeWebhookRespond(500, "Checkout Champ did not respond"); // Stripe retries
    }

    if ($decode->result === "SUCCESS") {
        stripeWebhookSetFlag($paymentIntent->id, 'approved');
        stripeWebhookNote($kApi, $cocCustomerId, $orderId." APPROVED automatically: Stripe Express payment succeeded.".$noteSuffix);
        stripeWebhookRespond(200, "Order ".$orderId." approved");
    }

    stripeWebhookSetFlag($paymentIntent->id, 'error');
    error_log("stripe-webhook: order_qa APPROVE failed for order ".$orderId.", PaymentIntent ".$paymentIntent->id.": ".$resp);
    stripeWebhookNote($kApi, $cocCustomerId, $orderId." Stripe Express payment SUCCEEDED but the automatic QA APPROVE failed, approve it manually. Checkout Champ: ".json_encode($decode->message ?? $resp).".".$noteSuffix);
    stripeWebhookRespond(200, "Approve failed, flagged for manual review");
}

// ---------------------------------------------------------------------
// payment_intent.payment_failed / payment_intent.canceled -> DECLINE the Checkout Champ order
// ---------------------------------------------------------------------
if (in_array($paymentIntent->status, ['requires_payment_method', 'canceled'], true)) {
    $reason = $paymentIntent->last_payment_error->message
           ?? ($paymentIntent->cancellation_reason ? "canceled (".$paymentIntent->cancellation_reason.")" : $paymentIntent->status);

    if ($mainOrderId !== '' && (string)$orderId === (string)$mainOrderId) {
        // Upsell attached to the already PAID main order: a QA DECLINE would decline the main order too
        stripeWebhookSetFlag($paymentIntent->id, 'decline_manual');
        stripeWebhookNote($kApi, $cocCustomerId, $orderId." Stripe Express upsell payment FAILED (".$reason."). The upsell is on the main order, QA decline skipped, remove the upsell item manually.".$noteSuffix);
        stripeWebhookRespond(200, "Upsell on main order, flagged for manual removal");
    }

    $resp = $kApi->order_qa(['orderId' => $orderId, 'action' => 'DECLINE']);
    $decode = json_decode($resp);

    if (!isset($decode->result)) {
        error_log("stripe-webhook: order_qa DECLINE no response for order ".$orderId.", PaymentIntent ".$paymentIntent->id);
        stripeWebhookRespond(500, "Checkout Champ did not respond"); // Stripe retries
    }

    if ($decode->result === "SUCCESS") {
        stripeWebhookSetFlag($paymentIntent->id, 'declined');
        stripeWebhookNote($kApi, $cocCustomerId, $orderId." DECLINED automatically: Stripe Express payment failed (".$reason.").".$noteSuffix);
        stripeWebhookRespond(200, "Order ".$orderId." declined");
    }

    stripeWebhookSetFlag($paymentIntent->id, 'error');
    error_log("stripe-webhook: order_qa DECLINE failed for order ".$orderId.", PaymentIntent ".$paymentIntent->id.": ".$resp);
    stripeWebhookNote($kApi, $cocCustomerId, $orderId." Stripe Express payment FAILED (".$reason.") but the automatic QA DECLINE failed, decline it manually. Checkout Champ: ".json_encode($decode->message ?? $resp).".".$noteSuffix);
    stripeWebhookRespond(200, "Decline failed, flagged for manual review");
}

// Still processing / other state: wait for the next event
stripeWebhookRespond(200, "PaymentIntent ".$paymentIntent->id." is ".$paymentIntent->status.", waiting");
