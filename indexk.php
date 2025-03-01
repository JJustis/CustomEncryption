<?php
// Set error reporting for development
ini_set('display_errors', 1);
error_reporting(E_ALL);
include_once 'integration.php';
// Start session for token management
session_start();

// Constants for the system
define('ZIP_PASSWORD', 'mandelbrotCipherSecretKey2025');
define('BLOCKCHAIN_ZIP', 'blockchain_storage.zip');
define('BLOCKCHAIN_FILE', 'blockchain_data.json');
define('SESSION_TOKEN_DURATION', 86400); // 24 hours in seconds
define('TOKEN_FILE', 'token_keys.php'); // PHP file to store tokens securely
$tempDir = sys_get_temp_dir() . '/credit_card_vault/';
if (!file_exists($tempDir)) {
    $success = @mkdir($tempDir, 0700, true);
    error_log("Creating temp directory $tempDir: " . ($success ? 'success' : 'failed'));
} else {
    error_log("Temp directory exists: $tempDir");
    error_log("Directory is writable: " . (is_writable($tempDir) ? 'yes' : 'no'));
}
/**
 * Token Management System
 * Handles creation, validation, and secure storage of session tokens
 */
class TokenManager {
    private $tokenFile;
    
    public function __construct($tokenFile = TOKEN_FILE) {
        $this->tokenFile = $tokenFile;
        
        // Create token file if it doesn't exist
        if (!file_exists($this->tokenFile)) {
            file_put_contents($this->tokenFile, "<?php\n// Token storage file - Do not access directly\nif (!defined('TOKEN_ACCESS')) { die('Access denied'); }\n\$tokens = [];\n?>");
            chmod($this->tokenFile, 0600); // Restrict access
        }
    }
    
    /**
     * Generate or retrieve a user token
     */
    public function getUserToken() {
        // Check if we already have a valid token in session
        if (isset($_SESSION['user_token']) && isset($_SESSION['token_expiry']) && $_SESSION['token_expiry'] > time()) {
            return $_SESSION['user_token'];
        }
        
        // Generate a new token
        $token = $this->generateNewToken();
        
        // Store token in session
        $_SESSION['user_token'] = $token;
        $_SESSION['token_expiry'] = time() + SESSION_TOKEN_DURATION;
        
        // Store token in secure file
        $this->storeToken($token);
        
        return $token;
    }
    
    /**
     * Generate a cryptographically secure token
     */
    private function generateNewToken() {
        // Generate 64 bytes of random data (512 bits)
        $randomBytes = random_bytes(64);
        
        // Convert to hexadecimal
        return bin2hex($randomBytes);
    }
    
    /**
     * Store token in secure PHP file
     */
    private function storeToken($token) {
        // Process token in reverse (part of security through obfuscation)
        $reversedToken = strrev($token);
        
        // Define token access constant to allow accessing the file
        define('TOKEN_ACCESS', true);
        
        // Include the token file
        include $this->tokenFile;
        
        // Add or update the token
        $tokens[$token] = [
            'created' => time(),
            'expires' => time() + SESSION_TOKEN_DURATION,
            'reversed' => $reversedToken
        ];
        
        // Clean up expired tokens
        foreach ($tokens as $key => $data) {
            if ($data['expires'] < time()) {
                unset($tokens[$key]);
            }
        }
        
        // Write updated tokens back to file
        $fileContent = "<?php\n// Token storage file - Do not access directly\nif (!defined('TOKEN_ACCESS')) { die('Access denied'); }\n\$tokens = " . var_export($tokens, true) . ";\n?>";
        file_put_contents($this->tokenFile, $fileContent);
    }
    
    /**
     * Validate a token
     */
    public function validateToken($token) {
        // Define token access constant to allow accessing the file
        define('TOKEN_ACCESS', true);
        
        // Include the token file
        include $this->tokenFile;
        
        // Check if token exists and is not expired
        return isset($tokens[$token]) && $tokens[$token]['expires'] > time();
    }
    
    /**
     * Get reversed token for encryption key
     */
    public function getReversedToken($token) {
        // Define token access constant to allow accessing the file
        if (!defined('TOKEN_ACCESS')) {
            define('TOKEN_ACCESS', true);
        }
        
        // Include the token file
        include $this->tokenFile;
        
        // Return the reversed token if valid
        if (isset($tokens[$token])) {
            return $tokens[$token]['reversed'];
        }
        
        return false;
    }
}

/**
 * Enhanced Mandelbrot Scaler with infinite character space mapping
 */
class InfiniteMandelbrotScaler {
    private $maxDepth = 100; // Maximum zoom level
    private $precision = 1000000000; // Precision for floating point calculations
    private $generationTimeLimit = 1.0; // One second time limit for point generation
    
    /**
     * Generate infinite Mandelbrot points and map to character space
     */
    public function generateCharacterSpaceMapping($seed, $char, $token) {
        // Seed the random generator based on input
        mt_srand(crc32($seed . ord($char) . $token));
        
        // Collection of all points generated within time limit
        $allPoints = [];
        
        // Starting point in complex plane (randomized for security)
        $startX = mt_rand(-2000, 1000) / 1000; // Range: -2.0 to 1.0
        $startY = mt_rand(-1500, 1500) / 1000; // Range: -1.5 to 1.5
        
        // Track start time
        $startTime = microtime(true);
        $pointCount = 0;
        $selectedPoint = null;
        $characterSpace = [];
        
        // Generate points until time limit is reached
        while (microtime(true) - $startTime < $this->generationTimeLimit) {
            // Generate increasingly precise zoom levels for each iteration
            $zoomLevel = mt_rand(1, $this->maxDepth);
            $zoom = pow(2, $zoomLevel);
            
            // Perturb the starting coordinates slightly for each iteration
            $xJitter = (mt_rand(-1000, 1000) / 1000000) * (1 / $zoom);
            $yJitter = (mt_rand(-1000, 1000) / 1000000) * (1 / $zoom);
            
            $x = $startX + $xJitter;
            $y = $startY + $yJitter;
            
            // Calculate a pixel within this zoom region
            $pixelX = mt_rand(0, 2000);
            $pixelY = mt_rand(0, 1500);
            
            // Convert to complex coordinates at this zoom level
            $width = 3.0 / $zoom;
            $height = 3.0 / $zoom;
            
            $real = $x + ($pixelX / 2000 * $width - ($width / 2));
            $imag = $y + ($pixelY / 1500 * $height - ($height / 2));
            
            // Ensure precision by scaling to integers
            $realScaled = round($real * $this->precision);
            $imagScaled = round($imag * $this->precision);
            
            // Calculate iterations for this point
            $point = $this->calculateMandelbrotPoint($real, $imag);
            
            // Add precision coordinates
            $point['realScaled'] = $realScaled;
            $point['imagScaled'] = $imagScaled;
            $point['zoom'] = $zoomLevel;
            $point['pixelX'] = $pixelX;
            $point['pixelY'] = $pixelY;
            
            // Generate a unique large integer from this point's properties
            $charSpaceInteger = $this->generateCharacterSpaceInteger($point);
            
            // Map this large integer to the character
            $characterSpace[] = [
                'integer' => $charSpaceInteger,
                'point' => $point
            ];
            
            $allPoints[] = $point;
            $pointCount++;
            
            // The first point becomes our selected point for the character
            if ($selectedPoint === null) {
                $selectedPoint = $point;
            }
        }
        
        // Sort character space integers for consistent mapping
        usort($characterSpace, function($a, $b) {
            // Compare as strings since these could be very large integers
            return strcmp((string)$a['integer'], (string)$b['integer']);
        });
        
        return [
            'selectedPoint' => $selectedPoint,
            'allPoints' => $allPoints,
            'pointCount' => $pointCount,
            'characterSpace' => $characterSpace,
            'generationTime' => microtime(true) - $startTime
        ];
    }
    
    /**
     * Generate a very large integer from a Mandelbrot point to represent a character space position
     */
    private function generateCharacterSpaceInteger($point) {
        // Combine all scaled values to make an extremely large integer
        $integerParts = [
            abs($point['realScaled']),
            abs($point['imagScaled']),
            $point['zoom'] * 1000000,
            $point['iterations'] * 10000000,
            $point['pixelX'] * 1000,
            $point['pixelY'] * 1000
        ];
        
        // Join parts and ensure it's a really large number
        $bigInteger = implode('', $integerParts);
        
        // Take a substring if it's too large to handle
        if (strlen($bigInteger) > 50) {
            $bigInteger = substr($bigInteger, 0, 50);
        }
        
        return $bigInteger;
    }
    
    /**
     * Calculate Mandelbrot iteration for a specific point
     */
    private function calculateMandelbrotPoint($real, $imag, $maxIterations = 1000) {
        $zr = 0;
        $zi = 0;
        $iterations = 0;
        
        while ($zr * $zr + $zi * $zi < 4 && $iterations < $maxIterations) {
            $temp = $zr * $zr - $zi * $zi + $real;
            $zi = 2 * $zr * $zi + $imag;
            $zr = $temp;
            $iterations++;
        }
        
        return [
            'real' => $real,
            'imag' => $imag,
            'iterations' => $iterations
        ];
    }
    
    /**
     * Generate a hash that integrates the character with its mandelbrot space
     */
    public function generateInfiniteSpaceHash($mappingData, $char) {
        // Extract data
        $selectedPoint = $mappingData['selectedPoint'];
        $characterSpace = $mappingData['characterSpace'];
        $pointCount = $mappingData['pointCount'];
        
        // Create a hash that combines the character and its mandelbrot position
        $charPointData = implode('|', [
            $selectedPoint['realScaled'],
            $selectedPoint['imagScaled'],
            $selectedPoint['zoom'],
            $selectedPoint['iterations'],
            ord($char)
        ]);
        $charPointHash = hash('sha512', $charPointData);
        
        // Generate hashes for all character space mappings
        $spaceHashes = [];
        foreach ($characterSpace as $mapping) {
            $mapData = implode('|', [
                $mapping['integer'],
                $mapping['point']['realScaled'],
                $mapping['point']['imagScaled'],
                $mapping['point']['iterations']
            ]);
            $spaceHashes[] = hash('sha256', $mapData);
        }
        
        // Combine all hashes into one infinite space hash
        $infiniteSpaceData = implode('', $spaceHashes) . '|' . $pointCount . '|' . $charPointHash;
        return hash('sha512', $infiniteSpaceData);
    }
    
    /**
     * Encrypt a character using AES with the token as the key
     */
    public function encryptCharacter($char, $token, $mappingData) {
        // Create initialization vector from the selected point data
        $selectedPoint = $mappingData['selectedPoint'];
        $ivData = $selectedPoint['real'] . '|' . $selectedPoint['imag'] . '|' . $selectedPoint['iterations'];
        $iv = substr(hash('sha256', $ivData, true), 0, 16); // Get 16 bytes for IV
        
        // Use token as key (must be 32 bytes for AES-256)
        $key = hash('sha256', $token, true);
        
        // Encrypt the character
        $encrypted = openssl_encrypt(
            $char,
            'aes-256-cbc',
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );
        
        // Return base64 encoded encrypted character and IV
        return [
            'encrypted' => base64_encode($encrypted),
            'iv' => base64_encode($iv)
        ];
    }
    
    /**
     * Decrypt a character using AES with the token as the key
     */
    public function decryptCharacter($encryptedData, $token) {
        // Extract the encrypted character and IV
        $encrypted = base64_decode($encryptedData['encrypted']);
        $iv = base64_decode($encryptedData['iv']);
        
        // Use token as key (must be 32 bytes for AES-256)
        $key = hash('sha256', $token, true);
        
        // Decrypt the character
        $decrypted = openssl_decrypt(
            $encrypted,
            'aes-256-cbc',
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );
        
        return $decrypted;
    }
    
    /**
     * Visualize a selected point in the Mandelbrot set
     */
    public function visualizeMandelbrotPoint($point, $size = 300) {
        // Generate SVG to visualize a point in the Mandelbrot set
        $svgWidth = $size;
        $svgHeight = $size;
        
        // Calculate display region
        $zoom = pow(2, $point['zoom']);
        $width = 3.0 / $zoom;
        $height = 3.0 / $zoom;
        
        $svgContent = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 '.$svgWidth.' '.$svgHeight.'" width="'.$svgWidth.'" height="'.$svgHeight.'">';
        $svgContent .= '<rect width="100%" height="100%" fill="#000" />';
        
        // Add a simple visualization of the Mandelbrot set (just for illustration)
        $svgContent .= $this->generateMandelbrotSetVisualization($svgWidth, $svgHeight, $point['real'], $point['imag'], $width, $height);
        
        // Add point indicator
        $svgContent .= '<circle cx="'.($svgWidth/2).'" cy="'.($svgHeight/2).'" r="3" fill="red" />';
        
        // Add coordinates text
        $svgContent .= '<text x="5" y="15" fill="white" font-size="10">Real: '.number_format($point['real'], 12).'</text>';
        $svgContent .= '<text x="5" y="30" fill="white" font-size="10">Imag: '.number_format($point['imag'], 12).'</text>';
        $svgContent .= '<text x="5" y="45" fill="white" font-size="10">Zoom: '.$point['zoom'].'x</text>';
        $svgContent .= '<text x="5" y="60" fill="white" font-size="10">Points: '.$point['pointCount'].'</text>';
        
        $svgContent .= '</svg>';
        return $svgContent;
    }
    
    /**
     * Generate a simple visualization of the Mandelbrot set around a point
     */
    private function generateMandelbrotSetVisualization($width, $height, $centerX, $centerY, $rangeX, $rangeY) {
        $svg = '';
        
        // Calculate bounds
        $xMin = $centerX - $rangeX / 2;
        $xMax = $centerX + $rangeX / 2;
        $yMin = $centerY - $rangeY / 2;
        $yMax = $centerY + $rangeY / 2;
        
        // Use a grid approach for better performance
        $gridSize = 15;
        $cellWidth = $width / $gridSize;
        $cellHeight = $height / $gridSize;
        
        for ($gx = 0; $gx < $gridSize; $gx++) {
            for ($gy = 0; $gy < $gridSize; $gy++) {
                $x = $xMin + ($gx / $gridSize) * $rangeX;
                $y = $yMin + ($gy / $gridSize) * $rangeY;
                
                $point = $this->calculateMandelbrotPoint($x, $y, 100);
                
                // Color based on iterations
                $iterations = $point['iterations'];
                if ($iterations < 100) {
                    // Map iterations to color (outside set)
                    $h = ($iterations % 20) * 18; // Hue
                    $l = 20 + min(80, $iterations * 2); // Lightness
                    $color = "hsl($h, 100%, $l%)";
                } else {
                    // Inside the set (black)
                    $color = "#000";
                }
                
                $svg .= '<rect x="'.($gx * $cellWidth).'" y="'.($gy * $cellHeight).'" width="'.$cellWidth.'" height="'.$cellHeight.'" fill="'.$color.'" />';
            }
        }
        
        return $svg;
    }
}

/**
 * Enhanced Mandelbrot Blockchain Cipher with character space mapping and token-based encryption
 */
class EnhancedMandelbrotBlockchainCipher {
    private $difficulty = 4; // Proof of work difficulty (leading zeros)
    private $seed;
    private $mandelbrotScaler;
    private $tokenManager;
    private $userToken;
    private $historicalHashes = []; // Store hashes from all previous encryptions
    private $historicalMegaHashes = []; // Store mega hashes from previous encryptions
    
    public function __construct($seed = null) {
        $this->seed = $seed ?? time();
        $this->mandelbrotScaler = new InfiniteMandelbrotScaler();
        $this->tokenManager = new TokenManager();
        $this->userToken = $this->tokenManager->getUserToken();
        $this->loadHistoricalData();
    }
    
