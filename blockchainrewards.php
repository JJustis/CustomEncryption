<?php
/**
 * BlockchainRewards System
 * Enterprise-grade blockchain management for distributed reward ecosystem
 * 
 * @package BlockchainRewards
 * @version 1.0.0
 */
class BlockchainRewards {
    /**
     * Configuration Constants
     * Centralized management of system parameters
     */
    private const BLOCKCHAIN_FILE = 'blockchain_rewards.json';
    private const REWARD_RATE = 0.1;          // Reserves per message decryption
    private const INITIAL_WALLET_BALANCE = 1000.0;
    private const DIFFICULTY_TARGET = 4;      // Leading zeroes for proof-of-work
    private const MAX_NONCE = 1000000;        // Prevent infinite mining
    private const MAX_TRANSACTION_AGE = 3600; // 1 hour transaction validity

    /**
     * System State Properties
     * Manages internal state of blockchain infrastructure
     */
    private $blockchainData = [];

    /**
     * Constructor
     * Initializes blockchain infrastructure
     */
    public function __construct() {
        $this->initializeBlockchainIfNeeded();
    }
public function getBlockchain(): array {
    // Ensure file exists and is readable
    if (!file_exists(self::BLOCKCHAIN_FILE) || !is_readable(self::BLOCKCHAIN_FILE)) {
        // Initialize blockchain if file is missing
        $this->initializeBlockchainIfNeeded();
    }

    try {
        $fileContents = file_get_contents(self::BLOCKCHAIN_FILE);
        
        if ($fileContents === false) {
            throw new Exception("Unable to read blockchain file");
        }

        $blockchain = json_decode($fileContents, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("JSON parsing error: " . json_last_error_msg());
        }

        // Additional validation
        if (!isset($blockchain['blocks']) || !is_array($blockchain['blocks'])) {
            throw new Exception("Invalid blockchain structure");
        }

        return $blockchain;
    } catch (Exception $e) {
        error_log('Blockchain Load Error: ' . $e->getMessage());
        
        // Reinitialize if data is corrupted
        $this->initializeBlockchainIfNeeded();
        return $this->blockchainData;
    }
}
    /**
     * Initialize Blockchain Infrastructure
     * Creates genesis block and initial system state
     */
private function initializeBlockchainIfNeeded(): void {
    if (!file_exists(self::BLOCKCHAIN_FILE)) {
        // Ensure the directory exists
        $directory = dirname(self::BLOCKCHAIN_FILE);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Create initial blockchain structure
        $this->blockchainData = [
            'blocks' => [$this->createGenesisBlock()],
            'pendingTransactions' => [],
            'wallet' => $this->createInitialWallet(),
            'statistics' => $this->initializeStatistics(),
            'miners' => []
        ];
        $this->saveBlockchain();
    } else {
        $this->loadBlockchain();
    }
}

private function saveBlockchain(): void {
    // Ensure proper JSON encoding with error handling
    $jsonData = json_encode($this->blockchainData, JSON_PRETTY_PRINT);
    
    if ($jsonData === false) {
        error_log('Failed to encode blockchain data: ' . json_last_error_msg());
        throw new Exception('Unable to save blockchain data');
    }

    $result = file_put_contents(self::BLOCKCHAIN_FILE, $jsonData);
    
    if ($result === false) {
        error_log('Failed to write blockchain file');
        throw new Exception('Unable to write blockchain file');
    }
}

private function loadBlockchain(): void {
    // Add robust error handling for file reading and JSON parsing
    $fileContents = file_get_contents(self::BLOCKCHAIN_FILE);
    
    if ($fileContents === false) {
        error_log('Failed to read blockchain file');
        $this->initializeBlockchainIfNeeded();
        return;
    }

    $parsedData = json_decode($fileContents, true);
    
    if ($parsedData === null) {
        error_log('Failed to parse blockchain JSON: ' . json_last_error_msg());
        $this->initializeBlockchainIfNeeded();
        return;
    }

    $this->blockchainData = $parsedData;
}

