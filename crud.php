<?php
session_start(); // Inicia la sesión para manejar datos del usuario y la cesta

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
    die("Error al conectar con la base de datos: " . $e->getMessage()); // Muestra un mensaje si la conexión falla
}

// Función para registrar un nuevo usuario
function registerUser($conn, $username, $password, $nombre, $apellidos, $correo, $fechaNacimiento, $genero) {
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT); // Encripta la contraseña

    // Inserta los datos del usuario en la tabla `usuarios`
    $stmt1 = $conn->prepare("INSERT INTO usuarios (usuario, contrasena) VALUES (:usuario, :contrasena)");
    $stmt1->bindParam(':usuario', $username);
    $stmt1->bindParam(':contrasena', $hashedPassword);

    // Inserta los datos adicionales en la tabla `clientes`
    $stmt2 = $conn->prepare("INSERT INTO clientes (usuario, nombre, apellidos, correo, fecha_nacimiento, genero) 
                             VALUES (:usuario, :nombre, :apellidos, :correo, :fecha_nacimiento, :genero)");
    $stmt2->bindParam(':usuario', $username);
    $stmt2->bindParam(':nombre', $nombre);
    $stmt2->bindParam(':apellidos', $apellidos);
    $stmt2->bindParam(':correo', $correo);
    $stmt2->bindParam(':fecha_nacimiento', $fechaNacimiento);
    $stmt2->bindParam(':genero', $genero);

    return $stmt1->execute() && $stmt2->execute(); // Devuelve true si ambas consultas se ejecutan correctamente
}

// Función para iniciar sesión
function loginUser($conn, $username, $password) {
    // Busca al usuario en la base de datos
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE usuario = :usuario");
    $stmt->bindParam(':usuario', $username);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verifica la contraseña y devuelve el nombre de usuario si es correcto
    if ($user && password_verify($password, $user['contrasena'])) {
        return $username;
    }
    return false; // Devuelve false si las credenciales son incorrectas
}

// Función para obtener todos los productos disponibles
function getAllProducts($conn) {
    $stmt = $conn->query("SELECT referencia, nombre, precio FROM productos");
    return $stmt->fetchAll(PDO::FETCH_ASSOC); // Devuelve un arreglo con los productos
}

// Función para registrar una compra
function buyProduct($conn, $productId, $username) {
    $stmt = $conn->prepare("INSERT INTO compras (user_id, product_id) VALUES (:user_id, :product_id)");
    $stmt->bindParam(':user_id', $username);
    $stmt->bindParam(':product_id', $productId);
    return $stmt->execute(); // Inserta la compra en la base de datos
}

// Función para obtener el historial de compras
function getPurchaseHistory($conn) {
    $stmt = $conn->query("
        SELECT c.user_id, p.nombre AS producto, p.precio, COUNT(c.id) AS cantidad, MAX(c.fecha_compra) AS ultima_compra
        FROM compras c
        JOIN productos p ON c.product_id = p.referencia
        GROUP BY c.user_id, p.nombre, p.precio
        ORDER BY ultima_compra DESC
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC); // Devuelve un arreglo con el historial de compras
}

// Inicializa la cesta si no existe
if (!isset($_SESSION['cesta'])) {
    $_SESSION['cesta'] = []; // Crea una cesta vacía en la sesión
}

$loggedInUser = isset($_SESSION['loggedInUser']) ? $_SESSION['loggedInUser'] : null; // Verifica si hay un usuario logueado
$message = ""; // Variable para mostrar mensajes al usuario

