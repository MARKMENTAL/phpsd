<?php
ini_set('max_execution_time', '300');
header('Content-Type: text/html; charset=iso-8859-1');

$saved_prompts_file = 'saved-prompts.json';

function loadSavedPrompts() {
    global $saved_prompts_file;
    if (file_exists($saved_prompts_file)) {
        $json = file_get_contents($saved_prompts_file);
        return json_decode($json, true) ?: array();
    }
    return array();
}

$saved_prompts = loadSavedPrompts();

$prompt = '';
$negative_prompt = '';
$width = 768;
$height = 768;
$steps = 35;
$cfg_scale = 3;
$sampler = "Euler a";

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['load_prompt'])) {
    if (isset($saved_prompts[$_GET['load_prompt']])) {
        if (is_array($saved_prompts[$_GET['load_prompt']])) {
            $prompt = $saved_prompts[$_GET['load_prompt']]['prompt'];
            $negative_prompt = $saved_prompts[$_GET['load_prompt']]['negative_prompt'];
        } else {
            $prompt = $saved_prompts[$_GET['load_prompt']];
            $negative_prompt = '';
        }
    }
}

$samplers = array(
    "Euler", "Euler a", "Heun", "DPM2", "DPM2 a", "DPM++ 2S a", "DPM++ 2M",
    "DPM++ SDE", "DPM++ 2M SDE", "DPM++ 3M SDE", "DPM fast", "DPM adaptive",
    "LMS", "DPM++ SDE Karras", "DPM++ 2M SDE Karras", "DPM++ 3M SDE Karras",
    "DDIM", "PLMS"
);
?>
<!DOCTYPE HTML>
<html>
<head>
    <title>Stable Diffusion Image Generator</title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
</head>
<body>
    <?php if (isset($_GET['save_success'])): ?>
	<div style="color:green;">Prompt saved successfully.</div>
    <?php elseif (isset($_GET['save_error'])): ?>
	<div style="color:red;">Error: Prompt and name are required.</div>
    <?php endif; ?>

    <h1>Stable Diffusion Image Generator</h1>

    <div style="border:1px solid #ccc;padding:10px;margin-bottom:10px;">
        <strong>How to use LORAs:</strong>
        <p>Use square bracket format: <code>[lora-name:weight]</code></p>
        <p>Example: <code>a detailed castle, [my-style-lora:0.7]</code></p>
        <p><b>Note:</b> Angle brackets are not allowed.</p>
    </div>

    <div>
        <form method="get" action="index.php">
            <label for="load_prompt">Load Saved Prompt:</label>
            <select name="load_prompt" id="load_prompt">
                <option value="">-- Choose --</option>
                <?php foreach ($saved_prompts as $name => $data): ?>
                    <option value="<?php echo htmlspecialchars($name); ?>">
                        <?php echo htmlspecialchars($name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <input type="submit" value="Load">
        </form>
    </div>

    <form method="post" action="loading.php">
        <p><label for="prompt">Prompt:</label><br>
        <textarea name="prompt" id="prompt" rows="5" cols="60"><?php echo htmlspecialchars($prompt); ?></textarea></p>

        <p><label for="negative_prompt">Negative Prompt:</label><br>
        <textarea name="negative_prompt" id="negative_prompt" rows="5" cols="60"><?php echo htmlspecialchars($negative_prompt); ?></textarea></p>

        <p>
            Width: <input type="number" name="width" value="<?php echo $width; ?>" min="64" max="2048" step="64">
            Height: <input type="number" name="height" value="<?php echo $height; ?>" min="64" max="2048" step="64">
        </p>

        <p>
            Steps: <input type="number" name="steps" value="<?php echo $steps; ?>" min="1" max="150">
            CFG Scale: <input type="number" name="cfg_scale" value="<?php echo $cfg_scale; ?>" min="1" max="30" step="0.5">
        </p>

        <p>
            Sampler:
            <select name="sampler">
                <?php foreach ($samplers as $s): ?>
                    <option value="<?php echo htmlspecialchars($s); ?>" <?php echo $s === $sampler ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($s); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </p>

        <p><input type="submit" value="Generate Image"></p>
    </form>
</body>
</html>

