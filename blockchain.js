/**
 * Blockchain UI Interface
 * Handles visualization and interaction with the blockchain rewards system
 */

class BlockchainInterface {
    constructor() {
        this.blockchain = null;
        this.miningWorker = null;
        this.isMining = false;
        this.workerInterval = null;
        this.difficulty = 4;
        this.walletInfo = null;
        this.creditCardNumber = null;
        this.cardBalance = 0;
        
        // Initialize the interface
        this.initialize();
    }
    
    /**
     * Initialize the blockchain interface
     */
    async initialize() {
        try {
            // Fetch initial blockchain data
            await this.fetchBlockchain();
            
            // Fetch mining requirements
            await this.fetchMiningRequirements();
            
            // Get wallet info
            await this.fetchWalletInfo();
            
            // Setup event listeners
            this.setupEventListeners();
            
            // Refresh blockchain data periodically
            setInterval(() => this.fetchBlockchain(), 30000);
            setInterval(() => this.fetchWalletInfo(), 30000);
        } catch (error) {
            console.error('Blockchain Interface Initialization Error:', error);
            this.showErrorNotification('Failed to initialize the blockchain interface. Please reload the page.');
        }
    }
    
    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Start mining button
        document.getElementById('start-mining')?.addEventListener('click', () => this.startMining());
        
        // Stop mining button
        document.getElementById('stop-mining')?.addEventListener('click', () => this.stopMining());
        
        // Credit card balance check
        document.getElementById('check-balance')?.addEventListener('click', () => {
            const cardNumber = document.getElementById('card-number')?.value;
            if (cardNumber) {
                this.fetchCardBalance(cardNumber);
            }
        });
        
        // Store credit card number when generated
        document.addEventListener('creditCardGenerated', (e) => {
            if (e.detail && e.detail.creditCardNumber) {
                this.creditCardNumber = e.detail.creditCardNumber;
                
                // Update UI if needed
                const cardNumberInput = document.getElementById('card-number');
                if (cardNumberInput) {
                    cardNumberInput.value = this.creditCardNumber;
                }
            }
        });
        
