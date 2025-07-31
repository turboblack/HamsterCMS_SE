<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = $_POST['input'] ?? '';
    $hash = password_hash($input, PASSWORD_DEFAULT);
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Generate password_hash</title>
</head>
<body style="font-family: sans-serif; margin: 40px;">
    <h2>🔐 Generate a hash for a login or password</h2>
    <form method="POST">
        <label>Enter a string:</label><br>
        <input type="text" name="input" style="width:300px;" required>
        <br><br>
        <button type="submit">Generate hash</button>
    </form>

    <?php if (!empty($hash)): ?>
        <h3>Result:</h3>
        <textarea style="width:100%; height:60px;" readonly><?= htmlspecialchars($hash) ?></textarea>
    <?php endif; ?>
</body>
</html>
