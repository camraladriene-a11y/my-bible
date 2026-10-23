<?php

session_start();

require_once "db.php";


$message = "";


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name = trim($_POST["name"]);

    $username = trim($_POST["username"]);

    $password = $_POST["password"];


    if (
        $name === "" ||
        $username === "" ||
        $password === ""
    ) {

        $message = "Please fill in all fields.";

    } else {

        $check = $conn->prepare(
            "SELECT id FROM users WHERE username = ?"
        );

        $check->execute([
            $username
        ]);


        if ($check->fetch()) {

            $message = "Username already exists.";

        } else {

            $hashedPassword =
                password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );


            $insert = $conn->prepare(
                "INSERT INTO users
                (name, username, password)
                VALUES (?, ?, ?)"
            );


            $insert->execute([

                $name,

                $username,

                $hashedPassword

            ]);


            header(
                "Location: login.php?registered=1"
            );

            exit;

        }

    }

}

?>

<!DOCTYPE html>

<html>

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Register - My Bible</title>

<link rel="stylesheet"
      href="style.css">

</head>

<body class="auth-page">


<div class="auth-card">

<div class="logo">

📖

</div>


<h1>Create Account</h1>

<p class="subtitle">

Create your My Bible account

</p>


<?php if ($message !== ""): ?>

<div class="error">

<?php

echo htmlspecialchars(
    $message
);

?>

</div>

<?php endif; ?>


<form method="POST">


<label>

Full Name

</label>

<input
    type="text"
    name="name"
    placeholder="Enter your name"
    required
>


<label>

Username

</label>

<input
    type="text"
    name="username"
    placeholder="Enter username"
    required
>


<label>

Password

</label>

<input
    type="password"
    name="password"
    placeholder="Enter password"
    required
>


<button type="submit">

Create Account

</button>


</form>


<p class="switch">

Already have an account?

<a href="login.php">

Login

</a>

</p>


</div>


</body>

</html>