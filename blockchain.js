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
    }
    // Add this method to the BlockchainInterface class
showErrorNotification(message) {
  const notification = document.createElement('div');
  notification.className = 'alert alert-danger alert-dismissible fade show';
  notification.innerHTML = `
    <strong>Error!</strong> ${message}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
  `;
  
  // Add to notification area
  const notificationArea = document.getElementById('notification-area');
  if (notificationArea) {
    notificationArea.appendChild(notification);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
      notification.classList.remove('show');
      setTimeout(() => notification.remove(), 500);
    }, 5000);
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
            const cardNumber = document.getElementById('card-number').value;
            if (cardNumber) {
                this.fetchCardBalance(cardNumber);
            }
        });
        
        // Store credit card number when generated
        document.addEventListener('creditCardGenerated', (e) => {
            if (e.detail && e.detail.creditCardNumber) {
                this.creditCardNumber = e.detail.creditCardNumber;
                // Update UI if needed
                if (document.getElementById('card-number')) {
                    document.getElementById('card-number').value = this.creditCardNumber;
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
     * Fetch the blockchain data
     */
    async fetchBlockchain() {
        try {
            const response = await fetch('api.php?action=get-blockchain');
            const data = await response.json();
            
            if (data.success) {
                this.blockchain = data.blockchain;
                this.renderBlockchain();
            }
        } catch (error) {
            console.error('Error fetching blockchain:', error);
        }
    }
    
    /**
     * Fetch mining requirements
     */
    async fetchMiningRequirements() {
        try {
            const response = await fetch('api.php?action=get-mining-requirements');
            const data = await response.json();
            
            if (data.success) {
                this.difficulty = data.requirements.difficulty;
                
                // Update UI
                if (document.getElementById('mining-difficulty')) {
                    document.getElementById('mining-difficulty').textContent = this.difficulty;
                }
                
                if (document.getElementById('reward-rate')) {
                    document.getElementById('reward-rate').textContent = data.requirements.rewardRate;
                }
                
                if (document.getElementById('pending-transactions')) {
                    document.getElementById('pending-transactions').textContent = data.requirements.pendingTransactions;
                }
            }
        } catch (error) {
            console.error('Error fetching mining requirements:', error);
        }
    }
    
    /**
     * Fetch wallet information
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
        }
    }
    
    /**
     * Fetch credit card balance
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
        }
    }
    
    /**
     * Show reward notification
     */
    showRewardNotification(result) {
        const notification = document.createElement('div');
        notification.className = 'alert alert-success alert-dismissible fade show';
        notification.innerHTML = `
            <strong>Reward Received!</strong> You earned ${result.transaction.amount} reserves.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        // Add to notification area
        const notificationArea = document.getElementById('notification-area');
        if (notificationArea) {
            notificationArea.appendChild(notification);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => notification.remove(), 500);
            }, 5000);
        }
    }
    
/**
 * Start mining process
 */
startMining() {
    if (this.isMining) {
        return;
    }
    
    try {
        // Create a new worker with proper initialization
        this.miningWorker = new Worker('miningWorker.js');
        
        // Setup event listener for worker messages
        this.miningWorker.onmessage = (e) => this.handleWorkerMessage(e.data);
        
        // Create a new block to mine
        const newBlock = this.createNewBlockTemplate();
        
        // Start mining
        this.miningWorker.postMessage({
            command: 'start-mining',
            block: newBlock,
            difficulty: this.difficulty
        });
        
        this.isMining = true;
        
        // Update UI
        this.updateMiningStatus(true);
        
        // Setup interval to request stats
        this.workerInterval = setInterval(() => {
            if (this.miningWorker) {
                this.miningWorker.postMessage({ command: 'get-stats' });
            }
        }, 1000);
    } catch (error) {
        console.error('Error starting mining worker:', error);
        alert('Mining is not supported in your browser. Please try a different browser.');
        this.updateMiningStatus(false);
    }
}

/**
 * Stop mining process
 */
stopMining() {
    if (!this.isMining) {
        return;
    }
    
    // Stop the worker
    if (this.miningWorker) {
        this.miningWorker.postMessage({ command: 'stop-mining' });
        // Terminate the worker
        this.miningWorker.terminate();
        this.miningWorker = null;
    }
    
    this.isMining = false;
    
    // Clear the stats interval
    if (this.workerInterval) {
        clearInterval(this.workerInterval);
        this.workerInterval = null;
    }
    
    // Update UI
    this.updateMiningStatus(false);
}
    
    /**
     * Handle messages from the mining worker
     */
    handleWorkerMessage(data) {
        switch (data.type) {
            case 'status':
                // Update status message
                if (document.getElementById('mining-status')) {
                    document.getElementById('mining-status').textContent = data.status;
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
  
  if (hashesElement) {
    hashesElement.textContent = data.hashesComputed.toLocaleString();
  }
  if (rateElement) {
    rateElement.textContent = data.hashRate.toLocaleString() + ' H/s';
  }
  if (nonceElement && data.nonce !== undefined) { // Check if nonce exists
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
     * Handle successful mining
     */
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
        block: data.block
    };
    
    console.log("Submitting proof of work:", {
        minerInfo: minerInfo,
        proofOfWork: proofOfWork
    });
    
    // Submit proof of work to server
    try {
        const formData = new FormData();
        formData.append('minerInfo', JSON.stringify(minerInfo));
        formData.append('proofOfWork', JSON.stringify(proofOfWork));
        
        const response = await fetch('api.php?action=submit-proof-of-work', {
            method: 'POST',
            body: formData
        });
        
        const responseText = await response.text();
        console.log("Server response:", responseText);
        
        let result;
        try {
            result = JSON.parse(responseText);
        } catch (e) {
            console.error("Failed to parse JSON response:", e);
            throw new Error("Server returned invalid JSON: " + responseText);
        }
        
        if (result.success) {
            // Show success notification
            this.showMiningSuccessNotification(result.result);
            
            // Refresh blockchain and wallet data
            await this.fetchBlockchain();
            await this.fetchWalletInfo();
            await this.fetchMiningRequirements();
        } else {
            console.error('Error submitting proof of work:', result.error);
            // Show error notification
            this.showErrorNotification("Mining verification failed: " + result.error);
        }
    } catch (error) {
        console.error('Error submitting proof of work:', error);
    }
}
    
    /**
     * Show mining success notification
     */
    showMiningSuccessNotification(result) {
        const notification = document.createElement('div');
        notification.className = 'alert alert-success alert-dismissible fade show';
        notification.innerHTML = `
            <strong>Block Mined Successfully!</strong> Block #${result.block.index} added to the blockchain.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        
        // Add to notification area
        const notificationArea = document.getElementById('notification-area');
        if (notificationArea) {
            notificationArea.appendChild(notification);
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => notification.remove(), 500);
            }, 5000);
        }
    }
    
    /**
     * Generate a unique miner ID
     */
    generateMinerId() {
        // Generate a semi-unique ID based on browser fingerprint
        const components = [
            navigator.userAgent,
            navigator.language,
            screen.width,
            screen.height,
            new Date().getTimezoneOffset()
        ];
        
        // Simple hash function for components
        let hash = 0;
        const str = components.join('|');
        for (let i = 0; i < str.length; i++) {
            const char = str.charCodeAt(i);
            hash = ((hash << 5) - hash) + char;
            hash = hash & hash; // Convert to 32bit integer
        }
        
        // Return a hexadecimal string
        return 'miner_' + (hash >>> 0).toString(16);
    }
    
    /**
     * Create a new block template for mining
     */
    createNewBlockTemplate() {
        if (!this.blockchain || !this.blockchain.blocks) {
            return null;
        }
        
        // Get the last block
        const lastBlock = this.blockchain.blocks[this.blockchain.blocks.length - 1];
        const newIndex = lastBlock.index + 1;
        
        // Prepare block data
        const blockData = {
            minerInfo: {
                timestamp: Date.now(),
                browser: navigator.userAgent
            },
            transactions: this.blockchain.pendingTransactions,
            minedAt: Date.now()
        };
        
        // Create new block template
        return {
            index: newIndex,
            timestamp: Date.now(),
            data: blockData,
            previousHash: lastBlock.hash,
            nonce: 0
        };
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
        
        // Display blocks in reverse order (newest first)
        const blocks = [...this.blockchain.blocks].reverse();
        
        blocks.forEach((block, index) => {
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
            
            // Add transactions if they exist
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
            
            // Close block
            html += `
                        </div>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        
        // Update container
        blockchainContainer.innerHTML = html;
    }
    
    /**
     * Render wallet information
     */
    renderWalletInfo() {
        if (!this.walletInfo) {
            return;
        }
        
        // Update wallet balance
        const balanceElement = document.getElementById('wallet-balance');
        if (balanceElement) {
            balanceElement.textContent = this.walletInfo.balance.toFixed(2);
        }
        
        // Update wallet address
        const addressElement = document.getElementById('wallet-address');
        if (addressElement) {
            addressElement.textContent = this.walletInfo.address;
        }
        
        // Update statistics
        if (this.walletInfo.statistics) {
            const stats = this.walletInfo.statistics;
            
            if (document.getElementById('total-transactions')) {
                document.getElementById('total-transactions').textContent = stats.totalTransactions;
            }
            
            if (document.getElementById('total-rewards')) {
                document.getElementById('total-rewards').textContent = stats.totalRewardsDistributed.toFixed(2);
            }
            
            if (document.getElementById('total-messages')) {
                document.getElementById('total-messages').textContent = stats.totalMessages;
            }
            
            if (document.getElementById('avg-hash-rate')) {
                document.getElementById('avg-hash-rate').textContent = stats.averageHashRate.toLocaleString() + ' H/s';
            }
        }
    }
    
    /**
     * Render card balance and transaction history
     */
    renderCardBalance(balance, transactions) {
        // Update balance display
        const balanceElement = document.getElementById('card-balance');
        if (balanceElement) {
            balanceElement.textContent = balance.toFixed(2);
        }
        
        // Update transaction history
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
}

// Initialize the blockchain interface
document.addEventListener('DOMContentLoaded', () => {
    window.blockchainInterface = new BlockchainInterface();
});