<?php
// Set error reporting for development
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Static password for the blockchain zip file
define('ZIP_PASSWORD', 'mandelbrotCipherSecretKey2025');
define('BLOCKCHAIN_ZIP', 'blockchain_storage.zip');
define('BLOCKCHAIN_FILE', 'blockchain_data.json');

class MandelbrotSVGScaler {
    private $maxDepth = 30; // Maximum zoom level
    private $precision = 1000000; // Precision for floating point calculations
    
    public function generateScaledLocation($seed, $char, $iterationLimit = 1000) {
        // Seed the random generator based on input
        mt_srand(crc32($seed . ord($char)));
        
        // Initial complex plane coordinates
        $x0 = mt_rand(-2000, 1000) / 1000; // Range: -2.0 to 1.0
        $y0 = mt_rand(-1500, 1500) / 1000; // Range: -1.5 to 1.5
        
        // Random zoom level for SVG scaling
        $zoomLevel = mt_rand(1, $this->maxDepth);
        $zoom = pow(2, $zoomLevel);
        
        // Calculate a pixel within this zoom region
        $pixelX = mt_rand(0, 800); // 800px width
        $pixelY = mt_rand(0, 600); // 600px height
        
        // Convert to complex coordinates at this zoom level
        $width = 3.0 / $zoom; // 3.0 is the range of x-axis (-2.0 to 1.0)
        $height = 3.0 / $zoom; // 3.0 is the range of y-axis (-1.5 to 1.5)
        
        $real = $x0 + ($pixelX / 800 * $width - ($width / 2));
        $imag = $y0 + ($pixelY / 600 * $height - ($height / 2));
        
        // Ensure precision by scaling to integers
        $realScaled = round($real * $this->precision);
        $imagScaled = round($imag * $this->precision);
        
        // Calculate iterations for this Mandelbrot point
        $zr = 0;
        $zi = 0;
        $iterations = 0;
        
        while ($zr * $zr + $zi * $zi < 4 && $iterations < $iterationLimit) {
            $temp = $zr * $zr - $zi * $zi + $real;
            $zi = 2 * $zr * $zi + $imag;
            $zr = $temp;
            $iterations++;
        }
        
        return [
            'real' => $real,
            'imag' => $imag,
            'realScaled' => $realScaled,
            'imagScaled' => $imagScaled,
            'zoom' => $zoomLevel,
            'iterations' => $iterations,
            'pixelX' => $pixelX,
            'pixelY' => $pixelY,
        ];
    }
    
    public function generateUniqueHash($location, $char) {
        // Create a highly unique hash based on the exact Mandelbrot location and character
        $uniqueData = implode('|', [
            $location['realScaled'],
            $location['imagScaled'],
            $location['zoom'],
            $location['iterations'],
            ord($char)
        ]);
        
        // Generate a 512-bit hash to ensure uniqueness
        return hash('sha512', $uniqueData);
    }
    
    public function visualizeMandelbrotPoint($location, $size = 300) {
        // Generate SVG to visualize a point in the Mandelbrot set
        $svgWidth = $size;
        $svgHeight = $size;
        
        // Calculate display region
        $zoom = pow(2, $location['zoom']);
        $width = 3.0 / $zoom;
        $height = 3.0 / $zoom;
        $xMin = $location['real'] - $width/2;
        $xMax = $location['real'] + $width/2;
        $yMin = $location['imag'] - $height/2;
        $yMax = $location['imag'] + $height/2;
        
        $svgContent = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 '.$svgWidth.' '.$svgHeight.'" width="'.$svgWidth.'" height="'.$svgHeight.'">';
        $svgContent .= '<rect width="100%" height="100%" fill="#000" />';
        
        // Add point indicator
        $svgContent .= '<circle cx="'.($svgWidth/2).'" cy="'.($svgHeight/2).'" r="3" fill="red" />';
        
        // Add coordinates text
        $svgContent .= '<text x="5" y="15" fill="white" font-size="10">Real: '.number_format($location['real'], 8).'</text>';
        $svgContent .= '<text x="5" y="30" fill="white" font-size="10">Imag: '.number_format($location['imag'], 8).'</text>';
        $svgContent .= '<text x="5" y="45" fill="white" font-size="10">Zoom: '.$location['zoom'].'x</text>';
        
        $svgContent .= '</svg>';
        return $svgContent;
    }
}

class MandelbrotBlockchainCipher {
    private $maxIterations = 10000;
    private $difficulty = 4; // Proof of work difficulty (leading zeros)
    private $seed;
    private $svgScaler;
    
    public function __construct($seed = null) {
        $this->seed = $seed ?? time();
        $this->svgScaler = new MandelbrotSVGScaler();
    }
    
