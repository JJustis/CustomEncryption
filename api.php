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
            try {
                // Ensure BlockchainRewards is fully initialized
                $blockchainRewards = new BlockchainRewards();
                
                // Verify blockchain data exists
                $blockchain = $blockchainRewards->getBlockchain();
                
                // Validate blockchain structure
                if (!is_array($blockchain) || empty($blockchain)) {
                    throw new Exception("Invalid blockchain data");
                }
                
                // Consistent JSON response
                header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'blockchain' => $blockchain
]);
                exit;
            } catch (Exception $e) {
                // Detailed error logging
                error_log('Blockchain Fetch Error: ' . $e->getMessage() . "\nStack Trace: " . $e->getTraceAsString());
                
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => 'An error occurred while fetching the blockchain. Please try again later.'
                ]);
                exit;
            }
            
        case 'get-mining-requirements':
            // Get mining difficulty and requirements
            try {
                $requirements = $blockchainRewards->getMiningRequirements();
                echo json_encode([
                    'success' => true,
                    'requirements' => $requirements
                ]);
            } catch (Exception $e) {
                error_log('Mining Requirements Error: ' . $e->getMessage() . "\nStack Trace: " . $e->getTraceAsString());
                
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => 'Failed to retrieve mining requirements. Please try again.'
                ]);
            }
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
            
            try {
                // Process the mining submission
                $result = $blockchainRewards->mineBlock($minerInfo, $proofOfWork);
                echo json_encode([
                    'success' => true,
                    'result' => $result
                ]);
            } catch (Exception $e) {
                error_log('Proof of Work Submission Error: ' . $e->getMessage() . "\nStack Trace: " . $e->getTraceAsString());
                
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => 'An error occurred while processing the proof of work. Please try again.'
                ]);
            }
            break;
            
        case 'process-decryption-reward':
            // Validate required parameters
            if (!isset($_POST['creditCardNumber']) || !isset($_POST['messageId'])) {
                throw new Exception('Missing required parameters');
            }
            
            $creditCardNumber = $_POST['creditCardNumber'];
            $messageId = $_POST['messageId'];
            
            try {
                // Process the reward
                $result = $blockchainRewards->processDecryptionReward($creditCardNumber, $messageId);
                echo json_encode([
                    'success' => true,
                    'result' => $result
                ]);
            } catch (Exception $e) {
                error_log('Decryption Reward Processing Error: ' . $e->getMessage() . "\nStack Trace: " . $e->getTraceAsString());
                
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => 'Failed to process decryption reward. Please try again later.'
                ]);
            }
            break;
            
        case 'get-wallet-info':
            try {
                // Get wallet balance and statistics
                $walletInfo = $blockchainRewards->getWalletInfo();
                echo json_encode([
                    'success' => true,
                    'wallet' => $walletInfo
                ]);
            } catch (Exception $e) {
                error_log('Wallet Info Retrieval Error: ' . $e->getMessage() . "\nStack Trace: " . $e->getTraceAsString());
                
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => 'Unable to retrieve wallet information. Please try again.'
                ]);
            }
            break;
            
        case 'get-card-balance':
            // Validate required parameters
            if (!isset($_POST['creditCardNumber'])) {
                throw new Exception('Missing credit card number');
            }
            
            $creditCardNumber = $_POST['creditCardNumber'];
            
            try {
                // Get card balance
                $balance = $blockchainRewards->getCardBalance($creditCardNumber);
                $transactions = $blockchainRewards->getCardTransactions($creditCardNumber);
                
                echo json_encode([
                    'success' => true,
                    'balance' => $balance,
                    'transactions' => $transactions
                ]);
            } catch (Exception $e) {
                error_log('Card Balance Retrieval Error: ' . $e->getMessage() . "\nStack Trace: " . $e->getTraceAsString());
                
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => 'Failed to retrieve card balance. Please try again later.'
                ]);
            }
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