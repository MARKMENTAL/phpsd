<?php
// save-prompt.php
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

function savePrompt($prompt_text, $negative_prompt_text, $prompt_name) {
    global $saved_prompts_file;
    $prompts = loadSavedPrompts();
    $prompts[$prompt_name] = array(
        'prompt' => $prompt_text,
        'negative_prompt' => $negative_prompt_text
    );
    file_put_contents($saved_prompts_file, json_encode($prompts, JSON_PRETTY_PRINT));
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $prompt = trim($_POST['prompt'] ?? '');
    $negative_prompt = trim($_POST['negative_prompt'] ?? '');
    $prompt_name = trim($_POST['prompt_name'] ?? '');

    if (!empty($prompt) && !empty($prompt_name)) {
        savePrompt($prompt, $negative_prompt, $prompt_name);
        header("Location: index.php?save_success=1");
    } else {
        header("Location: index.php?save_error=1");
    }
    exit;
} else {
    echo "Invalid request method.";
}

