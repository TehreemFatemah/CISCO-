<?php
session_start();
require_once 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (isset($users[$username]) && password_verify($password, $users[$username]['password'])) {
        $_SESSION['domain'] = $users[$username]['domain'];
        $_SESSION['username'] = $username;
        header("Location: index.php");
        exit;
    } else {
        $errorMessage = "Invalid username or password!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nayatel - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/styles.css"> <!-- Link to your login page CSS -->
    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
        }

        #myanimation {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw; /* Full width of the viewport */
            height: 100vh; /* Full height of the viewport */
            z-index: -1; /* Place it behind the content */
            overflow: hidden; /* Avoid overflow */
        }

        .card {
            z-index: 1; /* Place the card above the animation */
            position: relative;
        }
    </style>
</head>
<body class="bg-light">
    <div id="myanimation"></div> <!-- Lottie animation container -->
    <div class="container d-flex justify-content-center align-items-center vh-100">
        <div class="card p-5 shadow-lg" style="border-radius: 15px; max-width: 400px; width: 100%; z-index:1">
            <div class="text-center">
                <!-- Logo -->
                <img src="assets/logo.svg" alt="Nayatel" class="mb-4" style="width: 150px;">
                <h4>Welcome to Nayatel's</h4>
                <h2 class="mb-4">ELC Portal</h2>
            </div>
            <?php if (!empty($errorMessage)) { ?>
                <div class="alert alert-danger text-center"><?php echo htmlspecialchars($errorMessage); ?></div>
            <?php } ?>
            <form method="post" action="">
                <div class="form-group mb-3">
                    <input type="text" class="form-control" id="username" name="username" placeholder="User ID" required>
                </div>
                <div class="form-group mb-3 position-relative">
                    <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                </div>
                <button type="submit" class="btn btn-primary w-100 mb-3" style="background-color: #1f4690; border-color: #1f4690;">Login</button>
            </form>

            <!-- Single button for Forgot Username or Password -->
            <div class="text-center mt-3">
                <a href="mailto:taclevel2@nayatel.com" class="btn btn-link" style="color: #f47735; text-decoration: none;">
                    Forgot Username or Password?
                </a>
            </div>
        </div>
    </div>

    <!-- Include Lottie Library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bodymovin/5.7.14/lottie.min.js"></script>

    <!-- Initialize Lottie and Load Animation JSON -->
    <script>
        var animation = lottie.loadAnimation({
            container: document.getElementById('myanimation'), // ID of the container
            renderer: 'svg', // Use 'svg' for better quality
            loop: true, // Set to true to loop the animation
            autoplay: true, // Set to true to start playing automatically
            path: 'assets/login_bg_animation.json', // Replace with the path to your JSON file
            rendererSettings: {
                preserveAspectRatio: 'xMidYMid slice' // Ensure full-screen coverage
            }
        });
    </script>
</body>
</html>