    public function encrypt($plaintext) {
        $chars = str_split($plaintext);
        $indexCard = [];
        $hashes = [];
        $blockchain = [];
        $prevHash = str_repeat('0', 64); // Genesis block previous hash
        
        // First pass - generate all individual hashes
        foreach ($chars as $index => $char) {
            // Generate a unique Mandelbrot location using SVG scaling
            $location = $this->svgScaler->generateScaledLocation($this->seed . $index, $char);
            
            // Generate a unique hash based on this location
            $hash = $this->svgScaler->generateUniqueHash($location, $char);
            $hashes[] = $hash;
            
            $indexCard[] = [
                'location' => $location,
                'hash' => $hash,
                'char' => ord($char),
                'svg' => $this->svgScaler->visualizeMandelbrotPoint($location)
            ];
        }
        
        // Create mega hash from all hashes
        $megaHash = $this->createMegaHash($hashes);
        
        // Second pass - create HMACs and blockchain
        $blockData = [];
        foreach ($indexCard as $index => $mapping) {
            $hmac = $this->createHMAC($mapping['hash'], $megaHash);
            $indexCard[$index]['hmac'] = $hmac;
            
            // Group characters into blocks (5 chars per block)
            $blockData[] = [
                'char' => $mapping['char'],
                'hash' => $mapping['hash'],
                'hmac' => $hmac,
                'location' => [
                    'real' => $mapping['location']['real'],
                    'imag' => $mapping['location']['imag'],
                    'zoom' => $mapping['location']['zoom'],
                    'iterations' => $mapping['location']['iterations']
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
            'timestamp' => time()
        ];
        
        // Store to persistent blockchain
        $this->appendToBlockchain($result);
        
        return $result;
    }
    
    private function createMegaHash($hashes) {
        return hash('sha512', implode('', $hashes));
    }
    
    private function createHMAC($hash, $megaHash) {
        return hash_hmac('md5', $hash, $megaHash);
    }
    
    private function mineBlock($prevHash, $data, $difficulty) {
        $nonce = 0;
        $prefix = str_repeat('0', $difficulty);
        
        while (true) {
            $blockData = $prevHash . json_encode($data) . $nonce;
            $hash = hash('sha256', $blockData);
            
            if (substr($hash, 0, $difficulty) === $prefix) {
                return [
                    'hash' => $hash,
                    'prevHash' => $prevHash,
                    'data' => $data,
                    'nonce' => $nonce,
                    'timestamp' => time()
                ];
            }
            
            $nonce++;
            
            // Add a time limit to prevent infinite loops (3 seconds)
            if ($nonce % 10000 === 0 && (microtime(true) - $_SERVER['REQUEST_TIME_FLOAT']) > 3) {
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
                        'timestamp' => time(),
                        'note' => 'Mining timeout - difficulty reduced'
                    ];
                }
            }
        }
    }
    
    public function decrypt($encryptedPackage) {
        $decrypted = '';
        
        // Verify blockchain integrity
        $isValid = $this->verifyBlockchain($encryptedPackage['blockchain']);
        if (!$isValid) {
            return "ERROR: Blockchain integrity check failed";
        }
        
        // Verify HMACs
        $allHashes = array_map(function($mapping) {
            return $mapping['hash'];
        }, $encryptedPackage['indexCard']);
        
        $calculatedMegaHash = $this->createMegaHash($allHashes);
        if ($calculatedMegaHash !== $encryptedPackage['megaHash']) {
            return "ERROR: MegaHash verification failed";
        }
        
        foreach ($encryptedPackage['indexCard'] as $mapping) {
            $calculatedHMAC = $this->createHMAC($mapping['hash'], $encryptedPackage['megaHash']);
            if ($calculatedHMAC !== $mapping['hmac']) {
                return "ERROR: HMAC verification failed for character";
            }
            
            $char = chr($mapping['char']);
            $decrypted .= $char;
        }
        
        return $decrypted;
    }
    
    private function verifyBlockchain($blockchain) {
        $prevHash = str_repeat('0', 64);
        
        foreach ($blockchain as $block) {
            // Check previous hash
            if ($block['prevHash'] !== $prevHash) {
                return false;
            }
            
            // Verify hash
            $blockData = $block['prevHash'] . json_encode($block['data']) . $block['nonce'];
            $calculatedHash = hash('sha256', $blockData);
            
            if ($calculatedHash !== $block['hash']) {
                return false;
            }
            
            $prevHash = $block['hash'];
        }
        
        return true;
    }
    
    private function appendToBlockchain($data) {
        // Get existing blockchain data
        $existingData = $this->readBlockchainStorage();
        
        // Add new encryption data
        $existingData[] = [
            'timestamp' => time(),
            'seed' => $data['seed'],
            'blockchain' => $data['blockchain']
        ];
        
        // Save updated blockchain data
        $this->writeBlockchainStorage($existingData);
    }
    
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
            // Password is required to open the encrypted zip
            $zip->setPassword(ZIP_PASSWORD);
            $zip->extractTo($tempDir);
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
            
