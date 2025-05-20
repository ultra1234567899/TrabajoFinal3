<?php
session_start();

// Verifica si el usuario es "Administrador", de lo contrario, deniega el acceso.
if (!isset($_SESSION['loggedInUser']) || $_SESSION['loggedInUser'] !== 'Administrador') {
    die("Acceso denegado. Solo el administrador puede acceder a esta página.");
}

// Configuración de la conexión a la base de datos
$host = 'localhost:3307';
$dbname = 'tiendaretro';
$username = 'root';
$password = '';

try {
    // Conexión a la base de datos usando PDO
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Activa el modo de errores
} catch (PDOException $e) {
    die("Error al conectar con la base de datos: " . $e->getMessage());
}

// Funciones para gestionar productos y usuarios
function getAllProducts($conn) {
    return $conn->query("SELECT * FROM productos")->fetchAll(PDO::FETCH_ASSOC); // Devuelve todos los productos
}

function addProduct($conn, $name, $price) {
    $stmt = $conn->prepare("INSERT INTO productos (nombre, precio) VALUES (:nombre, :precio)");
    $stmt->bindParam(':nombre', $name);
    $stmt->bindParam(':precio', $price);
    return $stmt->execute(); // Inserta un nuevo producto
}

function updateProduct($conn, $productId, $name, $price) {
    $stmt = $conn->prepare("UPDATE productos SET nombre = :nombre, precio = :precio WHERE referencia = :id");
    $stmt->bindParam(':nombre', $name);
    $stmt->bindParam(':precio', $price);
    $stmt->bindParam(':id', $productId);
    return $stmt->execute(); // Actualiza un producto existente
}

function deleteProduct($conn, $productId) {
    $stmt = $conn->prepare("DELETE FROM productos WHERE referencia = :id");
    $stmt->bindParam(':id', $productId);
    return $stmt->execute(); // Elimina un producto
}

function getAllUsers($conn) {
    return $conn->query("SELECT * FROM usuarios")->fetchAll(PDO::FETCH_ASSOC); // Devuelve todos los usuarios
}

function updateUser($conn, $userId, $username, $password) {
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT); // Encripta la contraseña
    $stmt = $conn->prepare("UPDATE usuarios SET usuario = :usuario, contrasena = :contrasena WHERE id = :id");
    $stmt->bindParam(':usuario', $username);
    $stmt->bindParam(':contrasena', $hashedPassword);
    $stmt->bindParam(':id', $userId);
    return $stmt->execute(); // Actualiza un usuario
}

function deleteUser($conn, $userId) {
    $stmt = $conn->prepare("DELETE FROM usuarios WHERE id = :id");
    $stmt->bindParam(':id', $userId);
    return $stmt->execute(); // Elimina un usuario
}

// Procesa las acciones enviadas desde los formularios
$message = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action'])) {
        case 'add_product':
            $message = addProduct($conn, $_POST['name'], $_POST['price']) ? "Producto añadido con éxito." : "Error al añadir el producto.";
            break;
        case 'edit_product':
            $message = updateProduct($conn, $_POST['product_id'], $_POST['name'], $_POST['price']) ? "Producto actualizado con éxito." : "Error al actualizar el producto.";
            break;
        case 'delete_product':
            $message = deleteProduct($conn, $_POST['product_id']) ? "Producto eliminado con éxito." : "Error al eliminar el producto.";
            break;
        case 'edit_user':
            $message = updateUser($conn, $_POST['user_id'], $_POST['username'], $_POST['password']) ? "Usuario actualizado con éxito." : "Error al actualizar el usuario.";
            break;
        case 'delete_user':
            $message = deleteUser($conn, $_POST['user_id']) ? "Usuario eliminado con éxito." : "Error al eliminar el usuario.";
            break;
    }
}

// Obtiene los datos para mostrarlos en el panel
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
            background-image: url('descarga.jpeg'); /* Imagen de fondo */
            background-size: cover;
            background-repeat: no-repeat;
            background-attachment: fixed;
            color: #333;
            margin: 0;
            padding: 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: rgba(255, 255, 255, 0.9); /* Fondo blanco semitransparente */
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
            position: absolute; /* Posiciona el contenedor de forma absoluta */
            top: 10px; /* Margen desde la parte superior */
            right: 10px; /* Margen desde la parte derecha */
            background: rgba(255, 255, 255, 0.9); /* Fondo blanco semitransparente */
            border: 1px solid #ddd; /* Borde gris claro */
            border-radius: 5px; /* Bordes redondeados */
            padding: 10px; /* Espaciado interno */
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2); /* Sombra para darle profundidad */
            text-align: center; /* Centra el texto */
        }

        .top-right-cell a {
            text-decoration: none; /* Elimina el subrayado de los enlaces */
            color: #007bff; /* Color azul para los enlaces */
            font-weight: bold; /* Texto en negrita */
        }

        .top-right-cell a:hover {
            color: #0056b3; /* Cambia el color al pasar el cursor */
        }
    </style>
</head>
<body>
    <!-- Celda en la esquina superior derecha -->
    <div class="top-right-cell">
        <p>Opciones</p>
        <a href="logout.php">Cerrar Sesión</a> <!-- Enlace para cerrar sesión -->
        <br>
        <a href="index.php">Volver a la Tienda</a> <!-- Enlace para volver a la tienda -->
    </div>

    <h1 style="text-align: center;">Panel de Control</h1>
    <p style="text-align: center;"><?php echo $message; ?></p>

    <!-- Gestión de productos -->
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

    <!-- Gestión de usuarios -->
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