<?php
/**
 * Transaction System for Blockchain Rewards
 * Handles payments between credit card users using their reserves
 */
class TransactionSystem {
    private $blockchainRewards;
    
    /**
     * Constructor initializes the blockchain rewards system
     */
    public function __construct() {
        // Initialize the blockchain rewards system
        $this->blockchainRewards = new BlockchainRewards();
    }
    
    /**
     * Process a payment from one user to another
     * 
     * @param string $senderCardNumber The sender's credit card number
     * @param string $recipientCardNumber The recipient's credit card number
     * @param float $amount The amount to transfer
     * @param string $memo Optional transaction memo/description
     * @return array The transaction result
     */
    public function processPayment($senderCardNumber, $recipientCardNumber, $amount, $memo = '') {
        // Validate inputs
        if (empty($senderCardNumber) || empty($recipientCardNumber)) {
            throw new Exception("Sender and recipient card numbers are required");
        }
        
        if ($senderCardNumber === $recipientCardNumber) {
            throw new Exception("Cannot send payment to yourself");
        }
        
        $amount = floatval($amount);
        if ($amount <= 0) {
            throw new Exception("Payment amount must be greater than zero");
        }
        
        // Check sender's balance
        $senderBalance = $this->blockchainRewards->getCardBalance($senderCardNumber);
        if ($senderBalance < $amount) {
            throw new Exception("Insufficient funds. Available balance: $senderBalance");
        }
        
        // Create the transaction
        $transactionId = 'tx_' . bin2hex(random_bytes(8));
        
        // Record the debit transaction for sender
        $debitTransaction = $this->blockchainRewards->addTransaction(
            $senderCardNumber,
            $amount,
            'payment_sent',
            "Payment to " . substr($recipientCardNumber, 0, 4) . '...' . substr($recipientCardNumber, -4) . 
            ($memo ? ": $memo" : "")
        );
        
        // Record the credit transaction for recipient
        $creditTransaction = $this->blockchainRewards->addTransaction(
            $recipientCardNumber,
            $amount,
            'payment_received',
            "Payment from " . substr($senderCardNumber, 0, 4) . '...' . substr($senderCardNumber, -4) . 
            ($memo ? ": $memo" : "")
        );
        
        // Create a linkage between the two transactions
        $transactions = [
            'transactionId' => $transactionId,
            'timestamp' => time(),
            'senderCard' => substr($senderCardNumber, 0, 4) . '********' . substr($senderCardNumber, -4),
            'recipientCard' => substr($recipientCardNumber, 0, 4) . '********' . substr($recipientCardNumber, -4),
            'amount' => $amount,
            'memo' => $memo,
            'debitTransactionId' => $debitTransaction['id'],
            'creditTransactionId' => $creditTransaction['id'],
            'status' => 'completed'
        ];
        
        // Store the transaction linkage
        $this->saveTransaction($transactions);
        
        return [
            'success' => true,
            'transactionId' => $transactionId,
            'amount' => $amount,
            'newSenderBalance' => $senderBalance - $amount,
            'timestamp' => time(),
            'status' => 'completed'
        ];
    }
    public function getPendingRequests($cardNumber) {
    // Mask the card number
    $cardMasked = substr($cardNumber, 0, 4) . '********' . substr($cardNumber, -4);
    
    // Load all payment requests
    $requests = $this->loadAllPaymentRequests();
    
    // Filter for pending requests where the card is either the requestor or payer
    $pendingRequests = array_filter($requests, function($request) use ($cardMasked) {
        // Only return requests that are pending and involve this card
        return $request['status'] === 'pending' && 
               (
                   $request['requestorCard'] === $cardMasked || 
                   $request['payerCard'] === $cardMasked
               ) && 
               $request['expiresAt'] > time(); // Ensure request is not expired
    });
    
    // Transform requests to match the expected frontend format
    $formattedRequests = array_map(function($request) use ($cardMasked) {
        // Determine the direction of the request
        $isIncoming = $request['payerCard'] === $cardMasked;
        
        return [
            'id' => $request['requestId'],
            'timestamp' => $request['timestamp'],
            'amount' => $request['amount'],
            'memo' => $request['memo'] ?? '',
            'status' => $request['status'],
            'direction' => $isIncoming ? 'incoming' : 'outgoing',
            'otherParty' => $isIncoming ? $request['requestorCard'] : $request['payerCard'],
            'expiresAt' => $request['expiresAt']
        ];
    }, $pendingRequests);
    
    // Reindex the array to ensure it's a proper numeric array
    return array_values($formattedRequests);
}
    /**
     * Process a batch payment from one user to multiple recipients
     * 
     * @param string $senderCardNumber The sender's credit card number
     * @param array $recipients Array of recipient data (cardNumber, amount, memo)
     * @return array The batch transaction result
     */
    public function processBatchPayment($senderCardNumber, $recipients) {
        if (empty($senderCardNumber) || empty($recipients) || !is_array($recipients)) {
            throw new Exception("Sender card number and recipients array are required");
        }
        
        // Calculate total amount needed
        $totalAmount = 0;
        foreach ($recipients as $recipient) {
            if (!isset($recipient['cardNumber']) || !isset($recipient['amount'])) {
                throw new Exception("Each recipient must have cardNumber and amount specified");
            }
            $totalAmount += floatval($recipient['amount']);
        }
        
        // Check sender's balance
        $senderBalance = $this->blockchainRewards->getCardBalance($senderCardNumber);
        if ($senderBalance < $totalAmount) {
            throw new Exception("Insufficient funds. Available balance: $senderBalance, Required: $totalAmount");
        }
        
        // Process each payment
        $batchTransactionId = 'batch_' . bin2hex(random_bytes(8));
        $transactions = [];
        
        foreach ($recipients as $recipient) {
            $recipientCardNumber = $recipient['cardNumber'];
            $amount = floatval($recipient['amount']);
            $memo = $recipient['memo'] ?? '';
            
            if ($amount <= 0) {
                continue; // Skip zero or negative amounts
            }
            
            try {
                // Process individual payment
                $result = $this->processPayment($senderCardNumber, $recipientCardNumber, $amount, $memo);
                
                // Add to batch results
                $transactions[] = [
                    'recipientCard' => substr($recipientCardNumber, 0, 4) . '********' . substr($recipientCardNumber, -4),
                    'amount' => $amount,
                    'transactionId' => $result['transactionId'],
                    'status' => 'completed'
                ];
            } catch (Exception $e) {
                // Record failed transactions
                $transactions[] = [
                    'recipientCard' => substr($recipientCardNumber, 0, 4) . '********' . substr($recipientCardNumber, -4),
                    'amount' => $amount,
                    'status' => 'failed',
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return [
            'success' => true,
            'batchTransactionId' => $batchTransactionId,
            'totalAmount' => $totalAmount,
            'transactions' => $transactions,
            'timestamp' => time()
        ];
    }
    
    /**
     * Request payment from another user
     * 
     * @param string $requestorCardNumber The requestor's credit card number
     * @param string $payerCardNumber The payer's credit card number
     * @param float $amount The amount requested
     * @param string $memo Optional request memo/description
     * @return array The payment request result
     */
    public function requestPayment($requestorCardNumber, $payerCardNumber, $amount, $memo = '') {
        // Validate inputs
        if (empty($requestorCardNumber) || empty($payerCardNumber)) {
            throw new Exception("Requestor and payer card numbers are required");
        }
        
        if ($requestorCardNumber === $payerCardNumber) {
            throw new Exception("Cannot request payment from yourself");
        }
        
        $amount = floatval($amount);
        if ($amount <= 0) {
            throw new Exception("Request amount must be greater than zero");
        }
        
        // Create the payment request
        $requestId = 'req_' . bin2hex(random_bytes(8));
        
        $paymentRequest = [
            'requestId' => $requestId,
            'timestamp' => time(),
            'requestorCard' => substr($requestorCardNumber, 0, 4) . '********' . substr($requestorCardNumber, -4),
            'payerCard' => substr($payerCardNumber, 0, 4) . '********' . substr($payerCardNumber, -4),
            'amount' => $amount,
            'memo' => $memo,
            'status' => 'pending',
            'expiresAt' => time() + (7 * 24 * 60 * 60) // 7 days expiry
        ];
        
        // Store the payment request
        $this->savePaymentRequest($paymentRequest);
        
        return [
            'success' => true,
            'requestId' => $requestId,
            'amount' => $amount,
            'timestamp' => time(),
            'status' => 'pending',
            'expiresAt' => time() + (7 * 24 * 60 * 60)
        ];
    }
    
    /**
     * Respond to a payment request (accept or decline)
     * 
     * @param string $requestId The payment request ID
     * @param string $payerCardNumber The payer's credit card number
     * @param string $action Either 'accept' or 'decline'
     * @return array The response result
     */
    public function respondToPaymentRequest($requestId, $payerCardNumber, $action) {
        // Load the payment request
        $paymentRequest = $this->getPaymentRequest($requestId);
        
        if (!$paymentRequest) {
            throw new Exception("Payment request not found");
        }
        
        // Verify the request is pending and not expired
        if ($paymentRequest['status'] !== 'pending') {
            throw new Exception("Payment request has already been " . $paymentRequest['status']);
        }
        
        if ($paymentRequest['expiresAt'] < time()) {
            throw new Exception("Payment request has expired");
        }
        
        // Get the actual payer card number (unhidden)
        $payerCardFull = $payerCardNumber;
        $payerCardHidden = substr($payerCardNumber, 0, 4) . '********' . substr($payerCardNumber, -4);
        
        // Verify this is the correct payer
        if ($paymentRequest['payerCard'] !== $payerCardHidden) {
            throw new Exception("This payment request is for a different card");
        }
        
        // Get the requestor's card number (need to look it up from a user database or session)
        // For now, we'll extract it from the hidden format (this is a simplification)
        $requestorCardPrefix = substr($paymentRequest['requestorCard'], 0, 4);
        $requestorCardSuffix = substr($paymentRequest['requestorCard'], -4);
        // In a real system, you would look up the full card number from a secure database
        // This is just a placeholder for demonstration
        $requestorCardFull = $this->lookupCardFromMasked($requestorCardPrefix, $requestorCardSuffix);
        
        // Process the action
        if ($action === 'accept') {
            // Process the payment
            $result = $this->processPayment(
                $payerCardFull,
                $requestorCardFull,
                $paymentRequest['amount'],
                "Payment request #" . $requestId . ($paymentRequest['memo'] ? ": " . $paymentRequest['memo'] : "")
            );
            
            // Update the payment request status
            $paymentRequest['status'] = 'completed';
            $paymentRequest['completedAt'] = time();
            $paymentRequest['transactionId'] = $result['transactionId'];
            
            // Save the updated request
            $this->savePaymentRequest($paymentRequest);
            
            return [
                'success' => true,
                'requestId' => $requestId,
                'action' => 'accepted',
                'transactionId' => $result['transactionId'],
                'amount' => $paymentRequest['amount'],
                'timestamp' => time(),
                'status' => 'completed'
            ];
        } else if ($action === 'decline') {
            // Update the payment request status
            $paymentRequest['status'] = 'declined';
            $paymentRequest['declinedAt'] = time();
            
            // Save the updated request
            $this->savePaymentRequest($paymentRequest);
            
            return [
                'success' => true,
                'requestId' => $requestId,
                'action' => 'declined',
                'timestamp' => time(),
                'status' => 'declined'
            ];
        } else {
            throw new Exception("Invalid action. Must be 'accept' or 'decline'");
        }
    }
    
    /**
     * Lookup a full card number from masked format
     * This is a placeholder method - in a real system you would use a secure database
     */
    private function lookupCardFromMasked($prefix, $suffix) {
        // In a real implementation, this would query a secure database
        // For demo purposes, we're returning a dummy card number
        return $prefix . "12345678" . $suffix;
    }
    
    /**
     * Get a user's transaction history
     * 
     * @param string $cardNumber The credit card number
     * @return array The user's transaction history
     */
    public function getTransactionHistory($cardNumber) {
        // Get transactions where the user is either sender or recipient
        $transactions = $this->loadTransactionsByCard($cardNumber);
        
        // Get payment requests where the user is either requestor or payer
        $paymentRequests = $this->loadPaymentRequestsByCard($cardNumber);
        
        // Combine transactions and payment requests into a unified history
        $history = [];
        
        foreach ($transactions as $transaction) {
            $history[] = [
                'id' => $transaction['transactionId'],
                'type' => 'transaction',
                'timestamp' => $transaction['timestamp'],
                'amount' => $transaction['amount'],
                'direction' => $this->getTransactionDirection($transaction, $cardNumber),
                'otherParty' => $this->getOtherParty($transaction, $cardNumber),
                'memo' => $transaction['memo'],
                'status' => $transaction['status']
            ];
        }
        
        foreach ($paymentRequests as $request) {
            $history[] = [
                'id' => $request['requestId'],
                'type' => 'payment_request',
                'timestamp' => $request['timestamp'],
                'amount' => $request['amount'],
                'direction' => $this->getRequestDirection($request, $cardNumber),
                'otherParty' => $this->getRequestOtherParty($request, $cardNumber),
                'memo' => $request['memo'],
                'status' => $request['status'],
                'expiresAt' => $request['expiresAt']
            ];
        }
        
        // Sort by timestamp (newest first)
        usort($history, function($a, $b) {
            return $b['timestamp'] - $a['timestamp'];
        });
        
        return $history;
    }
    
    /**
     * Determine if a transaction is incoming or outgoing
     */
    private function getTransactionDirection($transaction, $cardNumber) {
        $cardMasked = substr($cardNumber, 0, 4) . '********' . substr($cardNumber, -4);
        return ($transaction['senderCard'] === $cardMasked) ? 'outgoing' : 'incoming';
    }
    
    /**
     * Get the other party in a transaction
     */
    private function getOtherParty($transaction, $cardNumber) {
        $cardMasked = substr($cardNumber, 0, 4) . '********' . substr($cardNumber, -4);
        return ($transaction['senderCard'] === $cardMasked) ? $transaction['recipientCard'] : $transaction['senderCard'];
    }
    
    /**
     * Determine if a payment request is incoming or outgoing
     */
    private function getRequestDirection($request, $cardNumber) {
        $cardMasked = substr($cardNumber, 0, 4) . '********' . substr($cardNumber, -4);
        return ($request['requestorCard'] === $cardMasked) ? 'outgoing' : 'incoming';
    }
    
    /**
     * Get the other party in a payment request
     */
    private function getRequestOtherParty($request, $cardNumber) {
        $cardMasked = substr($cardNumber, 0, 4) . '********' . substr($cardNumber, -4);
        return ($request['requestorCard'] === $cardMasked) ? $request['payerCard'] : $request['requestorCard'];
    }
    
    /**
     * Save a transaction to storage
     */
    private function saveTransaction($transaction) {
        // Get existing transactions
        $transactions = $this->loadAllTransactions();
        
        // Add new transaction
        $transactions[] = $transaction;
        
        // Save updated transactions
        $this->saveAllTransactions($transactions);
        
        // Also add to pending blockchain transactions
        $this->addToPendingBlockchainTransactions($transaction);
    }
    
    /**
     * Load all transactions from storage
     */
    private function loadAllTransactions() {
        $file = __DIR__ . '/transactions.json';
        
        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true);
            return $data ?: [];
        }
        
        return [];
    }
    
    /**
     * Save all transactions to storage
     */
    private function saveAllTransactions($transactions) {
        $file = __DIR__ . '/transactions.json';
        file_put_contents($file, json_encode($transactions, JSON_PRETTY_PRINT));
    }
    
    /**
     * Load transactions for a specific card
     */
    private function loadTransactionsByCard($cardNumber) {
        $cardMasked = substr($cardNumber, 0, 4) . '********' . substr($cardNumber, -4);
        $transactions = $this->loadAllTransactions();
        
        return array_filter($transactions, function($transaction) use ($cardMasked) {
            return $transaction['senderCard'] === $cardMasked || $transaction['recipientCard'] === $cardMasked;
        });
    }
    
    /**
     * Save a payment request to storage
     */
    private function savePaymentRequest($paymentRequest) {
        // Get existing payment requests
        $requests = $this->loadAllPaymentRequests();
        
        // Check if this request already exists
        $exists = false;
        foreach ($requests as &$request) {
            if ($request['requestId'] === $paymentRequest['requestId']) {
                // Update existing request
                $request = $paymentRequest;
                $exists = true;
                break;
            }
        }
        
        // Add new request if it doesn't exist
        if (!$exists) {
            $requests[] = $paymentRequest;
        }
        
        // Save updated requests
        $this->saveAllPaymentRequests($requests);
        
        // If the request was completed or declined, add to pending blockchain transactions
        if ($paymentRequest['status'] === 'completed' || $paymentRequest['status'] === 'declined') {
            $this->addPaymentRequestToBlockchain($paymentRequest);
        }
    }
    
    /**
     * Load all payment requests from storage
     */
    private function loadAllPaymentRequests() {
        $file = __DIR__ . '/payment_requests.json';
        
        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true);
            return $data ?: [];
        }
        
