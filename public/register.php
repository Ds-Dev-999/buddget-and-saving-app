<?php
require_once '../config/database.php'; // Include database connection

$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (:name, :email, :password)");
    $stmt->bindParam(':name', $name);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password', $password);

    if ($stmt->execute()) {
        $message = "Registration successful. <a href='login.php'>Login here</a>";
    } else {
        $message = "Error: Unable to register user.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/styles.css">
    <title>Register</title>
</head>
<body>
    <form method="POST" action="">
        <h1>Register</h1>
        <label for="name">Name:</label>
        <input type="text" id="name" name="name" required>
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>
        <button type="submit">Register</button>
    </form>

    <!-- Modal Structure -->
    <div id="successModal" class="modal">
        <div class="modal-content">
            <span class="modal-close">&times;</span>
            <h2>Success</h2>
            <p id="modal-message"><?= $message ?></p>
        </div>
    </div>

    <script>
        // JavaScript to show the modal if there's a message
        document.addEventListener("DOMContentLoaded", function () {
            const message = <?= json_encode($message) ?>;
            if (message) {
                const modal = document.getElementById('successModal');
                const closeBtn = document.querySelector('.modal-close');

                document.getElementById('modal-message').innerHTML = message;
                modal.style.display = 'block';

                // Close modal on close button click
                closeBtn.onclick = function () {
                    modal.style.display = 'none';
                };

                // Close modal on outside click
                window.onclick = function (event) {
                    if (event.target === modal) {
                        modal.style.display = 'none';
                    }
                };
            }
        });
    </script>
</body>
</html>
