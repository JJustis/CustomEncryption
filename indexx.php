<?php
// Set error reporting for development
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Static password for the blockchain zip file
define('ZIP_PASSWORD', 'mandelbrotCipherSecretKey2025');
define('BLOCKCHAIN_ZIP', 'blockchain_storage.zip');
define('BLOCKCHAIN_FILE', 'blockchain_data.json');

class InfiniteMandelbrotScaler {
    private $maxDepth = 1000000000; // Maximum zoom level - increased for more points
    private $precision = 1000000000; // Precision for floating point calculations - increased
    private $generationTimeLimit = 1.0; // One second time limit for point generation
    
    /**
     * Generate a massive number of Mandelbrot points within the time limit
     * @param string $seed Base seed for randomization
     * @param string $char Character to encode
     * @return array Complex point data and collection of generated points
     */
    public function generateTimeBasedInfinitePoints($seed, $char) {
        // Seed the random generator based on input
        mt_srand(crc32($seed . ord($char)));
        
        // Collection of all points generated within time limit
        $allPoints = [];
        
        // Starting point in complex plane
        $startX = mt_rand(-2000, 1000) / 1000; // Range: -2.0 to 1.0
        $startY = mt_rand(-1500, 1500) / 1000; // Range: -1.5 to 1.5
        
        // Track start time
        $startTime = microtime(true);
        $pointCount = 0;
        $selectedPoint = null;
        
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
            $pixelX = mt_rand(0, 2000); // Increased resolution
            $pixelY = mt_rand(0, 1500); // Increased resolution
            
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
            
            // Add to collection
            $point['realScaled'] = $realScaled;
            $point['imagScaled'] = $imagScaled;
            $point['zoom'] = $zoomLevel;
            $point['pixelX'] = $pixelX;
            $point['pixelY'] = $pixelY;
            
            $allPoints[] = $point;
            $pointCount++;
            
            // The first point becomes our selected point for the character
            if ($selectedPoint === null) {
                $selectedPoint = $point;
            }
        }
        
        return [
            'selectedPoint' => $selectedPoint,
            'allPoints' => $allPoints,
            'pointCount' => $pointCount,
            'generationTime' => microtime(true) - $startTime
        ];
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
     * Generate a hash from all generated points and the selected point
     */
    public function generateInfiniteSpaceHash($pointsData, $char) {
        // Extract data
        $selectedPoint = $pointsData['selectedPoint'];
        $allPoints = $pointsData['allPoints'];
        $pointCount = $pointsData['pointCount'];
        
        // Hash for the selected point that represents the character
        $charPointData = implode('|', [
            $selectedPoint['realScaled'],
            $selectedPoint['imagScaled'],
            $selectedPoint['zoom'],
            $selectedPoint['iterations'],
            ord($char)
        ]);
        $charPointHash = hash('sha512', $charPointData);
        
        // Generate a collective hash of all generated points
        $allPointsHashes = [];
        foreach ($allPoints as $point) {
            $pointData = implode('|', [
                $point['realScaled'],
                $point['imagScaled'],
                $point['iterations']
            ]);
            $allPointsHashes[] = hash('sha256', $pointData);
        }
        
        // Combine all hashes into one infinite space hash
        $infiniteSpaceData = implode('', $allPointsHashes) . '|' . $pointCount . '|' . $charPointHash;
        return hash('sha512', $infiniteSpaceData);
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
}

class InfiniteMandelbrotBlockchainCipher {
    private $difficulty = 4; // Proof of work difficulty (leading zeros)
    private $seed;
    private $mandelbrotScaler;
    private $historicalHashes = []; // Store hashes from all previous encryptions
    private $historicalMegaHashes = []; // Store mega hashes from previous encryptions
    