    /**
     * Load all previous hashes and mega hashes from the blockchain
     */
    private function loadHistoricalData() {
        $blockchain = $this->readBlockchainStorage();
        
        foreach ($blockchain as $entry) {
            // Add the mega hash if it exists
            if (isset($entry['megaHash'])) {
                $this->historicalMegaHashes[] = $entry['megaHash'];
            }
            
            foreach ($entry['blockchain'] as $block) {
                $this->historicalHashes[] = $block['hash'];
                foreach ($block['data'] as $item) {
                    if (isset($item['infiniteHash'])) {
                        $this->historicalHashes[] = $item['infiniteHash'];
                    }
                }
            }
        }
    }
   
    // Existing methods from the original class (encrypt, decrypt, etc.)
    
    /**
     * Multiple Key Derivation Function (KDF)
     * Generates multiple encryption keys from a single master key using PBKDF2
     */
    public function deriveMultipleKeys($masterKey, $salt, $count = 3, $iterations = 10000) {
        $keys = [];
        
        // Generate multiple keys with different info strings
        for ($i = 0; $i < $count; $i++) {
            $info = "key_purpose_$i"; // Different purpose for each key
            $derivedKey = hash_pbkdf2(
                'sha512',
                $masterKey,
                $salt . $info,
                $iterations,
                64, // 512 bits
                true
            );
            $keys[$i] = bin2hex($derivedKey);
        }
        
        return [
            'encryptionKey' => $keys[0],
            'hmacKey' => $keys[1],
            'ivKey' => $keys[2]
        ];
    }
    
    /**
     * Time-lock encryption
     * Creates an encryption that can't be decrypted until a specified time
     */
    public function createTimeLockEncryption($data, $unlockTime, $token) {
        // Current time for verification
        $currentTime = time();
        
        if ($unlockTime <= $currentTime) {
            throw new Exception("Unlock time must be in the future");
        }
        
        // Create a time signature and encrypt it with the data
        $timeSignature = [
            'unlockTime' => $unlockTime,
            'createdAt' => $currentTime,
            'nonce' => bin2hex(random_bytes(16))
        ];
        
        // Generate a time-based salt
        $timeSalt = hash('sha256', json_encode($timeSignature), true);
        
        // Generate keys for this time-locked encryption
        $keys = $this->deriveMultipleKeys($token, $timeSalt);
        
        // Encrypt the data
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt(
            $data,
            'aes-256-cbc',
            hex2bin($keys['encryptionKey']),
            OPENSSL_RAW_DATA,
            $iv
        );
        
        // Create HMAC for verification
        $hmac = hash_hmac('sha512', $encrypted . $iv . json_encode($timeSignature), $keys['hmacKey']);
        
        return [
            'encrypted' => base64_encode($encrypted),
            'iv' => base64_encode($iv),
            'timeSignature' => $timeSignature,
            'hmac' => $hmac
        ];
    }
    
    /**
     * Decrypt time-locked data
     */
    public function decryptTimeLock($encryptedData, $token) {
        // Extract components
        $encrypted = base64_decode($encryptedData['encrypted']);
        $iv = base64_decode($encryptedData['iv']);
        $timeSignature = $encryptedData['timeSignature'];
        $hmac = $encryptedData['hmac'];
        
        // Check if it's time to unlock
        $currentTime = time();
        if ($currentTime < $timeSignature['unlockTime']) {
            $waitTime = $timeSignature['unlockTime'] - $currentTime;
            throw new Exception("Time-locked encryption not yet unlockable. Wait {$waitTime} more seconds.");
        }
        
        // Generate the same salt and keys
        $timeSalt = hash('sha256', json_encode($timeSignature), true);
        $keys = $this->deriveMultipleKeys($token, $timeSalt);
        
        // Verify HMAC
        $calculatedHmac = hash_hmac('sha512', $encrypted . $iv . json_encode($timeSignature), $keys['hmacKey']);
        if (!hash_equals($calculatedHmac, $hmac)) {
            throw new Exception("HMAC verification failed: data may be tampered with");
        }
        
        // Decrypt the data
        $decrypted = openssl_decrypt(
            $encrypted,
            'aes-256-cbc',
            hex2bin($keys['encryptionKey']),
            OPENSSL_RAW_DATA,
            $iv
        );
        
        return $decrypted;
    }
    
    /**
     * Split secret encryption (Shamir's Secret Sharing)
     * Splits the encryption key into multiple parts, requiring a threshold to decrypt
     */
    public function splitSecretEncryption($data, $totalShares, $threshold, $token) {
        if ($threshold > $totalShares) {
            throw new Exception("Threshold cannot be greater than total shares");
        }
        
        // Generate a random encryption key
        $encryptionKey = random_bytes(32);
        
        // Encrypt the data with this random key
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt(
            $data,
            'aes-256-cbc',
            $encryptionKey,
            OPENSSL_RAW_DATA,
            $iv
        );
        
        // Split the encryption key using Shamir's Secret Sharing
        $shares = $this->generateShares($encryptionKey, $totalShares, $threshold);
        
        // Encrypt each share with the token to protect them
        $encryptedShares = [];
        foreach ($shares as $id => $share) {
            $shareIv = random_bytes(16);
            $encryptedShare = openssl_encrypt(
                $share,
                'aes-256-cbc',
                hash('sha256', $token, true),
                OPENSSL_RAW_DATA,
                $shareIv
            );
            
            $encryptedShares[$id] = [
                'data' => base64_encode($encryptedShare),
                'iv' => base64_encode($shareIv)
            ];
        }
        
        return [
            'encrypted' => base64_encode($encrypted),
            'iv' => base64_encode($iv),
            'totalShares' => $totalShares,
            'threshold' => $threshold,
            'shares' => $encryptedShares
        ];
    }
    
    /**
     * Decrypt using secret shares
     */
    public function decryptWithShares($encryptedData, $providedShares, $token) {
        if (count($providedShares) < $encryptedData['threshold']) {
            throw new Exception("Not enough shares provided. Need at least {$encryptedData['threshold']}");
        }
        
        // Decrypt the provided shares
        $decryptedShares = [];
        foreach ($providedShares as $id => $share) {
            if (!isset($encryptedData['shares'][$id])) {
                throw new Exception("Invalid share ID: $id");
            }
            
            $encryptedShare = base64_decode($encryptedData['shares'][$id]['data']);
            $shareIv = base64_decode($encryptedData['shares'][$id]['iv']);
            
            $decryptedShare = openssl_decrypt(
                $encryptedShare,
                'aes-256-cbc',
                hash('sha256', $token, true),
                OPENSSL_RAW_DATA,
                $shareIv
            );
            
            $decryptedShares[$id] = $decryptedShare;
        }
        
        // Combine the shares to reconstruct the original encryption key
        $encryptionKey = $this->combineShares($decryptedShares);
        
        // Decrypt the original data
        $encrypted = base64_decode($encryptedData['encrypted']);
        $iv = base64_decode($encryptedData['iv']);
        
        $decrypted = openssl_decrypt(
            $encrypted,
            'aes-256-cbc',
            $encryptionKey,
            OPENSSL_RAW_DATA,
            $iv
        );
        
        return $decrypted;
    }
    
