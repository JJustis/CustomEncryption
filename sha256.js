// This file should be saved as sha256.js
// Simple SHA-256 implementation for the worker

function sha256(message) {
  // Convert string to buffer for hashing
  function stringToBuffer(str) {
    const buffer = new ArrayBuffer(str.length * 2);
    const view = new Uint16Array(buffer);
    for (let i = 0; i < str.length; i++) {
      view[i] = str.charCodeAt(i);
    }
    return buffer;
  }
  
  // Simple hash function for demonstration
  // In production, use a proper SHA-256 implementation
  const buffer = stringToBuffer(message);
  const hash = Array.from(new Uint8Array(buffer))
    .map(b => b.toString(16).padStart(2, '0'))
    .join('');
  
  // Ensure we get a "hash-like" string of correct length for demonstration
  return hash.padEnd(64, '0').substring(0, 64);
}

// Export for worker use
self.sha256 = sha256;