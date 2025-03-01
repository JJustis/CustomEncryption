<?php
/**
 * BlockchainRewards System
 * Manages the distribution of mining rewards to credit card holders
 * through a proof-of-work verification system
 */
class BlockchainRewards {
    private $blockchainFile = 'blockchain_rewards.json';
    private $rewardRate = 0.1; // Reserves per message decrypted
    private $walletBalance = 1000.0; // Initial wallet balance
    private $difficultyTarget = 4; // Number of leading zeroes for proof-of-work
    
    /**
     * Constructor initializes blockchain if it doesn't exist
     */
    public function __construct() {
        if (!file_exists($this->blockchainFile)) {
            $this->initializeBlockchain();
        }
    }
    
    /**
     * Initialize the blockchain with a genesis block
     */
    private function initializeBlockchain() {
        $genesisBlock = [
            'index' => 0,
            'timestamp' => time(),
            'data' => [
                'message' => 'Genesis Block',
                'transactions' => [],
                'difficulty' => $this->difficultyTarget,
                'totalSupply' => $this->walletBalance
            ],
            'previousHash' => '0',
            'hash' => $this->calculateHash(0, '0', time(), ['message' => 'Genesis Block']),
            'nonce' => 0
        ];
        
        $blockchain = [
            'blocks' => [$genesisBlock],
            'pendingTransactions' => [],
            'wallet' => [
                'balance' => $this->walletBalance,
                'address' => 'master_wallet_' . bin2hex(random_bytes(8)),
                'created' => time()
            ],
            'statistics' => [
                'totalTransactions' => 0,
                'totalRewardsDistributed' => 0,
                'totalMessages' => 0,
                'averageHashRate' => 0
            ],
            'miners' => []
        ];
        
        $this->saveBlockchain($blockchain);
    }
    
    /**
     * Calculate hash for block
     */
/**
 * Calculate hash for block - simplified version to match JavaScript implementation
 */
private function calculateHash($index, $previousHash, $timestamp, $data, $nonce = 0) {
    $dataString = is_array($data) ? json_encode($data) : $data;
    $input = $index . $previousHash . $timestamp . $dataString . $nonce;
    return hash('sha256', $input);
}
    
    /**
     * Get the entire blockchain
     */
    public function getBlockchain() {
        return json_decode(file_get_contents($this->blockchainFile), true);
    }
    
    /**
     * Save blockchain to file
     */
    private function saveBlockchain($blockchain) {
        file_put_contents($this->blockchainFile, json_encode($blockchain, JSON_PRETTY_PRINT));
    }
    
    /**
     * Add a transaction to pending transactions
     */
    public function addTransaction($creditCardNumber, $amount, $type = 'reward', $message = '') {
        // Create transaction
        $transaction = [
            'id' => bin2hex(random_bytes(16)),
            'timestamp' => time(),
            'creditCardNumber' => substr($creditCardNumber, 0, 4) . '********' . substr($creditCardNumber, -4),
            'amount' => $amount,
            'type' => $type,
            'message' => $message,
            'status' => 'pending'
        ];
        
        // Get current blockchain
        $blockchain = $this->getBlockchain();
        
        // Add transaction to pending
        $blockchain['pendingTransactions'][] = $transaction;
        
        // Update statistics
        $blockchain['statistics']['totalTransactions']++;
        if ($type === 'reward') {
            $blockchain['statistics']['totalRewardsDistributed'] += $amount;
        }
        
        // Save updated blockchain
        $this->saveBlockchain($blockchain);
        
        return $transaction;
    }
    
    /**
     * Process a reward for decrypting a message
     */
    public function processDecryptionReward($creditCardNumber, $messageId) {
        $rewardAmount = $this->rewardRate;
        
        // Get current blockchain
        $blockchain = $this->getBlockchain();
        
        // Check if wallet has enough balance
        if ($blockchain['wallet']['balance'] < $rewardAmount) {
            throw new Exception("Insufficient funds in reward wallet");
        }
        
        // Deduct from wallet
        $blockchain['wallet']['balance'] -= $rewardAmount;
        
        // Add transaction
        $transaction = $this->addTransaction(
            $creditCardNumber, 
            $rewardAmount, 
            'reward', 
            "Reward for decrypting message ID: $messageId"
        );
        
        // Update statistics
        $blockchain['statistics']['totalMessages']++;
        
        // Save updated blockchain
        $this->saveBlockchain($blockchain);
        
        return [
            'success' => true,
            'transaction' => $transaction,
            'newBalance' => $blockchain['wallet']['balance']
        ];
    }
    
    /**
     * Verify proof-of-work submitted by client
     */
/**
 * Verify proof-of-work submitted by client
 */
public function verifyProofOfWork($block, $difficulty = null) {
    $difficulty = $difficulty ?? $this->difficultyTarget;
    $prefix = str_repeat('0', $difficulty);
    
    // Log incoming data for debugging
    error_log('Verifying proof of work with difficulty ' . $difficulty);
    error_log('Block data: ' . json_encode($block));
    
    // Verify the hash meets the difficulty target
    if (!isset($block['hash']) || substr($block['hash'], 0, $difficulty) !== $prefix) {
        error_log('Hash does not meet difficulty requirement');
        return false;
    }
    
    // Recalculate hash to verify it's correct
    $calculatedHash = $this->calculateHash(
        $block['index'],
        $block['previousHash'],
        $block['timestamp'],
        $block['data'],
        $block['nonce']
    );
    
    error_log('Original hash: ' . $block['hash']);
    error_log('Calculated hash: ' . $calculatedHash);
    
    return $calculatedHash === $block['hash'];
}
    
