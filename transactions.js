/**
 * Transaction UI Interface
 * Handles the user interface for payment and transaction features
 */

class TransactionInterface {
    constructor() {
        this.creditCardNumber = null;
        this.transactionHistory = [];
        this.pendingRequests = [];
        
        // Initialize the interface
        this.initialize();
    }
    
    /**
     * Initialize the transaction interface
     */
    async initialize() {
        // Setup event listeners
        this.setupEventListeners();
        
        // Try to get credit card number from storage
        this.loadCreditCardNumber();
        
        // If we have a card number, load transaction history
        if (this.creditCardNumber) {
            await this.loadTransactionHistory();
            await this.loadPendingRequests();
        }
    }
    
    /**
     * Load credit card number from storage
     */
loadCreditCardNumber() {
    console.log('Attempting to load credit card number');
    
    // Try to get from blockchain interface if available
    if (window.blockchainInterface && window.blockchainInterface.creditCardNumber) {
        console.log('Got card number from blockchain interface:', window.blockchainInterface.creditCardNumber);
        this.creditCardNumber = window.blockchainInterface.creditCardNumber;
    } else {
        // Try to get from localStorage
        const storedCard = localStorage.getItem('creditCardNumber');
        if (storedCard) {
            console.log('Got card number from localStorage:', storedCard);
            this.creditCardNumber = storedCard;
        } else {
            console.warn('No credit card number found');
        }
    }
    
    // Update UI if card number is available
    if (this.creditCardNumber) {
        this.updateCardDisplay();
    } else {
        console.error('No credit card number available. Please generate a credit card.');
        this.showNotification('Please generate a credit card first', 'warning');
    }
}
    
    /**
     * Save credit card number to storage
     */
    saveCreditCardNumber(cardNumber) {
        this.creditCardNumber = cardNumber;
        
        // Save to localStorage
        localStorage.setItem('creditCardNumber', cardNumber);
        
        // Update blockchain interface if available
        if (window.blockchainInterface) {
            window.blockchainInterface.creditCardNumber = cardNumber;
        }
        
        // Update UI
        this.updateCardDisplay();
    }
    
    /**
     * Update card display in UI
     */
    updateCardDisplay() {
        const cardDisplayElements = document.querySelectorAll('.card-number-display');
        const cardInputElements = document.querySelectorAll('.card-number-input');
        
        // Display masked version of card
        const maskedCard = this.getMaskedCardNumber();
        
        cardDisplayElements.forEach(element => {
            element.textContent = maskedCard;
        });
        
        // Set card number in input fields
        cardInputElements.forEach(element => {
            element.value = this.creditCardNumber;
        });
    }
    
    /**
     * Get masked version of card number
     */
    getMaskedCardNumber() {
        if (!this.creditCardNumber) return '';
        return this.creditCardNumber.substr(0, 4) + ' **** **** ' + this.creditCardNumber.substr(-4);
    }
    
