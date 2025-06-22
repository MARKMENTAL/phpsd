<?php
// result.php
session_start();
if (!isset($_SESSION['result'])) {
    die("No image generated yet.");
}
$data = $_SESSION['result'];
unset($_SESSION['result']); // clear for next use
?>
<!DOCTYPE HTML>
<html>
<head>
    <title>Stable Diffusion Result</title>
</head>
<body>
    <h1>Generated Image</h1>
    <img width="250px" src="<?php echo htmlspecialchars($data['generated_image']); ?>" alt="Generated image"/><br>
    <a download href="<?php echo htmlspecialchars($data['generated_image']); ?>">Download Generated Image</a>
    <div class="parameters">
        <strong>Parameters used:</strong><br>
        Prompt: <?php echo htmlspecialchars($data['prompt']); ?><br>
        Negative Prompt: <?php echo htmlspecialchars($data['negative_prompt']); ?><br>
        Processed Prompt: <?php echo htmlspecialchars($data['processed_prompt']); ?><br><br>
        Size: <?php echo $data['width']; ?>x<?php echo $data['height']; ?><br>
        Steps: <?php echo $data['steps']; ?><br>
        CFG Scale: <?php echo $data['cfg_scale']; ?><br>
        Sampler: <?php echo htmlspecialchars($data['sampler']); ?><br>
        <?php if (!empty($data['size_info'])): ?>
            <p>
                Original size: <?php echo $data['size_info']['original']; ?> KB<br>
                Converted size: <?php echo $data['size_info']['converted']; ?> KB<br>
                Size reduction: <?php echo $data['size_info']['reduction']; ?>%
            </p>
        <?php endif; ?>
    </div>
    <div class="save-prompt-form" style="margin-top:20px;padding:10px;border:1px solid #ccc;">
    <h3>Save This Prompt</h3>
	<form method="post" action="save-prompt.php">
	    <label for="prompt_name">Prompt Name:</label><br>
	    <input type="text" name="prompt_name" id="prompt_name" required>
	    <input type="hidden" name="prompt" value="<?php echo htmlspecialchars($data['prompt']); ?>">
	    <input type="hidden" name="negative_prompt" value="<?php echo htmlspecialchars($data['negative_prompt']); ?>">
	    <input type="submit" value="Save Prompt">
	</form>
    </div>
    <a href="/" >Return to Home</a>

</body>
</html>