    /**
     * Create Genesis Block
     * Establishes initial blockchain entry point
     * 
     * @return array Genesis block configuration
     */
    private function createGenesisBlock(): array {
        $timestamp = time();
        $genesisBlockData = [
            'index' => 0,
            'timestamp' => $timestamp,
            'data' => [
                'message' => 'Genesis Block',
                'transactions' => [],
                'difficulty' => self::DIFFICULTY_TARGET,
                'totalSupply' => self::INITIAL_WALLET_BALANCE
            ],
            'previousHash' => '0',
            'nonce' => 0
        ];

        $genesisBlockData['hash'] = $this->calculateBlockHash(
            $genesisBlockData['index'], 
            $genesisBlockData['previousHash'], 
            $genesisBlockData['timestamp'], 
            $genesisBlockData['data'], 
            $genesisBlockData['nonce']
        );

        return $genesisBlockData;
    }

    /**
     * Create Initial Wallet
     * Generates master wallet for reward distribution
     * 
     * @return array Wallet configuration
     */
    private function createInitialWallet(): array {
        return [
            'balance' => self::INITIAL_WALLET_BALANCE,
            'address' => 'master_wallet_' . bin2hex(random_bytes(8)),
            'created' => time()
        ];
    }

    /**
     * Initialize Blockchain Statistics
     * Sets up initial tracking metrics
     * 
     * @return array Initial statistics configuration
     */
    private function initializeStatistics(): array {
        return [
            'totalTransactions' => 0,
            'totalRewardsDistributed' => 0,
            'totalMessages' => 0,
            'averageHashRate' => 0
        ];
    }

    /**
     * Calculate Block Hash
     * Generates cryptographically secure block hash
     * 
     * @param int $index Block index
     * @param string $previousHash Previous block's hash
     * @param int $timestamp Block creation timestamp
     * @param mixed $data Block data
     * @param int $nonce Mining nonce
     * @return string Calculated SHA-256 hash
     */
    private function calculateBlockHash(
        int $index, 
        string $previousHash, 
        int $timestamp, 
        $data, 
        int $nonce = 0
    ): string {
        $dataString = is_array($data) ? json_encode($data) : $data;
        $input = implode('', [
            strval($index), 
            $previousHash, 
            strval($timestamp), 
            $dataString, 
            strval($nonce)
        ]);
        
        return hash('sha256', $input);
    }

    /**
     * Verify Proof of Work
     * Comprehensive validation of mining submission
     * 
     * @param array $block Block data
     * @param int|null $difficulty Optional difficulty override
     * @return bool Validation result
     */
    public function verifyProofOfWork(array $block, ?int $difficulty = null): bool {
        $difficulty = $difficulty ?? self::DIFFICULTY_TARGET;
        $prefix = str_repeat('0', $difficulty);

        // Hash meets difficulty requirement
        if (!isset($block['hash']) || substr($block['hash'], 0, $difficulty) !== $prefix) {
            error_log("Hash does not meet difficulty requirement: {$block['hash']}");
            return false;
        }

        // Hash authenticity verification
        $calculatedHash = $this->calculateBlockHash(
            $block['index'],
            $block['previousHash'],
            $block['timestamp'],
            $block['data'],
            $block['nonce']
        );

        $hashValid = $calculatedHash === $block['hash'];

        if (!$hashValid) {
            error_log("Hash verification failed. Calculated: $calculatedHash, Submitted: {$block['hash']}");
        }

        return $hashValid;
    }

