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
    <title>Mandelbrot SVG Blockchain Cipher</title>
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
    </style>
</head>
<body>
    <div class="container my-5">
        <h1 class="mb-4 text-center mandelbrot-title">
            <i class="fas fa-infinity me-2"></i> Mandelbrot SVG Blockchain Cipher
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
                                        <small class="text-muted">Seed determines the Mandelbrot locations</small>
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
                                        </div>
                                    <?php endfor; ?>
                                    <?php if (count($result['indexCard']) > 10): ?>
                                        <div class="svg-container" style="display:flex; align-items:center; justify-content:center;">
                                            <div>+<?= count($result['indexCard']) - 10 ?> more</div>
                                        </div>
                                    <?php endif; ?>
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
                                                <li><i class="fas fa-check-circle text-success me-2"></i>SVG-scaled Mandelbrot locations</li>
                                                <li><i class="fas fa-check-circle text-success me-2"></i>HMAC verification with mega-hash</li>
                                                <li><i class="fas fa-check-circle text-success me-2"></i>Blockchain-like proof of work</li>
                                                <li><i class="fas fa-check-circle text-success me-2"></i>Encrypted persistent storage</li>
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
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>System Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <h6 class="mb-3">Mandelbrot SVG Scaling</h6>
                                    <p>The system uses SVG scaling to generate near-infinite unique points in the Mandelbrot set for each character. Points are determined by:</p>
                                    <ul class="small">
                                        <li>Complex plane coordinates (-2.0 to 1.0, -1.5 to 1.5)</li>
                                        <li>Dynamic zoom levels (up to 30 levels)</li>
                                        <li>Precise pixel coordinates at each zoom level</li>
                                    </ul>
                                </div>
                                <div>
                                    <h6 class="mb-3">HMAC Verification</h6>
                                    <p>Character integrity is protected by:</p>
                                    <ul class="small">
                                        <li>SHA-512 hashes for each Mandelbrot location</li>
                                        <li>MegaHash created from all character hashes</li>
                                        <li>MD5 HMAC for each hash using MegaHash as key</li>
                                    </ul>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <h6 class="mb-3">Blockchain Security</h6>
                                    <p>Messages are secured by:</p>
                                    <ul class="small">
                                        <li>Proof-of-work mining (adjustable difficulty)</li>
                                        <li>Chained blocks with previous hash verification</li>
                                        <li>Character grouping (5 per block)</li>
                                        <li>Tamper-proof hash chain verification</li>
                                    </ul>
                                </div>
                                <div>
                                    <h6 class="mb-3">Persistent Storage</h6>
                                    <p>All blockchain data is stored in:</p>
                                    <ul class="small">
                                        <li>Password-protected ZIP archives</li>
                                        <li>AES-256 encryption for storage</li>
                                        <li>Time-stamped transaction history</li>
                                        <li>Secure cleanup of temporary files</li>
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
                        blockHeader.innerHTML = `
                            <div>
                                <strong>Block ${data.length - index}</strong>
                                <div class="text-muted small">${timestamp}</div>
                            </div>
                            <div class="text-end">
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
                            
                            chainHTML += `
                                <div class="mb-3">
                                    <div class="block-hash small mb-2">
                                        <i class="fas fa-hashtag me-1"></i>${block.hash.substring(0, 16)}...
                                    </div>
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
                                        <span class="float-end text-muted small">x${item.location.real.toFixed(3)}, y${item.location.imag.toFixed(3)}</span>
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
        });
    </script>
</body>
</html>