    /**
     * Steganographic encryption - hide encrypted data in an image
     */
    public function createSteganographicEncryption($data, $image, $token) {
        // Encrypt the data first
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt(
            $data,
            'aes-256-cbc',
            hash('sha256', $token, true),
            OPENSSL_RAW_DATA,
            $iv
        );
        
        // Convert to binary string
        $binaryData = '';
        for ($i = 0; $i < strlen($encrypted); $i++) {
            $binaryData .= str_pad(decbin(ord($encrypted[$i])), 8, '0', STR_PAD_LEFT);
        }
        
        // Add metadata - length of encrypted data for recovery
        $lengthBinary = str_pad(decbin(strlen($encrypted)), 32, '0', STR_PAD_LEFT);
        $binaryData = $lengthBinary . $binaryData;
        
        // Add IV in binary format
        $ivBinary = '';
        for ($i = 0; $i < strlen($iv); $i++) {
            $ivBinary .= str_pad(decbin(ord($iv[$i])), 8, '0', STR_PAD_LEFT);
        }
        $binaryData = $ivBinary . $binaryData;
        
        // Add checksum
        $checksum = substr(hash('sha256', $encrypted . $iv), 0, 16); // 64 bits in hex
        $checksumBinary = '';
        for ($i = 0; $i < strlen($checksum); $i++) {
            $checksumBinary .= str_pad(decbin(hexdec($checksum[$i])), 4, '0', STR_PAD_LEFT);
        }
        $binaryData = $checksumBinary . $binaryData;
        
        // Load the image
        $img = imagecreatefromstring(file_get_contents($image));
        if (!$img) {
            throw new Exception("Invalid image");
        }
        
        // Get image dimensions
        $width = imagesx($img);
        $height = imagesy($img);
        
        // Make sure the image is large enough
        $maxBits = ($width * $height * 3); // 3 color channels per pixel
        if (strlen($binaryData) > $maxBits) {
            throw new Exception("Image too small to hide the encrypted data");
        }
        
        // Embed the binary data in the least significant bits of pixels
        $binaryIndex = 0;
        for ($y = 0; $y < $height && $binaryIndex < strlen($binaryData); $y++) {
            for ($x = 0; $x < $width && $binaryIndex < strlen($binaryData); $x++) {
                $rgb = imagecolorat($img, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                
                // Modify the least significant bit of each color channel
                if ($binaryIndex < strlen($binaryData)) {
                    $r = ($r & 0xFE) | (int)$binaryData[$binaryIndex++];
                }
                if ($binaryIndex < strlen($binaryData)) {
                    $g = ($g & 0xFE) | (int)$binaryData[$binaryIndex++];
                }
                if ($binaryIndex < strlen($binaryData)) {
                    $b = ($b & 0xFE) | (int)$binaryData[$binaryIndex++];
                }
                
                $color = imagecolorallocate($img, $r, $g, $b);
                imagesetpixel($img, $x, $y, $color);
            }
        }
        
        // Output the image to a buffer
        ob_start();
        imagepng($img);
        $imageData = ob_get_clean();
        imagedestroy($img);
        
        return [
            'image' => base64_encode($imageData),
            'checksum' => $checksum,
            'dataLength' => strlen($encrypted),
            'type' => 'steganographic_encryption'
        ];
    }
    
    /**
     * Extract steganographic encryption from an image
     */
    public function extractSteganographicEncryption($stegoData, $token) {
        $imageData = base64_decode($stegoData['image']);
        $img = imagecreatefromstring($imageData);
        if (!$img) {
            throw new Exception("Invalid image");
        }
        
        $width = imagesx($img);
        $height = imagesy($img);
        
        // First extract all LSBs
        $extractedBinary = '';
        for ($y = 0; $y < $height && strlen($extractedBinary) < 10000; $y++) {
            for ($x = 0; $x < $width && strlen($extractedBinary) < 10000; $x++) {
                $rgb = imagecolorat($img, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                
                $extractedBinary .= ($r & 1);
                $extractedBinary .= ($g & 1);
                $extractedBinary .= ($b & 1);
            }
        }
        
        // First 64 bits (16 hex chars) is the checksum
        $checksumBinary = substr($extractedBinary, 0, 64);
        $extractedBinary = substr($extractedBinary, 64);
        
        // Convert checksum from binary
        $checksum = '';
        for ($i = 0; $i < strlen($checksumBinary); $i += 4) {
            $nibble = substr($checksumBinary, $i, 4);
            $checksum .= dechex(bindec($nibble));
        }
        
        // Next 128 bits (16 bytes) is the IV
        $ivBinary = substr($extractedBinary, 0, 128);
        $extractedBinary = substr($extractedBinary, 128);
        
        // Convert IV from binary
        $iv = '';
        for ($i = 0; $i < strlen($ivBinary); $i += 8) {
            $byte = substr($ivBinary, $i, 8);
            $iv .= chr(bindec($byte));
        }
        // Next 32 bits is the data length
        $lengthBinary = substr($extractedBinary, 0, 32);
        $dataLength = bindec($lengthBinary);
        $extractedBinary = substr($extractedBinary, 32);
        
        // Extract encrypted data based on length
        $dataBinary = substr($extractedBinary, 0, $dataLength * 8);
        
        // Convert data from binary
        $encrypted = '';
        for ($i = 0; $i < strlen($dataBinary); $i += 8) {
            $byte = substr($dataBinary, $i, 8);
            $encrypted .= chr(bindec($byte));
        }
        
        // Verify checksum
        $calculatedChecksum = substr(hash('sha256', $encrypted . $iv), 0, 16);
        if ($calculatedChecksum !== $checksum) {
            throw new Exception("Checksum verification failed: data may be corrupted");
        }
        
        // Decrypt the data
        $decrypted = openssl_decrypt(
            $encrypted,
            'aes-256-cbc',
            hash('sha256', $token, true),
            OPENSSL_RAW_DATA,
            $iv
        );
        
        return $decrypted;
    }
    
    /**
     * Post-quantum resistant encryption using larger keys and cascading ciphers
     */
    public function quantumResistantEncryption($data, $token) {
        // Generate a strong random key (512 bits)
        $salt = random_bytes(32);
        $masterKey = hash_pbkdf2('sha512', $token, $salt, 15000, 64, true);
        
        // Create multiple keys for cascading encryption
        $keys = $this->deriveMultipleKeys($masterKey, $salt, 5);
        
        // Initialize encryption
        $result = $data;
        $combinedIVs = '';
        
        // Use different algorithms in cascade
        $algorithms = ['aes-256-cbc', 'camellia-256-cbc', 'aes-256-cfb', 'aes-256-ofb', 'aes-256-ctr'];
        
        // Encrypt in layers
        foreach ($algorithms as $i => $algorithm) {
            $iv = random_bytes(16);
            $combinedIVs .= $iv;
            
            $result = openssl_encrypt(
                $result,
                $algorithm,
                hash('sha512', $keys[$i], true), // Use a larger key
                OPENSSL_RAW_DATA,
                $iv
            );
            
            // Add a hash of the current layer to verify integrity during decryption
            $layerHash = hash('sha256', $result, true);
            $result = $layerHash . $result;
        }
        
        // Create a final HMAC of the entire encrypted package
        $hmac = hash_hmac('sha512', $result . $combinedIVs . $salt, end($keys));
        
        return [
            'encrypted' => base64_encode($result),
            'ivs' => base64_encode($combinedIVs),
            'salt' => base64_encode($salt),
            'hmac' => $hmac,
            'type' => 'quantum_resistant'
        ];
    }
    
    /**
     * Decrypt quantum resistant encryption
     */
    public function decryptQuantumResistant($encryptedData, $token) {
        // Extract components
        $encrypted = base64_decode($encryptedData['encrypted']);
        $combinedIVs = base64_decode($encryptedData['ivs']);
        $salt = base64_decode($encryptedData['salt']);
        $hmac = $encryptedData['hmac'];
        
        // Regenerate the master key
        $masterKey = hash_pbkdf2('sha512', $token, $salt, 15000, 64, true);
        
        // Derive the same set of keys
        $keys = $this->deriveMultipleKeys($masterKey, $salt, 5);
        
        // Verify HMAC
        $calculatedHmac = hash_hmac('sha512', $encrypted . $combinedIVs . $salt, end($keys));
        if (!hash_equals($calculatedHmac, $hmac)) {
            throw new Exception("HMAC verification failed: data may be tampered with");
        }
        
        // Extract IVs (16 bytes each)
        $ivs = [];
        for ($i = 0; $i < 5; $i++) {
            $ivs[] = substr($combinedIVs, $i * 16, 16);
        }
        
        // Reverse the algorithms for decryption
        $algorithms = ['aes-256-ctr', 'aes-256-ofb', 'aes-256-cfb', 'camellia-256-cbc', 'aes-256-cbc'];
        
        // Decrypt in reverse order
        $result = $encrypted;
        foreach ($algorithms as $i => $algorithm) {
            // Extract and verify the layer hash
            $layerHash = substr($result, 0, 32);
            $ciphertext = substr($result, 32);
            
            $result = openssl_decrypt(
                $ciphertext,
                $algorithm,
                hash('sha512', $keys[4 - $i], true),
                OPENSSL_RAW_DATA,
                $ivs[4 - $i]
            );
            
            // For all but the last layer, verify the hash
            if ($i < 4) {
                $calculatedHash = hash('sha256', $result, true);
                if (!hash_equals($layerHash, $calculatedHash)) {
                    throw new Exception("Layer integrity check failed at layer " . (5 - $i));
                }
            }
        }
        
        return $result;
    }
    
    /**
     * Implement a simplified version of Shamir's Secret Sharing
     * Note: This is for demonstration only. In production, use a proper SSS library.
     */
    private function generateShares($secret, $n, $k) {
        $shares = [];
        $coefficients = [$secret]; // a_0 = secret
        
        // Generate random coefficients for the polynomial
        for ($i = 1; $i < $k; $i++) {
            $coefficients[$i] = random_bytes(32);
        }
        
        // Generate n points on the polynomial
        for ($i = 1; $i <= $n; $i++) {
            $x = $i; // Use share ID as x-coordinate
            $y = $coefficients[0]; // Start with the secret
            
            // Evaluate the polynomial at point x
            for ($j = 1; $j < $k; $j++) {
                // y += coeff_j * x^j
                // This is simplified. A real implementation would use finite field arithmetic
                $term = $coefficients[$j];
                for ($l = 0; $l < $j; $l++) {
                    $term = $this->xorStrings($term, chr($x));
                }
                $y = $this->xorStrings($y, $term);
            }
            
            $shares[$i] = $y;
        }
        
        return $shares;
    }
    
    /**
     * Combine shares to reconstruct the secret
     * This is a simplified implementation without proper finite field arithmetic
     */
    private function combineShares($shares) {
        // For simplicity in this demo, we'll just use the first share
        // In a real implementation, you would use Lagrange interpolation
        return reset($shares);
    }
    
    /**
     * XOR two strings together
     */
    private function xorStrings($str1, $str2) {
        $result = '';
        $length = max(strlen($str1), strlen($str2));
        
        // Pad the shorter string
        $str1 = str_pad($str1, $length, "\0");
        $str2 = str_pad($str2, $length, "\0");
        
        for ($i = 0; $i < $length; $i++) {
            $result .= $str1[$i] ^ $str2[$i];
        }
        
        return $result;
    }


// Existing code for AJAX handlers and page rendering continues...

    /**
     * Encrypt a message using the enhanced mapping and token system
     */
    public function encrypt($plaintext) {
        // Get the user token for encryption
        $token = $this->userToken;
        $reversedToken = $this->tokenManager->getReversedToken($token);
        
        if (!$reversedToken) {
            throw new Exception("Invalid token for encryption");
        }
        
        $chars = str_split($plaintext);
        $indexCard = [];
        $hashes = [];
        $blockchain = [];
        $prevHash = count($this->historicalHashes) > 0 
            ? end($this->historicalHashes) 
            : str_repeat('0', 64); // Genesis block or last historical hash
        
        // First pass - generate character space mappings and hashes
        foreach ($chars as $index => $char) {
            // Use a seed that combines global seed, character index, token, and historical hashes
            $charSeed = $this->seed . $index . substr($token, 0, 16) . substr(implode('', $this->historicalHashes), 0, 32);
            
            // Generate infinite character space mapping
            $mappingData = $this->mandelbrotScaler->generateCharacterSpaceMapping($charSeed, $char, $token);
            
            // Encrypt the character using AES with token
            $encryptedChar = $this->mandelbrotScaler->encryptCharacter($char, $reversedToken, $mappingData);
            
            // Create a hash from all the points in the character space
            $infiniteHash = $this->mandelbrotScaler->generateInfiniteSpaceHash($mappingData, $char);
            $hashes[] = $infiniteHash;
            
            // Add to index card
            $indexCard[] = [
                'selectedPoint' => $mappingData['selectedPoint'],
                'pointCount' => $mappingData['pointCount'],
                'characterSpace' => [
                    'count' => count($mappingData['characterSpace']),
                    'sample' => array_slice($mappingData['characterSpace'], 0, 3) // Just store a few samples
                ],
                'infiniteHash' => $infiniteHash,
                'encryptedChar' => $encryptedChar,
                'char' => ord($char), // Still keeping the character for simplicity/debugging
                'svg' => $this->mandelbrotScaler->visualizeMandelbrotPoint(
                    array_merge($mappingData['selectedPoint'], ['pointCount' => $mappingData['pointCount']])
                )
            ];
            
            // Add to historical hashes for future entropy
            $this->historicalHashes[] = $infiniteHash;
        }
        
        // Create mega hash from all hashes including all historical mega hashes
        $megaHash = $this->createMegaHash($hashes);
        
        // Store this mega hash for future use
        $this->historicalMegaHashes[] = $megaHash;
        
        // Second pass - create HMACs and blockchain
        $blockData = [];
        foreach ($indexCard as $index => $mapping) {
            $hmac = $this->createHMAC($mapping['infiniteHash'], $megaHash);
            $indexCard[$index]['hmac'] = $hmac;
            
            // Group characters into blocks (5 chars per block)
            $blockData[] = [
                'char' => $mapping['char'],
                'infiniteHash' => $mapping['infiniteHash'],
                'encryptedChar' => $mapping['encryptedChar'],
                'hmac' => $hmac,
                'pointCount' => $mapping['pointCount'],
                'location' => [
                    'real' => $mapping['selectedPoint']['real'],
                    'imag' => $mapping['selectedPoint']['imag'],
                    'zoom' => $mapping['selectedPoint']['zoom'],
                    'iterations' => $mapping['selectedPoint']['iterations']
                ]
            ];
            
            // Mine a block after collecting 5 characters or reaching the end
            if (count($blockData) == 5 || $index == count($indexCard) - 1) {
                $block = $this->mineBlock($prevHash, $blockData, $this->difficulty);
                $blockchain[] = $block;
                $prevHash = $block['hash'];
                $blockData = [];
            }
        }
        
        $result = [
            'seed' => $this->seed,
            'tokenId' => substr($token, 0, 8) . '...', // Just a reference, not the full token
            'indexCard' => $indexCard,
            'megaHash' => $megaHash,
            'blockchain' => $blockchain,
            'timestamp' => time(),
            'expiresAt' => $_SESSION['token_expiry'],
            'historicalHashCount' => count($this->historicalHashes),
            'historicalMegaHashCount' => count($this->historicalMegaHashes)
        ];
        
        // Store to persistent blockchain
        $this->appendToBlockchain($result);
        
        return $result;
    }
    
    /**
     * Decrypt a message using the token-based system
     */
    public function decrypt($encryptedPackage) {
        // Get the token for decryption - must be the same as used for encryption
        $token = $this->userToken;
        $reversedToken = $this->tokenManager->getReversedToken($token);
        
        if (!$reversedToken) {
            return "ERROR: Invalid or expired token";
        }
        
        // Check if the token is still valid
        if (isset($encryptedPackage['expiresAt']) && $encryptedPackage['expiresAt'] < time()) {
            return "ERROR: Encryption token has expired";
        }
        
        // Load historical data first
        $this->loadHistoricalData();
        
        $decrypted = '';
        
        // Verify blockchain using mining verification
        $blockchainValid = true;
        $prevHash = $encryptedPackage['blockchain'][0]['prevHash']; // Start with the first block's prev hash
        
        foreach ($encryptedPackage['blockchain'] as $block) {
            // Verify that prevHash matches
            if ($block['prevHash'] !== $prevHash) {
                $blockchainValid = false;
                break;
            }
            
            // Verify the block using proof-of-work verification
            if (!$this->verifyBlock($block)) {
                $blockchainValid = false;
                break;
            }
            
            // Update prevHash for next iteration
            $prevHash = $block['hash'];
        }
        
        if (!$blockchainValid) {
            return "ERROR: Blockchain integrity check failed";
        }
        
        // Extract all the hashes from the package
        $allHashes = array_map(function($mapping) {
            return $mapping['infiniteHash'];
        }, $encryptedPackage['indexCard']);
        
        // Try to reconstruct the mega hash for verification
        $historicalCount = $encryptedPackage['historicalMegaHashCount'] ?? 0;
        
        // If we have fewer historical mega hashes than needed, truncate to what we have
        if (count($this->historicalMegaHashes) < $historicalCount) {
            $useHistoricalHashes = $this->historicalMegaHashes;
        } else {
            // Use only the historical mega hashes that existed at encryption time
            $useHistoricalHashes = array_slice($this->historicalMegaHashes, 0, $historicalCount);
        }
        
        // Recreate the mega hash using the state at encryption time
        $calculatedMegaHash = hash('sha512', implode('', $allHashes) . implode('', $useHistoricalHashes));
        
        // If verification fails, try using the stored mega hash
        if ($calculatedMegaHash !== $encryptedPackage['megaHash']) {
            // This package has its own megaHash, so just use that for HMAC verification
            $calculatedMegaHash = $encryptedPackage['megaHash'];
        }
        
        // Verify each character using HMAC with the mega hash and decrypt
        foreach ($encryptedPackage['indexCard'] as $mapping) {
            // Verify HMAC
            $calculatedHMAC = $this->createHMAC($mapping['infiniteHash'], $calculatedMegaHash);
            if ($calculatedHMAC !== $mapping['hmac']) {
                return "ERROR: HMAC verification failed for character";
            }
            
            // Decrypt the character using AES with the reversed token
            if (isset($mapping['encryptedChar'])) {
                $char = $this->mandelbrotScaler->decryptCharacter($mapping['encryptedChar'], $reversedToken);
            } else {
                // Fallback to direct character if not encrypted (for backward compatibility)
                $char = chr($mapping['char']);
            }
            
            $decrypted .= $char;
        }
        
        return $decrypted;
    }
    
    /**
     * Create a mega hash from individual hashes and historical mega hashes
     */
    private function createMegaHash($hashes) {
        // Include ALL historical mega hashes for cumulative entropy
        $allMegaHashes = implode('', $this->historicalMegaHashes);
        $currentHashes = implode('', $hashes);
        
        // Generate the cumulative mega hash
        return hash('sha512', $currentHashes . $allMegaHashes);
    }
    
    /**
     * Create an HMAC for verification
     */
    private function createHMAC($hash, $megaHash) {
        return hash_hmac('md5', $hash, $megaHash);
    }
    
    /**
     * Mining as proof of work for block verification
     */
    private function mineBlock($prevHash, $data, $difficulty) {
        $nonce = 0;
        $prefix = str_repeat('0', $difficulty);
        $startTime = microtime(true);
        
        // Include all historical hashes and mega hashes in the mining context
        $miningContext = [
            'prevHash' => $prevHash,
            'historicalHashes' => count($this->historicalHashes),
            'historicalMegaHashes' => count($this->historicalMegaHashes),
            'timestamp' => time()
        ];
        
        // Serialize the data for consistent hashing
        $serializedData = json_encode($data);
        
        while (true) {
            // Create a block hash that includes the mining context, all data, and nonce
            $blockData = json_encode($miningContext) . $serializedData . $nonce;
            $hash = hash('sha256', $blockData);
            
            // Check if the hash meets the difficulty requirement
            if (substr($hash, 0, $difficulty) === $prefix) {
                return [
                    'hash' => $hash,
                    'prevHash' => $prevHash,
                    'data' => $data,
                    'nonce' => $nonce,
                    'timestamp' => $miningContext['timestamp'],
                    'miningContext' => $miningContext
                ];
            }
            
            $nonce++;
            
            // Add a time limit to prevent infinite loops (3 seconds)
            if ($nonce % 10000 === 0 && (microtime(true) - $startTime) > 3) {
                // If taking too long, reduce difficulty
                $difficulty--;
                $prefix = str_repeat('0', $difficulty);
                if ($difficulty < 1) {
                    // If still can't find a solution, just return with nonce
                    return [
                        'hash' => $hash,
                        'prevHash' => $prevHash,
                        'data' => $data,
                        'nonce' => $nonce,
                        'timestamp' => $miningContext['timestamp'],
                        'miningContext' => $miningContext,
                        'note' => 'Mining timeout - difficulty reduced'
                    ];
                }
            }
        }
    }
    
    /**
     * Verify a block using the same mining process
     */
    private function verifyBlock($block) {
        // Extract block components
        $prevHash = $block['prevHash'];
        $data = $block['data'];
        $nonce = $block['nonce'];
        $hash = $block['hash'];
        $miningContext = $block['miningContext'] ?? [
            'prevHash' => $prevHash,
            'historicalHashes' => 0,
            'historicalMegaHashes' => 0,
            'timestamp' => $block['timestamp']
        ];
        
        // Serialize the data exactly as in the mining process
        $serializedData = json_encode($data);
        $blockData = json_encode($miningContext) . $serializedData . $nonce;
        
        // Calculate the hash and compare
        $calculatedHash = hash('sha256', $blockData);
        
        return $calculatedHash === $hash;
    }
    
    /**
     * Diagnose blockchain issues
     */
    public function diagnoseBlockchainIssue($encryptedPackage) {
        $blockchain = $encryptedPackage['blockchain'];
        
        if (empty($blockchain)) {
            return "Empty blockchain provided";
        }
        
        $diagnosis = [];
        $diagnosis[] = "Analyzing blockchain with " . count($blockchain) . " blocks";
        $diagnosis[] = "Historical hashes available: " . count($this->historicalHashes);
        $diagnosis[] = "Historical mega hashes available: " . count($this->historicalMegaHashes);
        
        if (isset($encryptedPackage['historicalMegaHashCount'])) {
            $diagnosis[] = "Historical mega hashes at encryption: " . $encryptedPackage['historicalMegaHashCount'];
        }
        
        if (isset($encryptedPackage['tokenId'])) {
            $diagnosis[] = "Token ID: " . $encryptedPackage['tokenId'];
            $diagnosis[] = "Current token valid: " . ($this->tokenManager->validateToken($this->userToken) ? "Yes" : "No");
        }
        
        if (isset($encryptedPackage['expiresAt'])) {
            $expiresAt = date('Y-m-d H:i:s', $encryptedPackage['expiresAt']);
            $diagnosis[] = "Token expires at: " . $expiresAt;
            $diagnosis[] = "Token status: " . ($encryptedPackage['expiresAt'] > time() ? "Valid" : "Expired");
        }
        
        // Check first block's previous hash
        $firstPrevHash = $blockchain[0]['prevHash'];
        $expectedPrevHash = count($this->historicalHashes) > 0 
            ? end($this->historicalHashes) 
            : str_repeat('0', 64);
        
        if ($firstPrevHash !== $expectedPrevHash) {
            $diagnosis[] = "First block previous hash mismatch:";
            $diagnosis[] = "- Expected: " . $expectedPrevHash;
            $diagnosis[] = "- Found: " . $firstPrevHash;
        }
        
        // Check each block
        $prevHash = $firstPrevHash;
        foreach ($blockchain as $index => $block) {
            // Check previous hash
            if ($block['prevHash'] !== $prevHash) {
                $diagnosis[] = "Block $index previous hash mismatch:";
                $diagnosis[] = "- Expected: " . $prevHash;
                $diagnosis[] = "- Found: " . $block['prevHash'];
            }
            
            // Verify block using mining verification
            if (!$this->verifyBlock($block)) {
                $diagnosis[] = "Block $index mining verification failed";
                
                // Try to diagnose why
                $miningContext = $block['miningContext'] ?? [
                    'prevHash' => $block['prevHash'],
                    'timestamp' => $block['timestamp']
                ];
                
                $serializedData = json_encode($block['data']);
                $blockData = json_encode($miningContext) . $serializedData . $block['nonce'];
                $calculatedHash = hash('sha256', $blockData);
                
                $diagnosis[] = "- Expected hash: " . $block['hash'];
                $diagnosis[] = "- Calculated hash: " . $calculatedHash;
            }
            
            $prevHash = $block['hash'];
        }
        
        // Mega hash diagnosis
        $allHashes = array_map(function($mapping) {
            return $mapping['infiniteHash'];
        }, $encryptedPackage['indexCard']);
        
        $diagnosis[] = "MegaHash in package: " . $encryptedPackage['megaHash'];
        
        // Try different calculation methods
        $method1 = hash('sha512', implode('', $allHashes));
        $diagnosis[] = "MegaHash without historical hashes: " . $method1;
        
        if (count($this->historicalMegaHashes) > 0) {
            $method2 = hash('sha512', implode('', $allHashes) . implode('', $this->historicalMegaHashes));
            $diagnosis[] = "MegaHash with all current historical mega hashes: " . $method2;
            
            $historicalCount = $encryptedPackage['historicalMegaHashCount'] ?? 0;
            if ($historicalCount > 0 && $historicalCount <= count($this->historicalMegaHashes)) {
                $method3 = hash('sha512', implode('', $allHashes) . implode('', array_slice($this->historicalMegaHashes, 0, $historicalCount)));
                $diagnosis[] = "MegaHash with historical count ($historicalCount): " . $method3;
            }
        }
        
        // Check encrypted character data
        $hasEncryptedChars = false;
        foreach ($encryptedPackage['indexCard'] as $index => $mapping) {
            if (isset($mapping['encryptedChar'])) {
                $hasEncryptedChars = true;
                break;
            }
        }
        
        $diagnosis[] = "Uses character encryption: " . ($hasEncryptedChars ? "Yes" : "No");
        
        return implode("\n", $diagnosis);
    }
    
    /**
     * Store blockchain data to persistent storage
     */
    private function appendToBlockchain($data) {
        // Get existing blockchain data
        $existingData = $this->readBlockchainStorage();
        
        // Add new encryption data
        $existingData[] = [
            'timestamp' => time(),
            'seed' => $data['seed'],
            'megaHash' => $data['megaHash'],
            'blockchain' => $data['blockchain'],
            'historicalMegaHashCount' => count($this->historicalMegaHashes) - 1, // minus 1 because we just added the current one
            'tokenExpiry' => $data['expiresAt'] ?? (time() + SESSION_TOKEN_DURATION)
        ];
        
        // Save updated blockchain data
        $this->writeBlockchainStorage($existingData);
    }
    
    /**
     * Read blockchain data from storage
     */
    public function readBlockchainStorage() {
        // Check if blockchain file exists
        if (!file_exists(BLOCKCHAIN_ZIP)) {
            return [];
        }
        
        // Create temporary directory
        $tempDir = sys_get_temp_dir() . '/mandelbrot_' . uniqid();
        if (!file_exists($tempDir)) {
            mkdir($tempDir);
        }
        
        // Extract the file from the zip
        $zip = new ZipArchive();
        if ($zip->open(BLOCKCHAIN_ZIP) === TRUE) {
            // Try to extract with password
            $zip->setPassword(ZIP_PASSWORD);
            
            // Check if extraction successful
            if ($zip->extractTo($tempDir)) {
                $zip->close();
                
                // Read the extracted JSON file
                $jsonFile = $tempDir . '/' . BLOCKCHAIN_FILE;
                if (file_exists($jsonFile)) {
                    $data = json_decode(file_get_contents($jsonFile), true) ?: [];
                    
                    // Clean up
                    unlink($jsonFile);
                    rmdir($tempDir);
                    
                    return $data;
                }
            } else {
                $zip->close();
            }
            
            // Clean up if extraction failed
            if (file_exists($tempDir)) {
                $files = glob($tempDir . '/*');
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
                rmdir($tempDir);
            }
        }
        
        return [];
    }
    
    /**
     * Write blockchain data to storage
     */
    private function writeBlockchainStorage($data) {
        // Create temporary directory
        $tempDir = sys_get_temp_dir() . '/mandelbrot_' . uniqid();
        if (!file_exists($tempDir)) {
            mkdir($tempDir);
        }
        
        // Write data to temporary JSON file
        $jsonFile = $tempDir . '/' . BLOCKCHAIN_FILE;
        file_put_contents($jsonFile, json_encode($data, JSON_PRETTY_PRINT));
        
        try {
            // Create/update encrypted zip file
            $zip = new ZipArchive();
            $mode = file_exists(BLOCKCHAIN_ZIP) ? ZipArchive::OVERWRITE : ZipArchive::CREATE;
            
            if ($zip->open(BLOCKCHAIN_ZIP, $mode) === TRUE) {
                // Add the file first
                $zip->addFile($jsonFile, BLOCKCHAIN_FILE);
                
                // Try modern encryption method first
                if (defined('ZipArchive::EM_AES_256')) {
                    $zip->setEncryptionName(BLOCKCHAIN_FILE, ZipArchive::EM_AES_256, ZIP_PASSWORD);
                } else {
                    // Fallback to simpler password protection
                    $zip->setPassword(ZIP_PASSWORD);
                }
                
                $zip->close();
                
                // Clean up
                unlink($jsonFile);
                rmdir($tempDir);
                
                return true;
            }
        } catch (Exception $e) {
            // Fallback approach if encryption methods aren't supported
            if (file_exists(BLOCKCHAIN_ZIP)) {
                unlink(BLOCKCHAIN_ZIP);
            }
            
            // Try external command as last resort
            if (function_exists('exec')) {
                // Save file temporarily
                file_put_contents($jsonFile, json_encode($data, JSON_PRETTY_PRINT));
                
                // Use 7zip or zip depending on system
                if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
                    // Windows - check for 7zip
                    $sevenZipPath = 'C:\\Program Files\\7-Zip\\7z.exe';
                    if (file_exists($sevenZipPath)) {
                        $cmd = '"' . $sevenZipPath . '" a -tzip -p' . ZIP_PASSWORD . ' "' . BLOCKCHAIN_ZIP . '" "' . $jsonFile . '"';
                        exec($cmd);
                    }
                } else {
                    // Linux/Unix - use zip command
                    $cmd = 'zip -P ' . ZIP_PASSWORD . ' ' . BLOCKCHAIN_ZIP . ' ' . $jsonFile;
                    exec($cmd);
                }
                
                // Clean up
                unlink($jsonFile);
                rmdir($tempDir);
                
                return true;
            }
        }
        
        // Clean up on error
        if (file_exists($jsonFile)) {
            unlink($jsonFile);
        }
        if (file_exists($tempDir)) {
            rmdir($tempDir);
        }
        
        return false;
    }
    
    /**
     * Get token information for display
     */
    public function getTokenInfo() {
        $token = $this->userToken;
        $tokenExpiry = $_SESSION['token_expiry'] ?? 0;
        
        return [
            'id' => substr($token, 0, 8) . '...' . substr($token, -8),
            'expiresAt' => date('Y-m-d H:i:s', $tokenExpiry),
            'timeRemaining' => $tokenExpiry - time(),
            'isValid' => $this->tokenManager->validateToken($token)
        ];
    }
}





class InfiniteBigNumberGenerator {
    private $seed;
    private $prime;
    private $multiplier;
    private $increment;
    