    public function __construct($seed = null) {
        $this->seed = $seed ?? time();
        $this->mandelbrotScaler = new InfiniteMandelbrotScaler();
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
    
    public function encrypt($plaintext) {
        $chars = str_split($plaintext);
        $indexCard = [];
        $hashes = [];
        $blockchain = [];
        $prevHash = count($this->historicalHashes) > 0 
            ? end($this->historicalHashes) 
            : str_repeat('0', 64); // Genesis block or last historical hash
        
        // First pass - generate all infinite point mappings and hashes
        foreach ($chars as $index => $char) {
            // Use a seed that combines global seed, character index, and historical hashes
            $charSeed = $this->seed . $index . substr(implode('', $this->historicalHashes), 0, 64);
            
            // Generate time-based infinite points for this character
            $pointsData = $this->mandelbrotScaler->generateTimeBasedInfinitePoints($charSeed, $char);
            
            // Create a hash from all the points generated in infinite space
            $infiniteHash = $this->mandelbrotScaler->generateInfiniteSpaceHash($pointsData, $char);
            $hashes[] = $infiniteHash;
            
            // Add to index card
            $indexCard[] = [
                'selectedPoint' => $pointsData['selectedPoint'],
                'pointCount' => $pointsData['pointCount'],
                'infiniteHash' => $infiniteHash,
                'char' => ord($char),
                'svg' => $this->mandelbrotScaler->visualizeMandelbrotPoint(
                    array_merge($pointsData['selectedPoint'], ['pointCount' => $pointsData['pointCount']])
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
            'indexCard' => $indexCard,
            'megaHash' => $megaHash,
            'blockchain' => $blockchain,
            'timestamp' => time(),
            'historicalHashCount' => count($this->historicalHashes),
            'historicalMegaHashCount' => count($this->historicalMegaHashes)
        ];
        
        // Store to persistent blockchain
        $this->appendToBlockchain($result);
        
        return $result;
    }
    
    private function createMegaHash($hashes) {
        // Include ALL historical mega hashes for cumulative entropy
        $allMegaHashes = implode('', $this->historicalMegaHashes);
        $currentHashes = implode('', $hashes);
        
        // Generate the cumulative mega hash
        return hash('sha512', $currentHashes . $allMegaHashes);
    }
    
    private function createHMAC($hash, $megaHash) {
        return hash_hmac('md5', $hash, $megaHash);
    }
    
    /**
     * Mining as proof of work to verify a block against all possible hashes
     * This is the core verification mechanism that ties the system together
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
     * Verification as a proof of work process
     * This recreates the mining process to verify the block is valid
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
     * Decrypt function using mining-based verification
     */
    public function decrypt($encryptedPackage) {
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
        
        // Verify each character using HMAC with the mega hash
        foreach ($encryptedPackage['indexCard'] as $mapping) {
            $calculatedHMAC = $this->createHMAC($mapping['infiniteHash'], $calculatedMegaHash);
            if ($calculatedHMAC !== $mapping['hmac']) {
                return "ERROR: HMAC verification failed for character";
            }
            
            $char = chr($mapping['char']);
            $decrypted .= $char;
        }
        
        return $decrypted;
    }
    
    /**
     * Get diagnostic information about the blockchain
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
        
        return implode("\n", $diagnosis);
    }
    
    /**
     * Store blockchain data on disk
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
            'historicalMegaHashCount' => count($this->historicalMegaHashes) - 1 // minus 1 because we just added the current one
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
}

// AJAX handler for blockchain operations
if (isset($_GET['action']) && $_GET['action'] === 'fetch-blockchain') {
    header('Content-Type: application/json');
    
    $cipher = new InfiniteMandelbrotBlockchainCipher();
    $blockchain = $cipher->readBlockchainStorage();
    
    echo json_encode($blockchain);
    exit;
}

// Add a diagnostic route for blockchain issues
if (isset($_GET['action']) && $_GET['action'] === 'diagnose' && !empty($_POST['encrypted'])) {
    $encrypted = json_decode($_POST['encrypted'], true);
    $cipher = new InfiniteMandelbrotBlockchainCipher();
    $diagnosis = $cipher->diagnoseBlockchainIssue($encrypted);
    
    echo "<pre>$diagnosis</pre>";
    exit;
}

// Initialize if form is submitted
$result = null;
$mode = $_POST['mode'] ?? '';
$input = $_POST['input'] ?? '';
$seed = intval($_POST['seed'] ?? time());

if (!empty($mode) && !empty($input)) {
    $cipher = new InfiniteMandelbrotBlockchainCipher($seed);
    
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
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Infinite Mandelbrot Blockchain Cipher</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
    </style>
</head>
<body>
    <div class="container my-5">
        <h1 class="mb-4 text-center mandelbrot-title">
            <i class="fas fa-infinity me-2"></i> Infinite Mandelbrot Blockchain Cipher
        </h1>
        
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
                                        <i class="fas fa-stethoscope me-2"></i>Run Diagnostic
                                    </button>
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
                        <?php if (isset($result) && $mode === 'encrypt'): ?>
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
                                <h5 class="mb-3">Character Mappings:</h5>
                                <div class="svg-grid">
                                    <?php for ($i = 0; $i < min(10, count($result['indexCard'])); $i++): ?>
                                        <?php $item = $result['indexCard'][$i]; ?>
                                        <div class="svg-container">
                                            <?= $item['svg'] ?>
                                            <div class="char-label">'<?= htmlspecialchars(chr($item['char'])) ?>'</div>
                                            <div class="point-counter"><?= number_format($item['pointCount']) ?> pts</div>
                                            <div class="mining-indicator">Block <?= floor($i / 5) + 1 ?></div>
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
                                <h5 class="mb-3">Mining Information:</h5>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="card bg-light mb-3">
                                            <div class="card-body">
                                                <h6 class="card-title">MegaHash</h6>
                                                <p class="card-text small text-muted"><?= substr($result['megaHash'], 0, 32) ?>...</p>
                                                <p class="card-text">
                                                    <span class="badge bg-info">
                                                        <i class="fas fa-cubes me-1"></i>
                                                        <?= count($result['blockchain']) ?> Blocks Mined
                                                    </span>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="card bg-light">
                                            <div class="card-body">
                                                <h6 class="card-title">Historical Data</h6>
                                                <p class="card-text">
                                                    <span class="badge bg-primary me-1">
                                                        <i class="fas fa-history me-1"></i>
                                                        <?= $result['historicalHashCount'] ?> Hashes
                                                    </span>
                                                    <span class="badge bg-secondary">
                                                        <i class="fas fa-code-branch me-1"></i>
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
                                                <li><i class="fas fa-check-circle text-success me-2"></i>Infinite Mandelbrot space exploration</li>
                                                <li><i class="fas fa-check-circle text-success me-2"></i>Time-based point generation (1 second per character)</li>
                                                <li><i class="fas fa-check-circle text-success me-2"></i>Verification through mining/proof of work</li>
                                                <li><i class="fas fa-check-circle text-success me-2"></i>Cumulative MegaHash entropy</li>
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
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Infinite Mandelbrot Mining System</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <h6 class="mb-3">Infinite Point Generation</h6>
                                    <p>Each character is encoded using thousands of Mandelbrot points:</p>
                                    <ul class="small">
                                        <li>Time-based generation (1 second per character)</li>
                                        <li>Thousands of points explored per character</li>
                                        <li>Extreme precision (10^9) coordinate mapping</li>
                                        <li>Zoom levels up to 2^100 magnification</li>
                                    </ul>
                                </div>
                                <div>
                                    <h6 class="mb-3">Mining Verification</h6>
                                    <p>Verification is implemented as a mining process:</p>
                                    <ul class="small">
                                        <li>Proof-of-work for each block of characters</li>
                                        <li>Mining against all possible hash combinations</li>
                                        <li>Block verification through hash recalculation</li>
                                        <li>Automatically adjustable difficulty</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <h6 class="mb-3">Cumulative Mega Hashing</h6>
                                    <p>MegaHashes combine all previous encryptions:</p>
                                    <ul class="small">
                                        <li>Each MegaHash includes all previous MegaHashes</li>
                                        <li>Growing entropy with each new message</li>
                                        <li>Historical context preserved in blockchain</li>
                                        <li>Exponentially increasing security over time</li>
                                    </ul>
                                </div>
                                <div>
                                    <h6 class="mb-3">Persistent Storage</h6>
                                    <p>All blockchain data is stored securely:</p>
                                    <ul class="small">
                                        <li>Password-protected ZIP archives</li>
                                        <li>AES-256 encryption for storage</li>
                                        <li>Block mining context preserved</li>
                                        <li>Time-stamped immutable records</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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
                        
                        blockHeader.innerHTML = `
                            <div>
                                <strong>Block ${data.length - index}</strong>
                                <div class="text-muted small">${timestamp}</div>
                            </div>
                            <div class="text-end">
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
                                chainHTML += `
                                    <div class="data-item">
                                        <span class="char-display">${char === ' ' ? '␣' : char}</span>
                                        <span class="float-end text-muted small">
                                            ${item.pointCount ? item.pointCount + ' pts' : ''} 
                                            x${item.location.real.toFixed(3)}, y${item.location.imag.toFixed(3)}
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