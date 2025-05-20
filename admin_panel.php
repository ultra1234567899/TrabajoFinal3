<?php
session_start();

if (!isset($_SESSION['loggedInUser']) || $_SESSION['loggedInUser'] !== 'Administrador') {
    die("Acceso denegado. Solo el administrador puede acceder a esta página.");
}

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

function getAllProducts($conn) {
    return $conn->query("SELECT * FROM productos")->fetchAll(PDO::FETCH_ASSOC); 
}

function addProduct($conn, $name, $price) {
    $stmt = $conn->prepare("INSERT INTO productos (nombre, precio) VALUES (:nombre, :precio)");
    $stmt->bindParam(':nombre', $name);
    $stmt->bindParam(':precio', $price);
    return $stmt->execute(); 
}

function updateProduct($conn, $productId, $name, $price) {
    $stmt = $conn->prepare("UPDATE productos SET nombre = :nombre, precio = :precio WHERE referencia = :id");
    $stmt->bindParam(':nombre', $name);
    $stmt->bindParam(':precio', $price);
    $stmt->bindParam(':id', $productId);
    return $stmt->execute(); 
}

function deleteProduct($conn, $productId) {
    $stmt = $conn->prepare("DELETE FROM productos WHERE referencia = :id");
    $stmt->bindParam(':id', $productId);
    return $stmt->execute(); 
}

function getAllUsers($conn) {
    return $conn->query("SELECT * FROM usuarios")->fetchAll(PDO::FETCH_ASSOC); 
}

function updateUser($conn, $userId, $username, $password) {
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT); 
    $stmt = $conn->prepare("UPDATE usuarios SET usuario = :usuario, contrasena = :contrasena WHERE id = :id");
    $stmt->bindParam(':usuario', $username);
    $stmt->bindParam(':contrasena', $hashedPassword);
    $stmt->bindParam(':id', $userId);
    return $stmt->execute();
}

function deleteUser($conn, $userId) {
    $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = :id");
    $stmt->bindParam(':id', $userId);
    return $stmt->execute();
}

$message = "";
$messageClass = ""; 

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'add_product':
            if (addProduct($conn, $_POST['name'], $_POST['price'])) {
                $message = "Producto añadido con éxito.";
                $messageClass = "success";
            } else {
                $message = "Error al añadir el producto.";
                $messageClass = "error";
            }
            break;

        case 'edit_product':
            if (updateProduct($conn, $_POST['product_id'], $_POST['name'], $_POST['price'])) {
                $message = "Producto actualizado con éxito.";
                $messageClass = "success";
            } else {
                $message = "Error al actualizar el producto.";
                $messageClass = "error";
            }
            break;

        case 'delete_product':
            if (deleteProduct($conn, $_POST['product_id'])) {
                $message = "Producto eliminado con éxito.";
                $messageClass = "success";
            } else {
                $message = "Error al eliminar el producto.";
                $messageClass = "error";
            }
            break;

        case 'edit_user':
            if (updateUser($conn, $_POST['user_id'], $_POST['username'], $_POST['password'])) {
                $message = "Usuario actualizado con éxito.";
                $messageClass = "success";
            } else {
                $message = "Error al actualizar el usuario.";
                $messageClass = "error";
            }
            break;

        case 'delete_user':
            if (deleteUser($conn, $_POST['user_id'])) {
                $message = "Usuario eliminado con éxito.";
                $messageClass = "success";
            } else {
                $message = "Error al eliminar el usuario.";
                $messageClass = "error";
            }
            break;
    }
}