    /**
     * Setup event listeners
     */
    setupEventListeners() {
        // Send payment form
        document.getElementById('send-payment-form')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.sendPayment();
        });
        
        // Request payment form
        document.getElementById('request-payment-form')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.requestPayment();
        });
        
        // Generate QR code button
        document.getElementById('generate-qr-button')?.addEventListener('click', () => {
            this.generatePaymentQR();
        });
        
        // Scan QR code button
        document.getElementById('scan-qr-button')?.addEventListener('click', () => {
            this.showScanQRModal();
        });
        
        // Tabs for transaction history
        document.querySelectorAll('.transaction-tab')?.forEach(tab => {
            tab.addEventListener('click', () => {
                this.switchTransactionTab(tab.dataset.tabType);
            });
        });
        
        // Listen for credit card generated event
        document.addEventListener('creditCardGenerated', (e) => {
            if (e.detail && e.detail.creditCardNumber) {
                this.saveCreditCardNumber(e.detail.creditCardNumber);
            }
        });
        
        // Close modal buttons
        document.querySelectorAll('.close-modal')?.forEach(button => {
            button.addEventListener('click', () => {
                const modal = button.closest('.modal');
                if (modal) {
                    this.hideModal(modal);
                }
            });
        });
        
        // Refresh transaction history button
        document.getElementById('refresh-transactions')?.addEventListener('click', () => {
            this.loadTransactionHistory();
            this.loadPendingRequests();
        });
    }
    
    /**
     * Switch between transaction history tabs
     */
    switchTransactionTab(tabType) {
        // Update active tab
        document.querySelectorAll('.transaction-tab').forEach(tab => {
            if (tab.dataset.tabType === tabType) {
                tab.classList.add('active');
            } else {
                tab.classList.remove('active');
            }
        });
        
        // Show/hide corresponding content
        document.querySelectorAll('.transaction-content').forEach(content => {
            if (content.dataset.tabType === tabType) {
                content.style.display = 'block';
            } else {
                content.style.display = 'none';
            }
        });
    }
    
    /**
     * Send payment to another user
     */
    async sendPayment() {
        if (!this.creditCardNumber) {
            this.showNotification('Please enter your credit card number first', 'error');
            return;
        }
        
        const recipientCard = document.getElementById('recipient-card').value;
        const amount = parseFloat(document.getElementById('payment-amount').value);
        const memo = document.getElementById('payment-memo').value;
        
        if (!recipientCard || isNaN(amount) || amount <= 0) {
            this.showNotification('Please enter a valid recipient and amount', 'error');
            return;
        }
        
        try {
            const formData = new FormData();
            formData.append('senderCard', this.creditCardNumber);
            formData.append('recipientCard', recipientCard);
            formData.append('amount', amount);
            formData.append('memo', memo);
            
            const response = await fetch('transaction-api.php?action=send-payment', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showNotification(`Payment of ${amount} sent successfully!`, 'success');
                
                // Clear form
                document.getElementById('recipient-card').value = '';
                document.getElementById('payment-amount').value = '';
                document.getElementById('payment-memo').value = '';
                
                // Refresh transaction history
                await this.loadTransactionHistory();
                
                // Update blockchain balance if available
                if (window.blockchainInterface) {
                    window.blockchainInterface.fetchCardBalance(this.creditCardNumber);
                }
            } else {
                this.showNotification('Error: ' + data.error, 'error');
            }
        } catch (error) {
            console.error('Error sending payment:', error);
            this.showNotification('Error sending payment', 'error');
        }
    }
    
    /**
     * Request payment from another user
     */
    async requestPayment() {
        if (!this.creditCardNumber) {
            this.showNotification('Please enter your credit card number first', 'error');
            return;
        }
        
        const payerCard = document.getElementById('payer-card').value;
        const amount = parseFloat(document.getElementById('request-amount').value);
        const memo = document.getElementById('request-memo').value;
        
        if (!payerCard || isNaN(amount) || amount <= 0) {
            this.showNotification('Please enter a valid payer and amount', 'error');
            return;
        }
        
        try {
            const formData = new FormData();
            formData.append('requestorCard', this.creditCardNumber);
            formData.append('payerCard', payerCard);
            formData.append('amount', amount);
            formData.append('memo', memo);
            
            const response = await fetch('transaction-api.php?action=request-payment', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showNotification(`Payment request of ${amount} sent successfully!`, 'success');
                
                // Clear form
                document.getElementById('payer-card').value = '';
                document.getElementById('request-amount').value = '';
                document.getElementById('request-memo').value = '';
                
                // Refresh transaction history
                await this.loadTransactionHistory();
                await this.loadPendingRequests();
            } else {
                this.showNotification('Error: ' + data.error, 'error');
            }
        } catch (error) {
            console.error('Error requesting payment:', error);
            this.showNotification('Error requesting payment', 'error');
        }
    }
    
    /**
     * Generate payment QR code
     */
    async generatePaymentQR() {
        if (!this.creditCardNumber) {
            this.showNotification('Please enter your credit card number first', 'error');
            return;
        }
        
        const amount = parseFloat(document.getElementById('qr-amount').value);
        const memo = document.getElementById('qr-memo').value;
        
        if (isNaN(amount) || amount <= 0) {
            this.showNotification('Please enter a valid amount', 'error');
            return;
        }
        
        try {
            const formData = new FormData();
            formData.append('recipientCard', this.creditCardNumber);
            formData.append('amount', amount);
            formData.append('memo', memo);
            
            const response = await fetch('transaction-api.php?action=generate-payment-qr', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Display QR code
                this.displayPaymentQR(data.qrCode, amount, memo);
            } else {
                this.showNotification('Error: ' + data.error, 'error');
            }
        } catch (error) {
            console.error('Error generating QR code:', error);
            this.showNotification('Error generating QR code', 'error');
        }
    }
    
    /**
     * Display payment QR code
     */
    displayPaymentQR(qrCode, amount, memo) {
        const modal = document.getElementById('qr-code-modal');
        if (!modal) return;
        
        const qrDisplay = document.getElementById('qr-code-display');
        const amountDisplay = document.getElementById('qr-amount-display');
        const memoDisplay = document.getElementById('qr-memo-display');
        
        // In a real implementation, you would use a QR code library to generate the QR code
        // For this demo, we'll just display the data
        qrDisplay.innerHTML = `
            <div class="qr-code-placeholder">
                <pre>${JSON.stringify(qrCode.qrData, null, 2)}</pre>
            </div>
        `;
        
        amountDisplay.textContent = amount;
        memoDisplay.textContent = memo || 'No memo';
        
        // Show the modal
        this.showModal(modal);
    }
    
    /**
     * Show scan QR code modal
     */
    showScanQRModal() {
        const modal = document.getElementById('scan-qr-modal');
        if (!modal) return;
        
        // In a real implementation, you would initialize a QR code scanner
        // For this demo, we'll just show a text area to paste the QR code data
        
        // Show the modal
        this.showModal(modal);
    }
    
    /**
     * Process scanned QR code
     */
    async processScannedQR() {
        if (!this.creditCardNumber) {
            this.showNotification('Please enter your credit card number first', 'error');
            return;
        }
        
        const qrData = document.getElementById('qr-data-input').value;
        
        if (!qrData) {
            this.showNotification('Please scan or enter QR code data', 'error');
            return;
        }
        
        try {
            const formData = new FormData();
            formData.append('qrData', qrData);
            formData.append('senderCard', this.creditCardNumber);
            
            const response = await fetch('transaction-api.php?action=scan-payment-qr', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.showNotification('Payment processed successfully!', 'success');
                
                // Close the modal
                this.hideModal(document.getElementById('scan-qr-modal'));
                
                // Refresh transaction history
                await this.loadTransactionHistory();
                
                // Update blockchain balance if available
                if (window.blockchainInterface) {
                    window.blockchainInterface.fetchCardBalance(this.creditCardNumber);
                }
            } else {
                this.showNotification('Error: ' + data.error, 'error');
            }
        } catch (error) {
            console.error('Error processing QR code:', error);
            this.showNotification('Error processing QR code', 'error');
        }
    }
    
    /**
     * Load transaction history for the current user
     */
    async loadTransactionHistory() {
        if (!this.creditCardNumber) return;
        
        try {
            const formData = new FormData();
            formData.append('cardNumber', this.creditCardNumber);
            
            const response = await fetch('transaction-api.php?action=get-transaction-history', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.transactionHistory = data.history;
                this.renderTransactionHistory();
            } else {
                console.error('Error loading transaction history:', data.error);
            }
        } catch (error) {
            console.error('Error loading transaction history:', error);
        }
    }
    
    /**
     * Load pending payment requests
     */