        return [];
    }
    
    /**
     * Save all payment requests to storage
     */
    private function saveAllPaymentRequests($requests) {
        $file = __DIR__ . '/payment_requests.json';
        file_put_contents($file, json_encode($requests, JSON_PRETTY_PRINT));
    }
    
    /**
     * Get a specific payment request
     */
    private function getPaymentRequest($requestId) {
        $requests = $this->loadAllPaymentRequests();
        
        foreach ($requests as $request) {
            if ($request['requestId'] === $requestId) {
                return $request;
            }
        }
        
        return null;
    }
    
    /**
     * Load payment requests for a specific card
     */
    private function loadPaymentRequestsByCard($cardNumber) {
        $cardMasked = substr($cardNumber, 0, 4) . '********' . substr($cardNumber, -4);
        $requests = $this->loadAllPaymentRequests();
        
        return array_filter($requests, function($request) use ($cardMasked) {
            return $request['requestorCard'] === $cardMasked || $request['payerCard'] === $cardMasked;
        });
    }
    
    /**
     * Add a transaction to pending blockchain transactions
     */
    private function addToPendingBlockchainTransactions($transaction) {
        // Create a blockchain-friendly transaction entry
        $blockchainTransaction = [
            'id' => $transaction['transactionId'],
            'type' => 'transfer',
            'timestamp' => $transaction['timestamp'],
            'sender' => $transaction['senderCard'],
            'recipient' => $transaction['recipientCard'],
            'amount' => $transaction['amount'],
            'memo' => $transaction['memo'],
            'status' => $transaction['status']
        ];
        
        // Add to pending transactions in the blockchain
        $this->blockchainRewards->addToPendingTransactions($blockchainTransaction);
    }
    
    /**
     * Add a payment request to the blockchain
     */
    private function addPaymentRequestToBlockchain($paymentRequest) {
        // Create a blockchain-friendly payment request entry
        $blockchainRequest = [
            'id' => $paymentRequest['requestId'],
            'type' => 'payment_request',
            'timestamp' => $paymentRequest['timestamp'],
            'requestor' => $paymentRequest['requestorCard'],
            'payer' => $paymentRequest['payerCard'],
            'amount' => $paymentRequest['amount'],
            'memo' => $paymentRequest['memo'],
            'status' => $paymentRequest['status']
        ];
        
        // Add completed info if available
        if ($paymentRequest['status'] === 'completed') {
            $blockchainRequest['completedAt'] = $paymentRequest['completedAt'];
            $blockchainRequest['transactionId'] = $paymentRequest['transactionId'];
        } else if ($paymentRequest['status'] === 'declined') {
            $blockchainRequest['declinedAt'] = $paymentRequest['declinedAt'];
        }
        
        // Add to pending transactions in the blockchain
        $this->blockchainRewards->addToPendingTransactions($blockchainRequest);
    }
}
?>