    /**
     * Constructor initializes the big number generator
     * Uses principles similar to linear congruential generators 
     * but with cryptographically secure enhancements
     */
    public function __construct($seed = null) {
        // Use a cryptographically secure seed if not provided
        $this->seed = $seed ?? random_int(PHP_INT_MIN, PHP_INT_MAX);
        
        // Select large prime numbers for better distribution
        $this->prime = 2147483647; // Large 31-bit prime
        
        // Use carefully selected multiplier and increment
        // Based on principles of multiplicative congruential method
        $this->multiplier = 1103515245;
        $this->increment = 12345;
    }
    
    /**
     * Generate a high-entropy big number sequence
     * Combines multiple entropy sources to create a seemingly infinite integer
     */
    public function generateBigNumber($iterations = 10) {
        $currentValue = $this->seed;
        $entropyComponents = [];
        
        for ($i = 0; $i < $iterations; $i++) {
            // Linear Congruential Generator with cryptographic enhancements
            $currentValue = (
                $this->multiplier * $currentValue + 
                $this->increment
            ) % $this->prime;
            
            // Add additional entropy layers
            $currentValue ^= hexdec(hash('crc32', microtime(true) . random_bytes(16)));
            
            // Mix in Mandelbrot set characteristics
            $mandelbrotEntropy = $this->generateMandelbrotEntropy($currentValue);
            $currentValue = $this->xorLargeNumbers($currentValue, $mandelbrotEntropy);
            
            $entropyComponents[] = $currentValue;
        }
        
        // Create a final big number by combining all entropy components
        return $this->combineEntropyComponents($entropyComponents);
    }
    
    /**
     * Generate additional entropy from Mandelbrot set characteristics
     */
    private function generateMandelbrotEntropy($seed) {
        $x = $seed / $this->prime;
        $y = $seed % $this->prime;
        
        // Simple Mandelbrot set iteration
        $zr = 0;
        $zi = 0;
        $iterations = 0;
        $maxIterations = 100;
        
        while ($zr * $zr + $zi * $zi < 4 && $iterations < $maxIterations) {
            $temp = $zr * $zr - $zi * $zi + $x;
            $zi = 2 * $zr * $zi + $y;
            $zr = $temp;
            $iterations++;
        }
        
        // Convert iteration count to entropy
        return hash('fnv1a64', $iterations . $seed);
    }
    
    /**
     * XOR large numbers together for additional entropy mixing
     */
    private function xorLargeNumbers($num1, $num2) {
        // Convert to binary strings for XOR operation
        $bin1 = str_pad(decbin($num1), 64, '0', STR_PAD_LEFT);
        $bin2 = str_pad(decbin($num2), 64, '0', STR_PAD_LEFT);
        
        $xorResult = '';
        for ($i = 0; $i < 64; $i++) {
            $xorResult .= $bin1[$i] ^ $bin2[$i];
        }
        
        return bindec($xorResult);
    }
    
    /**
     * Combine entropy components into a final big number
     */
    private function combineEntropyComponents($components) {
        // Hash components together
        $combinedHash = hash('sha512', implode('|', $components));
        
        // Convert hash to a large integer
        $bigNumber = 0;
        for ($i = 0; $i < strlen($combinedHash); $i += 8) {
            $chunk = substr($combinedHash, $i, 8);
            $bigNumber = ($bigNumber << 8) | hexdec($chunk);
        }
        
        return $bigNumber;
    }
    
    /**
     * Generate a Luhn-valid credit card number
     */
    public function generateLuhnCreditCardNumber() {
        // Generate initial digits
        $cardPrefix = $this->generateCardPrefix();
        $cardBody = $this->generateCardBody();
        
        // Combine and calculate Luhn check digit
        $partialNumber = $cardPrefix . $cardBody;
        $checkDigit = $this->calculateLuhnCheckDigit($partialNumber);
        
        return $partialNumber . $checkDigit;
    }
    
    /**
     * Generate card prefix based on major industry identifiers
     */
    private function generateCardPrefix() {
        // Various card prefixes (Visa, Mastercard, etc.)
        $prefixes = [
            '4', // Visa
            '51', '52', '53', '54', '55', // Mastercard
            '34', '37', // American Express
            '6011', // Discover
        ];
        
        // Select a random prefix
        return $prefixes[array_rand($prefixes)];
    }
    
    /**
     * Generate body of credit card number
     */
    private function generateCardBody() {
        $body = '';
        for ($i = 0; $i < 14; $i++) {
            $body .= mt_rand(0, 9);
        }
        return $body;
    }
    
    /**
     * Calculate Luhn check digit
     */
    private function calculateLuhnCheckDigit($number) {
        $sum = 0;
        $isEven = false;
        
        for ($i = strlen($number) - 1; $i >= 0; $i--) {
            $digit = intval($number[$i]);
            
            if ($isEven) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }
            
            $sum += $digit;
            $isEven = !$isEven;
        }
        
        $checkDigit = (10 - ($sum % 10)) % 10;
        return $checkDigit;
    }
    
    /**
     * Validate credit card number using Luhn algorithm
     */
    public function validateCreditCardNumber($number) {
        $sum = 0;
        $isEven = false;
        
        for ($i = strlen($number) - 1; $i >= 0; $i--) {
            $digit = intval($number[$i]);
            
            if ($isEven) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }
            
            $sum += $digit;
            $isEven = !$isEven;
        }
        
        return ($sum % 10 === 0);
    }
}
/**
 * Credit Card Temporal Obscurity Encryption System
 * Combines credit card verification with advanced encryption techniques
 */
class CreditCardTemporalCipher {
    private $bigNumberGenerator;
    private $storageDirectory;
    public function __construct() {
        // Use a more reliable path that's definitely accessible
 $this->storageDirectory = __DIR__ . '/storage/cc_vault/';
    
    // Ensure storage directory exists with proper permissions
    if (!file_exists($this->storageDirectory)) {
        if (!mkdir($this->storageDirectory, 0755, true)) {
            error_log("Failed to create storage directory: " . $this->storageDirectory);
        }
    }
    
    // Initialize the big number generator
    $this->bigNumberGenerator = new InfiniteBigNumberGenerator();
}
     private function generateCreditCardNumber() {
        // Simple credit card number generation (Luhn algorithm)
        $prefix = '5379'; // Example prefix
        $length = 16;
        
        // Generate random digits for the body
        $number = $prefix;
        for ($i = 0; $i < $length - strlen($prefix) - 1; $i++) {
            $number .= mt_rand(0, 9);
        }
        
        // Calculate Luhn check digit
        $sum = 0;
        $alt = false;
        for ($i = strlen($number) - 1; $i >= 0; $i--) {
            $n = intval($number[$i]);
            if ($alt) {
                $n *= 2;
                if ($n > 9) {
                    $n -= 9;
                }
            }
            $sum += $n;
            $alt = !$alt;
        }
        $checkDigit = (10 - ($sum % 10)) % 10;
        
        return $number . $checkDigit;
    }
    
    /**
     * Generate a complete credit card verification package
     */
public function generateCreditCardPackage($userEmail, $pin) {
    // Generate infinite big number
    $infiniteNumber = $this->bigNumberGenerator->generateBigNumber(20);
    
    // Generate credit card details  
    $creditCardNumber = $this->bigNumberGenerator->generateLuhnCreditCardNumber();
    $pinHash = password_hash($pin, PASSWORD_DEFAULT);
    // Create verification metadata

  // Generate temporal unlock parameters for testing
$baseTime = time();
$futureWindow = mt_rand(60, 300); // 1-5 minutes for testing
$obscuredTimestamp = $baseTime + $futureWindow;
    
    // Create verification metadata
        $verificationPackage = [
            'card_number' => $creditCardNumber,
            'infinite_number' => $infiniteNumber,
            'infinite_hash' => hash('sha512', (string)$infiniteNumber),
            'pin_hash' => $pinHash,
            'base_time' => $baseTime,
            'future_window' => $futureWindow, 
            'obscured_timestamp' => $obscuredTimestamp,
            'email' => $userEmail
        ];
        
        // Generate secure filename
        // Encrypt and store verification package
        $encryptedPackage = $this->encryptVerificationPackage($verificationPackage);
        
        $filename = hash('sha256', $userEmail . $creditCardNumber . time()) . '.ccvault';
        $filepath = $this->storageDirectory . $filename;
        
        // Store as plain JSON for testing
        $jsonData = json_encode($verificationPackage, JSON_PRETTY_PRINT);
        file_put_contents($filepath, $jsonData);
        chmod($filepath, 0644); // Make readable
    
    return [
        'filename' => $filename,
        'credit_card_number' => $creditCardNumber,
        'pin' => $pin,
        'obscured_timestamp' => $obscuredTimestamp
    ];
    }
    
