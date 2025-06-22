<?php
// loading.php
?>
<!DOCTYPE HTML>
<html>
<head>
    <title>Generating Image...</title>
    <meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
</head>
<body onload="document.forms[0].submit();">
    <h1>Generating Your Image...</h1>
    <p>Please wait. This may take up to 30 seconds depending on prompt complexity.</p>

    <form method="post" action="generate.php">
        <?php
        foreach ($_POST as $key => $value):
            $safeKey = htmlspecialchars($key, ENT_QUOTES);
            $safeValue = htmlspecialchars($value, ENT_QUOTES);
        ?>
            <input type="hidden" name="<?php echo $safeKey; ?>" value="<?php echo $safeValue; ?>">
        <?php endforeach; ?>
        <noscript>
            <p><input type="submit" value="Click here if not redirected."></p>
        </noscript>
    </form>
</body>
</html>