        // Listen for message decryption to process rewards
        document.addEventListener('messageDecrypted', (e) => {
            if (e.detail && e.detail.messageId && this.creditCardNumber) {
                this.processDecryptionReward(this.creditCardNumber, e.detail.messageId);
            }
        });
    }
    
    /**
     * Fetch blockchain data from API
     */
    async fetchBlockchain() {
        try {
            // Enhanced error handling
            const response = await fetch('api.php?action=get-blockchain', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                }
            });

            // Check HTTP response status
            if (!response.ok) {
                throw new Error(`HTTP error! Status: ${response.status}`);
            }

            // Get response text for debugging
            const responseText = await response.text();
            
            // Verbose logging
            console.log('Raw Blockchain Response:', responseText);

            try {
                // Strict JSON parsing
                const data = JSON.parse(responseText);

                if (data.success && data.blockchain) {
                    this.blockchain = data.blockchain;
                    this.renderBlockchain();
                } else {
                    throw new Error('Invalid blockchain data structure');
                }
            } catch (parseError) {
                console.error('JSON Parsing Error:', parseError);
                console.error('Response Text:', responseText);
                
                // User-friendly error notification
                this.showErrorNotification('Unable to parse blockchain data. Please contact support.');
            }
        } catch (error) {
            console.error('Blockchain Fetch Error:', error);
            this.showErrorNotification('Failed to retrieve blockchain. Please try again later.');
        }
    }
    
    /**
     * Fetch mining requirements from API
     */
    async fetchMiningRequirements() {
        try {
            const response = await fetch('api.php?action=get-mining-requirements');
            const data = await response.json();
            
            if (data.success) {
                this.difficulty = data.requirements.difficulty;
                
                // Update UI
                const difficultyElement = document.getElementById('mining-difficulty');
                if (difficultyElement) {
                    difficultyElement.textContent = this.difficulty;
                }
                
                const rewardRateElement = document.getElementById('reward-rate');
                if (rewardRateElement) {
                    rewardRateElement.textContent = data.requirements.rewardRate;
                }
                
                const pendingTransactionsElement = document.getElementById('pending-transactions');
                if (pendingTransactionsElement) {
                    pendingTransactionsElement.textContent = data.requirements.pendingTransactions;
                }
            }
        } catch (error) {
            console.error('Error fetching mining requirements:', error);
            this.showErrorNotification('Failed to retrieve mining requirements. Please try again later.');
        }
    }
    
    /**
     * Fetch wallet information from API
     */
    async fetchWalletInfo() {
        try {
            const response = await fetch('api.php?action=get-wallet-info');
            const data = await response.json();
            
            if (data.success) {
                this.walletInfo = data.wallet;
                this.renderWalletInfo();
            }
        } catch (error) {
            console.error('Error fetching wallet info:', error);
            this.showErrorNotification('Failed to retrieve wallet information. Please try again later.');
        }
    }
    
    /**
     * Fetch credit card balance from API
     */
    async fetchCardBalance(creditCardNumber) {
        try {
            const formData = new FormData();
            formData.append('creditCardNumber', creditCardNumber);
            
            const response = await fetch('api.php?action=get-card-balance', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.cardBalance = data.balance;
                this.renderCardBalance(data.balance, data.transactions);
            }
        } catch (error) {
            console.error('Error fetching card balance:', error);
            this.showErrorNotification('Failed to retrieve card balance. Please try again later.');
        }
    }
    
    /**
     * Process reward for decrypting a message
     */
    async processDecryptionReward(creditCardNumber, messageId) {
        try {
            const formData = new FormData();
            formData.append('creditCardNumber', creditCardNumber);
            formData.append('messageId', messageId);
            
            const response = await fetch('api.php?action=process-decryption-reward', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Show reward notification
                this.showRewardNotification(data.result);
                
                // Update card balance
                await this.fetchCardBalance(creditCardNumber);
                
                // Update wallet info
                await this.fetchWalletInfo();
                
                // Refresh blockchain
                await this.fetchBlockchain();
            }
        } catch (error) {
            console.error('Error processing decryption reward:', error);
            this.showErrorNotification('Failed to process decryption reward. Please try again later.');
        }
    }
    
    /**
     * Show reward notification
     */
    showRewardNotification(result) {
        const notificationArea = document.getElementById('notification-area');
        if (!notificationArea) {
            console.error('Notification area not found');
            return;
        }
        
        const notification = document.createElement('div');
        notification.className = 'alert alert-success alert-dismissible fade show';
        notification.innerHTML = `
            <strong>Reward Received!</strong> You earned ${result.transaction.amount} reserves.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        notificationArea.appendChild(notification);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 500);
        }, 5000);
    }
    
    /**
     * Start mining process
     */
  /**
 * Start mining process
 */
startMining() {
    if (this.isMining) return;

    try {
        // Create block to mine
        const newBlock = this.createNewBlockTemplate();
        if (!newBlock) {
            throw new Error('Failed to create block template');
        }
        
        console.log("Starting mining with block:", newBlock);
        
        // Initialize worker
        this.miningWorker = new Worker('miningWorker.js');
        
        // Set up message handler
        this.miningWorker.onmessage = (event) => {
            console.log("Received worker message:", event.data);
            
            if (!event.data || !event.data.type) {
                console.error("Invalid message from worker");
                return;
            }
            
            switch (event.data.type) {
                case 'stats':
                    this.updateMiningStats(event.data);
                    break;
                    
                case 'success':
                    // If block is missing from the response, add it
                    if (!event.data.block) {
                        event.data.block = newBlock;
                        event.data.block.nonce = event.data.nonce;
                    }
                    this.handleMiningSuccess(event.data);
                    break;
                    
                case 'error':
                    this.handleMiningError(event.data);
                    break;
            }
        };
        
        // Set up error handler
        this.miningWorker.onerror = (error) => {
            console.error("Worker error:", error);
            this.handleMiningError({
                type: 'error',
                message: error.message || "Unknown worker error"
            });
        };
        
        // Send the mining request - using the exact format the worker expects
        this.miningWorker.postMessage({
            block: newBlock,
            difficulty: this.difficulty || 4
        });
        
        this.isMining = true;
        this.updateMiningStatus(true);
    } catch (error) {
        console.error('Mining initialization error:', error);
        this.showErrorNotification('Failed to start mining: ' + error.message);
        this.updateMiningStatus(false);
    }
}

/**
 * Handle mining error
 */
handleMiningError(errorData) {
    console.error('Mining error:', errorData);
    
    // Check if error message contains specific error strings
    if (errorData && errorData.message) {
        if (errorData.message.includes('block is undefined')) {
            console.log('Attempting to recover from undefined block error');
            
            // Check if blockchain data is available
            if (this.blockchain && this.blockchain.blocks && this.blockchain.blocks.length > 0) {
                // Try to recreate the block template
                const newBlock = this.createNewBlockTemplate();
                
                if (newBlock && this.miningWorker) {
                    console.log('Restarting mining with new block template:', newBlock);
                    
                    // Create a safe clone to ensure all properties are properly transferred
                    const blockClone = {
                        index: newBlock.index,
                        timestamp: newBlock.timestamp,
                        data: JSON.parse(JSON.stringify(newBlock.data)), // Deep clone
                        previousHash: newBlock.previousHash,
                        nonce: newBlock.nonce || 0
                    };
                    
                    // Send the new block to the worker
                    this.miningWorker.postMessage({
                        command: 'start-mining',
                        block: blockClone,
                        difficulty: this.difficulty || 4
                    });
                    return;
                }
            }
        } else if (errorData.message.includes('undefined has no properties')) {
            console.log('Handling "undefined has no properties" error');
            
            // This suggests the worker is trying to access a property on undefined
            // Let's restart the worker and try again with a more carefully constructed message
            this.stopMining();
            setTimeout(() => {
                if (this.blockchain && this.blockchain.blocks && this.blockchain.blocks.length > 0) {
                    console.log('Attempting to restart mining with simplified block structure');
                    this.startMining();
                }
            }, 1000); // Wait 1 second before trying again
            return;
        }
    }
    
    // If recovery fails or it's a different error, stop mining
    this.stopMining();
    this.showErrorNotification('Mining error: ' + (errorData ? errorData.message : 'Unknown error'));
}

/**
 * Create a new block template
 */
createNewBlockTemplate() {
    try {
        if (!this.blockchain || !this.blockchain.blocks || this.blockchain.blocks.length === 0) {
            console.error('Invalid blockchain state');
            throw new Error('Invalid blockchain state');
        }
        
        const lastBlock = this.blockchain.blocks[this.blockchain.blocks.length - 1];
        const newIndex = lastBlock.index + 1;
        
        const blockData = {
            minerInfo: {
                timestamp: Date.now(),
                browser: navigator.userAgent
            },
            transactions: this.blockchain.pendingTransactions || [],
            minedAt: Date.now()
        };
        
        return {
            index: newIndex,
            timestamp: Date.now(),
            data: blockData,
            previousHash: lastBlock.hash,
            nonce: 0
        };
    } catch (error) {
        console.error('Block template creation error:', error);
        this.showErrorNotification('Failed to create block template: ' + error.message);
        return null;
    }
}

    /**
     * Stop mining process
     */
    stopMining() {
        // Early return if not currently mining
          if (this.isMining) return;

    // Enhanced validation of blockchain data
    if (!this.blockchain || !this.blockchain.blocks) {
        console.error("No blockchain data available");
        this.showErrorNotification("Cannot start mining: No blockchain data available.");
        return;
    }

    if (this.blockchain.blocks.length === 0) {
        console.error("Blockchain has no blocks");
        this.showErrorNotification("Cannot start mining: Blockchain has no blocks.");
        return;
    }

    console.log("Starting mining with blockchain:", this.blockchain);
    console.log("Last block:", this.blockchain.blocks[this.blockchain.blocks.length - 1]);

        
        // Safely stop the worker
        try {
            if (this.miningWorker) {
                this.miningWorker.postMessage({ command: 'stop-mining' });
                this.miningWorker.terminate();
                this.miningWorker = null;
            }
        } catch (error) {
            console.error('Error stopping mining worker:', error);
            this.showErrorNotification('Failed to stop mining worker gracefully.');
        }
        
        // Reset mining state
        this.isMining = false;
        
        // Clear stats interval
        if (this.workerInterval) {
            clearInterval(this.workerInterval);
            this.workerInterval = null;
        }
        
        // Update UI
        this.updateMiningStatus(false);
        this.showMiningStoppedNotification();
    }
    
    /**
     * Show mining stopped notification
     */
    showMiningStoppedNotification() {
        const notificationArea = document.getElementById('notification-area');
        if (!notificationArea) {
            console.error('Notification area not found');
            return;
        }
        
        const notification = document.createElement('div');
        notification.className = 'alert alert-info alert-dismissible fade show';
        notification.innerHTML = `
            <strong>Mining Stopped</strong> The mining process has been terminated.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        notificationArea.appendChild(notification);
        
        // Auto-remove after 3 seconds
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 500);
        }, 3000);
    }
    
    /**
     * Handle messages from the mining worker
     */
    handleWorkerMessage(data) {
        switch (data.type) {
            case 'status':
                // Update status message
                const statusElement = document.getElementById('mining-status');
                if (statusElement) {
                    statusElement.textContent = data.status;
                }
                break;
                
            case 'progress':
                // Update progress stats
                this.updateMiningProgress(data);
                break;
                
            case 'stats':
                // Update mining stats
                this.updateMiningStats(data);
                break;
                
            case 'success':
                // Mining successful
                this.handleMiningSuccess(data);
                break;
                
            case 'error':
                // Mining error occurred
                this.handleMiningError(data);
                break;
        }
    }
    
    /**
     * Update mining status UI
     */
    updateMiningStatus(isMining) {
        const startButton = document.getElementById('start-mining');
        const stopButton = document.getElementById('stop-mining');
        const statusElement = document.getElementById('mining-status');
        
        if (startButton) {
            startButton.disabled = isMining;
        }
        if (stopButton) {
            stopButton.disabled = !isMining;
        }
        if (statusElement) {
            statusElement.textContent = isMining ? 'Mining in progress...' : 'Mining stopped';
        }
    }
    
    /**
     * Update mining progress UI
     */
