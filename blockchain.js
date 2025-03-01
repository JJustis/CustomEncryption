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
        // Ensure proper worker initialization
        this.miningWorker = new Worker('miningWorker.js');
        
        // Robust message handling
        this.miningWorker.onmessage = (event) => {
            const data = event.data;
            
            switch (data.type) {
                case 'status':
                    this.updateMiningStatus(data);
                    break;
                
                case 'stats':
                    this.updateMiningStats(data);
                    break;
                
                case 'success':
                    this.handleMiningSuccess(data);
                    break;
                
                case 'error':
                    this.handleMiningError(data);
                    break;
            }
        };

        // Add error handling for worker
        this.miningWorker.onerror = (error) => {
            console.error('Mining worker error:', error);
            this.stopMining();
            this.showErrorNotification('Mining worker encountered an error. Mining has been stopped.');
        };

        // Create a new block to mine
        const newBlock = this.createNewBlockTemplate();
        
        // Ensure block exists and has required properties
        if (!newBlock) {
            throw new Error('Failed to create block template');
        }

        // Start mining with explicit difficulty
        this.miningWorker.postMessage({
            command: 'start-mining',
            block: newBlock,
            difficulty: this.difficulty || 4
        });

        this.isMining = true;
        this.updateMiningStatus(true);

        // Setup interval for stats (with additional error protection)
        this.workerInterval = setInterval(() => {
            if (this.miningWorker && this.isMining) {
                try {
                    this.miningWorker.postMessage({ command: 'get-stats' });
                } catch (error) {
                    console.error('Error requesting mining stats:', error);
                    this.stopMining();
                    this.showErrorNotification('Failed to communicate with mining worker. Mining has been stopped.');
                }
            } else {
                clearInterval(this.workerInterval);
            }
        }, 1000);

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
    
    // Check if block is undefined
    if (errorData.message.includes('block is undefined')) {
        console.error('Block data is missing or undefined');
        // Implement fallback or default behavior for missing block data
        // For example, you can try to recreate the block template
        const newBlock = this.createNewBlockTemplate();
        if (newBlock) {
            console.log('Retrying mining with new block template');
            this.miningWorker.postMessage({
                command: 'start-mining',
                block: newBlock,
                difficulty: this.difficulty || 4
            });
            return;
        }
    }
    
    // If block is undefined or other errors occur, stop mining
    this.stopMining();
    this.showErrorNotification('Mining encountered an error: ' + errorData.message);
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
        if (!this.isMining) {
            return;
        }
        
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
    handleMiningError(errorData) {
        console.error('Mining error:', errorData);
        this.stopMining();
        this.showErrorNotification('Mining error occurred: ' + errorData.message);
    }
    
    /**
     * Handle successful mining
     */
    async handleMiningSuccess(data) {
        // Update UI
        const statusElement = document.getElementById('mining-status');
        if (statusElement) {
            statusElement.textContent = `Block mined successfully! Hash: ${data.hash.substring(0, 12)}...`;
        }
        
        // Stop mining process
        this.stopMining();
        
        // Calculate and verify the hash locally before submitting
        const block = data.block;
        const dataString = JSON.stringify(block.data);
        const rawData = block.index + block.previousHash + block.timestamp + dataString + block.nonce;
        
        console.log("Block being verified:", {
            index: block.index,
            previousHash: block.previousHash,
            timestamp: block.timestamp,
            nonce: block.nonce,
            data: block.data
        });
        console.log("Raw data for hash calculation:", rawData);
        console.log("Expected hash from worker:", data.hash);
        
        // Verify hash using the raw block data
        const expectedHash = await this.calculateHash(rawData);
        
        if (expectedHash !== data.hash) {
            console.error('Hash verification failed. Expected:', expectedHash, 'Received:', data.hash);
            this.showErrorNotification('Mining result verification failed. Please try again.');
            return;
        }
        
        console.log('Hash verification successful.');
        
        // Prepare miner info
        const minerInfo = {
            identifier: this.generateMinerId(),
            hashRate: data.hashRate,
            hashesComputed: data.hashesComputed,
            timeElapsed: data.timeElapsed,
            userAgent: navigator.userAgent
        };
        
        // Prepare proof of work
        const proofOfWork = {
            hash: data.hash,
            nonce: data.nonce,
            block: block
        };
        
        console.log("Submitting proof of work:", {
            minerInfo: minerInfo,
            proofOfWork: proofOfWork
        });
        
        // Submit proof of work to server
               try {
            const response = await fetch('api.php?action=submit-proof-of-work', {
                method: 'POST',
                body: JSON.stringify({
                    minerInfo: minerInfo,
                    proofOfWork: proofOfWork
                }),
                headers: {
                    'Content-Type': 'application/json'
                }
            });

            const result = await response.json();

            if (result.success) {
                // Show success notification
                this.showMiningSuccessNotification(result.data);
                
                // Refresh blockchain and wallet data
                await this.fetchBlockchain();
                await this.fetchWalletInfo();
                await this.fetchMiningRequirements();
            } else {
                throw new Error(result.error);
            }
        } catch (error) {
            console.error('Error submitting proof of work:', error);
            this.showErrorNotification('Failed to submit proof of work: ' + error.message);
        }
    } catch (error) {
        console.error('Mining success error:', error);
        this.showErrorNotification('Mining success failed: ' + error.message);
    }

    
    /**
     * Calculate hash for given data
     */
    async calculateHash(data) {
        const encoder = new TextEncoder();
        const dataArray = encoder.encode(data);
        const hashBuffer = await crypto.subtle.digest('SHA-256', dataArray);
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