// Procesa las acciones enviadas desde los formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'login': // Maneja el inicio de sesión
                $username = $_POST['username'];
                $password = $_POST['password'];
                $loggedInUser = loginUser($conn, $username, $password);
                if ($loggedInUser) {
                    $_SESSION['loggedInUser'] = $loggedInUser; // Guarda el usuario en la sesión
                    $message = "Inicio de sesión exitoso. Bienvenido, $loggedInUser.";
                } else {
                    $message = "Credenciales incorrectas.";
                }
                break;

            case 'buy': // Maneja la compra de un producto
                $productId = $_POST['product_id'];
                if ($loggedInUser && buyProduct($conn, $productId, $loggedInUser)) {
                    $message = "Producto comprado con éxito por $loggedInUser.";
                } else {
                    $message = "Error al comprar el producto. Inicia sesión primero.";
                }
                break;

            case 'add_to_cart': // Añade un producto a la cesta
                if ($loggedInUser) {
                    $productId = $_POST['product_id'];
                    $productName = $_POST['product_name'];
                    $productPrice = $_POST['product_price'];

                    $_SESSION['cesta'][] = [ // Agrega el producto a la cesta
                        'id' => $productId,
                        'nombre' => $productName,
                        'precio' => $productPrice
                    ];
                    $message = "Producto añadido a la cesta.";
                } else {
                    $message = "Debes iniciar sesión para añadir productos a la cesta.";
                }
                break;

            case 'clear_cart': // Vacía la cesta
                $_SESSION['cesta'] = [];
                $message = "Cesta vaciada.";
                break;

            case 'logout': // Cierra la sesión
                session_destroy();
                $loggedInUser = null;
                $message = "Has cerrado sesión.";
                break;

            case 'buy_cart': // Compra todos los productos de la cesta
                if ($loggedInUser) {
                    foreach ($_SESSION['cesta'] as $item) {
                        buyProduct($conn, $item['id'], $loggedInUser); // Registra cada producto como comprado
                    }
                    $_SESSION['cesta'] = []; // Vacía la cesta después de la compra
                    $message = "Todos los productos de la cesta han sido comprados con éxito.";
                } else {
                    $message = "Debes iniciar sesión para comprar los productos de la cesta.";
                }
                break;
        }
    }
}

