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
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
    <title>Stable Diffusion Image Generator</title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
    <style type="text/css">
        .lora-help {
            background-color: #f8f8f8;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
        }
        .lora-help code {
            background-color: #eee;
            padding: 2px 4px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        textarea#prompt {
            width: 100%;
            height: 100px;
            padding: 8px;
            font-family: Arial, sans-serif;
            font-size: 14px;
            border: 1px solid #ddd;
        }
        .error-message {
            color: #dc3545;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            padding: 10px;
            margin: 10px 0;
        }
        .success-message {
            color: #28a745;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            padding: 10px;
            margin: 10px 0;
        }
        .saved-prompts {
            margin: 20px 0;
            padding: 15px;
            background-color: #f8f8f8;
            border: 1px solid #ddd;
        }
        .save-prompt-form {
            margin: 20px 0;
            padding: 15px;
            background-color: #f8f8f8;
            border: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <h1>Stable Diffusion Image Generator</h1>

    <div class="lora-help">
        <h3>How to use LORAs:</h3>
        <p>Include LORAs in your prompt using this syntax: <code>[lora-name:weight]</code></p>
        <p>Example: <code>a beautiful landscape by [my-artist-lora:0.8]</code></p>
        <p>Weight values typically range from 0.1 to 1.0</p>
        <p><strong>Note:</strong> Angle brackets (< >) are not allowed in prompts. Use square brackets instead.</p>
    </div>

    <?php if (!empty($error_message)): ?>
    <div class="error-message">
        <?php echo htmlspecialchars($error_message); ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($success_message)): ?>
    <div class="success-message">
        <?php echo htmlspecialchars($success_message); ?>
    </div>
    <?php endif; ?>

    <div class="saved-prompts">
        <h3>Saved Prompts</h3>
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
    </div>

    <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
        <div class="form-group">
            <label for="prompt">Enter your prompt (including LORA syntax if needed):</label><br>
            <textarea name="prompt" id="prompt"><?php echo htmlspecialchars($prompt); ?></textarea>
        </div>

        <div class="form-group">
            <label for="width">Width:</label>
            <input type="number" name="width" id="width" value="<?php echo $width; ?>" min="64" max="2048" step="64">
        </div>

        <div class="form-group">
            <label for="height">Height:</label>
            <input type="number" name="height" id="height" value="<?php echo $height; ?>" min="64" max="2048" step="64">
        </div>

        <div class="form-group">
            <label for="steps">Steps:</label>
            <input type="number" name="steps" id="steps" value="<?php echo $steps; ?>" min="1" max="150">
        </div>

        <div class="form-group">
            <label for="cfg_scale">CFG Scale:</label>
            <input type="number" name="cfg_scale" id="cfg_scale" value="<?php echo $cfg_scale; ?>" min="1" max="30" step="0.5">
        </div>

        <div class="form-group">
            <label for="sampler">Sampler:</label>
            <select name="sampler" id="sampler">
                <?php foreach ($samplers as $s): ?>
                    <option value="<?php echo htmlspecialchars($s); ?>" <?php echo $s === $sampler ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($s); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <input type="submit" value="Generate Image">
        </div>
    </form>

    <div class="save-prompt-form">
        <h3>Save Current Prompt</h3>
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
            <div class="form-group">
                <label for="prompt_name">Prompt Name:</label>
                <input type="text" name="prompt_name" id="prompt_name" required>
            </div>
            <input type="hidden" name="prompt" value="<?php echo htmlspecialchars($prompt); ?>">
            <input type="submit" name="save_prompt" value="Save Prompt">
        </form>
    </div>

	<?php if (!empty($generated_image)): ?>
	<div class="result">
	    <h2>Generated Image:</h2>
	    <img width="250px" src="<?php echo htmlspecialchars($generated_image); ?>" alt="Generated image"/>
	    <br/>
	    <a download href="<?php echo htmlspecialchars($generated_image); ?>"> Download Generated Image </a>
	    <?php if (isset($size_info)): ?>
	    <div class="size-info">
		<p>
		    Original size: <?php echo $size_info['original']; ?> KB<br>
		    Converted size: <?php echo $size_info['converted']; ?> KB<br>
		    Size reduction: <?php echo $size_info['reduction']; ?>%
		</p>
	    </div>
	    <?php endif; ?>
	    <div class="parameters">
		<strong>Parameters used:</strong><br>
		Prompt: <?php echo htmlspecialchars($prompt); ?><br>
		Processed Prompt: <?php echo htmlspecialchars($processed_prompt); ?><br>
		Size: <?php echo $width; ?>x<?php echo $height; ?><br>
		Steps: <?php echo $steps; ?><br>
		CFG Scale: <?php echo $cfg_scale; ?><br>
		Sampler: <?php echo htmlspecialchars($sampler); ?>
	    </div>
	</div>
	<?php endif; ?>

    <?php if (!empty($response)): ?>
    <div class="result">
        <h2>Error:</h2>
        <p><?php echo htmlspecialchars($response); ?></p>
    </div>
    <?php endif; ?>
</body>
</html>

