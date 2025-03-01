<?php
// Include required files
require_once 'BlockchainRewards.php';
require_once 'TransactionSystem.php';

// Initialize systems
$transactionSystem = new TransactionSystem();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #3a0ca3;
            --secondary-color: #4361ee;
            --accent-color: #7209b7;
            --light-color: #f8f9fa;
            --dark-color: #212529;
        }
        
        body {
            background-color: #f0f2f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .transaction-title {
            color: var(--primary-color);
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        
        .custom-card {
            border-radius: 12px;
            box-shadow: 0 8px 16px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border: none;
            overflow: hidden;
            margin-bottom: 20px;
        }
        
        .custom-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 20px rgba(0,0,0,0.12);
        }
        
        .card-header {
            background-color: white;
border-bottom: 1px solid rgba(0,0,0,0.08);
            padding: 1rem 1.5rem;
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        .nav-tabs .nav-link {
            color: var(--dark-color);
            border: none;
            padding: 0.75rem 1.25rem;
            font-weight: 500;
        }
        
        .nav-tabs .nav-link.active {
            color: var(--primary-color);
            border-bottom: 3px solid var(--primary-color);
            background-color: transparent;
        }
        
        .transaction-content {
            display: none;
        }
        
        .transaction-content.active {
            display: block;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
        }
        
        /* Credit Card Display */
        .credit-card {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            position: relative;
            overflow: hidden;
        }
        
        .credit-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxMDAgMTAwIj48Y2lyY2xlIGN4PSI1MCIgY3k9IjUwIiByPSI0MCIgZmlsbD0ibm9uZSIgc3Ryb2tlPSJyZ2JhKDI1NSwyNTUsMjU1LDAuMSkiIHN0cm9rZS13aWR0aD0iMTAiLz48L3N2Zz4=');
            background-size: 150px;
            opacity: 0.1;
        }
        
        .card-chip {
            width: 40px;
            height: 30px;
            background: linear-gradient(135deg, #fa7, #ffd700);
            border-radius: 5px;
            margin-bottom: 20px;
        }
        
        .card-number {
            font-size: 1.5rem;
            font-family: 'Courier New', monospace;
            letter-spacing: 2px;
            margin-bottom: 20px;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
        }
        
        .card-details {
            display: flex;
            justify-content: space-between;
        }
        
        .card-holder, .card-expiry {
            font-size: 0.8rem;
            opacity: 0.8;
        }
        
        .card-holder span, .card-expiry span {
            display: block;
            font-size: 0.9rem;
            opacity: 1;
            font-weight: bold;
            margin-top: 5px;
        }
        
        /* QR Code */
        .qr-code-placeholder {
            background-color: white;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin: 20px auto;
            max-width: 200px;
            min-height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: monospace;
            font-size: 0.7rem;
            overflow: auto;
        }
        
        /* Notification area */
        #notification-area {
            position: fixed;
            top: 20px;
            right: 20px;
            width: 350px;
            z-index: 9999;
        }
    </style>
