<?php
/**
 * Extension to BlockchainRewards for transaction support
 * This adds methods to the BlockchainRewards class to handle pending transactions
 */

// Extend the BlockchainRewards class with these methods

/**
 * Add a transaction to the pending transactions list
 * 
 * @param array $transaction The transaction to add
 * @return bool Success status
 */
public function addToPendingTransactions($transaction) {
    // Get current blockchain data
    $blockchain = $this->getBlockchain();
    
    // Add transaction to pending transactions
    $blockchain['pendingTransactions'][] = $transaction;
    
    // Update statistics
    $blockchain['statistics']['totalTransactions']++;
    
    // Save updated blockchain
    $this->saveBlockchain($blockchain);
    
    return true;
}

/**
 * Get all pending transactions
 * 
 * @return array The pending transactions
 */
public function getPendingTransactions() {
    $blockchain = $this->getBlockchain();
    return $blockchain['pendingTransactions'] ?? [];
}

/**
 * Clear pending transactions
 * 
 * @return bool Success status
 */
public function clearPendingTransactions() {
    $blockchain = $this->getBlockchain();
    $blockchain['pendingTransactions'] = [];
    $this->saveBlockchain($blockchain);
    return true;
}

/**
 * Get transaction history for a specific account
 * 
 * @param string $accountIdentifier The account identifier (credit card number)
 * @return array The transaction history
 */
public function getAccountTransactionHistory($accountIdentifier) {
    $maskedIdentifier = $this->maskAccountIdentifier($accountIdentifier);
    $blockchain = $this->getBlockchain();
    $history = [];
    
    // Check pending transactions
    if (isset($blockchain['pendingTransactions'])) {
        foreach ($blockchain['pendingTransactions'] as $transaction) {
            if ($this->transactionInvolvesAccount($transaction, $maskedIdentifier)) {
                $history[] = array_merge($transaction, ['status' => 'pending']);
            }
        }
    }
    
    // Check all blocks for transactions
    if (isset($blockchain['blocks'])) {
        foreach ($blockchain['blocks'] as $block) {
            if (isset($block['data']['transactions'])) {
                foreach ($block['data']['transactions'] as $transaction) {
                    if ($this->transactionInvolvesAccount($transaction, $maskedIdentifier)) {
                        $history[] = array_merge($transaction, ['status' => 'confirmed', 'blockIndex' => $block['index']]);
                    }
                }
            }
        }
    }
    
    // Sort by timestamp (newest first)
    usort($history, function($a, $b) {
        return $b['timestamp'] - $a['timestamp'];
    });
    
    return $history;
}

/**
 * Mask an account identifier for privacy
 * 
 * @param string $accountIdentifier The account identifier (credit card number)
 * @return string The masked account identifier
 */
private function maskAccountIdentifier($accountIdentifier) {
    // Simple masking: show first 4 and last 4 digits
    return substr($accountIdentifier, 0, 4) . '********' . substr($accountIdentifier, -4);
}

/**
 * Check if a transaction involves a specific account
 * 
 * @param array $transaction The transaction to check
 * @param string $maskedIdentifier The masked account identifier
 * @return bool True if the account is involved in the transaction
 */
private function transactionInvolvesAccount($transaction, $maskedIdentifier) {
    // Check different transaction types
    if (isset($transaction['type'])) {
        switch ($transaction['type']) {
            case 'transfer':
                return $transaction['sender'] === $maskedIdentifier || 
                       $transaction['recipient'] === $maskedIdentifier;
                
            case 'payment_request':
                return $transaction['requestor'] === $maskedIdentifier || 
                       $transaction['payer'] === $maskedIdentifier;
                
            case 'reward':
            case 'payment_sent':
            case 'payment_received':
                return $transaction['creditCardNumber'] === $maskedIdentifier;
                
            default:
                // For other transaction types, check if any field matches
                foreach ($transaction as $key => $value) {
                    if (is_string($value) && $value === $maskedIdentifier) {
                        return true;
                    }
                }
        }
    }
    
    return false;
}

/**
 * Generate a payment QR code
 * 
 * @param string $recipientCardNumber The recipient's card number
 * @param float $amount The payment amount
 * @param string $memo The payment memo
 * @return string The QR code data URI
 */
public function generatePaymentQRCode($recipientCardNumber, $amount, $memo = '') {
    // QR code data format: card:amount:timestamp:memo
    $data = [
        'type' => 'payment',
        'recipient' => substr($recipientCardNumber, 0, 4) . '********' . substr($recipientCardNumber, -4),
        'amount' => $amount,
        'timestamp' => time(),
        'memo' => $memo,
        'signature' => hash('sha256', $recipientCardNumber . $amount . time() . $memo)
    ];
    
    // Convert to JSON
    $jsonData = json_encode($data);
    
    // Create QR code (if you have a QR code library)
    // For this example, we'll just return the data that would be encoded
    return [
        'qrData' => $jsonData,
        'recipientCard' => $data['recipient'],
        'amount' => $amount,
        'memo' => $memo
    ];
}
?>