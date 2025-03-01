class BlockTemplate {
    constructor(blockchain) {
        if (!blockchain || !blockchain.blocks || blockchain.blocks.length === 0) {
            throw new Error('Invalid blockchain state');
        }

        const lastBlock = blockchain.blocks[blockchain.blocks.length - 1];
        
        return {
            index: lastBlock.index + 1,
            timestamp: Date.now(),
            previousHash: lastBlock.hash,
            data: {
                transactions: blockchain.pendingTransactions || [],
                minerMetadata: {
                    timestamp: Date.now(),
                    userAgent: navigator.userAgent,
                    ipAddress: null, // Would be populated server-side
                }
            },
            difficulty: this.calculateDynamicDifficulty(blockchain),
            nonce: 0
        };
    }

    calculateDynamicDifficulty(blockchain) {
        // Adaptive difficulty algorithm
        const blockCount = blockchain.blocks.length;
        const BASE_DIFFICULTY = 4;
        const DIFFICULTY_ADJUSTMENT_INTERVAL = 10;
        
        if (blockCount % DIFFICULTY_ADJUSTMENT_INTERVAL === 0) {
            // Adjust difficulty based on recent block creation times
            const recentBlocks = blockchain.blocks.slice(-DIFFICULTY_ADJUSTMENT_INTERVAL);
            const averageBlockTime = this.calculateAverageBlockTime(recentBlocks);
            
            // Target block time: 10 minutes
            const TARGET_BLOCK_TIME = 600000; // milliseconds
            
            if (averageBlockTime < TARGET_BLOCK_TIME * 0.5) {
                return BASE_DIFFICULTY + 1;
            } else if (averageBlockTime > TARGET_BLOCK_TIME * 1.5) {
                return Math.max(BASE_DIFFICULTY - 1, 1);
            }
        }
        
        return BASE_DIFFICULTY;
    }

    calculateAverageBlockTime(blocks) {
        const timeDifferences = blocks.slice(1).map((block, index) => 
            block.timestamp - blocks[index].timestamp
        );
        
        return timeDifferences.reduce((a, b) => a + b, 0) / timeDifferences.length;
    }
}