</head>
<body>
    <div id="notification-area"></div>
    
    <div class="container my-5">
        <h1 class="mb-4 text-center transaction-title">
            <i class="fas fa-exchange-alt me-2"></i> Transactions & Payments
        </h1>
        
        <div class="row">
            <div class="col-md-4">
                <!-- Credit Card Display -->
                <div class="credit-card">
                    <div class="card-chip"></div>
                    <div class="card-number card-number-display">**** **** **** ****</div>
                    <div class="card-details">
                        <div class="card-holder">
                            CARD HOLDER
                            <span>REWARDS USER</span>
                        </div>
                        <div class="card-expiry">
                            VALID THRU
                            <span>FOREVER</span>
                        </div>
                    </div>
                </div>
                
                <!-- Send Payment Form -->
                <div class="custom-card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-paper-plane me-2"></i>Send Payment</h5>
                    </div>
                    <div class="card-body">
                        <form id="send-payment-form">
                            <div class="mb-3">
                                <label for="recipient-card" class="form-label">Recipient Card Number</label>
                                <input type="text" class="form-control" id="recipient-card" placeholder="Enter recipient's card number" required>
                            </div>
                            <div class="mb-3">
                                <label for="payment-amount" class="form-label">Amount</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-coins"></i></span>
                                    <input type="number" class="form-control" id="payment-amount" placeholder="0.00" step="0.01" min="0.01" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="payment-memo" class="form-label">Memo (Optional)</label>
                                <textarea class="form-control" id="payment-memo" rows="2" placeholder="Add a note"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-paper-plane me-2"></i>Send Payment
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- Request Payment Form -->
                <div class="custom-card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-hand-holding-usd me-2"></i>Request Payment</h5>
                    </div>
                    <div class="card-body">
                        <form id="request-payment-form">
                            <div class="mb-3">
                                <label for="payer-card" class="form-label">Payer Card Number</label>
                                <input type="text" class="form-control" id="payer-card" placeholder="Enter payer's card number" required>
                            </div>
                            <div class="mb-3">
                                <label for="request-amount" class="form-label">Amount</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-coins"></i></span>
                                    <input type="number" class="form-control" id="request-amount" placeholder="0.00" step="0.01" min="0.01" required>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="request-memo" class="form-label">Memo (Optional)</label>
                                <textarea class="form-control" id="request-memo" rows="2" placeholder="Add a reason for request"></textarea>
                            </div>
                            <button type="submit" class="btn btn-outline-primary w-100">
                                <i class="fas fa-hand-holding-usd me-2"></i>Request Payment
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- QR Payment -->
                <div class="custom-card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-qrcode me-2"></i>QR Payments</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="qr-amount" class="form-label">Amount to Request</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-coins"></i></span>
                                <input type="number" class="form-control" id="qr-amount" placeholder="0.00" step="0.01" min="0.01">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="qr-memo" class="form-label">Memo (Optional)</label>
                            <input type="text" class="form-control" id="qr-memo" placeholder="Add a note">
                        </div>
                        <div class="d-flex justify-content-between">
                            <button id="generate-qr-button" class="btn btn-primary flex-fill me-2">
                                <i class="fas fa-qrcode me-1"></i> Generate Payment QR
                            </button>
                            <button id="scan-qr-button" class="btn btn-outline-primary flex-fill">
                                <i class="fas fa-camera me-1"></i> Scan QR Code
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <!-- Transaction History -->
                <div class="custom-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <ul class="nav nav-tabs card-header-tabs">
                            <li class="nav-item">
                                <a class="nav-link transaction-tab active" data-tab-type="history" href="#">
                                    <i class="fas fa-history me-1"></i> Transaction History
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link transaction-tab" data-tab-type="pending" href="#">
                                    <i class="fas fa-clock me-1"></i> Pending Requests
                                </a>
                            </li>
                        </ul>
                        <button id="refresh-transactions" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="transaction-content active" data-tab-type="history">
                            <div id="transaction-history">
                                <p class="text-center text-muted">No transaction history</p>
                            </div>
                        </div>
                        <div class="transaction-content" data-tab-type="pending">
                            <div id="pending-requests">
                                <p class="text-center text-muted">No pending requests</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Transaction Information -->
                <div class="custom-card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>About Transactions</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <h6 class="mb-3">Payment System</h6>
                                    <p>Our transaction system allows you to:</p>
                                    <ul class="small">
                                        <li>Send payments to other users using their card number</li>
                                        <li>Request payments from others</li>
                                        <li>Generate QR codes for easy payment reception</li>
                                        <li>Scan QR codes to send payments quickly</li>
                                    </ul>
                                </div>
                                <div>
                                    <h6 class="mb-3">Transaction Security</h6>
                                    <p>All transactions are secured using:</p>
                                    <ul class="small">
                                        <li>Blockchain verification technology</li>
                                        <li>Transaction hashing and validation</li>
                                        <li>Proof-of-work consensus mechanism</li>
                                        <li>Immutable transaction ledger</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <h6 class="mb-3">Earning Rewards</h6>
                                    <p>Increase your reserves through:</p>
                                    <ul class="small">
                                        <li>Decrypt messages to earn 0.1 reserves each time</li>
                                        <li>Mine blocks to validate transactions</li>
                                        <li>Receive payments from other users</li>
                                        <li>Participate in the blockchain network</li>
                                    </ul>
                                </div>
                                <div>
                                    <h6 class="mb-3">Blockchain Integration</h6>
                                    <p>Every transaction is:</p>
                                    <ul class="small">
                                        <li>Added to the pending transaction pool</li>
                                        <li>Verified by miners through proof-of-work</li>
                                        <li>Included in blocks on the blockchain</li>
                                        <li>Permanently recorded and immutable</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- QR Code Modal -->
    <div class="modal fade" id="qr-code-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Payment QR Code</h5>
                    <button type="button" class="btn-close close-modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <div id="qr-code-display" class="mb-3">
                        <div class="qr-code-placeholder">
                            <span>QR Code will display here</span>
                        </div>
                    </div>
                    <div class="mb-3">
                        <span class="text-muted">Amount:</span>
                        <span id="qr-amount-display" class="fw-bold">0.00</span>
                    </div>
                    <div class="mb-3">
                        <span class="text-muted">Memo:</span>
                        <span id="qr-memo-display">No memo</span>
                    </div>
                    <p class="small text-muted">Show this QR code to the sender to receive payment</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary close-modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Scan QR Modal -->
    <div class="modal fade" id="scan-qr-modal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Scan QR Code</h5>
                    <button type="button" class="btn-close close-modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="qr-scanner-container" class="mb-3 text-center">
                        <p>In a real implementation, a camera interface would be here</p>
                        <p>For this demo, please paste the QR code data below:</p>
                    </div>
                    <div class="mb-3">
                        <label for="qr-data-input" class="form-label">QR Code Data</label>
                        <textarea class="form-control" id="qr-data-input" rows="5" placeholder="Paste QR code data here"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary close-modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="transactionInterface.processScannedQR()">Process Payment</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- JavaScript Resources -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="transactions.js"></script>
</body>
</html>