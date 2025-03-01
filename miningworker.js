// In miningWorker.js
class ProofOfWorkMiner {
    async calculateHash(block, nonce) {
        // Standardize hash calculation to match server-side method
        const blockData = JSON.stringify(block.data);
        const rawData = 
            block.index.toString() + 
            block.previousHash + 
            block.timestamp.toString() + 
            blockData + 
            nonce.toString();
        
        // Use Web Crypto API for consistent hashing
        const hashBuffer = await crypto.subtle.digest('SHA-256', new TextEncoder().encode(rawData));
        const hashArray = Array.from(new Uint8Array(hashBuffer));
        const hashHex = hashArray
            .map(b => b.toString(16).padStart(2, '0'))
            .join('');
        
        return hashHex;
    }

    async mine(block, difficulty) {
        const prefix = '0'.repeat(difficulty);
        let nonce = 0;

        while (true) {
            const hash = await this.calculateHash(block, nonce);
            
            if (hash.startsWith(prefix)) {
                return { hash, nonce };
            }

            nonce++;
        }
    }
}

// Worker message handler
self.onmessage = async (event) => {
    const { block, difficulty } = event.data;
    const miner = new ProofOfWorkMiner();
    
    try {
        const result = await miner.mine(block, difficulty);
        self.postMessage({ 
            type: 'success', 
            hash: result.hash, 
            nonce: result.nonce 
        });
    } catch (error) {
        self.postMessage({ 
            type: 'error', 
            message: error.toString() 
        });
    }
};