<?php
// Conexión a la base de datos
$host = 'localhost:3307';
$dbname = 'tiendaretro';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error al conectar con la base de datos: " . $e->getMessage());
}

// Definir la función registerUser
function registerUser($conn, $username, $password, $nombre, $apellidos, $correo, $fechaNacimiento, $genero) {
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    $stmt1 = $conn->prepare("INSERT INTO usuarios (usuario, contrasena) VALUES (:usuario, :contrasena)");
    $stmt1->bindParam(':usuario', $username);
    $stmt1->bindParam(':contrasena', $hashedPassword);

    $stmt2 = $conn->prepare("INSERT INTO clientes (usuario, nombre, apellidos, correo, fecha_nacimiento, genero) 
                             VALUES (:usuario, :nombre, :apellidos, :correo, :fecha_nacimiento, :genero)");
    $stmt2->bindParam(':usuario', $username);
    $stmt2->bindParam(':nombre', $nombre);
    $stmt2->bindParam(':apellidos', $apellidos);
    $stmt2->bindParam(':correo', $correo);
    $stmt2->bindParam(':fecha_nacimiento', $fechaNacimiento);
    $stmt2->bindParam(':genero', $genero);

    return $stmt1->execute() && $stmt2->execute();
}

// Procesar el formulario de registro
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Capturar los datos del formulario
    $username = $_POST['username'] ?? null;
    $password = $_POST['password'] ?? null;
    $nombre = $_POST['nombre'] ?? null;
    $apellidos = $_POST['apellidos'] ?? null;
    $correo = $_POST['correo'] ?? null;
    $fechaNacimiento = $_POST['fecha_nacimiento'] ?? null;
    $genero = $_POST['genero'] ?? null;

    // Validar que todos los campos estén completos
    if ($username && $password && $nombre && $apellidos && $correo && $fechaNacimiento && $genero) {
        if (registerUser($conn, $username, $password, $nombre, $apellidos, $correo, $fechaNacimiento, $genero)) {
            echo "<p style='color: green; text-align: center;'>Usuario registrado con éxito. <a href='crud.php'>Volver al inicio</a></p>";
        } else {
            echo "<p style='color: red; text-align: center;'>Error al registrar el usuario. Intente nuevamente.</p>";
        }
    } else {
        echo "<p style='color: red; text-align: center;'>Por favor, complete todos los campos.</p>";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-image: url('/pixelated_og_mario.jpg');
            background-size: cover;
            background-repeat: no-repeat;
            background-attachment: fixed;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 400px;
            margin: 50px auto;
            padding: 20px;
            background: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        h2 {
            text-align: center;
            color: #333;
        }
        form label {
            display: block;
            margin: 10px 0 5px;
            font-weight: bold;
        }
        form input {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
        }
        form button {
            width: 100%;
            padding: 10px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
        }
        form button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Registrarse</h2>
        <form method="POST">
            <label>Usuario:</label>
            <input type="text" name="username" required>
            <label>Contraseña:</label>
            <input type="password" name="password" required>
            <label>Nombre:</label>
            <input type="text" name="nombre" required>
            <label>Apellidos:</label>
            <input type="text" name="apellidos" required>
            <label>Correo:</label>
            <input type="email" name="correo" required>
            <label>Fecha de nacimiento:</label>
            <input type="date" name="fecha_nacimiento" required>
            <label>Género (M/F):</label>
            <input type="text" name="genero" maxlength="1" required>
            <button type="submit">Registrarse</button>
        </form>
    </div>
</body>
</html>