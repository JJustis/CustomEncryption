class ProofOfWorkValidator {
    private function calculateHash($block, $nonce) {
        $miningContext = [
            'blockIndex' => $block['index'],
            'timestamp' => time(),
            'difficulty' => $block['difficulty']
        ];
        
        $combinedData = json_encode([
            'block' => $block,
            'miningContext' => $miningContext,
            'nonce' => $nonce
        ]);
        
        return hash('sha256', $combinedData);
    }
    
    public function validateProofOfWork($block, $submittedHash, $nonce) {
        // Verify hash meets difficulty requirement
        $difficulty = $block['difficulty'] ?? 4;
        $prefix = str_repeat('0', $difficulty);
        
        // Recalculate hash
        $calculatedHash = $this->calculateHash($block, $nonce);
        
        // Comprehensive validation
        $validations = [
            'hash_match' => substr($calculatedHash, 0, $difficulty) === $prefix,
            'submitted_hash_match' => $submittedHash === $calculatedHash,
            'nonce_valid' => $nonce > 0 && $nonce < 1000000,
            'timestamp_valid' => $block['timestamp'] > (time() - 3600), // Block not too old
        ];
        
        // Log detailed validation results
        error_log('Proof of Work Validation: ' . json_encode($validations));
        
        // Return comprehensive validation result
        return array_reduce($validations, function($carry, $item) {
            return $carry && $item;
        }, true);
    }
}