updateMiningProgress(data) {
  const hashesElement = document.getElementById('hashes-computed');
  const rateElement = document.getElementById('hash-rate');
  const nonceElement = document.getElementById('current-nonce');

  if (hashesElement && data.hashesComputed !== undefined) {
    hashesElement.textContent = data.hashesComputed.toLocaleString();
  }
  if (rateElement && data.hashRate !== undefined) {
    rateElement.textContent = data.hashRate.toLocaleString() + ' H/s';
  }
  if (nonceElement && data.nonce !== undefined) {
    nonceElement.textContent = data.nonce.toLocaleString();
  }
}
    
    /**
     * Update mining stats UI
     */
    updateMiningStats(data) {
        // Similar to updateMiningProgress but called from the stats message
        this.updateMiningProgress(data);
    }
    
    /**
     * Handle mining error
     */
// Updated handleMiningError function that properly handles the 'block is undefined' error
handleMiningError(errorData) {
    console.error('Mining error:', errorData);
    
    // Check if error message contains 'block is undefined'
    if (errorData && errorData.message && errorData.message.includes('block is undefined')) {
        console.log('Attempting to recover from undefined block error');
        
        // Check if blockchain data is available
        if (this.blockchain && this.blockchain.blocks && this.blockchain.blocks.length > 0) {
            // Try to recreate the block template
            const newBlock = this.createNewBlockTemplate();
            
            if (newBlock && this.miningWorker) {
                console.log('Restarting mining with new block template:', newBlock);
                
                // Send the new block to the worker
                this.miningWorker.postMessage({
                    command: 'start-mining',
                    block: newBlock,
                    difficulty: this.difficulty || 4
                });
                return;
            }
        }
    }
    
    // If recovery fails or it's a different error, stop mining
    this.stopMining();
    this.showErrorNotification('Mining error: ' + (errorData ? errorData.message : 'Unknown error'));
}
    
    /**
     * Handle successful mining
     */
