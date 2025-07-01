<?php
// generate.php
session_start();
ini_set('max_execution_time', '300');
header('Content-Type: text/html; charset=iso-8859-1');

// Input values
$prompt = $_POST['prompt'] ?? '';
$negative_prompt = $_POST['negative_prompt'] ?? '';
$width = isset($_POST['width']) ? (int)$_POST['width'] : 512;
$height = isset($_POST['height']) ? (int)$_POST['height'] : 512;
$steps = isset($_POST['steps']) ? (int)$_POST['steps'] : 20;
$cfg_scale = isset($_POST['cfg_scale']) ? (float)$_POST['cfg_scale'] : 7.5;
$sampler = $_POST['sampler'] ?? 'Euler';

// Input validation
if (strpos($prompt, '<') !== false || strpos($prompt, '>') !== false) {
    die("Error: Angle brackets are not allowed in prompts.");
}

// Convert LORA-style square bracket syntax
$processed_prompt = preg_replace('/\[([\w\s\-]+?):([\d\.]+)\]/', '<lora:$1:$2>', $prompt);

// Build request
$data = array(
    'prompt' => $processed_prompt,
    'negative_prompt' => $negative_prompt,
    'steps' => $steps,
    'width' => $width,
    'height' => $height,
    'cfg_scale' => $cfg_scale,
    'sampler_name' => $sampler,
    'override_settings' => new stdClass(),
    'override_settings_restore_afterwards' => true
);

// Call Stable Diffusion API
$ch = curl_init('http://127.0.0.1:7860/sdapi/v1/txt2img');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
$result = curl_exec($ch);
curl_close($ch);

if (!$result) {
    die("Error: Failed to communicate with the image generation API.");
}

$decoded = json_decode($result, true);
$seed = $decoded['info'] ?? '';
if (is_string($seed)) {
    // Try to parse seed from info block
    if (preg_match('/"seed":\s*(\d+)/', $seed, $matches)) {
        $seed = $matches[1];
    } else {
        $seed = 'unknown';
    }
}

if (!isset($decoded['images'][0])) {
    die("Error: No image returned.");
}

// Decode base64 PNG
$image_data = base64_decode($decoded['images'][0]);

// Save original PNG temporarily
$temp_png = 'temp_' . time() . '_' . bin2hex(random_bytes(8)) . '.png';
file_put_contents($temp_png, $image_data);

// Convert PNG to JPG with ImageMagick
$generated_image = 'generated_' . time() . '_' . bin2hex(random_bytes(8)) . '.jpg';
$quality = 75;
#$cmd = "convert \"$temp_png\" -strip -interlace Plane -gaussian-blur 0.05 -quality $quality -sampling-factor 4:2:0 \"$generated_image\" 2>&1";
#
$metadata_comment = escapeshellarg(
    "Prompt: $prompt\n" .
    "Negative prompt: $negative_prompt\n" .
    "Steps: $steps, Sampler: $sampler, CFG scale: $cfg_scale, Size: {$width}x{$height}, Seed: $seed\n"
);

$cmd = "convert \"$temp_png\" -strip -interlace Plane -gaussian-blur 0.05 -quality $quality -sampling-factor 4:2:0 -set comment $metadata_comment \"$generated_image\" 2>&1";

exec($cmd, $output, $returnVar);

// Get image size info
if ($returnVar !== 0 || !file_exists($generated_image)) {
    $generated_image = $temp_png;
    $size_info = null;
} else {
    $originalSize = filesize($temp_png);
    $convertedSize = filesize($generated_image);
    unlink($temp_png);

    $size_info = array(
        'original' => round($originalSize / 1024, 2),
        'converted' => round($convertedSize / 1024, 2),
        'reduction' => round((($originalSize - $convertedSize) / $originalSize) * 100, 2)
    );
}

// Save result to session
$_SESSION['result'] = array(
    'generated_image' => $generated_image,
    'prompt' => $prompt,
    'negative_prompt' => $negative_prompt,
    'processed_prompt' => $processed_prompt,
    'width' => $width,
    'height' => $height,
    'steps' => $steps,
    'cfg_scale' => $cfg_scale,
    'sampler' => $sampler,
    'size_info' => $size_info
);

// Redirect to result page
header("Location: result.php");
exit;

