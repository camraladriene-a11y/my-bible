<?php

session_start();

require_once "db.php";


$message = "";


if (
    isset($_GET["registered"])
) {

    $message =
        "Account created successfully. Please login.";

}


if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username =
        trim($_POST["username"]);

    $password =
        $_POST["password"];


    $query = $conn->prepare(
        "SELECT * FROM users WHERE username = ?"
    );


    $query->execute([
        $username
    ]);


    $user = $query->fetch(
        PDO::FETCH_ASSOC
    );


    if (
        $user &&
        password_verify(
            $password,
            $user["password"]
        )
    ) {

        $_SESSION["user_id"] =
            $user["id"];

        $_SESSION["user_name"] =
            $user["name"];

        $_SESSION["username"] =
            $user["username"];


        header(
            "Location: index.php"
        );

        exit;

    } else {

        $message =
            "Incorrect username or password.";

    }

}

?>

<!DOCTYPE html>

<html>

<head>

<meta charset="UTF-8">

<meta name="viewport"
      content="width=device-width, initial-scale=1.0">

<title>Login - My Bible</title>

<link rel="stylesheet"
      href="style.css">

</head>

<body class="auth-page">


<div class="auth-card">


<div class="logo">

📖

</div>


<h1>Welcome Back</h1>

<p class="subtitle">

Login to My Bible

</p>


<?php if ($message !== ""): ?>

<div class="message">

<?php

echo htmlspecialchars(
    $message
);

?>

</div>

<?php endif; ?>


<form method="POST">


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

Login

</button>


</form>


<p class="switch">

Don't have an account?

<a href="register.php">

Register

</a>

</p>


</div>


</body>

</html>