async handleMiningSuccess(data) {
    try {
        // Validate input data
        if (!data || !data.block) {
            console.error("Mining success received but block data was missing");
            this.showErrorNotification("Mining process completed but block data was missing");
            this.stopMining();
            return;
        }
        
        // Stop mining process
        this.stopMining();
        
        // Fetch current blockchain state
        const blockchainResponse = await fetch('api.php?action=get-blockchain');
        const blockchainResult = await blockchainResponse.json();
        const currentBlockchain = blockchainResult.blockchain;
        
        // Get the last block from the current blockchain
        const lastBlock = currentBlockchain.blocks[currentBlockchain.blocks.length - 1];
        
        // Prepare block for submission
        const block = data.block;
        
        // Detailed hash verification
        const dataString = JSON.stringify(block.data);
        const rawData = 
            block.index + 
            lastBlock.hash + 
            block.timestamp + 
            dataString + 
            data.nonce;
        
        console.log("Detailed Hash Verification:", {
            blockIndex: block.index,
            previousHash: lastBlock.hash,
            timestamp: block.timestamp,
            nonce: data.nonce,
            dataString: dataString
        });

        // Calculate hash using exact method
        const expectedHash = await this.calculateHash(rawData);
        
        // Validate hash matches worker's hash
        if (expectedHash !== data.hash) {
            console.error('Hash Verification Failed', {
                expectedHash: expectedHash,
                workerHash: data.hash,
                rawData: rawData
            });
            this.showErrorNotification('Hash verification failed. Please try again.');
            return;
        }
        
        // Prepare submission payload with extensive debugging
        const payload = {
            minerInfo: {
                identifier: this.generateMinerId(),
                timestamp: Date.now(),
                hashRate: data.hashRate,
                hashesComputed: data.hashesComputed,
                timeElapsed: data.timeElapsed,
                userAgent: navigator.userAgent
            },
            proofOfWork: {
                hash: data.hash,
                nonce: data.nonce,
                block: {
                    index: 1,
                    timestamp: block.timestamp,
                    previousHash: lastBlock.hash,
                    data: {
                        minerInfo: block.data.minerInfo,
                        transactions: currentBlockchain.pendingTransactions || [],
                        minedAt: block.data.minedAt
                    },
                    nonce: data.nonce
                }
            },
            debugContext: {
                rawDataUsedForHash: rawData,
                calculatedHash: expectedHash,
                originalWorkerHash: data.hash,
                hashMatches: expectedHash === data.hash,
                currentBlockchainState: {
                    lastBlockIndex: lastBlock.index,
                    lastBlockHash: lastBlock.hash,
                    pendingTransactionsCount: currentBlockchain.pendingTransactions?.length || 0
                },
                submissionAttemptTimestamp: Date.now()
            }
        };
        
        console.log("Comprehensive Submission Payload:", JSON.stringify(payload, null, 2));
        
        // Submit proof of work with comprehensive error handling
        const response = await fetch('api.php?action=submit-proof-of-work', {
            method: 'POST',
            body: JSON.stringify(payload),
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Mining-Diagnostic': 'full-details'
            }
        });
        
        // Detailed response handling
        const responseText = await response.text();
        console.log('Raw Server Response:', responseText);
        
        let result;
        try {
            result = JSON.parse(responseText);
        } catch (parseError) {
            console.error('Response Parsing Error:', parseError);
            throw new Error(`Failed to parse server response: ${responseText}`);
        }
        
        // Validate response
        if (!result.success) {
            console.error('Proof of Work Submission Detailed Error:', {
                error: result.error,
                trace: result.trace,
                inputData: result.inputData
            });
            
            // Construct a more informative error message
            const detailedErrorMessage = [
                'Proof of Work Submission Failed',
                `Error: ${result.error}`,
                `Block Index: ${payload.proofOfWork.block.index}`,
                `Hash: ${data.hash}`,
                `Nonce: ${data.nonce}`,
                `Previous Hash: ${payload.proofOfWork.block.previousHash}`
            ].join('\n');
            
            throw new Error(detailedErrorMessage);
        }
        
        // Success handling
        console.log('Proof of Work Submission Successful:', result);
        this.showMiningSuccessNotification(result.result);
        
        // Refresh data
        await Promise.all([
            this.fetchBlockchain(),
            this.fetchWalletInfo(),
            this.fetchMiningRequirements()
        ]);
        
    } catch (error) {
        console.error('Mining Submission Process Error:', {
            message: error.message,
            name: error.name,
            stack: error.stack
        });
        
        this.showErrorNotification(`Mining submission failed: ${error.message}`);
    }
}

