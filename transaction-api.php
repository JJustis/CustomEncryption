<?php
/**
 * API Handler for Transaction System
 * Processes API requests for the payment and transaction features
 */

// Include required files
require_once 'BlockchainRewards.php';
require_once 'TransactionSystem.php';

// Initialize systems
$transactionSystem = new TransactionSystem();

// Set headers for JSON response
header('Content-Type: application/json');

// Get the action from request
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
case 'send-payment':
    // Validate required parameters
    if (!isset($_POST['senderCard']) || !isset($_POST['recipientCard']) || !isset($_POST['amount'])) {
        throw new Exception('Missing required parameters');
    }
    
    $senderCard = $_POST['senderCard'];
    $recipientCard = $_POST['recipientCard'];
    $amount = floatval($_POST['amount']);
    $memo = $_POST['memo'] ?? '';
    
    // Debug logging
    error_log("Send Payment Request Details:");
    error_log("Sender Card: $senderCard");
    error_log("Recipient Card: $recipientCard");
    error_log("Amount: $amount");
    error_log("Memo: $memo");
    
    // Process the payment
    try {
        $result = $transactionSystem->processPayment($senderCard, $recipientCard, $amount, $memo);
        
        // More detailed logging of the result
        error_log("Transaction Result: " . json_encode($result));
        
        echo json_encode([
            'success' => true,
            'transaction' => $result
        ]);
    } catch (Exception $e) {
        // Log any exceptions in processing
        error_log("Payment Processing Error: " . $e->getMessage());
        throw $e;
    }
    break;
            
        case 'send-batch-payment':
            // Validate required parameters
            if (!isset($_POST['senderCard']) || !isset($_POST['recipients'])) {
                throw new Exception('Missing required parameters');
            }
            
            $senderCard = $_POST['senderCard'];
            $recipients = json_decode($_POST['recipients'], true);
            
            if (!is_array($recipients)) {
                throw new Exception('Recipients must be a valid JSON array');
            }
            
            // Process the batch payment
            $result = $transactionSystem->processBatchPayment($senderCard, $recipients);
            
            echo json_encode([
                'success' => true,
                'batchTransaction' => $result
            ]);
            break;
            
        case 'request-payment':
            // Validate required parameters
            if (!isset($_POST['requestorCard']) || !isset($_POST['payerCard']) || !isset($_POST['amount'])) {
                throw new Exception('Missing required parameters');
            }
            
            

$requestorCard = $_POST['requestorCard'];
            $payerCard = $_POST['payerCard'];
            $amount = floatval($_POST['amount']);
            $memo = $_POST['memo'] ?? '';
            
            // Process the payment request
            $result = $transactionSystem->requestPayment($requestorCard, $payerCard, $amount, $memo);
            
            echo json_encode([
                'success' => true,
                'paymentRequest' => $result
            ]);
            break;
            
        case 'respond-to-request':
            // Validate required parameters
            if (!isset($_POST['requestId']) || !isset($_POST['payerCard']) || !isset($_POST['action'])) {
                throw new Exception('Missing required parameters');
            }
            
            $requestId = $_POST['requestId'];
            $payerCard = $_POST['payerCard'];
            $action = $_POST['action'];
            
            // Process the response to payment request
            $result = $transactionSystem->respondToPaymentRequest($requestId, $payerCard, $action);
            
            echo json_encode([
                'success' => true,
                'response' => $result
            ]);
            break;
            
        case 'get-transaction-history':
            // Validate required parameters
            if (!isset($_POST['cardNumber'])) {
                throw new Exception('Missing card number');
            }
            
            $cardNumber = $_POST['cardNumber'];
            
            // Get transaction history
            $history = $transactionSystem->getTransactionHistory($cardNumber);
            
            echo json_encode([
                'success' => true,
                'history' => $history
            ]);
            break;
            
case 'get-pending-requests':
    try {
        // Validate required parameters
        if (!isset($_POST['cardNumber'])) {
            throw new Exception('Missing card number');
        }
        
        $cardNumber = $_POST['cardNumber'];
        
        // Get pending payment requests
        $pendingRequests = $transactionSystem->getPendingRequests($cardNumber);
        
        // Always return a valid JSON response
        echo json_encode([
            'success' => true,
            'pendingRequests' => $pendingRequests
        ]);
    } catch (Exception $e) {
        // Log the error
        error_log('Pending Requests Error: ' . $e->getMessage());
        
        // Return an error response
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
            'pendingRequests' => []
        ]);
    }
    break;
            
        case 'generate-payment-qr':
            // Validate required parameters
            if (!isset($_POST['recipientCard']) || !isset($_POST['amount'])) {
                throw new Exception('Missing required parameters');
            }
            
            $recipientCard = $_POST['recipientCard'];
            $amount = floatval($_POST['amount']);
            $memo = $_POST['memo'] ?? '';
            
            // Generate QR code
            $blockchainRewards = new BlockchainRewards();
            $qrCode = $blockchainRewards->generatePaymentQRCode($recipientCard, $amount, $memo);
            
            echo json_encode([
                'success' => true,
                'qrCode' => $qrCode
            ]);
            break;
            
        case 'scan-payment-qr':
            // Validate required parameters
            if (!isset($_POST['qrData']) || !isset($_POST['senderCard'])) {
                throw new Exception('Missing required parameters');
            }
            
            $qrData = $_POST['qrData'];
            $senderCard = $_POST['senderCard'];
            
            // Parse the QR code data
            $paymentData = json_decode($qrData, true);
            
            if (!$paymentData || !isset($paymentData['type']) || $paymentData['type'] !== 'payment') {
                throw new Exception('Invalid QR code data');
            }
            
            // Extract recipient card (need to look up the full number from db)
            $recipientCardMasked = $paymentData['recipient'];
            $recipientPrefix = substr($recipientCardMasked, 0, 4);
            $recipientSuffix = substr($recipientCardMasked, -4);
            
            // In a real system, you would look up the full recipient card number
            // Simplification for demo
            $recipientCard = $recipientPrefix . "12345678" . $recipientSuffix;
            
            // Process the payment
            $result = $transactionSystem->processPayment(
                $senderCard,
                $recipientCard,
                $paymentData['amount'],
                $paymentData['memo'] ?? 'QR code payment'
            );
            
            echo json_encode([
                'success' => true,
                'transaction' => $result
            ]);
            break;
            
        default:
            throw new Exception('Unknown action');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>