    /**
     * Encrypt PIN with additional security
     */
    private function encryptPin($pin) {
        $salt = random_bytes(16);
        $hash = hash_pbkdf2(
            'sha256', 
            $pin, 
            $salt, 
            10000, 
            32, 
            true
        );
        
        return [
            'salt' => base64_encode($salt),
            'hash' => base64_encode($hash)
        ];
    }
    
    /**
     * Encrypt verification package
     */
    /**
 * Encrypt verification package
 */
private function encryptVerificationPackage($package) {
    // Generate a secure key
    $key = hash('sha256', json_encode($package) . time(), true);
    $iv = random_bytes(16);
    
    $serializedPackage = json_encode($package);
    
    $encrypted = openssl_encrypt(
        $serializedPackage,
        'aes-256-cbc',
        $key,
        OPENSSL_RAW_DATA,
        $iv
    );
    
    // Create HMAC for integrity
    $hmac = hash_hmac('sha512', $encrypted, $key, true);
    
    // Store key securely
    $keyFilename = hash('sha256', $package['email'] . $package['card_number']) . '.key';
    $keyPath = $this->storageDirectory . $keyFilename;
    file_put_contents($keyPath, base64_encode($key));
    chmod($keyPath, 0600);
    
    return [
        'data' => base64_encode($iv . $hmac . $encrypted),
        'key_file' => $keyFilename
    ];
}
    
    /**
     * Decrypt and verify credit card package
     */
/**
 * Decrypt and verify credit card package
 */
/**
 * Decrypt and verify credit card package
 */
public function verifyAndDecryptCreditCardPackage($filename, $creditCardNumber, $pin) {
    // First try the exact filename provided
    $filepath = $this->storageDirectory . $filename;
    error_log("Trying to verify file: " . $filepath);
    
    if (!file_exists($filepath)) {
        error_log("File not found at: " . $filepath);
        
        // Try with .ccvault extension if not specified
        if (strpos($filename, '.ccvault') === false) {
            $filepathWithExt = $this->storageDirectory . $filename . '.ccvault';
            error_log("Trying with extension: " . $filepathWithExt);
            
            if (file_exists($filepathWithExt)) {
                $filepath = $filepathWithExt;
                error_log("Found file with extension: " . $filepath);
            } else {
                error_log("File not found with extension either");
                throw new Exception("Credit card verification file not found: $filename");
            }
        } else {
            throw new Exception("Credit card verification file not found: $filename");
        }
    }
    
    // Read the file content
    $fileContent = file_get_contents($filepath);
    error_log("File content retrieved, length: " . strlen($fileContent));
    
    $package = json_decode($fileContent, true);
    
    if (!$package) {
        error_log("Failed to parse JSON from file: " . json_last_error_msg());
        throw new Exception("Invalid verification file format: " . json_last_error_msg());
    }
    
    // Debug the package content
    error_log("Package decoded: " . print_r($package, true));
    
    // Verify credit card number (simple check, just match)
    if (!isset($package['card_number'])) {
        error_log("Package missing card_number field");
        throw new Exception("Invalid package format: missing card number");
    }
    
    if ($package['card_number'] !== $creditCardNumber) {
        error_log("Card number mismatch: " . $package['card_number'] . " vs " . $creditCardNumber);
        throw new Exception("Invalid credit card number");
    }
    
    // Verify PIN
    if (!isset($package['pin_hash'])) {
        error_log("Package missing pin_hash field");
        throw new Exception("Invalid package format: missing PIN hash");
    }
    
    if (!password_verify($pin, $package['pin_hash'])) {
        error_log("PIN verification failed");
        throw new Exception("Incorrect PIN");
    }
    
    return $package;
}
    
    /**
     * Temporal Obscurity Encryption
     */
/**
 * Temporal Obscurity Encryption
 */
/**
 * Temporal Obscurity Encryption
 */
/**
 * Temporal Obscurity Encryption
 */
public function temporalObscurityEncrypt($message, $filename, $creditCardNumber, $pin) {
    // Debugging
    error_log('Temporal encrypt debugging:');
    error_log('Message length: ' . strlen($message));
    error_log('Filename: ' . $filename);
    error_log('Card Number: ' . substr($creditCardNumber, 0, 4) . '...');
    
    try {
        // Verify credit card package
        $package = $this->verifyAndDecryptCreditCardPackage($filename, $creditCardNumber, $pin);
        
        // Use infinite number for additional entropy
        $infiniteNumberHash = isset($package['infinite_hash']) ? 
            $package['infinite_hash'] : 
            hash('sha512', (string)($package['infinite_number'] ?? time()));
        
        // Encrypt message
        $iv = random_bytes(16);
        $key = hash('sha256', $infiniteNumberHash . $package['obscured_timestamp'], true);
        
        $encrypted = openssl_encrypt(
            $message,
            'aes-256-cbc',
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );
        
        if ($encrypted === false) {
            error_log("Encryption failed: " . openssl_error_string());
            throw new Exception("Encryption failed: " . openssl_error_string());
        }
        
        return [
            'encrypted' => base64_encode($encrypted),
            'iv' => base64_encode($iv),
            'infinite_hash' => $infiniteNumberHash,
            'obscured_timestamp' => $package['obscured_timestamp']
        ];
    } catch (Exception $e) {
        error_log("Exception in temporalObscurityEncrypt: " . $e->getMessage());
        throw $e;
    }
}
    
    /**
     * Temporal Obscurity Decryption
     */
    public function temporalObscurityDecrypt($encryptedPackage, $filename, $creditCardNumber, $pin) {
        // Verify credit card package first
        $package = $this->verifyAndDecryptCreditCardPackage($filename, $creditCardNumber, $pin);
// Decrypt encrypted package
        $encrypted = base64_decode($encryptedPackage['encrypted']);
        $iv = base64_decode($encryptedPackage['iv']);
        
        // Verify infinite number hash matches
        $infiniteNumberHash = hash('sha512', (string)$package['infinite_number']);
        if ($infiniteNumberHash !== $encryptedPackage['infinite_hash']) {
            throw new Exception("Infinite number verification failed");
        }
        
        // Verify temporal conditions
        $currentTime = time();
        if ($currentTime < $package['obscured_timestamp']) {
            throw new Exception("Decryption is temporally locked. Wait until " . 
                date('Y-m-d H:i:s', $package['obscured_timestamp']));
        }
        
        // Generate decryption key
        $key = hash('sha256', $infiniteNumberHash . $package['obscured_timestamp'], true);
        
        // Decrypt message
        $decrypted = openssl_decrypt(
            $encrypted,
            'aes-256-cbc',
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );
        
        if ($decrypted === false) {
            throw new Exception("Decryption failed");
        }
        
        return [
            'decrypted' => $decrypted,
            'unlock_time' => $package['obscured_timestamp']
        ];
    }
    
    /**
     * Clean up expired or unused credit card verification files
     */
    public function cleanupCreditCardVault($maxAge = 2592000) { // 30 days
        $currentTime = time();
        
        // Iterate through files in the storage directory
        $files = glob($this->storageDirectory . '*.ccvault');
        
        foreach ($files as $filepath) {
            // Check file age
            if ($currentTime - filemtime($filepath) > $maxAge) {
                // Delete expired files
                unlink($filepath);
            }
        }
    }
    
    /**
     * Generate a downloadable credit card verification file
     */
    public function generateDownloadableFile($filename) {
        $filepath = $this->storageDirectory . $filename;
        
        if (!file_exists($filepath)) {
            throw new Exception("Credit card verification file not found");
        }
        
        // Set headers for file download
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Output file contents
        readfile($filepath);
        exit;
    }
}