    /**
     * Mine a new block with pending transactions
     */
    public function mineBlock($minerInfo, $proofOfWork) {
        // Get current blockchain
        $blockchain = $this->getBlockchain();
        
        // Get the last block
        $lastBlock = end($blockchain['blocks']);
        $newIndex = $lastBlock['index'] + 1;
        
        // Create new block data
        $blockData = [
            'minerInfo' => $minerInfo,
            'transactions' => $blockchain['pendingTransactions'],
            'minedAt' => time(),
            'difficulty' => $this->difficultyTarget
        ];
        
        // Create new block template
        $newBlock = [
            'index' => $newIndex,
            'timestamp' => time(),
            'data' => $blockData,
            'previousHash' => $lastBlock['hash'],
            'hash' => $proofOfWork['hash'],
            'nonce' => $proofOfWork['nonce']
        ];
        
        // Verify the proof of work
        if (!$this->verifyProofOfWork($newBlock)) {
    error_log("Invalid proof of work details:");
    error_log("Block: " . json_encode($newBlock));
    error_log("Difficulty target: " . $this->difficultyTarget);
    error_log("Calculated hash: " . $this->calculateHash(
        $newBlock['index'],
        $newBlock['previousHash'],
        $newBlock['timestamp'],
        $newBlock['data'],
        $newBlock['nonce']
    ));
    error_log("Submitted hash: " . $newBlock['hash']);
    throw new Exception("Invalid proof of work");
}
        
        // Add the block to the blockchain
        $blockchain['blocks'][] = $newBlock;
        
        // Mark transactions as processed
        foreach ($blockchain['pendingTransactions'] as &$transaction) {
            $transaction['status'] = 'processed';
            $transaction['blockIndex'] = $newIndex;
        }
        
        // Clear pending transactions
        $blockchain['pendingTransactions'] = [];
        
        // Track miner statistics
        $minerIdentifier = $minerInfo['identifier'];
        if (!isset($blockchain['miners'][$minerIdentifier])) {
            $blockchain['miners'][$minerIdentifier] = [
                'totalBlocks' => 0,
                'lastMined' => 0,
                'hashRate' => 0,
                'rewards' => 0
            ];
        }
        
        $blockchain['miners'][$minerIdentifier]['totalBlocks']++;
        $blockchain['miners'][$minerIdentifier]['lastMined'] = time();
        $blockchain['miners'][$minerIdentifier]['hashRate'] = $minerInfo['hashRate'];
        
        // Update total hash rate average
        $totalHashRate = 0;
        $minerCount = count($blockchain['miners']);
        foreach ($blockchain['miners'] as $miner) {
            $totalHashRate += $miner['hashRate'];
        }
        $blockchain['statistics']['averageHashRate'] = $minerCount > 0 ? $totalHashRate / $minerCount : 0;
        
        // Save updated blockchain
        $this->saveBlockchain($blockchain);
        
        return [
            'success' => true,
            'block' => $newBlock,
            'blockchain' => $blockchain
        ];
    }
    
    /**
     * Get wallet balance and statistics
     */
    public function getWalletInfo() {
        $blockchain = $this->getBlockchain();
        
        return [
            'balance' => $blockchain['wallet']['balance'],
            'address' => $blockchain['wallet']['address'],
            'statistics' => $blockchain['statistics']
        ];
    }
    
    /**
     * Get mining difficulty and requirements
     */
    public function getMiningRequirements() {
        return [
            'difficulty' => $this->difficultyTarget,
            'prefix' => str_repeat('0', $this->difficultyTarget),
            'rewardRate' => $this->rewardRate,
            'pendingTransactions' => count($this->getBlockchain()['pendingTransactions'])
        ];
    }
    
    /**
     * Get transactions for a specific credit card
     */
    public function getCardTransactions($creditCardNumber) {
        $blockchain = $this->getBlockchain();
        $maskedNumber = substr($creditCardNumber, 0, 4) . '********' . substr($creditCardNumber, -4);
        
        $transactions = [];
        
        // Check pending transactions
        foreach ($blockchain['pendingTransactions'] as $transaction) {
            if ($transaction['creditCardNumber'] === $maskedNumber) {
                $transactions[] = $transaction;
            }
        }
        
        // Check all blocks for transactions
        foreach ($blockchain['blocks'] as $block) {
            if (isset($block['data']['transactions']) && is_array($block['data']['transactions'])) {
                foreach ($block['data']['transactions'] as $transaction) {
                    if ($transaction['creditCardNumber'] === $maskedNumber) {
                        $transactions[] = $transaction;
                    }
                }
            }
        }
        
        return $transactions;
    }
    
    /**
     * Get total reward balance for a credit card
     */
    public function getCardBalance($creditCardNumber) {
        $transactions = $this->getCardTransactions($creditCardNumber);
        
        $balance = 0;
        foreach ($transactions as $transaction) {
            if ($transaction['type'] === 'reward') {
                $balance += $transaction['amount'];
            } elseif ($transaction['type'] === 'spend') {
                $balance -= $transaction['amount'];
            }
        }
        
        return $balance;
    }
}
?>