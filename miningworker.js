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
        let startTime = Date.now();
        
        while (true) {
            try {
                const hash = await this.calculateHash(block, nonce);
                
                // Send regular progress updates
                if (nonce % 10000 === 0) {
                    const timeElapsed = (Date.now() - startTime) / 1000;
                    const hashRate = Math.floor(nonce / (timeElapsed || 1));
                    
                    self.postMessage({
                        type: 'stats',
                        hashesComputed: nonce,
                        hashRate: hashRate,
                        nonce: nonce
                    });
                }
                
                if (hash.startsWith(prefix)) {
                    // Save the nonce back to the block for verification
                    block.nonce = nonce;
                    return { hash, nonce, block };
                }
                
                nonce++;
            } catch (error) {
                console.error("Mining calculation error:", error);
                throw error;
            }
        }
    }
}
// Inside the mining worker
function mineBlock(block, difficulty) {
    const prefix = '0'.repeat(difficulty);
    let nonce = 0;
    let hash = '';
    const startTime = Date.now();

    while (!hash.startsWith(prefix)) {
        // Construct the raw data for hash calculation
        const dataString = JSON.stringify(block.data);
        const rawData = 
            block.index + 
            block.previousHash + 
            block.timestamp + 
            dataString + 
            nonce;

        // Calculate hash
        hash = calculateHash(rawData);
        nonce++;

        // Periodic stats reporting
        if (nonce % 10000 === 0) {
            postMessage({
                type: 'stats', 
                hashesComputed: nonce, 
                hashRate: Math.round(nonce / ((Date.now() - startTime) / 1000)),
                nonce: nonce
            });
        }

        // Prevent infinite loop
        if (nonce > 1000000) {
            postMessage({
                type: 'error', 
                message: 'Max nonce reached without finding valid hash'
            });
            return;
        }
    }

    const timeElapsed = (Date.now() - startTime) / 1000;
    const hashRate = Math.round(nonce / timeElapsed);

    postMessage({
        type: 'success',
        hash: hash,
        nonce: nonce - 1,
        block: block,
        hashesComputed: nonce,
        hashRate: hashRate,
        timeElapsed: timeElapsed
    });
}

// Hash calculation function
function calculateHash(input) {
    // Implement SHA-256 hash calculation
    // This should match the server-side hash calculation exactly
    const crypto = self.crypto || self.msCrypto;
    const buffer = new TextEncoder().encode(input);
    return crypto.subtle.digest('SHA-256', buffer)
        .then(hash => {
            return Array.from(new Uint8Array(hash))
                .map(b => b.toString(16).padStart(2, '0'))
                .join('');
        });
}
// Worker message handler
// In miningWorker.js - onmessage function
self.onmessage = async (event) => {
    console.log("Worker received message:", event.data);
    
    try {
        // Get command type and extract data
        let block, difficulty;
        
        // Support both message formats
        if (event.data.command === 'start-mining') {
            block = event.data.block;
            difficulty = event.data.difficulty;
        } else {
            block = event.data.block;
            difficulty = event.data.difficulty;
        }
        
        // Validate required fields
        if (!block) {
            throw new Error("Missing block data in mining request");
        }
        
        if (!difficulty) {
            difficulty = 4; // Default difficulty
        }
        
        // Log what we're mining
        console.log("Mining block:", block, "with difficulty:", difficulty);
        
        // Define startTime here - THIS IS THE FIX
        const startTime = Date.now();
        
        const miner = new ProofOfWorkMiner();
        const result = await miner.mine(block, difficulty);
        
        // Use the defined startTime variable here
        self.postMessage({ 
            type: 'success', 
            hash: result.hash, 
            nonce: result.nonce,
            block: result.block,
            hashesComputed: result.block.nonce,
            hashRate: Math.floor(result.nonce / ((Date.now() - startTime) / 1000)),
            timeElapsed: (Date.now() - startTime) / 1000
        });
    } catch (error) {
        console.error("Mining worker error:", error);
        self.postMessage({ 
            type: 'error', 
            message: error.toString() 
        });
    }
};