// Ensure hash calculation matches server-side method exactly
async calculateHash(input) {
    // Use Web Crypto API for consistent hashing
    const encoder = new TextEncoder();
    const data = encoder.encode(input);
    
    const hashBuffer = await crypto.subtle.digest('SHA-256', data);
    const hashArray = Array.from(new Uint8Array(hashBuffer));
    const hashHex = hashArray.map(b => b.toString(16).padStart(2, '0')).join('');
    
    return hashHex;
}
    
    /**
     * Show mining success notification
     */
    showMiningSuccessNotification(result) {
        const notificationArea = document.getElementById('notification-area');
        if (!notificationArea) {
            console.error('Notification area not found');
            return;
        }
        
        const notification = document.createElement('div');
        notification.className = 'alert alert-success alert-dismissible fade show';
        notification.innerHTML = `
            <strong>Block Mined Successfully!</strong> Block #${result.block.index} added to the blockchain.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        notificationArea.appendChild(notification);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 500);
        }, 5000);
    }
    
    /**
     * Generate a unique miner ID
     */
    generateMinerId() {
        const components = [
            navigator.userAgent,
            navigator.language,
            screen.width,
            screen.height,
            new Date().getTimezoneOffset()
        ];
        
        const str = components.join('|');
        let hash = 0;
        for (let i = 0; i < str.length; i++) {
            const char = str.charCodeAt(i);
            hash = ((hash << 5) - hash) + char;
            hash = hash & hash;
        }
        
        return 'miner_' + (hash >>> 0).toString(16);
    }
    
    /**
     * Create a new block template
     */
    createNewBlockTemplate() {
        try {
            if (!this.blockchain || !this.blockchain.blocks || this.blockchain.blocks.length === 0) {
                throw new Error('Invalid blockchain state');
            }
            
            const lastBlock = this.blockchain.blocks[this.blockchain.blocks.length - 1];
            const newIndex = lastBlock.index + 1;
            
            const blockData = {
                minerInfo: {
                    timestamp: Date.now(),
                    browser: navigator.userAgent
                },
                transactions: this.blockchain.pendingTransactions || [],
                minedAt: Date.now()
            };
            
            return {
                index: newIndex,
                timestamp: Date.now(),
                data: blockData,
                previousHash: lastBlock.hash,
                nonce: 0
            };
        } catch (error) {
            console.error('Block template creation error:', error);
            this.showErrorNotification('Failed to create block template: ' + error.message);
            return null;
        }
    }
    
    /**
     * Render the blockchain UI
     */
    renderBlockchain() {
        const blockchainContainer = document.getElementById('blockchain-container');
        if (!blockchainContainer || !this.blockchain) {
            return;
        }
        
        let html = '<div class="blockchain-timeline">';
        
        const blocks = [...this.blockchain.blocks].reverse();
        
        blocks.forEach(block => {
            const blockIndex = block.index;
            const timestamp = new Date(block.timestamp * 1000).toLocaleString();
            const shortHash = block.hash.substring(0, 8) + '...' + block.hash.substring(block.hash.length - 8);
            
            html += `
                <div class="block-item">
                    <div class="block-icon">
                        <i class="fas fa-cube"></i>
                    </div>
                    <div class="block-content">
                        <div class="block-header">
                            <div>
                                <strong>Block #${blockIndex}</strong>
                                <div class="text-muted small">${timestamp}</div>
                            </div>
                            <div class="badge bg-primary">Nonce: ${block.nonce}</div>
                        </div>
                        <div class="block-hash small mb-2">
                            <i class="fas fa-hashtag me-1"></i>${shortHash}
                        </div>
                        <div class="block-details">
                            <div class="small text-muted mb-1">
                                <i class="fas fa-link me-1"></i>Previous: ${block.previousHash.substring(0, 8)}...
                            </div>
                        </div>
                        <div class="block-transactions mt-2">
                            <div class="small fw-bold mb-1">Transactions:</div>
            `;
            
            if (block.data && block.data.transactions && block.data.transactions.length > 0) {
                html += '<div class="transaction-list">';
                
                block.data.transactions.forEach(transaction => {
                    const transactionType = transaction.type === 'reward' ? 'bg-success' : 'bg-primary';
                    html += `
                        <div class="transaction-item">
                            <span class="badge ${transactionType}">${transaction.type}</span>
                            <span class="transaction-card">${transaction.creditCardNumber}</span>
                            <span class="transaction-amount">${transaction.amount}</span>
                        </div>
                    `;
                });
                
                html += '</div>';
            } else {
                html += '<div class="text-muted small">No transactions in this block</div>';
            }
            
            html += `
                        </div>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        
        blockchainContainer.innerHTML = html;
    }
    
    /**
     * Render wallet information
     */
    renderWalletInfo() {
        if (!this.walletInfo) {
            return;
        }
        
        const balanceElement = document.getElementById('wallet-balance');
		
		if (balanceElement) {
            balanceElement.textContent = this.walletInfo.balance.toFixed(2);
        }
        
        const addressElement = document.getElementById('wallet-address');
        if (addressElement) {
            addressElement.textContent = this.walletInfo.address;
        }
        
        if (this.walletInfo.statistics) {
            const stats = this.walletInfo.statistics;
            
            const totalTransactionsElement = document.getElementById('total-transactions');
            if (totalTransactionsElement) {
                totalTransactionsElement.textContent = stats.totalTransactions;
            }
            
            const totalRewardsElement = document.getElementById('total-rewards');
            if (totalRewardsElement) {
                totalRewardsElement.textContent = stats.totalRewardsDistributed.toFixed(2);
            }
            
            const totalMessagesElement = document.getElementById('total-messages');
            if (totalMessagesElement) {
                totalMessagesElement.textContent = stats.totalMessages;
            }
            
            const avgHashRateElement = document.getElementById('avg-hash-rate');
            if (avgHashRateElement) {
                avgHashRateElement.textContent = stats.averageHashRate.toLocaleString() + ' H/s';
            }
        }
    }
    
    /**
     * Render card balance and transaction history
     */
    renderCardBalance(balance, transactions) {
        const balanceElement = document.getElementById('card-balance');
        if (balanceElement) {
            balanceElement.textContent = balance.toFixed(2);
        }
        
        const historyElement = document.getElementById('transaction-history');
        if (historyElement && transactions && transactions.length > 0) {
            let html = '<div class="list-group">';
            
            transactions.forEach(transaction => {
                const date = new Date(transaction.timestamp * 1000).toLocaleString();
                const typeClass = transaction.type === 'reward' ? 'text-success' : 'text-primary';
                
                html += `
                    <div class="list-group-item">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1 ${typeClass}">${transaction.type.toUpperCase()}</h6>
                            <small>${date}</small>
                        </div>
                        <p class="mb-1">${transaction.message || 'Transaction'}</p>
                        <div class="d-flex justify-content-between">
                            <small class="text-muted">Status: ${transaction.status}</small>
                            <strong>${transaction.amount.toFixed(2)}</strong>
                        </div>
                    </div>
                `;
            });
            
            html += '</div>';
            historyElement.innerHTML = html;
        } else if (historyElement) {
            historyElement.innerHTML = '<p class="text-center text-muted">No transactions found</p>';
        }
    }
    
    /**
     * Show error notification
     */
    showErrorNotification(message) {
        const notificationArea = document.getElementById('notification-area');
        if (!notificationArea) {
            console.error('Notification area not found');
            return;
        }
        
        const notification = document.createElement('div');
        notification.className = 'alert alert-danger alert-dismissible fade show';
        notification.innerHTML = `
            <strong>Error!</strong> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        notificationArea.appendChild(notification);
        
        // Auto-remove after 5 seconds
        setTimeout(() => {
            notification.classList.remove('show');
            setTimeout(() => notification.remove(), 500);
        }, 5000);
    }
}

// Initialize the blockchain interface
document.addEventListener('DOMContentLoaded', () => {
    window.blockchainInterface = new BlockchainInterface();
});