            // Clean up if no file
            rmdir($tempDir);
        }
        
        return [];
    }
    
    private function writeBlockchainStorage($data) {
        // Create temporary directory
        $tempDir = sys_get_temp_dir() . '/mandelbrot_' . uniqid();
        if (!file_exists($tempDir)) {
            mkdir($tempDir);
        }
        
        // Write data to temporary JSON file
        $jsonFile = $tempDir . '/' . BLOCKCHAIN_FILE;
        file_put_contents($jsonFile, json_encode($data, JSON_PRETTY_PRINT));
        
        // Create/update encrypted zip file
        $zip = new ZipArchive();
        $mode = file_exists(BLOCKCHAIN_ZIP) ? ZipArchive::OVERWRITE : ZipArchive::CREATE;
        
        if ($zip->open(BLOCKCHAIN_ZIP, $mode) === TRUE) {
            // Set encryption method and password
            $zip->setEncryptionName(BLOCKCHAIN_FILE, ZipArchive::EM_AES_256, ZIP_PASSWORD);
            $zip->addFile($jsonFile, BLOCKCHAIN_FILE);
            $zip->close();
            
            // Clean up
            unlink($jsonFile);
            rmdir($tempDir);
            
            return true;
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
    
    $cipher = new MandelbrotBlockchainCipher();
    $blockchain = $cipher->readBlockchainStorage();
    
    echo json_encode($blockchain);
    exit;
}

// Initialize if form is submitted
$result = null;
$mode = $_POST['mode'] ?? '';
$input = $_POST['input'] ?? '';
$seed = intval($_POST['seed'] ?? time());

if (!empty($mode) && !empty($input)) {
    $cipher = new MandelbrotBlockchainCipher($seed);
    
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
    <title>Mandelbrot Blockchain Cipher</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .json-display {
            max-height: 400px;
            overflow-y: auto;
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
        }
        .processing-time {
            font-size: 0.9rem;
            color: #6c757d;
        }
        .blockchain-visualization .card {
            transition: all 0.3s ease;
        }
        .blockchain-visualization .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container my-5">
        <h1 class="mb-4 text-center">Mandelbrot Blockchain Cipher</h1>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <ul class="nav nav-tabs card-header-tabs" id="myTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="encrypt-tab" data-bs-toggle="tab" data-bs-target="#encrypt" type="button" role="tab">Encrypt</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="decrypt-tab" data-bs-toggle="tab" data-bs-target="#decrypt" type="button" role="tab">Decrypt</button>
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
                                    </div>
                                    <button type="submit" class="btn btn-primary">Encrypt</button>
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
                                    <button type="submit" class="btn btn-primary">Decrypt</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">Result</div>
                    <div class="card-body">
                        <?php if (isset($result) && $mode === 'encrypt'): ?>
                            <div class="alert alert-success">Encryption successful!</div>
                            <div class="mb-3">
                                <span class="processing-time">Processed in <?= number_format($processTime, 4) ?> seconds</span>
                                <h5 class="mt-3">Encrypted Data:</h5>
                                <div class="json-display">
                                    <pre id="json-result"><?= json_encode($result, JSON_PRETTY_PRINT) ?></pre>
                                </div>
                                <button class="btn btn-sm btn-secondary mt-2" onclick="copyToClipboard()">Copy to Clipboard</button>
                            </div>
                            
                            <div class="mt-4">
                                <h5>Blockchain Visualization:</h5>
                                <div class="blockchain-visualization">
                                    <div class="d-flex flex-nowrap overflow-auto py-3">
                                        <?php foreach ($result['blockchain'] as $index => $block): ?>
                                            <div class="card me-3" style="min-width: 200px; max-width: 200px;">
                                                <div class="card-body">
                                                    <h6 class="card-title">Block #<?= $index + 1 ?></h6>
                                                    <p class="card-text">
                                                        <small>
                                                            Hash: <?= substr($block['hash'], 0, 8) ?>...<br>
                                                            Prev: <?= substr($block['prevHash'], 0, 8) ?>...<br>
                                                            Nonce: <?= $block['nonce'] ?><br>
                                                            Data: <?= count($block['data']) ?> chars
                                                        </small>
                                                    </p>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        <?php elseif (isset($result) && $mode === 'decrypt'): ?>
                            <div class="alert alert-success">Decryption successful!</div>
                            <div class="mb-3">
                                <span class="processing-time">Processed in <?= number_format($processTime, 4) ?> seconds</span>
                                <h5 class="mt-3">Decrypted Text:</h5>
                                <div class="p-3 bg-light rounded">
                                    <?= htmlspecialchars($result) ?>
                                </div>
                            </div>
                            <div class="mt-3">
                                <h5>Verification:</h5>
                                <?php if ($result === $input): ?>
                                    <div class="alert alert-success">✓ Text matches expected input</div>
                                <?php else: ?>
                                    <div class="alert alert-danger">✗ Text does not match expected input</div>
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
                                                <li>✓ Mandelbrot-based character hiding</li>
                                                <li>✓ HMAC verification with mega-hash</li>
                                                <li>✓ Blockchain-like proof of work</li>
                                                <li>✓ Multi-layer security</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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
    </script>
</body>
</html>
