<?php
// Include required files
require_once 'BlockchainRewards.php';

// Get blockchain data for initial rendering
$blockchainRewards = new BlockchainRewards();
$walletInfo = $blockchainRewards->getWalletInfo();
$miningRequirements = $blockchainRewards->getMiningRequirements();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blockchain Rewards System</title>
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
        
        .blockchain-title {
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
        
        /* Blockchain Visualization */
        .blockchain-container {
            position: relative;
            overflow: hidden;
            padding: 20px;
        }
        
        .blockchain-timeline {
            position: relative;
            padding-left: 30px;
        }
        
        .blockchain-timeline::before {
            content: '';
            position: absolute;
            left: 10px;
            top: 0;
            bottom: 0;
            width: 4px;
            background: linear-gradient(to bottom, var(--primary-color), var(--secondary-color));
            border-radius: 2px;
        }
        
        .block-item {
            position: relative;
            margin-bottom: 30px;
            animation: fadeIn 0.5s ease-out forwards;
            opacity: 0;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .block-icon {
            position: absolute;
            left: -40px;
            top: 0;
            width: 24px;
            height: 24px;
            background-color: var(--accent-color);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 12px;
            box-shadow: 0 0 10px rgba(114, 9, 183, 0.5);
        }
        
        .block-content {
            background: white;
            border-radius: 10px;
            padding: 15px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.05);
        }
        
        .block-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
            margin-bottom: 10px;
        }
        
        .block-hash {
            font-family: 'Consolas', monospace;
            font-size: 0.85rem;
            color: var(--secondary-color);
            word-break: break-all;
        }
        
        .transaction-list {
            margin-top: 8px;
        }
        
        .transaction-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 5px 0;
            font-size: 0.85rem;
            border-bottom: 1px dashed #eee;
        }
        
        .transaction-card {
            font-family: 'Consolas', monospace;
            margin: 0 10px;
        }
        
        /* Mining Section */
        .mining-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .stat-label {
            font-size: 0.85rem;
            color: #6c757d;
        }
        
        /* Wallet Section */
        .wallet-info {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .wallet-balance {
            font-size: 2rem;
            font-weight: bold;
            margin-top: 10px;
        }
        
        .wallet-address {
            font-family: 'Consolas', monospace;
            background-color: rgba(255, 255, 255, 0.2);
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 0.85rem;
            word-break: break-all;
            margin-top: 15px;
        }
        
        /* Loading animation */
        .loader {
            border: 4px solid #f3f3f3;
            border-top: 4px solid var(--primary-color);
            border-radius: 50%;
            width: 30px;
            height: 30px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
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
        <h1 class="mb-4 text-center blockchain-title">
            <i class="fas fa-link me-2"></i> Blockchain Rewards System
        </h1>
        
        <div class="row">
            <div class="col-md-4">
                <!-- Master Wallet Card -->
                <div class="custom-card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-wallet me-2"></i>Master Wallet</h5>
                    </div>
                    <div class="card-body">
                        <div class="wallet-info">
                            <div class="text-uppercase small">Current Balance</div>
                            <div class="wallet-balance">
                                <i class="fas fa-coins me-2"></i>
                                <span id="wallet-balance"><?= number_format($walletInfo['balance'], 2) ?></span>
                            </div>
                            <div class="text-uppercase small mt-3">Wallet Address</div>
                            <div class="wallet-address" id="wallet-address">
                                <?= $walletInfo['address'] ?>
                            </div>
                        </div>
                        
                        <h6 class="mt-3 mb-3">Blockchain Statistics</h6>
                        <div class="list-group">
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                Total Transactions
                                <span class="badge bg-primary rounded-pill" id="total-transactions">
                                    <?= $walletInfo['statistics']['totalTransactions'] ?>
                                </span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                Rewards Distributed
                                <span class="badge bg-success rounded-pill" id="total-rewards">
                                    <?= number_format($walletInfo['statistics']['totalRewardsDistributed'], 2) ?>
                                </span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                Messages Decrypted
                                <span class="badge bg-info rounded-pill" id="total-messages">
                                    <?= $walletInfo['statistics']['totalMessages'] ?>
                                </span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                Avg Hash Rate
                                <span class="badge bg-secondary rounded-pill" id="avg-hash-rate">
                                    <?= number_format($walletInfo['statistics']['averageHashRate']) ?> H/s
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Mining Dashboard -->
                <div class="custom-card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-hammer me-2"></i>Mining Dashboard</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Mining uses your device's processing power to verify transactions and earn rewards.
                        </div>
                        
                        <div class="mining-stats">
                            <div class="stat-card">
                                <div class="stat-value" id="mining-difficulty">
                                    <?= $miningRequirements['difficulty'] ?>
                                </div>
                                <div class="stat-label">Difficulty</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-value" id="reward-rate">
                                    <?= $miningRequirements['rewardRate'] ?>
                                </div>
                                <div class="stat-label">Reward Rate</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-value" id="hashes-computed">0</div>
                                <div class="stat-label">Hashes Computed</div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-value" id="hash-rate">0 H/s</div>
                                <div class="stat-label">Hash Rate</div>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-between mb-3">
                            <button id="start-mining" class="btn btn-primary">
                                <i class="fas fa-play me-2"></i>Start Mining
                            </button>
                            <button id="stop-mining" class="btn btn-secondary" disabled>
                                <i class="fas fa-stop me-2"></i>Stop Mining
                            </button>
                        </div>
                        
                        <div class="progress mb-2">
                            <div id="mining-progress" class="progress-bar progress-bar-striped progress-bar-animated" 
                                 role="progressbar" style="width: 0%"></div>
                        </div>
                        
                        <div id="mining-status" class="text-center text-muted small">
                            Mining inactive
                        </div>
                        
                        <div class="text-center mt-3">
                            <span class="text-muted small">Current Nonce: </span>
                            <span id="current-nonce" class="small fw-bold">0</span>
                        </div>
                    </div>
                </div>
                
                <!-- Credit Card Balance -->
                <div class="custom-card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-credit-card me-2"></i>Credit Card Balance</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="card-number" class="form-label">Credit Card Number</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="card-number" 
                                       placeholder="Enter your card number">
                                <button class="btn btn-outline-primary" type="button" id="check-balance">
                                    Check Balance
                                </button>
                            </div>
                        </div>
                        
                        <div class="wallet-info">
                            <div class="text-uppercase small">Available Rewards</div>
                            <div class="wallet-balance">
                                <i class="fas fa-coins me-2"></i>
                                <span id="card-balance">0.00</span>
                            </div>
                        </div>
                        
                        <h6 class="mt-3 mb-2">Transaction History</h6>
                        <div id="transaction-history">
                            <p class="text-center text-muted">No transactions found</p>
                        </div>
                    </div>
                </div>
            </div>
            
<!-- Blockchain Viewer -->
                <div class="custom-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-cube me-2"></i>Blockchain Explorer</h5>
                        <button class="btn btn-sm btn-outline-primary" id="refresh-blockchain">
                            <i class="fas fa-sync-alt me-1"></i>Refresh
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-primary">
                            <i class="fas fa-info-circle me-2"></i>
                            The blockchain contains all transactions and rewards from message decryptions.
                            Each block is secured with proof-of-work mining.
                        </div>
                        
                        <div class="blockchain-container">
                            <div id="blockchain-placeholder" class="text-center py-4">
                                <div class="loader"></div>
                                <p class="mt-3 text-muted">Loading blockchain data...</p>
                            </div>
                            <div id="blockchain-container" class="blockchain-timeline">
                                <!-- Blockchain blocks will be displayed here via JavaScript -->
                            </div>
                            <div id="empty-blockchain" class="text-center py-5" style="display: none;">
                                <i class="fas fa-cube text-muted" style="font-size: 3rem;"></i>
                                <p class="mt-3 text-muted">No blocks in the blockchain yet.</p>
                                <p class="text-muted">Decrypt a message or mine a block to get started!</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- System Information -->
                <div class="custom-card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>System Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <h6 class="mb-3">Reward System Overview</h6>
                                    <p>The blockchain rewards system distributes reserves to credit card holders:</p>
                                    <ul class="small">
                                        <li>Earn 0.1 reserves for each message you decrypt</li>
                                        <li>Mine blocks to verify transactions</li>
                                        <li>All transactions are recorded in the blockchain</li>
                                        <li>Reserves are tied to your credit card number</li>
                                    </ul>
                                </div>
                                <div>
                                    <h6 class="mb-3">Mining Explanation</h6>
                                    <p>Mining secures the blockchain and processes transactions:</p>
                                    <ul class="small">
                                        <li>Uses proof-of-work to validate blocks</li>
                                        <li>Difficulty determines required computing power</li>
                                        <li>Mining happens in your browser with JavaScript</li>
                                        <li>Can be stopped and started at any time</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <h6 class="mb-3">How Rewards Work</h6>
                                    <p>Decrypting messages earns you valuable reserves:</p>
                                    <ul class="small">
                                        <li>Each successful decryption triggers a reward</li>
                                        <li>Rewards are automatically credited to your card</li>
                                        <li>View your balance and transaction history any time</li>
                                        <li>All rewards come from the master blockchain wallet</li>
                                    </ul>
                                </div>
                                <div>
                                    <h6 class="mb-3">Technical Details</h6>
                                    <p>The system uses advanced cryptographic concepts:</p>
                                    <ul class="small">
                                        <li>SHA-256 hashing for blockchain security</li>
                                        <li>Distributed verification through mining</li>
                                        <li>Transaction verification via proof-of-work</li>
                                        <li>Temporal obscurity for time-locked rewards</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- JavaScript Resources -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="miningWorker.js"></script>
    <script src="blockchain.js"></script>
    <script>
        // Additional page initialization if needed
        document.addEventListener('DOMContentLoaded', function() {
            // Example of dispatching a credit card generated event
            document.getElementById('check-balance').addEventListener('click', function() {
                const cardNumber = document.getElementById('card-number').value;
                if (cardNumber) {
                    // Update the blockchain interface with this card number
                    if (window.blockchainInterface) {
                        window.blockchainInterface.creditCardNumber = cardNumber;
                        window.blockchainInterface.fetchCardBalance(cardNumber);
                    }
                }
            });
            
            // Setup refresh blockchain button
            document.getElementById('refresh-blockchain').addEventListener('click', function() {
                if (window.blockchainInterface) {
                    window.blockchainInterface.fetchBlockchain();
                }
            });
            
            // Mining progress bar update
            let progressUpdater = setInterval(function() {
                if (window.blockchainInterface && window.blockchainInterface.isMining) {
                    // Simulate progress based on nonce
                    const nonce = document.getElementById('current-nonce').textContent;
                    const progress = (parseInt(nonce) % 100);
                    document.getElementById('mining-progress').style.width = progress + '%';
                } else {
                    document.getElementById('mining-progress').style.width = '0%';
                }
            }, 1000);
        });

    </script>
</body>
</html>