    /**
     * Add Transaction
     * Creates and stores a new blockchain transaction
     * 
     * @param string $creditCardNumber Credit card identifier
     * @param float $amount Transaction amount
     * @param string $type Transaction type
     * @param string $message Transaction description
     * @return array Created transaction
     */
    public function addTransaction(
        string $creditCardNumber, 
        float $amount, 
        string $type = 'reward', 
        string $message = ''
    ): array {
        $maskedCardNumber = substr($creditCardNumber, 0, 4) . '********' . substr($creditCardNumber, -4);
        
        $transaction = [
            'id' => bin2hex(random_bytes(16)),
            'timestamp' => time(),
            'creditCardNumber' => $maskedCardNumber,
            'amount' => $amount,
            'type' => $type,
            'message' => $message,
            'status' => 'pending'
        ];

        $this->blockchainData['pendingTransactions'][] = $transaction;
        
        $this->blockchainData['statistics']['totalTransactions']++;
        
        if ($type === 'reward') {
            $this->blockchainData['statistics']['totalRewardsDistributed'] += $amount;
        }

        $this->saveBlockchain();
        
        return $transaction;
    }
/**
 * Get wallet balance and statistics
 * 
 * @return array Wallet information
 */
public function getWalletInfo(): array {
    return [
        'balance' => $this->blockchainData['wallet']['balance'],
        'address' => $this->blockchainData['wallet']['address'],
        'statistics' => $this->blockchainData['statistics']
    ];
}

/**
 * Get mining difficulty and requirements
 * 
 * @return array Mining requirements
 */
public function getMiningRequirements(): array {
    return [
        'difficulty' => self::DIFFICULTY_TARGET,
        'prefix' => str_repeat('0', self::DIFFICULTY_TARGET),
        'rewardRate' => self::REWARD_RATE,
        'pendingTransactions' => count($this->blockchainData['pendingTransactions'])
    ];
}

/**
 * Get transactions for a specific credit card
 * 
 * @param string $creditCardNumber Credit card number
 * @return array Transactions associated with the card
 */
public function getCardTransactions(string $creditCardNumber): array {
    $maskedNumber = substr($creditCardNumber, 0, 4) . '********' . substr($creditCardNumber, -4);
    
    $transactions = [];
    
    // Check pending transactions
    foreach ($this->blockchainData['pendingTransactions'] as $transaction) {
        if ($transaction['creditCardNumber'] === $maskedNumber) {
            $transactions[] = $transaction;
        }
    }
    
    // Check processed transactions in blocks
    foreach ($this->blockchainData['blocks'] as $block) {
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
 * 
 * @param string $creditCardNumber Credit card number
 * @return float Total balance
 */
public function getCardBalance(string $creditCardNumber): float {
    $transactions = $this->getCardTransactions($creditCardNumber);
    
    $balance = 0.0;
    foreach ($transactions as $transaction) {
        if ($transaction['type'] === 'reward') {
            $balance += $transaction['amount'];
        } elseif ($transaction['type'] === 'spend') {
            $balance -= $transaction['amount'];
        }
    }
    
    return $balance;
}

/**
 * Process a reward for decrypting a message
 * 
 * @param string $creditCardNumber Credit card number
 * @param string $messageId Unique message identifier
 * @return array Reward processing result
 * @throws Exception If insufficient funds
 */
// Add these methods to the BlockchainRewards class in BlockchainRewards.php
private const PROCESSED_MESSAGES_FILE = 'processed_messages.json';

private function loadProcessedMessages() {
    $file = __DIR__ . '/' . self::PROCESSED_MESSAGES_FILE;
    
    if (!file_exists($file)) {
        return [];
    }
    
    $data = json_decode(file_get_contents($file), true);
    return $data ?: [];
}

private function saveProcessedMessages($processedMessages) {
    $file = __DIR__ . '/' . self::PROCESSED_MESSAGES_FILE;
    file_put_contents($file, json_encode($processedMessages, JSON_PRETTY_PRINT));
}

public function processDecryptionReward($creditCardNumber, $messageId) {
    // Check if message has already been processed
    $processedMessages = $this->loadProcessedMessages();
    
    if (isset($processedMessages[$messageId])) {
        throw new Exception("Message $messageId has already been rewarded. No duplicate rewards allowed.");
    }
    
    // Check if wallet has enough balance
    $rewardAmount = self::REWARD_RATE;
    
    if ($this->blockchainData['wallet']['balance'] < $rewardAmount) {
        throw new Exception("Insufficient funds in reward wallet");
    }
    
    // Deduct from wallet
    $this->blockchainData['wallet']['balance'] -= $rewardAmount;
    
    // Add transaction
    $transaction = $this->addTransaction(
        $creditCardNumber, 
        $rewardAmount, 
        'reward', 
        "Reward for decrypting message ID: $messageId"
    );
    
    // Track this message as processed
    $processedMessages[$messageId] = [
        'timestamp' => time(),
        'creditCardNumber' => $creditCardNumber,
        'transactionId' => $transaction['id']
    ];
    
    // Save processed messages
    $this->saveProcessedMessages($processedMessages);
    
    // Update statistics
    $this->blockchainData['statistics']['totalMessages']++;
    
    // Save updated blockchain
    $this->saveBlockchain();
    
    return [
        'success' => true,
        'transaction' => $transaction,
        'newBalance' => $this->blockchainData['wallet']['balance']
    ];
}
    /**
     * Mine Block
     * Processes mining submissions and updates blockchain
     * 
     * @param array $minerInfo Miner identification
     * @param array $proofOfWork Proof of work submission
     * @return array Mining result
     * @throws Exception Invalid proof of work
     */
    public function mineBlock(array $minerInfo, array $proofOfWork): array {
        $lastBlock = end($this->blockchainData['blocks']);
        $newIndex = $lastBlock['index'] + 1;

        $blockData = [
            'minerInfo' => $minerInfo,
            'transactions' => $this->blockchainData['pendingTransactions'],
            'minedAt' => time(),
            'difficulty' => self::DIFFICULTY_TARGET
        ];

        $newBlock = [
            'index' => $newIndex,
            'timestamp' => time(),
            'data' => $blockData,
            'previousHash' => $lastBlock['hash'],
            'hash' => $proofOfWork['hash'],
            'nonce' => $proofOfWork['nonce']
        ];

        if (!$this->verifyProofOfWork($newBlock)) {
            throw new Exception("Invalid proof of work submission");
        }

        $this->blockchainData['blocks'][] = $newBlock;
        
        $this->processMinedBlockTransactions($newIndex);
        $this->updateMinerStatistics($minerInfo);
        
        $this->saveBlockchain();

        return [
            'success' => true,
            'block' => $newBlock,
            'blockchain' => $this->blockchainData
        ];
    }

    /**
     * Process Mined Block Transactions
     * Marks transactions as processed within a mined block
     * 
     * @param int $blockIndex Current block index
     */
    private function processMinedBlockTransactions(int $blockIndex): void {
        foreach ($this->blockchainData['pendingTransactions'] as &$transaction) {
            $transaction['status'] = 'processed';
            $transaction['blockIndex'] = $blockIndex;
        }

        $this->blockchainData['pendingTransactions'] = [];
    }


/**
 * Update Miner Statistics
 * Tracks and updates mining performance metrics
 * 
 * @param array $minerInfo Miner identification
 */
private function updateMinerStatistics(array $minerInfo): void {
    $minerIdentifier = $minerInfo['identifier'];
    
    $this->blockchainData['miners'][$minerIdentifier] = $this->blockchainData['miners'][$minerIdentifier] ?? [
        'totalBlocks' => 0,
        'lastMined' => 0,
        'hashRate' => 0,
        'rewards' => 0
    ];

    $this->blockchainData['miners'][$minerIdentifier]['totalBlocks']++;
    $this->blockchainData['miners'][$minerIdentifier]['lastMined'] = time();
    $this->blockchainData['miners'][$minerIdentifier]['hashRate'] = $minerInfo['hashRate'];

    $this->recalculateAverageHashRate();
}

    /**
     * Recalculate Average Hash Rate
     * Computes network-wide mining performance metric
     */
    private function recalculateAverageHashRate(): void {
        $miners = $this->blockchainData['miners'];
        $totalHashRate = array_sum(array_column($miners, 'hashRate'));
        $minerCount = count($miners);

        $this->blockchainData['statistics']['averageHashRate'] = 
            $minerCount > 0 ? $totalHashRate / $minerCount : 0;
    }



    // Remaining methods (getCardTransactions, getCardBalance, etc.) 
    // would be implemented similarly with modern PHP practices
}
?>