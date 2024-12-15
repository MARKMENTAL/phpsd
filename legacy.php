<?php
ini_set('max_execution_time', '300');
header('Content-Type: text/html; charset=iso-8859-1');

$response = "";
$generated_image = "";
$prompt = "";
$width = 512;
$height = 512;
$steps = 20;
$cfg_scale = 7.5;
$sampler = "Euler";
$error_message = "";
$success_message = "";
$processed_prompt = "";

// File to store saved prompts
$saved_prompts_file = 'saved-prompts.json';

// Load saved prompts
function loadSavedPrompts() {
    global $saved_prompts_file;
    if (file_exists($saved_prompts_file)) {
        $json = file_get_contents($saved_prompts_file);
        return json_decode($json, true) ?: array();
    }
    return array();
}

// Save a new prompt
function savePrompt($prompt_text, $prompt_name) {
    global $saved_prompts_file;
    $prompts = loadSavedPrompts();
    $prompts[$prompt_name] = $prompt_text;
    file_put_contents($saved_prompts_file, json_encode($prompts, JSON_PRETTY_PRINT));
}

$saved_prompts = loadSavedPrompts();

// List of available samplers
$samplers = array(
    "Euler",
    "Euler a",
    "Heun",
    "DPM2",
    "DPM2 a",
    "DPM++ 2S a",
    "DPM++ 2M",
    "DPM++ SDE",
    "DPM++ 2M SDE",
    "DPM++ 3M SDE",
    "DPM fast",
    "DPM adaptive",
    "LMS",
    "DPM++ SDE Karras",
    "DPM++ 2M SDE Karras",
    "DPM++ 3M SDE Karras",
    "DDIM",
    "PLMS"
);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Handle prompt saving
    if (isset($_POST['save_prompt']) && isset($_POST['prompt_name'])) {
        $prompt_to_save = trim($_POST['prompt']);
        $prompt_name = trim($_POST['prompt_name']);

        if (!empty($prompt_to_save) && !empty($prompt_name)) {
            savePrompt($prompt_to_save, $prompt_name);
            $success_message = "Prompt saved successfully!";
            $saved_prompts = loadSavedPrompts(); // Reload prompts
        } else {
            $error_message = "Both prompt and prompt name are required to save.";
        }
    }
    // Handle image generation
    else {
        $prompt = isset($_POST['prompt']) ? $_POST['prompt'] : '';
        $width = isset($_POST['width']) ? (int)$_POST['width'] : 512;
        $height = isset($_POST['height']) ? (int)$_POST['height'] : 512;
        $steps = isset($_POST['steps']) ? (int)$_POST['steps'] : 20;
        $cfg_scale = isset($_POST['cfg_scale']) ? (float)$_POST['cfg_scale'] : 7.5;
        $sampler = isset($_POST['sampler']) ? $_POST['sampler'] : 'Euler';

        // Check for angle brackets in the prompt
        if (strpos($prompt, '<') !== false || strpos($prompt, '>') !== false) {
            $error_message = "Error: Angle brackets are not allowed in the prompt. Please use square brackets [lora-name:weight] for LORAs.";
        }
        // Only proceed if there's no error
        elseif (!empty($prompt)) {
            // Process the prompt to handle LORA syntax
            $processed_prompt = preg_replace('/\[([\w\s\-]+?):([\d\.]+)\]/', '<lora:$1:$2>', $prompt);

            $data = array(
                'prompt' => $processed_prompt,
                'steps' => $steps,
                'width' => $width,
                'height' => $height,
                'cfg_scale' => $cfg_scale,
                'sampler_name' => $sampler,
                'override_settings' => new stdClass(),
                'override_settings_restore_afterwards' => true
            );

	    // Replace 127.0.0.1 with your Stable diffusion server's IP if running externally
            $ch = curl_init('http://127.0.0.1:7860/sdapi/v1/txt2img');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

            $result = curl_exec($ch);

            if (curl_errno($ch)) {
                $response = 'Error: ' . curl_error($ch);
            } else {
                $decoded = json_decode($result, true);
                if (isset($decoded['images'][0])) {
                    $image_data = base64_decode($decoded['images'][0]);

                    // Save original PNG temporarily
                    $temp_png = 'temp_' . time() . '_' . bin2hex(random_bytes(8)) . '.png';
                    file_put_contents($temp_png, $image_data);

                    // Create output JPG filename
                    $generated_image = 'generated_' . time() . '_' . bin2hex(random_bytes(8)) . '.jpg';

                    // Build ImageMagick convert command with optimization
                    $quality = 85; // Balanced quality setting
                    $cmd = "convert \"$temp_png\" -strip -interlace Plane -gaussian-blur 0.05 -quality $quality ";
                    $cmd .= "-sampling-factor 4:2:0 \"$generated_image\" 2>&1";

                    // Execute conversion
                    $output = [];
                    $returnVar = 0;
                    exec($cmd, $output, $returnVar);

                    if ($returnVar === 0 && file_exists($generated_image)) {
                        // Get file sizes for comparison
                        $originalSize = filesize($temp_png);
                        $convertedSize = filesize($generated_image);

                        // Clean up temporary PNG file
                        if (file_exists($temp_png)) {
                            unlink($temp_png);
                        }

                        // Clean up old generated files
                        foreach (glob("generated_*.png") as $file) {
                            if ($file != $generated_image && (time() - filemtime($file)) > 3600) {
                                unlink($file);
                            }
                        }

                        // Store size information for display
                        $size_info = array(
                            'original' => round($originalSize / 1024, 2),
                            'converted' => round($convertedSize / 1024, 2),
                            'reduction' => round((($originalSize - $convertedSize) / $originalSize) * 100, 2)
                        );
                    } else {
                        // If conversion fails, keep the original PNG
                        if (file_exists($temp_png)) {
                            $generated_image = $temp_png;
                        }
                        $error_message = "Image conversion failed. Using original PNG format.";
                    }
                }
            }

            curl_close($ch);
        }
    }
} else if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['load_prompt'])) {
    // Load saved prompt into textarea
    $saved_prompts = loadSavedPrompts();
    $prompt = isset($saved_prompts[$_GET['load_prompt']]) ? $saved_prompts[$_GET['load_prompt']] : '';
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 3.2//EN">
<html>
<head>
<title>Stable Diffusion Image Generator</title>
</head>
<body bgcolor="#FFFFFF">

<h1>Stable Diffusion Image Generator</h1>

<?php if (!empty($error_message)): ?>
<table border="1" cellpadding="4" cellspacing="0" width="100%" bgcolor="#FFE4E1">
    <tr>
        <td><font color="#8B0000"><?php echo htmlspecialchars($error_message); ?></font></td>
    </tr>
</table>
<br>
<?php endif; ?>

<?php if (!empty($success_message)): ?>
<table border="1" cellpadding="4" cellspacing="0" width="100%" bgcolor="#E0FFE0">
    <tr>
        <td><font color="#006400"><?php echo htmlspecialchars($success_message); ?></font></td>
    </tr>
</table>
<br>
<?php endif; ?>

<table border="1" cellpadding="4" cellspacing="0" width="100%">
    <tr bgcolor="#F0F0F0">
        <td>
            <h3>How to use LORAs:</h3>
            <p>Include LORAs using: [lora-name:weight]<br>
            Example: a beautiful landscape by [my-artist-lora:0.8]<br>
            Weight values: 0.1 to 1.0<br>
            Note: Do not use angle brackets (&lt; &gt;). Use square brackets instead.</p>
        </td>
    </tr>
</table>

<br>

<table border="1" cellpadding="4" cellspacing="0" width="100%">
    <tr>
        <td>
            <b>Saved Prompts</b><br>
            <form method="get" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <select name="load_prompt">
                    <option value="">Select a saved prompt</option>
                    <?php foreach ($saved_prompts as $name => $saved_prompt): ?>
                        <option value="<?php echo htmlspecialchars($name); ?>">
                            <?php echo htmlspecialchars($name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="submit" value="Load Prompt">
            </form>
        </td>
    </tr>
</table>

<br>

<form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
<table border="0" cellpadding="4" cellspacing="0" width="100%">
    <tr>
        <td colspan="2"><b>Enter your prompt:</b><br>
        <textarea name="prompt" rows="6" cols="60"><?php echo htmlspecialchars($prompt); ?></textarea></td>
    </tr>
    <tr>
        <td><b>Width:</b><br>
        <input type="text" name="width" value="<?php echo $width; ?>" size="6"></td>
        <td><b>Height:</b><br>
        <input type="text" name="height" value="<?php echo $height; ?>" size="6"></td>
    </tr>
    <tr>
        <td><b>Steps:</b><br>
        <input type="text" name="steps" value="<?php echo $steps; ?>" size="6"></td>
        <td><b>CFG Scale:</b><br>
        <input type="text" name="cfg_scale" value="<?php echo $cfg_scale; ?>" size="6"></td>
    </tr>
    <tr>
        <td colspan="2"><b>Sampler:</b><br>
        <select name="sampler">
            <?php foreach ($samplers as $s): ?>
                <option value="<?php echo htmlspecialchars($s); ?>" <?php echo $s === $sampler ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($s); ?>
                </option>
            <?php endforeach; ?>
        </select></td>
    </tr>
    <tr>
        <td colspan="2" align="center">
        <input type="submit" value="Generate Image">
        </td>
    </tr>
</table>
</form>

<br>

<table border="1" cellpadding="4" cellspacing="0" width="100%">
    <tr bgcolor="#F0F0F0">
        <td>
            <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                <b>Save Current Prompt</b><br>
                Prompt Name: <input type="text" name="prompt_name" size="30"><br>
                <input type="hidden" name="prompt" value="<?php echo htmlspecialchars($prompt); ?>">
                <input type="submit" name="save_prompt" value="Save Prompt">
            </form>
        </td>
    </tr>
</table>

<?php if (!empty($generated_image)): ?>
<br>
<table border="1" cellpadding="4" cellspacing="0" width="100%">
    <tr>
        <td>
            <h2>Generated Image:</h2>
            <img width="250" src="<?php echo htmlspecialchars($generated_image); ?>" alt="Generated image">
            <br>
            <a href="<?php echo htmlspecialchars($generated_image); ?>">Download Generated Image</a>
            
            <?php if (isset($size_info)): ?>
            <p>
                Original size: <?php echo $size_info['original']; ?> KB<br>
                Converted size: <?php echo $size_info['converted']; ?> KB<br>
                Size reduction: <?php echo $size_info['reduction']; ?>%
            </p>
            <?php endif; ?>
            
            <p><b>Parameters used:</b><br>
            Prompt: <?php echo htmlspecialchars($prompt); ?><br>
            <?php if (!empty($processed_prompt)): ?>
            Processed Prompt: <?php echo htmlspecialchars($processed_prompt); ?><br>
            <?php endif; ?>
            Size: <?php echo $width; ?>x<?php echo $height; ?><br>
            Steps: <?php echo $steps; ?><br>
            CFG Scale: <?php echo $cfg_scale; ?><br>
            Sampler: <?php echo htmlspecialchars($sampler); ?></p>
        </td>
    </tr>
</table>
<?php endif; ?>

<?php if (!empty($response)): ?>
<br>
<table border="1" cellpadding="4" cellspacing="0" width="100%">
    <tr>
        <td>
            <h2>Error:</h2>
            <p><?php echo htmlspecialchars($response); ?></p>
        </td>
    </tr>
</table>
<?php endif; ?>

</body>
</html>