// Obtiene los productos y el historial de compras para mostrarlos en la página
$products = getAllProducts($conn);
$purchases = getPurchaseHistory($conn);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tienda Retro</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        /* Estilos adicionales */
        body {
            font-family: Arial, sans-serif;
            background-image: url('/pixelated_og_mario.jpg');
            background-size: cover;
            background-repeat: no-repeat;
            background-attachment: fixed;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .login-container {
            width: 300px;
            margin: 20px auto;
            padding: 20px;
            background: rgba(255, 255, 255, 0.9); /* Fondo blanco semitransparente */
            border: 1px solid #ddd;
            border-radius: 10px;
            text-align: center;
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

        .product-image {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 5px;
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
    <h1 style="text-align: center;">Tienda Retro</h1>
    <p style="text-align: center;"><?php echo $message; ?></p>

    <!-- Formulario de inicio de sesión -->
    <?php if (!$loggedInUser): ?>
        <div class="login-container">
            <h2>Iniciar Sesión</h2>
            <form method="POST">
                <input type="hidden" name="action" value="login">
                <label>Usuario:</label>
                <input type="text" name="username" required>
                <label>Contraseña:</label>
                <input type="password" name="password" required>
                <button type="submit">Iniciar Sesión</button>
            </form>
            <p><a href="registro.php" target="_blank">Si no tiene una cuenta, cree una presionando aquí.</a></p>
        </div>
    <?php else: ?>
        <p style="text-align: center;">Bienvenido, <?php echo $loggedInUser; ?>. 
            <form method="POST" style="display:inline;">
                <input type="hidden" name="action" value="logout">
                <button type="submit">Cerrar Sesión</button>
            </form>
        </p>
        <?php if ($loggedInUser === 'Administrador'): ?>
            
            <!-- Celda en la esquina superior derecha -->
            <div class="top-right-cell">
                <a href="admin_panel.php">Panel de Control</a> <!-- Enlace al Panel de Control -->
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Productos disponibles -->
    <div>
        <h2 style="text-align: center;">Productos Disponibles</h2>
        <table>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Precio</th>
                <th>Imagen</th>
                <th>Añadir a la Cesta</th>
            </tr>
            <?php foreach ($products as $product): ?>
                <tr>
                    <td><?php echo $product['referencia']; ?></td>
                    <td><?php echo $product['nombre']; ?></td>
                    <td><?php echo $product['precio']; ?></td>
                    <td>
                        <?php
                        // Asignar la ruta de la imagen según el nombre del producto
                        $imagePath = '';
                        switch ($product['nombre']) {
                            case 'NES':
                                $imagePath = '/98986.webp';
                                break;
                            case 'SNES':
                                $imagePath = '/Wikipedia_SNES_PAL.jpg';
                                break;
                            case 'Mega Drive':
                                $imagePath = '/Sega-Mega-Drive-JP-Mk1-Console-Set.jpg';
                                break;
                            case 'PS1':
                                $imagePath = '/1200px-PSX-Console-wController.png';
                                break;
                            case 'Nintendo 64':
                                $imagePath = '/Nintendo-64-wController-L.jpg';
                                break;
                            case 'Super Mario Bros 3':
                                $imagePath = '/il_570xN.4144306203_s1w2.jpg';
                                break;
                            case 'The Legend of Zelda: A Link to the Past':
                                $imagePath = '/il_fullxfull.3866101342_n7fu.avif';
                                break;
                            case 'Sonic the Hedgehog 2':
                                $imagePath = '/Sonic_2_title_screen.webp';
                                break;
                            case 'Final Fantasy VII':
                                $imagePath = '/Hafffd062c64742af8f28dedcb93714bad.avif';
                                break;
                            case 'Super Smash Bros 64':
                                $imagePath = '/s-l400.jpg';
                                break;
                            default:
                                $imagePath = '/il_570xN.4144306203_s1w2.jpg'; // Imagen por defecto si no coincide
                                break;
                        }
                        ?>
                        <img src="<?php echo $imagePath; ?>" alt="<?php echo $product['nombre']; ?>" class="product-image">
                    </td>
                    <td>
                        <?php if ($loggedInUser): ?>
                            <form method="POST">
                                <input type="hidden" name="action" value="add_to_cart">
                                <input type="hidden" name="product_id" value="<?php echo $product['referencia']; ?>">
                                <input type="hidden" name="product_name" value="<?php echo $product['nombre']; ?>">
                                <input type="hidden" name="product_price" value="<?php echo $product['precio']; ?>">
                                <button type="submit">Añadir</button>
                            </form>
                        <?php else: ?>
                            <p>Inicia sesión para añadir</p>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>

    <!-- Comprar producto -->
    <?php if ($loggedInUser): ?>
        <h2 style="text-align: center;">Comprar Producto</h2>
        <form method="POST" style="text-align: center;">
            <input type="hidden" name="action" value="buy">
            <label>ID del Producto:</label><br>
            <input type="number" name="product_id" required><br>
            <button type="submit">Comprar</button>
        </form>
    <?php endif; ?>

    <!-- Ver Cesta -->
    <h2 style="text-align: center;">Cesta de Productos</h2>
    <?php if (empty($_SESSION['cesta'])): ?>
        <p style="text-align: center;">La cesta está vacía.</p>
    <?php else: ?>
        <table>
            <tr>
                <th>ID</th>
                <th>Nombre</th>
                <th>Precio</th>
            </tr>
            <?php foreach ($_SESSION['cesta'] as $item): ?>
                <tr>
                    <td><?php echo $item['id']; ?></td>
                    <td><?php echo $item['nombre']; ?></td>
                    <td><?php echo $item['precio']; ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
        <form method="POST" style="text-align: center; margin-top: 20px;">
            <input type="hidden" name="action" value="buy_cart">
            <button type="submit">Comprar Todo</button>
        </form>
        <form method="POST" style="text-align: center; margin-top: 10px;">
            <input type="hidden" name="action" value="clear_cart">
            <button type="submit">Vaciar Cesta</button>
        </form>
    <?php endif; ?>

    <!-- Historial de compras -->
    <h2 style="text-align: center;">Historial de Compras</h2>
    <?php if (empty($purchases)): ?>
        <p style="text-align: center;">No hay compras registradas.</p>
    <?php else: ?>
        <table>
            <tr>
                <th>Usuario</th>
                <th>Producto</th>
                <th>Precio</th>
                <th>Cantidad</th>
                <th>Última Compra</th>
            </tr>
            <?php foreach ($purchases as $purchase): ?>
                <tr>
                    <td><?php echo $purchase['user_id']; ?></td>
                    <td><?php echo $purchase['producto']; ?></td>
                    <td><?php echo $purchase['precio']; ?></td>
                    <td><?php echo $purchase['cantidad']; ?></td>
                    <td><?php echo $purchase['ultima_compra']; ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</body>
</html>