$products = getAllProducts($conn);
$users = getAllUsers($conn);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Control</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-image: url('electronica-1ovz9er6jk6otp61.jpg'); 
            background-size: cover;
            background-repeat: no-repeat;
            background-attachment: fixed;
            color: #333;
            margin: 0;
            padding: 0;
        }

        h1, h2, h3 {
            color: #007bff; 
        }

        label {
            color: #007bff; 
            font-weight: bold; 
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: rgba(255, 255, 255, 0.9); 
        }

        table th, table td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }

        table th {
            background-color: #007bff;
            color: white;
        }

        form {
            display: inline-block;
            margin: 5px;
        }

        input[type="text"], input[type="number"], input[type="password"] {
            margin: 5px 0;
            padding: 5px;
            width: 150px;
            border: 1px solid #007bff;
            border-radius: 3px; 
            color: #007bff; 
        }

        input[type="text"]:focus, input[type="number"]:focus, input[type="password"]:focus {
            outline: none; 
            border-color: #0056b3; 
            box-shadow: 0 0 5px #0056b3;
        }

        button {
            padding: 5px 10px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }

        button:hover {
            background-color: #0056b3;
        }

        .top-right-cell {
            position: absolute; 
            top: 10px; 
            right: 10px; 
            background: rgba(255, 255, 255, 0.9); 
            border: 1px solid #ddd; 
            border-radius: 5px;
            padding: 10px; 
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2); 
            text-align: center;
        }

        .top-right-cell a {
            text-decoration: none; /* Elimina el subrayado de los enlaces */
            color: #007bff; /* Color azul para los enlaces */
            font-weight: bold; 
        }

        .top-right-cell a:hover {
            color: #0056b3;
        }

        table td:first-child {
            color: #000; 
            font-weight: bold; 
        }

        input[name="name"], input[name="price"], input[name="username"] {
            color: #000; 
            border: 1px solid #ddd; 
        }

        input[name="name"]:focus, input[name="price"]:focus, input[name="username"]:focus {
            outline: none; 
            border-color: #007bff; 
            box-shadow: 0 0 5px #007bff; 
        }

        .message {
            text-align: center;
            font-weight: bold;
            margin-top: 10px;
        }

        .message.success {
            color: green; 
        }

        .message.error {
            color: red; 
        }
    </style>
</head>
<body>
    <div class="top-right-cell">
        <p>Opciones</p>
        <a href="registro.php">Agregar Usuario</a> 
        <br>
        <a href="crud.php">Volver a la Tienda</a> 
    </div>

    <h1 style="text-align: center;">Panel de Control</h1>
    <?php if (!empty($message)): ?>
        <p class="message <?php echo $messageClass; ?>"><?php echo $message; ?></p>
    <?php endif; ?>

    <h2>Gestionar Productos</h2>
    <form method="POST">
        <input type="hidden" name="action" value="add_product">
        <label>Nombre:</label>
        <input type="text" name="name" required>
        <label>Precio:</label>
        <input type="number" step="0.01" name="price" required>
        <button type="submit">Añadir Producto</button>
    </form>

    <h3>Lista de Productos</h3>
    <table>
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Precio</th>
            <th>Acción</th>
        </tr>
        <?php foreach ($products as $product): ?>
            <tr>
                <td><?php echo $product['referencia']; ?></td>
                <td><?php echo $product['nombre']; ?></td>
                <td><?php echo $product['precio']; ?></td>
                <td>
                    <form method="POST">
                        <input type="hidden" name="action" value="delete_product">
                        <input type="hidden" name="product_id" value="<?php echo $product['referencia']; ?>">
                        <button type="submit">Eliminar</button>
                    </form>
                    <form method="POST">
                        <input type="hidden" name="action" value="edit_product">
                        <input type="hidden" name="product_id" value="<?php echo $product['referencia']; ?>">
                        <input type="text" name="name" value="<?php echo $product['nombre']; ?>" required>
                        <input type="number" step="0.01" name="price" value="<?php echo $product['precio']; ?>" required>
                        <button type="submit">Editar</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>

    <h2>Gestionar Usuarios</h2>
    <h3>Lista de Usuarios</h3>
    <table>
        <tr>
            <th>ID</th>
            <th>Usuario</th>
            <th>Acción</th>
        </tr>
        <?php foreach ($users as $user): ?>
            <tr>
                <td><?php echo $user['id']; ?></td>
                <td><?php echo $user['usuario']; ?></td>
                <td>
                    <form method="POST">
                        <input type="hidden" name="action" value="delete_user">
                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                        <button type="submit">Eliminar</button>
                    </form>
                    <form method="POST">
                        <input type="hidden" name="action" value="edit_user">
                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                        <input type="text" name="username" value="<?php echo $user['usuario']; ?>" required>
                        <input type="password" name="password" placeholder="Nueva contraseña" required>
                        <button type="submit">Editar</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>
