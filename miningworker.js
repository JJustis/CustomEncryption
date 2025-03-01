// Mining Worker for blockchain proof-of-work
// Simple SHA-256 implementation for the worker
// Replace the current sha256 function in miningWorker.js
function sha256(message) {
  // Simple deterministic hash function for demonstration
  // In a production environment, use a real cryptographic library
  let hash = 0;
  for (let i = 0; i < message.length; i++) {
    const char = message.charCodeAt(i);
    hash = ((hash << 5) - hash) + char;
    hash = hash & hash; // Convert to 32bit integer
  }
  
  // Generate a hex string that resembles a SHA-256 hash (64 characters)
  let hexHash = '';
  // Use the generated hash number to create a 64-character hex string
  for (let i = 0; i < 16; i++) {
    // For each iteration, take a portion of the hash and convert to hex
    const portion = (hash & (0xFF << (i * 8))) >>> (i * 8);
    hexHash += portion.toString(16).padStart(4, '0');
  }
  
  return hexHash.padStart(64, '0');
}

// Then modify calculateHash to work with async
async function calculateHash(block) {
  const dataString = JSON.stringify(block.data);
  return await sha256(block.index + block.previousHash + block.timestamp + dataString + block.nonce);
}

// Variables to track mining progress
let mining = false;
let hashesComputed = 0;
let startTime = 0;
let hashRate = 0;
let targetPrefix = '';

// Process messages from the main thread
self.onmessage = function(e) {
  const message = e.data;
  
  switch (message.command) {
    case 'start-mining':
      startMining(message.block, message.difficulty);
      break;
      
    case 'stop-mining':
      stopMining();
      break;
      
case 'get-stats':
  self.postMessage({
    type: 'stats',
    hashesComputed: hashesComputed,
    hashRate: hashRate,
    mining: mining,
    nonce: block ? block.nonce : 0 // Ensure nonce is always included
  });
  break;
  }
};

/**
 * Start the mining process
 */
function startMining(block, difficulty) {
  // Set target prefix (required number of leading zeros)
  targetPrefix = '0'.repeat(difficulty);
  
  // Store reference to the current block
  currentBlock = block;
  
  // Initialize mining variables
  mining = true;
  hashesComputed = 0;
  startTime = Date.now();
  
  // Start the mining loop
  self.postMessage({ type: 'status', status: 'Mining started...' });
  mineBlock(block);
}

/**
 * Stop the mining process
 */
function stopMining() {
  mining = false;
  self.postMessage({ type: 'status', status: 'Mining stopped' });
}

/**
 * Calculate hash of a block
 */


/**
 * Mine a block with increasing nonce until finding a valid hash
 */
function mineBlock(block) {
  // Continue mining until stopped or valid hash found
  while (mining) {
    // Calculate hash with current nonce
    const hash = calculateHash(block);
    
    // Increment number of hashes computed
    hashesComputed++;
    
    // Calculate hash rate every 1000 hashes
    if (hashesComputed % 1000 === 0) {
      const currentTime = Date.now();
      const elapsedTime = (currentTime - startTime) / 1000; // seconds
      hashRate = Math.round(hashesComputed / elapsedTime);
      
      // Report progress to main thread
      self.postMessage({
        type: 'progress',
        hashesComputed: hashesComputed,
        hashRate: hashRate,
        nonce: block.nonce
      });
    }
    
    // Check if hash meets the difficulty target
    if (hash.startsWith(targetPrefix)) {
      // Found a valid hash!
      const result = {
        type: 'success',
        block: block,
        hash: hash,
        nonce: block.nonce,
        hashesComputed: hashesComputed,
        hashRate: hashRate,
        timeElapsed: (Date.now() - startTime) / 1000
      };
      
      self.postMessage(result);
      mining = false;
      return;
    }
    
    // Increment nonce and continue
    block.nonce++;
    
    // Prevent UI blocking by yielding to other processes occasionally
    if (block.nonce % 5000 === 0) {
      setTimeout(() => {
        if (mining) {
          mineBlock(block);
        }
      }, 0);
      return;
    }
  }
}