async loadPendingRequests() {
    if (!this.creditCardNumber) return;
    
    try {
        const formData = new FormData();
        formData.append('cardNumber', this.creditCardNumber);
        
        const response = await fetch('transaction-api.php?action=get-pending-requests', {
            method: 'POST',
            body: formData
        });
        
        // Log the raw response text before parsing
        const responseText = await response.text();
        console.log('Raw Pending Requests Response:', responseText);
        
        // Check if response is empty or not JSON
        if (!responseText.trim()) {
            console.error('Empty response received');
            this.pendingRequests = [];
            this.renderPendingRequests();
            return;
        }
        
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (parseError) {
            console.error('JSON Parsing Error:', parseError);
            console.error('Response that failed to parse:', responseText);
            
            // Show error notification
            this.showNotification('Error loading pending requests. Please try again.', 'error');
            
            // Clear pending requests
            this.pendingRequests = [];
            this.renderPendingRequests();
            return;
        }
        
        if (data.success) {
            this.pendingRequests = data.pendingRequests || [];
            this.renderPendingRequests();
        } else {
            console.error('Server returned error:', data.error);
            this.showNotification(data.error || 'Error loading pending requests', 'error');
        }
    } catch (error) {
        console.error('Error loading pending requests:', error);
        this.showNotification('Network error. Unable to load pending requests.', 'error');
    }
}
    
    /**
     * Render transaction history
     */
    renderTransactionHistory() {
        const container = document.getElementById('transaction-history');
        if (!container) return;
        
        if (!this.transactionHistory || this.transactionHistory.length === 0) {
            container.innerHTML = '<p class="text-center text-muted">No transaction history</p>';
            return;
        }
        
        let html = '<div class="list-group">';
        
        this.transactionHistory.forEach(transaction => {
            const date = new Date(transaction.timestamp * 1000).toLocaleString();
            const isIncoming = transaction.direction === 'incoming';
            const amountClass = isIncoming ? 'text-success' : 'text-danger';
            const amountPrefix = isIncoming ? '+' : '-';
            const amountDisplay = `${amountPrefix}${transaction.amount.toFixed(2)}`;
            const typeDisplay = this.getTransactionTypeDisplay(transaction.type);
            const statusClass = this.getStatusClass(transaction.status);
            
            html += `
                <div class="list-group-item">
                    <div class="d-flex w-100 justify-content-between">
                        <h6 class="mb-1">${typeDisplay}</h6>
                        <small>${date}</small>
                    </div>
                    <p class="mb-1">${transaction.memo || 'No memo'}</p>
                    <div class="d-flex justify-content-between">
                        <small class="text-muted">
                            ${transaction.otherParty || 'System'} · 
                            <span class="badge ${statusClass}">${transaction.status}</span>
                        </small>
                        <strong class="${amountClass}">${amountDisplay}</strong>
                    </div>
                </div>
            `;
        });
        
        html += '</div>';
        container.innerHTML = html;
    }
    
    /**
     * Render pending payment requests
     */
    renderPendingRequests() {
        const container = document.getElementById('pending-requests');
        if (!container) return;
        
        if (!this.pendingRequests || this.pendingRequests.length === 0) {
            container.innerHTML = '<p class="text-center text-muted">No pending requests</p>';
            return;
        }
        
        let html = '<div class="list-group">';
        
        this.pendingRequests.forEach(request => {
            const date = new Date(request.timestamp * 1000).toLocaleString();
            const expiryDate = new Date(request.expiresAt * 1000).toLocaleString();
            const isIncoming = request.direction === 'incoming';
            const actionButtons = isIncoming ? `
                <div class="mt-2">
                    <button class="btn btn-sm btn-success me-2" onclick="transactionInterface.respondToRequest('${request.id}', 'accept')">
                        Accept
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="transactionInterface.respondToRequest('${request.id}', 'decline')">
                        Decline
                    </button>
                </div>
            ` : '';
            
            html += `
                <div class="list-group-item">
                    <div class="d-flex w-100 justify-content-between">
                        <h6 class="mb-1">${isIncoming ? 'Payment Request' : 'Request Sent'}</h6>
                        <small>${date}</small>
                    </div>
                    <p class="mb-1">${request.memo || 'No memo'}</p>
                    <div class="d-flex justify-content-between">
                        <small class="text-muted">
                            ${request.otherParty || 'Unknown'} · 
                            Expires: ${expiryDate}
                        </small>
                        <strong>${request.amount.toFixed(2)}</strong>
                    </div>
                    ${actionButtons}
                </div>
            `;
        });
        
        html += '</div>';
        container.innerHTML = html;
    }
    
    /**
     * Respond to a payment request (accept or decline)
     */
    async respondToRequest(requestId, action) {
        if (!this.creditCardNumber) {
            this.showNotification('Please enter your credit card number first', 'error');
            return;
        }
        
        try {
            const formData = new FormData();
            formData.append('requestId', requestId);
            formData.append('payerCard', this.creditCardNumber);
            formData.append('action', action);
            
            const response = await fetch('transaction-api.php?action=respond-to-request', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                const actionText = action === 'accept' ? 'accepted' : 'declined';
                this.showNotification(`Payment request ${actionText} successfully!`, 'success');
                
                // Refresh transaction history and pending requests
                await this.loadTransactionHistory();
                await this.loadPendingRequests();
                
                // Update blockchain balance if available
                if (window.blockchainInterface) {
                    window.blockchainInterface.fetchCardBalance(this.creditCardNumber);
                }
            } else {
                this.showNotification('Error: ' + data.error, 'error');
            }
        } catch (error) {
            console.error('Error responding to request:', error);
            this.showNotification('Error responding to request', 'error');
        }
    }
    
    /**
     * Get display text for transaction type
     */
    getTransactionTypeDisplay(type) {
        switch (type) {
            case 'transaction':
                return 'Payment';
            case 'payment_sent':
                return 'Payment Sent';
            case 'payment_received':
                return 'Payment Received';
            case 'payment_request':
                return 'Payment Request';
            case 'reward':
                return 'Mining Reward';
            default:
                return 'Transaction';
        }
    }
    
    /**
     * Get CSS class for transaction status
     */
    getStatusClass(status) {
        switch (status) {
            case 'completed':
                return 'bg-success';
            case 'pending':
                return 'bg-warning';
            case 'declined':
                return 'bg-danger';
            default:
                return 'bg-secondary';
        }
    }
    
    /**
     * Show a notification message
     */
    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `alert alert-${type} alert-dismissible fade show`;
        notification.innerHTML = `
            ${message}
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
     * Show a modal dialog
     */
    showModal(modal) {
        if (!modal) return;
        
        // Add Bootstrap modal classes
        modal.classList.add('show');
        modal.style.display = 'block';
        document.body.classList.add('modal-open');
        
        // Add backdrop
        const backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop fade show';
        document.body.appendChild(backdrop);
    }
    
    /**
     * Hide a modal dialog
     */
    hideModal(modal) {
        if (!modal) return;
        
        // Remove Bootstrap modal classes
        modal.classList.remove('show');
        modal.style.display = 'none';
        document.body.classList.remove('modal-open');
        
        // Remove backdrop
        const backdrop = document.querySelector('.modal-backdrop');
        if (backdrop) {
            backdrop.remove();
        }
    }
}

// Initialize the transaction interface
document.addEventListener('DOMContentLoaded', () => {
    window.transactionInterface = new TransactionInterface();
});