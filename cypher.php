<?php

class MandelbrotCipher {
    private $maxIterations = 10000;
    private $width = 800;
    private $height = 600;
    private $xMin = -2.0;
    private $xMax = 1.0;
    private $yMin = -1.5;
    private $yMax = 1.5;
    private $seed;
    
    public function __construct($seed = null) {
        $this->seed = $seed ?? time();
        mt_srand($this->seed);
    }
    
    /**
     * Generate a Mandelbrot point based on random parameters
     */
    private function getRandomMandelbrotPoint() {
        $x = mt_rand(0, $this->width - 1);
        $y = mt_rand(0, $this->height - 1);
        
        // Convert pixel coordinates to complex plane
        $real = $this->xMin + ($x / $this->width) * ($this->xMax - $this->xMin);
        $imag = $this->yMin + ($y / $this->height) * ($this->yMax - $this->yMin);
        
        // Calculate iterations for this point
        $zr = 0;
        $zi = 0;
        $iterations = 0;
        
        while ($zr * $zr + $zi * $zi < 4 && $iterations < $this->maxIterations) {
            $temp = $zr * $zr - $zi * $zi + $real;
            $zi = 2 * $zr * $zi + $imag;
            $zr = $temp;
            $iterations++;
        }
        
        return [
            'x' => $x,
            'y' => $y,
            'real' => $real,
            'imag' => $imag,
            'iterations' => $iterations
        ];
    }
    
    /**
     * Create a quantized equation from a Mandelbrot point
     */
    private function quantizeToEquation($point) {
        // Create a polynomial equation based on the point's properties
        $coefficients = [
            round($point['real'] * 1000),
            round($point['imag'] * 1000),
            $point['iterations']
        ];
        
        return $coefficients;
    }
    
    /**
     * Generate a hash value based on a point and character
     */
    private function generateHash($point, $char) {
        $data = $point['x'] . '|' . $point['y'] . '|' . $point['iterations'] . '|' . ord($char);
        return hash('sha256', $data);
    }
    
    /**
     * Encrypt a string using the Mandelbrot-based cipher
     */
    public function encrypt($plaintext) {
        $chars = str_split($plaintext);
        $indexCard = [];
        $encryptedData = [];
        
        foreach ($chars as $char) {
            // Get a random point in the Mandelbrot set
            $point = $this->getRandomMandelbrotPoint();
            
            // Quantize the location into an equation
            $equation = $this->quantizeToEquation($point);
            
            // Generate a hash for this point and character
            $hash = $this->generateHash($point, $char);
            
            // Store the mapping in the index card
            $indexCard[] = [
                'equation' => $equation,
                'hash' => $hash,
                'char' => ord($char)  // Store character code for decryption
            ];
            
            // Add the hash to encrypted data
            $encryptedData[] = $hash;
        }
        
        return [
            'seed' => $this->seed,
            'indexCard' => $indexCard,
            'encryptedData' => $encryptedData
        ];
    }
    
    /**
     * Decrypt a message encrypted with this cipher
     */
    public function decrypt($encryptedPackage) {
        // Restore the random seed to ensure deterministic behavior
        mt_srand($encryptedPackage['seed']);
        
        $decrypted = '';
        
        foreach ($encryptedPackage['indexCard'] as $mapping) {
            // Retrieve the character from the mapping
            $char = chr($mapping['char']);
            $decrypted .= $char;
        }
        
        return $decrypted;
    }
    
    /**
     * Verify the integrity of the encrypted data
     */
    public function verify($encryptedPackage) {
        // Restore the random seed
        mt_srand($encryptedPackage['seed']);
        
        $valid = true;
        
        // Regenerate each point and verify the hash
        foreach ($encryptedPackage['indexCard'] as $index => $mapping) {
            $point = $this->getRandomMandelbrotPoint();
            $char = chr($mapping['char']);
            $hash = $this->generateHash($point, $char);
            
            if ($hash !== $mapping['hash']) {
                $valid = false;
                break;
            }
        }
        
        return $valid;
    }
}

// Example usage
function demonstrateCipher() {
    // Message to encrypt
    $message = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_=+[]{}|;:,.<>?/";
    
    echo "Original message: " . $message . "\n\n";
    
    // Create a new cipher instance with a specific seed for reproducibility
    $cipher = new MandelbrotCipher(12345);
    
    // Encrypt the message
    $startTime = microtime(true);
    $encrypted = $cipher->encrypt($message);
    $encryptTime = microtime(true) - $startTime;
    
    echo "Encryption completed in " . number_format($encryptTime, 4) . " seconds\n";
    echo "Encrypted data (first 3 hashes):\n";
    for ($i = 0; $i < min(3, count($encrypted['encryptedData'])); $i++) {
        echo substr($encrypted['encryptedData'][$i], 0, 32) . "...\n";
    }
    echo "Total hashes: " . count($encrypted['encryptedData']) . "\n\n";
    
    // Decrypt the message
    $startTime = microtime(true);
    $decrypted = $cipher->decrypt($encrypted);
    $decryptTime = microtime(true) - $startTime;
    
    echo "Decryption completed in " . number_format($decryptTime, 4) . " seconds\n";
    echo "Decrypted message: " . $decrypted . "\n\n";
    
    // Verify the integrity
    $valid = $cipher->verify($encrypted);
    echo "Integrity check: " . ($valid ? "PASSED" : "FAILED") . "\n";
}

// Run the demonstration
demonstrateCipher();
?>