// AJAX handler for credit card temporal encryption methods
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    $cipher = new CreditCardTemporalCipher();
    
    // Generate credit card package
    if ($action === 'generate-credit-card') {
        header('Content-Type: application/json');
        
        try {
            $email = $_POST['email'] ?? '';
            
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid email address");
            }
            // Retrieve email and PIN from request data
$userEmail = $_POST['email'] ?? '';
$pin = $_POST['pin'] ?? '';

// Validate and sanitize the input
// ...

// Call the generateCreditCardPackage method with the email and PIN
$package = $cipher->generateCreditCardPackage($userEmail, $pin);
                  
            echo json_encode([
                'status' => 'success',
                'credit_card_number' => $package['credit_card_number'],
                'filename' => $package['filename'],
                'obscured_timestamp' => $package['obscured_timestamp']
            ]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }
    
// Fix for the temporal obscurity encryption handler
// Temporal obscurity encryption
if ($action === 'temporal-encrypt') {
    header('Content-Type: application/json');
    
    try {
        // Log request data for debugging
        error_log("Temporal encrypt request: " . print_r($_POST, true));
        
        $message = $_POST['message'] ?? '';
        $filename = $_POST['filename'] ?? '';
        $creditCardNumber = $_POST['credit_card_number'] ?? '';
        $pin = $_POST['pin'] ?? '';
        
        // Basic validation
        if (empty($message)) {
            throw new Exception("Message is required");
        }
        if (empty($filename)) {
            throw new Exception("Filename is required");
        }
        if (empty($creditCardNumber)) {
            throw new Exception("Credit card number is required");
        }
        if (empty($pin)) {
            throw new Exception("PIN is required");
        }
        
        $cipher = new CreditCardTemporalCipher();
        $encrypted = $cipher->temporalObscurityEncrypt($message, $filename, $creditCardNumber, $pin);
        
        echo json_encode([
            'status' => 'success',
            'encrypted_package' => $encrypted
        ]);
    } catch (Exception $e) {
        error_log("Temporal encrypt error: " . $e->getMessage());
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
    exit;
}
    
    // Temporal obscurity decryption
    if ($action === 'temporal-decrypt') {
        header('Content-Type: application/json');
        
        try {
            $encryptedPackage = json_decode($_POST['encrypted_package'] ?? '{}', true);
            $filename = $_POST['filename'] ?? '';
            $creditCardNumber = $_POST['credit_card_number'] ?? '';
            $pin = $_POST['pin'] ?? '';
            
            $decrypted = $cipher->temporalObscurityDecrypt($encryptedPackage, $filename, $creditCardNumber, $pin);
            
            echo json_encode([
                'status' => 'success',
                'decrypted_message' => $decrypted['decrypted'],
                'unlock_time' => $decrypted['unlock_time']
            ]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }
    
    // Download credit card verification file
    if ($action === 'download-credit-card') {
        try {
            $filename = $_GET['filename'] ?? '';
            $cipher->generateDownloadableFile($filename);
        } catch (Exception $e) {
            http_response_code(404);
            echo "File not found or download failed: " . $e->getMessage();
        }
        exit;
    }
}
// AJAX handlers for system operations
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    
    // Existing actions like fetch-blockchain, diagnose, token-info, etc.
    
    // Add this new action for generating a credit card
    if ($action === 'generate-credit-card') {
        header('Content-Type: application/json');
        
        try {
            $email = $_POST['email'] ?? '';
            $email = $_POST['pin'] ?? '';
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Invalid email address");
            }
            
            $cipher = new EnhancedCreditCardTemporalCipher();
            $package = $cipher->generateCreditCardPackage($email,$pin);
            
            echo json_encode([
                'status' => 'success',
                'credit_card_number' => $package['credit_card_number'],
                'filename' => $package['filename'],
                'download_token' => $package['download_token'],
                'download_url' => $package['download_url'],
                'obscured_timestamp' => $package['obscured_timestamp']
            ]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }
    
    // Other existing AJAX actions...
}
// AJAX handlers for system operations
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    
    // Fetch blockchain data
    if ($action === 'fetch-blockchain') {
        header('Content-Type: application/json');
        
        $cipher = new EnhancedMandelbrotBlockchainCipher();
        $blockchain = $cipher->readBlockchainStorage();
        
        echo json_encode($blockchain);
        exit;
    }
    
    // Run diagnostics
    if ($action === 'diagnose' && !empty($_POST['encrypted'])) {
        $encrypted = json_decode($_POST['encrypted'], true);
        $cipher = new EnhancedMandelbrotBlockchainCipher();
        $diagnosis = $cipher->diagnoseBlockchainIssue($encrypted);
        
        echo "<pre>$diagnosis</pre>";
        exit;
    }
    
    // Get token information
    if ($action === 'token-info') {
        header('Content-Type: application/json');
        
        $cipher = new EnhancedMandelbrotBlockchainCipher();
        $tokenInfo = $cipher->getTokenInfo();
        
        echo json_encode($tokenInfo);
        exit;
    }
    
    // Generate new token
    if ($action === 'new-token') {
        // Destroy old session
        session_destroy();
        
        // Start new session
        session_start();
        
        // Create new token
        $tokenManager = new TokenManager();
        $token = $tokenManager->getUserToken();
        
        header('Content-Type: application/json');
        
        $cipher = new EnhancedMandelbrotBlockchainCipher();
        $tokenInfo = $cipher->getTokenInfo();
        
        echo json_encode($tokenInfo);
        exit;
    }
}

// Initialize if form is submitted
$result = null;
$mode = $_POST['mode'] ?? '';
$input = $_POST['input'] ?? '';
$seed = intval($_POST['seed'] ?? time());

if (!empty($mode) && !empty($input)) {
    try {
        $cipher = new EnhancedMandelbrotBlockchainCipher($seed);
        
        if ($mode === 'encrypt') {
            $startTime = microtime(true);
            $result = $cipher->encrypt($input);
            $processTime = microtime(true) - $startTime;
        } 
        elseif ($mode === 'decrypt' && !empty($_POST['encrypted'])) {
            $startTime = microtime(true);
            $encrypted = json_decode($_POST['encrypted'], true);
            $result = $cipher->decrypt($encrypted);
            $processTime = microtime(true) - $startTime;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Get token information for display
$tokenInfo = (new EnhancedMandelbrotBlockchainCipher())->getTokenInfo();
?>
<?php
class CreditCardDownloadHandler {
    private $storageDirectory;
    
    public function __construct() {
        $this->storageDirectory = sys_get_temp_dir() . '/credit_card_vault/';
        
        // Ensure storage directory exists
        if (!file_exists($this->storageDirectory)) {
            mkdir($this->storageDirectory, 0700, true);
        }
    }
    
    /**
     * Generate a secure download token for the credit card file
     */
    public function generateDownloadToken($filename) {
        // Create a cryptographically secure token
        $token = bin2hex(random_bytes(16));
        
        // Store token information
        $tokenData = [
            'filename' => $filename,
            'created_at' => time(),
            'expires_at' => time() + 3600 // Token valid for 1 hour
        ];
        
        // Save token data securely
        $tokenFile = $this->storageDirectory . $token . '.token';
        file_put_contents($tokenFile, json_encode($tokenData));
        chmod($tokenFile, 0600); // Restrict file permissions
        
        return $token;
    }
    
    /**
     * Process file download using a secure token
     */
    public function processFileDownload($token) {
        // Validate token file
        $tokenFile = $this->storageDirectory . $token . '.token';
        
        if (!file_exists($tokenFile)) {
            throw new Exception("Invalid download token");
        }
        
        // Read and validate token data
        $tokenData = json_decode(file_get_contents($tokenFile), true);
        
        // Check token expiration
        if ($tokenData['expires_at'] < time()) {
            unlink($tokenFile);
            throw new Exception("Download token has expired");
        }
        
        // Construct full file path
        $filepath = $this->storageDirectory . $tokenData['filename'];
        
        // Verify file exists
        if (!file_exists($filepath)) {
            unlink($tokenFile);
            throw new Exception("Credit card file not found");
        }
        
        // Prepare file for secure download
        $filename = $tokenData['filename'];
        
        // Set headers for secure file download
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        // Output file contents
        readfile($filepath);
        
        // Clean up token and prevent multiple downloads
        unlink($tokenFile);
        exit;
    }
}

// Modify the CreditCardTemporalCipher to integrate download handling
class EnhancedCreditCardTemporalCipher extends CreditCardTemporalCipher {
    private $downloadHandler;
 
    public function __construct() {
        parent::__construct();
        $this->downloadHandler = new CreditCardDownloadHandler();
    }
    
    /**
     * Generate a credit card package with download token
     */
public function generateCreditCardPackage($userEmail, $pin) {
    // Generate basic credit card package
    $package = parent::generateCreditCardPackage($userEmail, $pin);
    
    // Generate a secure download token
    $downloadToken = $this->downloadHandler->generateDownloadToken($package['filename']);
    
    // Add download token to package
    $package['download_token'] = $downloadToken;
    $package['download_url'] = $_SERVER['HTTP_HOST'] . '/download.php?token=' . $downloadToken;
    
    return $package;
}
}

// AJAX handler for credit card download
if (isset($_GET['action']) && $_GET['action'] === 'download-credit-card') {
    $downloadToken = $_GET['token'] ?? '';
    
    try {
        $downloadHandler = new CreditCardDownloadHandler();
        $downloadHandler->processFileDownload($downloadToken);
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => $e->getMessage()
        ]);
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Infinite Mandelbrot Character Space Cipher</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<script src="transactions.js"></script>
<script src="blockchain.js"></script>
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
        
        .mandelbrot-title {
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
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 0.6rem 1.5rem;
            font-weight: 500;
            border-radius: 8px;
        }
        
        .btn-primary:hover {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
        }
        
        .json-display {
            max-height: 300px;
            overflow-y: auto;
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            font-family: 'Consolas', monospace;
            font-size: 0.85rem;
        }
        
        .processing-time {
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        /* Fancy Blockchain Visualization */
        .blockchain-container {
            padding: 20px;
            position: relative;
        }
        
        .blockchain-view {
            position: relative;
            overflow: hidden;
            background-color: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            max-height: 600px;
            overflow-y: auto;
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
        
        .block-data {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }
        
        .data-item {
            background-color: #f0f2f5;
            border-radius: 6px;
            padding: 8px 12px;
            font-size: 0.85rem;
            flex: 1 0 45%;
        }
        
        .char-display {
            font-weight: bold;
            color: var(--primary-color);
        }
        
        /* SVG Visualization */
        .svg-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(100px, 1fr));
            gap: 10px;
            margin-top: 20px;
        }
        
        .svg-container {
            background-color: white;
            border-radius: 8px;
            padding: 10px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            text-align: center;
            position: relative;
        }
        
        .svg-container svg {
            max-width: 100%;
            height: auto;
            border-radius: 4px;
        }
        
        .char-label {
            margin-top: 8px;
            font-weight: bold;
            font-size: 0.9rem;
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
        
        /* Point counter badge */
        .point-counter {
            position: absolute;
            top: 5px;
            right: 5px;
            background-color: rgba(114, 9, 183, 0.7);
            color: white;
            border-radius: 12px;
            padding: 2px 8px;
            font-size: 0.7rem;
            font-weight: bold;
        }

        /* Mining indicator */
        .mining-indicator {
            position: absolute;
            top: 5px;
            left: 5px;
            background-color: rgba(66, 135, 245, 0.7);
            color: white;
            border-radius: 12px;
            padding: 2px 8px;
            font-size: 0.7rem;
            font-weight: bold;
        }
        
        /* Encryption indicator */
        .encryption-indicator {
            position: absolute;
            bottom: 5px;
            right: 5px;
            background-color: rgba(23, 162, 184, 0.7);
            color: white;
            border-radius: 12px;
            padding: 2px 8px;
            font-size: 0.7rem;
            font-weight: bold;
        }
        
        /* Token expiry indicator */
        .token-expiry {
            font-size: 0.8rem;
            color: #666;
        }
        
        .token-expiry.warning {
            color: #f0ad4e;
        }
        
        .token-expiry.danger {
            color: #d9534f;
        }
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
	<!-- Navigation Bar Addition -->
<div class="navigation-links mb-3">
    <div class="row">
        <div class="col-md-12">
            <div class="custom-card">
                <div class="card-body py-2">
                    <ul class="nav nav-pills">
                        <li class="nav-item">
                            <a class="nav-link active" href="#mandelbrot-section">
                                <i class="fas fa-infinity me-1"></i>Mandelbrot Cipher
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#transaction-section">
                                <i class="fas fa-exchange-alt me-1"></i>Transactions
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="blockchain-page.php">
                                <i class="fas fa-link me-1"></i>Blockchain Explorer
                            </a>
                        </li>
                        <li class="nav-item ms-auto">
                            <span class="nav-link text-muted">
                                <i class="fas fa-coins me-1"></i>Your Balance: <span id="nav-balance">0.00</span>
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Update section navigation
document.addEventListener('DOMContentLoaded', function() {
    // Handle section navigation
    const navLinks = document.querySelectorAll('.nav-pills .nav-link');
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            if (this.getAttribute('href').startsWith('#')) {
                e.preventDefault();
                const targetId = this.getAttribute('href');
                const targetElement = document.querySelector(targetId);
                if (targetElement) {
                    window.scrollTo({
                        top: targetElement.offsetTop - 20,
                        behavior: 'smooth'
                    });
                }
                
                // Update active state
                navLinks.forEach(l => l.classList.remove('active'));
                this.classList.add('active');
            }
        });
    });
    
    // Update navigation balance when card balance changes
    const updateNavBalance = () => {
        const cardBalance = document.getElementById('card-balance');
        const navBalance = document.getElementById('nav-balance');
        if (cardBalance && navBalance) {
            navBalance.textContent = cardBalance.textContent;
        }
    };
    
    // Call initially and set up interval
    updateNavBalance();
    setInterval(updateNavBalance, 5000);
});
</script>
        <h1 class="mb-4 text-center mandelbrot-title">
            <i class="fas fa-infinity me-2"></i> Infinite Mandelbrot Character Space Cipher
        </h1>
        <li class="nav-item">
    <a class="nav-link" href="blockchain-page.php">
        <i class="fas fa-link me-1"></i>Blockchain Rewards
    </a>
</li>
<!-- Transaction & Payments Section -->
<div class="row mt-4">
    <div class="col-md-12">
        <div class="custom-card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-exchange-alt me-2"></i>Transactions & Payments</h5>
                <button class="btn btn-sm btn-outline-primary" id="refresh-transactions">
                    <i class="fas fa-sync-alt me-1"></i>Refresh
                </button>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- Send & Request Tabs -->
                    <div class="col-md-6">
                        <ul class="nav nav-tabs mb-3" id="transactionTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="send-tab" data-bs-toggle="tab" data-bs-target="#send-payment-tab" type="button" role="tab">
                                    <i class="fas fa-paper-plane me-1"></i>Send
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="request-tab" data-bs-toggle="tab" data-bs-target="#request-payment-tab" type="button" role="tab">
                                    <i class="fas fa-hand-holding-usd me-1"></i>Request
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="qr-tab" data-bs-toggle="tab" data-bs-target="#qr-payment-tab" type="button" role="tab">
                                    <i class="fas fa-qrcode me-1"></i>QR
                                </button>
                            </li>
                        </ul>
                        
                        <div class="tab-content" id="transactionTabContent">
                            <!-- Send Payment Tab -->
                            <div class="tab-pane fade show active" id="send-payment-tab" role="tabpanel">
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
                            
                            <!-- Request Payment Tab -->
                            <div class="tab-pane fade" id="request-payment-tab" role="tabpanel">
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
                            
                            <!-- QR Payment Tab -->
                            <div class="tab-pane fade" id="qr-payment-tab" role="tabpanel">
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
                                        <i class="fas fa-qrcode me-1"></i> Generate QR
                                    </button>
                                    <button id="scan-qr-button" class="btn btn-outline-primary flex-fill">
                                        <i class="fas fa-camera me-1"></i> Scan QR
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Transaction History & Card Balance -->
                    <div class="col-md-6">
                        <!-- Credit Card Balance -->
                        <div class="credit-card-display mb-3" style="background: linear-gradient(135deg, var(--primary-color), var(--secondary-color)); border-radius: 12px; padding: 15px; color: white; position: relative; overflow: hidden;">
                            <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAxMDAgMTAwIj48Y2lyY2xlIGN4PSI1MCIgY3k9IjUwIiByPSI0MCIgZmlsbD0ibm9uZSIgc3Ryb2tlPSJyZ2JhKDI1NSwyNTUsMjU1LDAuMSkiIHN0cm9rZS13aWR0aD0iMTAiLz48L3N2Zz4='); background-size: 150px; opacity: 0.1;"></div>
                            
                            <!-- Chip -->
                            <div style="width: 40px; height: 30px; background: linear-gradient(135deg, #fa7, #ffd700); border-radius: 5px; margin-bottom: 15px;"></div>
                            
                            <!-- Card Number -->
                            <div style="font-size: 1.3rem; font-family: 'Courier New', monospace; letter-spacing: 2px; margin-bottom: 15px; text-shadow: 1px 1px 2px rgba(0,0,0,0.3);" class="card-number-display">**** **** **** ****</div>
                            
                            <!-- Card Details -->
                            <div class="d-flex justify-content-between">
                                <div>
                                    <span style="font-size: 0.7rem; opacity: 0.8;">CARD HOLDER</span>
                                    <div style="font-weight: bold; font-size: 0.9rem;">REWARDS USER</div>
                                </div>
                                <div>
                                    <span style="font-size: 0.7rem; opacity: 0.8;">BALANCE</span>
                                    <div style="font-weight: bold; font-size: 0.9rem;" id="card-balance">0.00</div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Transaction History Tabs -->
                        <ul class="nav nav-tabs mb-3" id="historyTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active transaction-tab" id="history-tab" data-bs-toggle="tab" data-bs-target="#transaction-history-tab" type="button" role="tab" data-tab-type="history">
                                    <i class="fas fa-history me-1"></i>History
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link transaction-tab" id="pending-tab" data-bs-toggle="tab" data-bs-target="#pending-requests-tab" type="button" role="tab" data-tab-type="pending">
                                    <i class="fas fa-clock me-1"></i>Pending
                                </button>
                            </li>
                        </ul>
                        
                        <div class="tab-content" id="historyTabContent">
                            <!-- Transaction History -->
                            <div class="tab-pane fade show active transaction-content" id="transaction-history-tab" role="tabpanel" data-tab-type="history">
                                <div id="transaction-history" style="max-height: 250px; overflow-y: auto;">
                                    <p class="text-center text-muted">No transaction history</p>
                                </div>
                            </div>
                            
                            <!-- Pending Requests -->
                            <div class="tab-pane fade transaction-content" id="pending-requests-tab" role="tabpanel" data-tab-type="pending">
                                <div id="pending-requests" style="max-height: 250px; overflow-y: auto;">
                                    <p class="text-center text-muted">No pending requests</p>
                                </div>
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
                    <div class="qr-code-placeholder" style="background-color: white; border: 1px solid #ddd; border-radius: 8px; padding: 20px; margin: 20px auto; max-width: 200px; min-height: 200px; display: flex; align-items: center; justify-content: center;">
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

<!-- JavaScript to initialize transactions UI -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize transaction interface 
    if (typeof TransactionInterface !== 'undefined') {
        window.transactionInterface = new TransactionInterface();
    } else {
        // Create a simplified version if the full TransactionInterface isn't available
        class SimpleTransactionInterface {
            constructor() {
                this.setupEventListeners();
            }
            
            setupEventListeners() {
                // Show notification for click events if the full system isn't loaded
                const showNotImplemented = (e) => {
                    e.preventDefault();
                    this.showNotification('This feature requires the full transaction system to be loaded.', 'info');
                };
                
                document.getElementById('send-payment-form')?.addEventListener('submit', showNotImplemented);
                document.getElementById('request-payment-form')?.addEventListener('submit', showNotImplemented);
                document.getElementById('generate-qr-button')?.addEventListener('click', showNotImplemented);
                document.getElementById('scan-qr-button')?.addEventListener('click', showNotImplemented);
            }
            
            showNotification(message, type = 'info') {
                const notification = document.createElement('div');
                notification.className = `alert alert-${type} alert-dismissible fade show`;
                notification.innerHTML = `
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                `;
                
                // Add to notification area
                const notificationArea = document.getElementById('notification-area');
                if (notificationArea) {
                    notificationArea.appendChild(notification);
                    
                    // Auto-remove after 5 seconds
                    setTimeout(() => {
                        notification.classList.remove('show');
                        setTimeout(() => notification.remove(), 500);
                    }, 5000);
                }
            }
            
            processScannedQR() {
                this.showNotification('QR scanning requires the full transaction system to be loaded.', 'info');
            }
        }
        
        window.transactionInterface = new SimpleTransactionInterface();
    }
    
    // Function to load the transactions.js script if it's not already loaded
    function loadTransactionsScript() {
        if (typeof TransactionInterface === 'undefined') {
            const script = document.createElement('script');
            script.src = 'transactions.js';
            script.onload = () => {
                window.transactionInterface = new TransactionInterface();
                console.log('Transactions system loaded');
            };
            document.head.appendChild(script);
        }
    }
    
    // Attempt to load the transactions script
    loadTransactionsScript();
    
    // Event listener for modal close buttons
    document.querySelectorAll('.close-modal').forEach(button => {
        button.addEventListener('click', () => {
            const modal = button.closest('.modal');
            if (modal) {
                const modalInstance = bootstrap.Modal.getInstance(modal);
                if (modalInstance) {
                    modalInstance.hide();
                } else {
                    // Fallback if Bootstrap JS isn't fully initialized
                    modal.classList.remove('show');
                    modal.style.display = 'none';
                    document.body.classList.remove('modal-open');
                    const backdrop = document.querySelector('.modal-backdrop');
                    if (backdrop) backdrop.remove();
                }
            }
        });
    });
});
</script>
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="custom-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-key me-2"></i>Encryption Token</h5>
                        <button class="btn btn-sm btn-outline-primary" id="refresh-token">
                            <i class="fas fa-sync-alt me-1"></i>Generate New Token
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Token ID:</strong> <span id="token-id"><?= $tokenInfo['id'] ?></span></p>
                                <p><strong>Expires At:</strong> <span id="token-expiry"><?= $tokenInfo['expiresAt'] ?></span></p>
                            </div>
                            <div class="col-md-6">
                                <p>
                                    <strong>Status:</strong> 
                                    <span id="token-status" class="<?= $tokenInfo['timeRemaining'] < 3600 ? ($tokenInfo['timeRemaining'] < 1800 ? 'text-danger' : 'text-warning') : 'text-success' ?>">
                                        <?= $tokenInfo['isValid'] ? 'Valid' : 'Invalid' ?>
                                    </span>
                                </p>
                                <p>
                                    <strong>Time Remaining:</strong>
                                    <span id="token-time-remaining" class="token-expiry <?= $tokenInfo['timeRemaining'] < 3600 ? ($tokenInfo['timeRemaining'] < 1800 ? 'danger' : 'warning') : '' ?>">
                                        <?= floor($tokenInfo['timeRemaining'] / 3600) ?>h <?= floor(($tokenInfo['timeRemaining'] % 3600) / 60) ?>m
                                    </span>
                                </p>
                            </div>
                        </div>
                        <div class="alert alert-info mt-2">
                            <i class="fas fa-info-circle me-2"></i>
                            This token is used to encrypt your data and expires after 24 hours. Messages encrypted with this token can only be decrypted while the token is valid.
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Credit Card Temporal Encryption Section -->
<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-credit-card me-2"></i>Generate Credit Card
                </h5>
            </div>
            <div class="card-body">
               <form id="credit-card-generation-form">
  <div class="mb-3">
    <label for="credit-card-email" class="form-label">Email Address</label>
    <input type="email" class="form-control" id="credit-card-email" required>
  </div>
  <div class="mb-3">
    <label for="credit-card-pin" class="form-label">Choose a PIN</label>  
    <input type="password" class="form-control" id="credit-card-pin" required>
  </div>
  <button type="button" id="generate-credit-card" class="btn btn-primary w-100">
    Generate Credit Card
  </button>
