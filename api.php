<?php
/**
 * API Handler for Blockchain Rewards System
 * Handles all AJAX requests for mining, rewards, and blockchain operations
 */

require_once 'BlockchainRewards.php';

// Initialize the blockchain rewards system
$blockchainRewards = new BlockchainRewards();

// Set headers for JSON response
header('Content-Type: application/json');

// Get the action from request
$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'get-blockchain':
            // Return the entire blockchain
            $blockchain = $blockchainRewards->getBlockchain();
            echo json_encode([
                'success' => true,
                'blockchain' => $blockchain
            ]);
            break;
            
        case 'get-mining-requirements':
            // Get mining difficulty and requirements
            $requirements = $blockchainRewards->getMiningRequirements();
            echo json_encode([
                'success' => true,
                'requirements' => $requirements
            ]);
            break;
            
        case 'submit-proof-of-work':
    // Validate required parameters
    if (!isset($_POST['minerInfo']) || !isset($_POST['proofOfWork'])) {
        error_log('Missing required parameters for proof of work');
        throw new Exception('Missing required parameters');
    }
    
    // Parse JSON data
    $minerInfo = json_decode($_POST['minerInfo'], true);
    $proofOfWork = json_decode($_POST['proofOfWork'], true);
    
    // Debug logging
    error_log('Received minerInfo: ' . $_POST['minerInfo']);
    error_log('Received proofOfWork: ' . $_POST['proofOfWork']);
    
    // Validate parsed data
    if (!$minerInfo || !$proofOfWork) {
        error_log('Invalid JSON data in proof of work submission');
        throw new Exception('Invalid JSON data');
    }
    
    // Process the mining submission
    $result = $blockchainRewards->mineBlock($minerInfo, $proofOfWork);
    echo json_encode([
        'success' => true,
        'result' => $result
    ]);
    break;
            
        case 'process-decryption-reward':
            // Validate required parameters
            if (!isset($_POST['creditCardNumber']) || !isset($_POST['messageId'])) {
                throw new Exception('Missing required parameters');
            }
            
            $creditCardNumber = $_POST['creditCardNumber'];
            $messageId = $_POST['messageId'];
            
            // Process the reward
            $result = $blockchainRewards->processDecryptionReward($creditCardNumber, $messageId);
            echo json_encode([
                'success' => true,
                'result' => $result
            ]);
            break;
            
        case 'get-wallet-info':
            // Get wallet balance and statistics
            $walletInfo = $blockchainRewards->getWalletInfo();
            echo json_encode([
                'success' => true,
                'wallet' => $walletInfo
            ]);
            break;
            
        case 'get-card-balance':
            // Validate required parameters
            if (!isset($_POST['creditCardNumber'])) {
                throw new Exception('Missing credit card number');
            }
            
            $creditCardNumber = $_POST['creditCardNumber'];
            
            // Get card balance
            $balance = $blockchainRewards->getCardBalance($creditCardNumber);
            $transactions = $blockchainRewards->getCardTransactions($creditCardNumber);
            
            echo json_encode([
                'success' => true,
                'balance' => $balance,
                'transactions' => $transactions
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