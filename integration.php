<?php
/**
 * Integration with Mandelbrot Encryption System
 * This file provides the necessary hooks to integrate the blockchain rewards
 * with the existing Mandelbrot encryption system
 */

// Include the blockchain rewards system
require_once 'BlockchainRewards.php';

/**
 * Integrate blockchain rewards with CreditCardTemporalCipher
 * This class extends the original class to add blockchain rewards functionality
 */
class BlockchainEnabledCreditCardCipher extends CreditCardTemporalCipher {
    private $blockchainRewards;
    
    /**
     * Constructor initializes both the temporal cipher and blockchain rewards
     */
    public function __construct() {
        parent::__construct();
        $this->blockchainRewards = new BlockchainRewards();
    }
    
    /**
     * Override the temporalObscurityDecrypt method to add reward processing
     */
    public function temporalObscurityDecrypt($encryptedPackage, $filename, $creditCardNumber, $pin) {
        // Call the original decryption method
        $result = parent::temporalObscurityDecrypt($encryptedPackage, $filename, $creditCardNumber, $pin);
        
        // Process reward after successful decryption
        try {
            // Generate a unique message ID
            $messageId = 'msg_' . bin2hex(random_bytes(8));
            
            // Process the reward
            $rewardResult = $this->blockchainRewards->processDecryptionReward($creditCardNumber, $messageId);
            
            // Add reward information to the result
            $result['reward'] = [
                'transactionId' => $rewardResult['transaction']['id'],
                'amount' => $rewardResult['transaction']['amount'],
                'newWalletBalance' => $rewardResult['newBalance']
            ];
            
            // Log the reward
            error_log("Reward processed for decryption: " . json_encode($result['reward']));
        } catch (Exception $e) {
            error_log("Error processing reward: " . $e->getMessage());
            // Continue even if reward processing fails
            $result['reward'] = [
                'error' => $e->getMessage(),
                'status' => 'failed'
            ];
        }
        
        return $result;
    }
    
    /**
     * Get blockchain and reward statistics for the current credit card
     */
    public function getBlockchainStats($creditCardNumber) {
        try {
            $balance = $this->blockchainRewards->getCardBalance($creditCardNumber);
            $transactions = $this->blockchainRewards->getCardTransactions($creditCardNumber);
            $walletInfo = $this->blockchainRewards->getWalletInfo();
            
            return [
                'success' => true,
                'cardBalance' => $balance,
                'transactions' => $transactions,
                'walletInfo' => $walletInfo
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}

/**
 * Update the AJAX handler for Credit Card Temporal Decryption
 * Override the existing handler to use the blockchain-enabled version
 */
function updateDecryptionHandler() {
    global $action;
    
    if (isset($_GET['action']) && $_GET['action'] === 'temporal-decrypt') {
        header('Content-Type: application/json');
        
        try {
            $encryptedPackage = json_decode($_POST['encrypted_package'] ?? '{}', true);
            $filename = $_POST['filename'] ?? '';
            $creditCardNumber = $_POST['credit_card_number'] ?? '';
            $pin = $_POST['pin'] ?? '';
            
            // Use the blockchain-enabled cipher
            $cipher = new BlockchainEnabledCreditCardCipher();
            $decrypted = $cipher->temporalObscurityDecrypt($encryptedPackage, $filename, $creditCardNumber, $pin);
            
            echo json_encode([
                'status' => 'success',
                'decrypted_message' => $decrypted['decrypted'],
                'unlock_time' => $decrypted['unlock_time'],
                'reward' => $decrypted['reward'] ?? null
            ]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }
}

/**
 * Add blockchain stats endpoint to the AJAX handlers
 */
function addBlockchainStatsEndpoint() {
    global $action;
    
    if (isset($_GET['action']) && $_GET['action'] === 'blockchain-stats') {
        header('Content-Type: application/json');
        
        try {
            $creditCardNumber = $_POST['credit_card_number'] ?? '';
            
            if (empty($creditCardNumber)) {
                throw new Exception("Credit card number is required");
            }
            
            $cipher = new BlockchainEnabledCreditCardCipher();
            $stats = $cipher->getBlockchainStats($creditCardNumber);
            
            echo json_encode($stats);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
        exit;
    }
}

// Initialize the integration
function initializeBlockchainIntegration() {
    // Override the existing temporal decryption handler
    updateDecryptionHandler();
    
    // Add new blockchain stats endpoint
    addBlockchainStatsEndpoint();
}

// Call the initialization function
initializeBlockchainIntegration();
?>