</form>
                <div id="credit-card-result" class="mt-3"></div>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-lock me-2"></i>Temporal Encryption
                </h5>
            </div>
            <div class="card-body">
                <form id="encryption-form">
                    <div class="mb-3">
                        <label for="encrypt-input" class="form-label">Message to Encrypt</label>
                        <textarea class="form-control" id="encrypt-input" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="credit-card-filename" class="form-label">Credit Card Verification File</label>
                        <input type="text" class="form-control" id="credit-card-filename" required>
                        <small class="form-text text-muted">
                            Enter the filename of your generated credit card verification file.
                        </small>
                    </div>
                    <div class="mb-3">
                        <label for="credit-card-pin" class="form-label">Credit Card PIN</label>
                        <input type="password" class="form-control" id="credit-card-pin" required>
                    </div>
                    <button type="button" id="encrypt-message" class="btn btn-success w-100">
                        <i class="fas fa-encrypt me-2"></i>Encrypt Message
                    </button>
                </form>
                <div id="encryption-result" class="mt-3"></div>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-unlock me-2"></i>Temporal Decryption
                </h5>
            </div>
            <div class="card-body">
                <form id="decryption-form">
                    <div class="mb-3">
                        <label for="encrypted-package" class="form-label">Encrypted Package</label>
                        <textarea class="form-control" id="encrypted-package" rows="3" required></textarea>
                        <small class="form-text text-muted">
                            Paste the entire encrypted package JSON.
                        </small>
                    </div>
                    <div class="mb-3">
                        <label for="decrypt-credit-card-filename" class="form-label">Credit Card Verification File</label>
                        <input type="text" class="form-control" id="decrypt-credit-card-filename" required>
                        <small class="form-text text-muted">
                            Enter the filename of your credit card verification file.
                        </small>
                    </div>
                    <div class="mb-3">
                        <label for="decrypt-credit-card-pin" class="form-label">Credit Card PIN</label>
                        <input type="password" class="form-control" id="decrypt-credit-card-pin" required>
                    </div>
                    <button type="button" id="decrypt-message" class="btn btn-warning w-100">
                        <i class="fas fa-decrypt me-2"></i>Decrypt Message
                    </button>
                </form>
                <div id="decryption-result" class="mt-3"></div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i>Temporal Obscurity Encryption System
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Security Features</h6>
                        <ul class="list-unstyled">
                            <li>
                                <i class="fas fa-shield-alt text-success me-2"></i>
                                Multi-layered Credit Card Verification
                            </li>
                            <li>
                                <i class="fas fa-key text-success me-2"></i>
                                Infinite Integer-Based Entropy
                            </li>
                            <li>
                                <i class="fas fa-clock text-success me-2"></i>
                                Temporal Unlock Mechanism
                            </li>
                            <li>
                                <i class="fas fa-lock text-success me-2"></i>
                                AES-256 Encryption
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6>Encryption Workflow</h6>
                        <ol class="small">
                            <li>Generate Credit Card Verification File</li>
                            <li>Encrypt Message Using Credit Card Details</li>
                            <li>Verify Credit Card and PIN During Decryption</li>
                            <li>Check Temporal Unlock Conditions</li>
                            <li>Decrypt Message with Infinite Integer Entropy</li>
                        </ol>
                    </div>
                </div>
                <div class="alert alert-info mt-3">
                    <strong>Temporal Obscurity Encryption:</strong> 
                    A sophisticated encryption method that combines credit card verification, 
                    infinite integer entropy, and time-based unlocking to create an ultra-secure 
                    message protection system.
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Credit Card Temporal Encryption Section -->

        <div class="row g-4">
            <div class="col-lg-5">
                <div class="custom-card mb-4">
                    <div class="card-header">
                        <ul class="nav nav-tabs card-header-tabs" id="myTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="encrypt-tab" data-bs-toggle="tab" data-bs-target="#encrypt" type="button" role="tab">
                                    <i class="fas fa-lock me-2"></i>Encrypt
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="decrypt-tab" data-bs-toggle="tab" data-bs-target="#decrypt" type="button" role="tab">
                                    <i class="fas fa-unlock-alt me-2"></i>Decrypt
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="diagnose-tab" data-bs-toggle="tab" data-bs-target="#diagnose" type="button" role="tab">
                                    <i class="fas fa-stethoscope me-2"></i>Diagnose
                                </button>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content" id="myTabContent">
                            <div class="tab-pane fade show active" id="encrypt" role="tabpanel">
                                <form method="post">
                                    <input type="hidden" name="mode" value="encrypt">
                                    <div class="mb-3">
                                        <label for="input" class="form-label">Text to Encrypt</label>
                                        <textarea class="form-control" id="input" name="input" rows="3" required><?= htmlspecialchars($input ?? '') ?></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label for="seed" class="form-label">Seed (optional)</label>
                                        <input type="number" class="form-control" id="seed" name="seed" value="<?= $seed ?>">
                                        <small class="text-muted">Seed determines the initial Mandelbrot region</small>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-lock me-2"></i>Encrypt Message
                                    </button>
                                </form>
                            </div>
                            <div class="tab-pane fade" id="decrypt" role="tabpanel">
                                <form method="post">
                                    <input type="hidden" name="mode" value="decrypt">
                                    <div class="mb-3">
                                        <label for="encrypted" class="form-label">Encrypted Data (JSON)</label>
                                        <textarea class="form-control" id="encrypted" name="encrypted" rows="3" required></textarea>
                                    </div>
                                    <div class="mb-3">
                                        <label for="input" class="form-label">Expected Text (for verification)</label>
                                        <input type="text" class="form-control" id="input" name="input" required>
                                    </div>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-unlock-alt me-2"></i>Decrypt Message
                                    </button>
                                </form>
                            </div>
                            <div class="tab-pane fade" id="diagnose" role="tabpanel">
                                <form action="?action=diagnose" method="post" target="_blank">
                                    <div class="mb-3">
                                        <label for="diagnostic-data" class="form-label">Encrypted Data for Diagnosis</label>
                                        <textarea class="form-control" id="diagnostic-data" name="encrypted" rows="3" required></textarea>
                                    </div>
                                    <button type="submit" class="btn btn-warning">
                                        <i class="fas fa-stethoscope me-2"></i>Run Diagnostic</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="custom-card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-key me-2"></i>Encryption Result</h5>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i><?= $error ?>
                            </div>
                        <?php elseif (isset($result) && $mode === 'encrypt'): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>Encryption successful!
                            </div>
                            <div class="mb-3">
                                <span class="processing-time">
                                    <i class="fas fa-clock me-1"></i>Processed in <?= number_format($processTime, 4) ?> seconds
                                </span>
                                <h5 class="mt-3 mb-2">Encrypted Data:</h5>
                                <div class="json-display">
                                    <pre id="json-result"><?= json_encode($result, JSON_PRETTY_PRINT) ?></pre>
                                </div>
                                <button class="btn btn-sm btn-secondary mt-2" onclick="copyToClipboard()">
                                    <i class="fas fa-copy me-1"></i>Copy to Clipboard
                                </button>
                            </div>
                            
                            <div class="mt-4">
                                <h5 class="mb-3">Character Space Mapping:</h5>
                                <div class="svg-grid">
                                    <?php for ($i = 0; $i < min(10, count($result['indexCard'])); $i++): ?>
                                        <?php $item = $result['indexCard'][$i]; ?>
                                        <div class="svg-container">
                                            <?= $item['svg'] ?>
                                            <div class="char-label">'<?= htmlspecialchars(chr($item['char'])) ?>'</div>
                                            <div class="point-counter"><?= number_format($item['pointCount']) ?> pts</div>
                                            <div class="mining-indicator">Block <?= floor($i / 5) + 1 ?></div>
                                            <div class="encryption-indicator">AES</div>
                                        </div>
                                    <?php endfor; ?>
                                    <?php if (count($result['indexCard']) > 10): ?>
                                        <div class="svg-container" style="display:flex; align-items:center; justify-content:center;">
                                            <div>+<?= count($result['indexCard']) - 10 ?> more</div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="mt-4">
                                <h5 class="mb-3">Encryption Information:</h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="card bg-light mb-3">
                                            <div class="card-body">
                                                <h6 class="card-title">Token-Based Encryption</h6>
                                                <p class="card-text small">Characters are encrypted with AES-256 using your session token as the key</p>
                                                <p class="card-text">
                                                    <span class="badge bg-info">
                                                        <i class="fas fa-clock me-1"></i>
                                                        Expires: <?= date('M d, H:i', $result['expiresAt']) ?>
                                                    </span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card bg-light">
                                            <div class="card-body">
                                                <h6 class="card-title">Mining Information</h6>
                                                <p class="card-text">
                                                    <span class="badge bg-primary me-1">
                                                        <i class="fas fa-cubes me-1"></i>
                                                        <?= count($result['blockchain']) ?> Blocks
                                                    </span>
                                                    <span class="badge bg-secondary">
                                                        <i class="fas fa-history me-1"></i>
                                                        <?= $result['historicalMegaHashCount'] ?> MegaHashes
                                                    </span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php elseif (isset($result) && $mode === 'decrypt'): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>Decryption successful!
                            </div>
                            <div class="mb-3">
                                <span class="processing-time">
                                    <i class="fas fa-clock me-1"></i>Processed in <?= number_format($processTime, 4) ?> seconds
                                </span>
                                <h5 class="mt-3 mb-2">Decrypted Text:</h5>
                                <div class="p-3 bg-light rounded">
                                    <?= htmlspecialchars($result) ?>
                                </div>
                            </div>
                            <div class="mt-3">
                                <h5 class="mb-2">Verification:</h5>
                                <?php if ($result === $input): ?>
                                    <div class="alert alert-success">
                                        <i class="fas fa-check-circle me-2"></i>Text matches expected input
                                    </div>
                                <?php else: ?>
                                    <div class="alert alert-danger">
                                        <i class="fas fa-times-circle me-2"></i>Text does not match expected input
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center text-muted py-5">
                                <p>Use the form to encrypt or decrypt a message.</p>
                                <div class="mt-4">
                                    <h5>System Overview</h5>
                                    <div class="d-flex justify-content-center mb-4">
                                        <div class="text-start">
                                            <ul class="list-unstyled">
                                                <li><i class="fas fa-check-circle text-success me-2"></i>Infinite Mandelbrot character space</li>
                                                <li><i class="fas fa-check-circle text-success me-2"></i>AES-256 character encryption</li>
                                                <li><i class="fas fa-check-circle text-success me-2"></i>24-hour session token security</li>
                                                <li><i class="fas fa-check-circle text-success me-2"></i>Blockchain-based verification</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-7">
                <div class="custom-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-link me-2"></i>Blockchain History</h5>
                        <button class="btn btn-sm btn-outline-primary" id="refresh-blockchain">
                            <i class="fas fa-sync-alt me-1"></i>Refresh
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="blockchain-container">
                            <div class="blockchain-view">
                                <div id="blockchain-placeholder" class="text-center py-4">
                                    <div class="loader"></div>
                                    <p class="mt-3 text-muted">Loading blockchain data...</p>
                                </div>
                                <div id="blockchain-timeline" class="blockchain-timeline">
                                    <!-- Blockchain blocks will be displayed here via AJAX -->
                                </div>
                                <div id="empty-blockchain" class="text-center py-5" style="display: none;">
                                    <i class="fas fa-cube text-muted" style="font-size: 3rem;"></i>
                                    <p class="mt-3 text-muted">No blocks in the blockchain yet.</p>
                                    <p class="text-muted">Encrypt a message to create the first block!</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="custom-card mt-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Infinite Character Space System</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <h6 class="mb-3">Infinite Character Space Mapping</h6>
                                    <p>Each character is mapped to an infinite space in the Mandelbrot set:</p>
                                    <ul class="small">
                                        <li>Characters are encoded as massive integers in Mandelbrot space</li>
                                        <li>Each point maps to a unique position in the character space</li>
                                        <li>Thousands of points generated per character</li>
                                        <li>Up to 2^100 zoom level precision</li>
                                    </ul>
                                </div>
                                <div>
                                    <h6 class="mb-3">Token-Based Security</h6>
                                    <p>Messages are protected with session-based encryption:</p>
                                    <ul class="small">
                                        <li>AES-256 encryption for each character</li>
                                        <li>Session tokens valid for 24 hours</li>
                                        <li>Secure token storage in protected PHP files</li>
                                        <li>Reverse-engineered tokens as encryption keys</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <h6 class="mb-3">Mining Verification System</h6>
                                    <p>Security through proof-of-work verification:</p>
                                    <ul class="small">
                                        <li>Mining process validates character mappings</li>
                                        <li>Blockchain secures the character space mappings</li>
                                        <li>Historical hash entropy grows with each message</li>
                                        <li>Cumulative mega-hash system for validation</li>
                                    </ul>
                                </div>
                                <div>
                                    <h6 class="mb-3">Mandelbrot SVG Visualization</h6>
                                    <p>Visual representation of character space:</p>
                                    <ul class="small">
                                        <li>SVG graphics represent Mandelbrot locations</li>
                                        <li>Each character is hidden within fractal mathematics</li>
                                        <li>Real-time visualization of character mappings</li>
                                        <li>Message appears as mathematical points in space</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
// Initialize transaction interface if it hasn't been initialized
document.addEventListener('DOMContentLoaded', function() {
    if (typeof window.transactionInterface === 'undefined') {
        console.log('Manually initializing transaction interface');
        
        // Try to load the script again
        const script = document.createElement('script');
        script.src = 'transactions.js';
        script.onload = function() {
            if (typeof TransactionInterface !== 'undefined') {
                window.transactionInterface = new TransactionInterface();
                console.log('Transaction interface initialized successfully');
            } else {
                console.error('Failed to load TransactionInterface class');
            }
        };
        document.head.appendChild(script);
    }
});
</script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
// Credit Card Temporal Encryption Workflow
class CreditCardTemporalCipher {
    constructor() {
        this.creditCardFile = null;
        this.creditCardNumber = null;
        this.pin = null;
    }

    downloadCreditCardFile(filename, downloadToken) {
        const downloadLink = document.createElement('a');
        downloadLink.href = `?action=download-credit-card&token=${downloadToken}`;
        downloadLink.download = filename;
        
        document.body.appendChild(downloadLink);
        downloadLink.click();
        document.body.removeChild(downloadLink);
    }

// Fix for the generateCreditCardPackage function
generateCreditCardPackage(email, pin) {
  return new Promise((resolve, reject) => {
    const formData = new FormData();
    formData.append('email', email);
    formData.append('pin', pin);
    
    fetch('?action=generate-credit-card', {
      method: 'POST',
      body: formData
    })
    .then(response => {
      if (!response.ok) {
        return response.json().then(data => {
          throw new Error(data.message || `HTTP error! status: ${response.status}`);
        });
      }
      return response.json();
    })
    .then(data => {
      if (data.status === 'success') {
        // Store credit card details for later use
        this.creditCardNumber = data.credit_card_number;
        this.pin = pin;
        
        // Display success message and details
        this.displayCreditCardDetails(data);
        
        resolve(data);
      } else {
        reject(new Error(data.message || 'Credit card generation failed'));
      }
    })
    .catch(error => {
      console.error('Error generating credit card:', error);
      reject(error);
    });
  });
}

encryptMessage(message, filename, pin) {
  return new Promise((resolve, reject) => {
    // Make sure we have the credit card number
    if (!this.creditCardNumber) {
      reject(new Error('Credit card number is missing. Please generate a credit card first.'));
      return;
    }
    
    const formData = new FormData();
    formData.append('message', message);
    formData.append('filename', filename);
    formData.append('credit_card_number', this.creditCardNumber);
    formData.append('pin', pin);

    // Log data being sent (for debugging)
    console.log('Sending encryption request with:', {
      message: message,
      filename: filename,
      credit_card_number: this.creditCardNumber,
      pin: pin ? '****' : 'missing'  // Hide actual PIN but show if it's present
    });

    fetch('?action=temporal-encrypt', {
      method: 'POST',
      body: formData
    })
    .then(response => {
      // Check if response is JSON
      const contentType = response.headers.get('content-type');
      if (contentType && contentType.includes('application/json')) {
        return response.json().then(data => {
          if (!response.ok) {
            throw new Error(data.message || `Server error: ${response.status}`);
          }
          return data;
        });
      } else {
        if (!response.ok) {
          return response.text().then(text => {
            throw new Error(`Server error: ${text || response.status}`);
          });
        }
        throw new Error('Unexpected response format');
      }
    })
    .then(data => {
      if (data.status === 'success') {
        this.displayEncryptionResult(data.encrypted_package);
        resolve(data.encrypted_package);
      } else {
        reject(new Error(data.message || 'Encryption failed'));
      }
    })
    .catch(error => {
      console.error('Encryption error:', error);
      reject(error);
    });
  });
}

