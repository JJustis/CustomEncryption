class BlockchainMiningInterface {
    constructor(blockchain) {
        this.blockchain = blockchain;
        this.miningWorker = null;
    }
    
    startMining() {
        // Create block template
        const blockTemplate = new BlockTemplate(this.blockchain);
        
        // Initialize web worker
        this.miningWorker = new Worker('miningWorker.js');
        
        this.miningWorker.onmessage = async (event) => {
            switch (event.data.type) {
                case 'success':
                    await this.submitProofOfWork(event.data);
                    break;
                case 'progress':
                    this.updateMiningUI(event.data);
                    break;
                case 'error':
                    this.handleMiningError(event.data);
                    break;
            }
        };
        
        // Start mining
        this.miningWorker.postMessage({
            block: blockTemplate,
            difficulty: blockTemplate.difficulty
        });
    }
    
    async submitProofOfWork(miningResult) {
        try {
            const response = await fetch('api.php?action=submit-proof-of-work', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    block: miningResult.block,
                    hash: miningResult.hash,
                    nonce: miningResult.nonce,
                    minerMetadata: {
                        hashRate: miningResult.hashRate,
                        hashesComputed: miningResult.hashesComputed
                    }
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.updateBlockchain(result.newBlock);
                this.showSuccessNotification(result.newBlock);
            } else {
                throw new Error(result.error);
            }
        } catch (error) {
            this.handleMiningError({ message: error.toString() });
        }
    }
}