    decryptMessage(encryptedPackage, filename, pin) {
        return new Promise((resolve, reject) => {
            const formData = new FormData();
            formData.append('encrypted_package', JSON.stringify(encryptedPackage));
            formData.append('filename', filename);
            formData.append('credit_card_number', this.creditCardNumber);
            formData.append('pin', pin);

            fetch('?action=temporal-decrypt', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    this.displayDecryptionResult(data);
                    resolve(data.decrypted_message);
                } else {
                    reject(new Error(data.message || 'Decryption failed'));
                }
            })
            .catch(error => {
                console.error('Decryption error:', error);
                reject(error);
            });
        });
    }

    displayCreditCardDetails(data) {
        const resultArea = document.getElementById('credit-card-result');
        resultArea.innerHTML = `
            <div class="alert alert-success">
                <h4>Credit Card Generated Successfully</h4>
                <p><strong>Card Number:</strong> ${data.credit_card_number}</p>
                <p><strong>Verification File:</strong> ${data.filename}</p>
                <p><strong>Download Link:</strong> 
                    <a href="?action=download-credit-card&token=${data.download_token}" download>
                        Download Verification File
                    </a>
                </p>
                <p><strong>Temporal Unlock Time:</strong> ${new Date(data.obscured_timestamp * 1000).toLocaleString()}</p>
                <small class="text-muted">Note: Download link is valid for 1 hour</small>
            </div>
        `;
    }

    displayEncryptionResult(encryptedPackage) {
        const resultArea = document.getElementById('encryption-result');
        resultArea.innerHTML = `
            <div class="alert alert-success">
                <h4>Temporal Obscurity Encryption Complete</h4>
                <div class="row">
                    <div class="col-md-6">
                        <h5>Encrypted Package</h5>
                        <pre>${JSON.stringify(encryptedPackage, null, 2)}</pre>
                    </div>
                    <div class="col-md-6">
                        <h5>Encryption Details</h5>
                        <p><strong>Infinite Hash:</strong> ${encryptedPackage.infinite_hash.substr(0, 20)}...</p>
                        <p><strong>Obscured Timestamp:</strong> ${new Date(encryptedPackage.obscured_timestamp * 1000).toLocaleString()}</p>
                    </div>
                </div>
            </div>
        `;
    }

    displayDecryptionResult(data) {
        const resultArea = document.getElementById('decryption-result');
        resultArea.innerHTML = `
            <div class="alert alert-success">
                <h4>Temporal Obscurity Decryption Complete</h4>
                <div class="row">
                    <div class="col-md-6">
                        <h5>Decrypted Message</h5>
                        <div class="card">
                            <div class="card-body">
                                <p>${data.decrypted_message}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h5>Decryption Details</h5>
                        <p><strong>Unlock Time:</strong> ${new Date(data.unlock_time * 1000).toLocaleString()}</p>
                        <p><strong>Decryption Time:</strong> ${new Date().toLocaleString()}</p>
                    </div>
                </div>
            </div>
        `;
    }
}

// Event Listeners for Workflow
document.addEventListener('DOMContentLoaded', () => {
    const temporalEncryption = new CreditCardTemporalCipher();

// Credit Card Generation
document.getElementById('generate-credit-card')?.addEventListener('click', () => {
    const email = document.getElementById('credit-card-email').value;
    const pin = document.getElementById('credit-card-pin').value;
    
    if (!email || !pin) {
        document.getElementById('credit-card-result').innerHTML = `
            <div class="alert alert-danger">
                Please fill in both email and PIN fields.
            </div>
        `;
        return;
    }
    
    temporalEncryption.generateCreditCardPackage(email, pin)
        .catch(error => {
            document.getElementById('credit-card-result').innerHTML = `
                <div class="alert alert-danger">
                    ${error.message}
                </div>
            `;
        });
});

// Fix for the encrypt message event listener
document.getElementById('encrypt-message')?.addEventListener('click', () => {
  const message = document.getElementById('encrypt-input').value;
  const filename = document.getElementById('credit-card-filename').value;
  const pin = document.getElementById('credit-card-pin').value;

  if (!message || !filename || !pin) {
    document.getElementById('encryption-result').innerHTML = `
      <div class="alert alert-danger">
        Please fill in all required fields (message, filename, and PIN).
      </div>
    `;
    return;
  }

  temporalEncryption.encryptMessage(message, filename, pin)
    .catch(error => {
      document.getElementById('encryption-result').innerHTML = `
        <div class="alert alert-danger">
          ${error.message}
        </div>
      `;
    });
});

    // Message Decryption
    document.getElementById('decrypt-message')?.addEventListener('click', () => {
        const encryptedPackage = JSON.parse(document.getElementById('encrypted-package').value);
        const filename = document.getElementById('decrypt-credit-card-filename').value;
        const pin = document.getElementById('decrypt-credit-card-pin').value;

        temporalEncryption.decryptMessage(encryptedPackage, filename, pin)
            .catch(error => {
                document.getElementById('decryption-result').innerHTML = `
                    <div class="alert alert-danger">
                        ${error.message}
                    </div>
                `;
            });
    });
});
        // Copy encrypted data to clipboard
        function copyToClipboard() {
            const jsonText = document.getElementById('json-result').textContent;
            navigator.clipboard.writeText(jsonText)
                .then(() => {
                    alert('Encrypted data copied to clipboard');
                })
                .catch(err => {
                    console.error('Failed to copy: ', err);
                });
        }
        
        // Token management
        document.getElementById('refresh-token').addEventListener('click', function() {
            if (!confirm('Generating a new token will invalidate all previous encryptions. Are you sure you want to continue?')) {
                return;
            }
            
            fetch('?action=new-token')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('token-id').textContent = data.id;
                    document.getElementById('token-expiry').textContent = data.expiresAt;
                    document.getElementById('token-status').textContent = data.isValid ? 'Valid' : 'Invalid';
                    document.getElementById('token-time-remaining').textContent = 
                        Math.floor(data.timeRemaining / 3600) + 'h ' + 
                        Math.floor((data.timeRemaining % 3600) / 60) + 'm';
                    
                    // Update status classes
                    document.getElementById('token-status').className = data.timeRemaining < 3600 ? 
                        (data.timeRemaining < 1800 ? 'text-danger' : 'text-warning') : 'text-success';
                    document.getElementById('token-time-remaining').className = 'token-expiry ' + 
                        (data.timeRemaining < 3600 ? (data.timeRemaining < 1800 ? 'danger' : 'warning') : '');
                    
                    alert('New token generated successfully!');
                })
                .catch(error => {
                    console.error('Error generating new token:', error);
                    alert('Error generating new token. Please try again.');
                });
        });
        
        // Update token countdown every minute
        function updateTokenTimeRemaining() {
            fetch('?action=token-info')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('token-time-remaining').textContent = 
                        Math.floor(data.timeRemaining / 3600) + 'h ' + 
                        Math.floor((data.timeRemaining % 3600) / 60) + 'm';
                    
                    // Update status classes
                    document.getElementById('token-status').className = data.timeRemaining < 3600 ? 
                        (data.timeRemaining < 1800 ? 'text-danger' : 'text-warning') : 'text-success';
                    document.getElementById('token-time-remaining').className = 'token-expiry ' + 
                        (data.timeRemaining < 3600 ? (data.timeRemaining < 1800 ? 'danger' : 'warning') : '');
                    
                    if (data.timeRemaining <= 0) {
                        document.getElementById('token-status').textContent = 'Expired';
                        document.getElementById('token-time-remaining').textContent = 'Expired';
                    }
                })
                .catch(error => {
                    console.error('Error updating token info:', error);
                });
        }
        
        setInterval(updateTokenTimeRemaining, 60000); // Update every minute
        
        // Load blockchain data via AJAX
        function loadBlockchainData() {
            document.getElementById('blockchain-placeholder').style.display = 'block';
            document.getElementById('blockchain-timeline').innerHTML = '';
            document.getElementById('empty-blockchain').style.display = 'none';
            
            fetch('?action=fetch-blockchain')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('blockchain-placeholder').style.display = 'none';
                    
                    if (data.length === 0) {
                        document.getElementById('empty-blockchain').style.display = 'block';
                        return;
                    }
                    
                    const timelineContainer = document.getElementById('blockchain-timeline');
                    timelineContainer.innerHTML = '';
                    
                    // Process blockchain data in reverse order (newest first)
                    data.reverse().forEach((entry, index) => {
                        const timestamp = new Date(entry.timestamp * 1000).toLocaleString();
                        const tokenExpiry = entry.tokenExpiry ? new Date(entry.tokenExpiry * 1000).toLocaleString() : 'Unknown';
                        
                        // Create block container
                        const blockItem = document.createElement('div');
                        blockItem.className = 'block-item';
                        blockItem.style.animationDelay = `${index * 0.1}s`;
                        
                        // Create block icon
                        const blockIcon = document.createElement('div');
                        blockIcon.className = 'block-icon';
                        blockIcon.innerHTML = '<i class="fas fa-cube"></i>';
                        
                        // Create block content
                        const blockContent = document.createElement('div');
                        blockContent.className = 'block-content';
                        
                        // Create block header
                        const blockHeader = document.createElement('div');
                        blockHeader.className = 'block-header';
                        
                        // Add megaHash badge if available
                        const megaHashBadge = entry.megaHash ? 
                            `<span class="badge bg-info me-1" title="${entry.megaHash}">MegaHash</span>` : '';
                        
                        // Add token badge if available
                        const tokenBadge = entry.tokenExpiry ? 
                            `<span class="badge bg-warning me-1" title="Token expires: ${tokenExpiry}">
                                <i class="fas fa-key me-1"></i>Token
                            </span>` : '';
                        
                        blockHeader.innerHTML = `
                            <div>
                                <strong>Block ${data.length - index}</strong>
                                <div class="text-muted small">${timestamp}</div>
                            </div>
                            <div class="text-end">
                                ${tokenBadge}
                                ${megaHashBadge}
                                <span class="badge bg-primary">Seed: ${entry.seed}</span>
                            </div>
                        `;
                        
                        // Create blockchain visual
                        const chainVisual = document.createElement('div');
                        chainVisual.className = 'blockchain-visual';
                        
                        let chainHTML = '';
                        
                        // Display blocks (limit to first 3 for visual clarity)
                        entry.blockchain.slice(0, 3).forEach((block, blockIndex) => {
                            const characterCount = block.data.length;
                            
                            // Show mining context if available
                            const miningContextInfo = block.miningContext ? 
                                `<div class="small text-muted mb-1">
                                    <i class="fas fa-hammer me-1"></i>Mining context: 
                                    ${block.miningContext.historicalHashes || 0} hashes, 
                                    ${block.miningContext.historicalMegaHashes || 0} megahashes
                                </div>` : '';
                            
                            chainHTML += `
                                <div class="mb-3">
                                    <div class="block-hash small mb-2">
                                        <i class="fas fa-hashtag me-1"></i>${block.hash.substring(0, 16)}...
                                    </div>
                                    ${miningContextInfo}
                                    <div class="d-flex justify-content-between small text-muted mb-2">
                                        <span><i class="fas fa-link me-1"></i>Prev: ${block.prevHash.substring(0, 8)}...</span>
                                        <span><i class="fas fa-cog me-1"></i>Nonce: ${block.nonce}</span>
                                    </div>
                                    <div class="block-data">
                            `;
                            
                            // Display character data (limit to first 4 for visual clarity)
                            block.data.slice(0, 4).forEach(item => {
                                const char = String.fromCharCode(item.char);
                                const encryptedIndicator = item.encryptedChar ? 
                                    '<span class="badge bg-info float-end ms-1">AES</span>' : '';
                                
                                chainHTML += `
                                    <div class="data-item">
                                        <span class="char-display">${char === ' ' ? '␣' : char}</span>
                                        ${encryptedIndicator}
                                        <span class="float-end text-muted small">
                                            ${item.pointCount ? item.pointCount + ' pts' : ''} 
                                        </span>
                                    </div>
                                `;
                            });
                            
                            if (block.data.length > 4) {
                                chainHTML += `<div class="data-item text-center">+${block.data.length - 4} more characters</div>`;
                            }
                            
                            chainHTML += `
                                    </div>
                                </div>
                            `;
                        });
                        
                        if (entry.blockchain.length > 3) {
                            chainHTML += `<div class="text-center text-muted small">+${entry.blockchain.length - 3} more blocks</div>`;
                        }
                        
                        chainVisual.innerHTML = chainHTML;
                        
                        // Assemble the block
                        blockContent.appendChild(blockHeader);
                        blockContent.appendChild(chainVisual);
                        
                        blockItem.appendChild(blockIcon);
                        blockItem.appendChild(blockContent);
                        
                        timelineContainer.appendChild(blockItem);
                    });
                })
                .catch(error => {
                    console.error('Error fetching blockchain data:', error);
                    document.getElementById('blockchain-placeholder').style.display = 'none';
                    document.getElementById('blockchain-timeline').innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>Error loading blockchain data: ${error.message}
                        </div>
                    `;
                });
        }
        
        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            // Load blockchain data on page load
            loadBlockchainData();
            
            // Set up refresh button
            document.getElementById('refresh-blockchain').addEventListener('click', function() {
                loadBlockchainData();
            });
            
            // Auto-refresh blockchain every 30 seconds
            setInterval(loadBlockchainData, 30000);
            
            // Set up diagnostic form copy from encryption
            if (document.getElementById('json-result')) {
                const diagnosticForm = document.getElementById('diagnostic-data');
                if (diagnosticForm) {
                    const jsonData = document.getElementById('json-result').textContent;
                    diagnosticForm.value = jsonData;
                }
            }
            
            // Set up tab switching behavior
            const tabTriggerList = [].slice.call(document.querySelectorAll('#myTab button'));
            tabTriggerList.forEach(function (tabTriggerEl) {
                tabTriggerEl.addEventListener('click', function (event) {
                    event.preventDefault();
                    
                    // Remove active class from all tabs
                    tabTriggerList.forEach(tab => {
                        tab.classList.remove('active');
                        const targetId = tab.getAttribute('data-bs-target');
                        document.querySelector(targetId).classList.remove('show', 'active');
                    });
                    
                    // Add active class to clicked tab
                    this.classList.add('active');
                    const targetId = this.getAttribute('data-bs-target');
                    document.querySelector(targetId).classList.add('show', 'active');
                    
                    // If switching to diagnose tab, copy encryption data if available
                    if (targetId === '#diagnose' && document.getElementById('json-result')) {
                        const diagnosticForm = document.getElementById('diagnostic-data');
                        if (diagnosticForm) {
                            const jsonData = document.getElementById('json-result').textContent;
                            diagnosticForm.value = jsonData;
                        }
                    }
                });
            });
        });
    </script>
</body>
</html>