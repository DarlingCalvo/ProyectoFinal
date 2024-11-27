<?php
session_start();

require_once "db.php";

$onjconn = new Conexion();
$conn = $onjconn->getConexion();
// Función para agregar un nuevo usuario a la base de datos
function addUser($cedula, $nombre, $apellido, $email, $hashed_password, $imagenConRuta = null) {
    global $conn;
    $link = $conn;
    
    if ($link->connect_errno) {
        echo "Falló la conexión a MySQL: (" . $link->connect_errno . ") " . $link->connect_error;
    } else {
        $sql = "INSERT INTO cliente (cedula, nombre, apellido, password, email) 
                VALUES ('$cedula', '$nombre', '$apellido', '$hashed_password', '$email')";
        
        $result = $link->query($sql);
        if ($result) {
            $_SESSION['registro'] = 'ok';
        } else {
            $_SESSION['registro'] = 'fail';
        }
    }

}

// Función para buscar un usuario por su cédula (usando mysqli)
function BuscarUsuario($cedula) {
    global $conn;
    $link = $conn;
    
    if ($link->connect_errno) {
        echo "Falló la conexión a MySQL: (" . $link->connect_errno . ") " . $link->connect_error;
        return false;
    }
    
    $sql = "SELECT COUNT(*) FROM cliente WHERE cedula = ?";
    $stmt = $link->prepare($sql);
    $stmt->bind_param('s', $cedula);
    $stmt->execute();
    $stmt->bind_result($result);
    $stmt->fetch();

    $stmt->close();

    
    return $result > 0;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Obtener datos del formulario
    $cedula = $_POST['cedula'];
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $saldo = 0;
    $prestamo = 0;

    $rutaTemp = $_FILES["fotocliente"]["tmp_name"];
    $name = $_FILES["fotocliente"]["name"];
    $carpeta = $cedula;

    $ruta = "./img/$carpeta";
    $imagenConRuta = "$ruta/$name";

    if (isset($_FILES['fotocliente']) && !BuscarUsuario($cedula)) {

        if ($_FILES['fotocliente']['error'] === 0) {

            if ($_FILES['fotocliente']['size'] > 0) {

                $min_length = 8;
                $max_length = 16;
                
                if (strlen($password) < $min_length || strlen($password) > $max_length) {
                    $_SESSION["registro"] = "passwordNoSeguro";
                } elseif (!preg_match('/[A-Za-z]/', $password)) {
                    $_SESSION["registro"] = "passwordSinLetra";
                } elseif (!preg_match('/[0-9]/', $password)) {
                    $_SESSION["registro"] = "passwordSinNumero";
                } else {
                    // Creación carpeta con el número de la cédula
                    mkdir($ruta, 0700);
                    // Mover imagen hacia la nueva ruta
                    move_uploaded_file($rutaTemp, "./img/$carpeta/$name");

                    // Encriptar la contraseña
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
                    // Agregar el usuario a la base de datos
                    addUser($cedula, $nombre, $apellido, $email, $hashed_password);
                }
            } else {
                $_SESSION["registro"] = "archivoVacio";
            }
        } else {
            $_SESSION["registro"] = "errorSubirArchivo";
        }
    } else {
        if (BuscarUsuario($cedula)) {
            $_SESSION["registro"] = "cedulaEncontrada";
        }

        if (!isset($_FILES['fotocliente'])) {
            $_SESSION["registro"] = "imagenNoEncontrada";
        }
    }
}
?>

<?php 
if (isset($_SESSION['login'])) {
    header("Location: dashboard.php");
} else {
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Formulario de Registro</title>
    <link rel="stylesheet" href="css/registro.css">
</head>
<body>
    <div class="center">
        <h1>Registro</h1>
        <?php
            if (isset($_SESSION["registro"])) {
                $registro = $_SESSION["registro"];
                switch ($registro) {
                    case 'cedulaEncontrada':
                        echo "<h4>La cédula ya está registrada!</h4>";
                        break;
                    case 'imagenNoEncontrada':
                        echo "<h4>No ha subido una imagen!</h4>";
                        break;
                    case 'archivoVacio':
                        echo "<h4>El archivo está vacío.</h4>";
                        break;
                    case 'errorSubirArchivo':
                        echo "<h4>Error al subir el archivo.</h4>";
                        break;
                    case 'passwordNoSeguro':
                        echo "<h4>La contraseña debe tener entre $min_length y $max_length caracteres.</h4>";
                        break;
                    case 'passwordSinLetra':
                        echo "<h4>La contraseña debe contener al menos una letra.</h4>";
                        break;
                    case 'passwordSinNumero':
                        echo "<h4>La contraseña debe contener al menos un número.</h4>";
                        break;
                    case 'ok':
                        echo "<h3>Registro exitoso. Puedes iniciar sesión <a href='login.php'>aquí</a></h3>";
                        break;
                    case 'fail':
                        echo "<h4>Ha ocurrido un error, por favor intente nuevamente!</h4>";
                        break;
                }
                unset($_SESSION["registro"]);
            }
        ?>
        <form method="POST" enctype="multipart/form-data">
            <div class="txt_field">
                <input type="text" id="cedula" name="cedula" required>
                <span></span>
                <label>Cedula</label>
            </div>
            <div class="txt_field">
                <input type="text" id="nombre" name="nombre" required>
                <span></span>
                <label>Nombre</label>
            </div>
            <div class="txt_field">
                <input type="text" id="apellido" name="apellido" required>
                <span></span>
                <label>Apellido</label>
            </div>
            <div class="txt_field">
                <input type="email" id="email" name="email" required>
                <span></span>
                <label>Email</label>
            </div>
            <div class="txt_field">
                <input type="password" id="password" name="password" required>
                <span></span>
                <label>Contraseña</label>
            </div>
            <div class="txt_field">
                <input type="file" name="fotocliente">
                <span></span>
                <label for="fotocliente">Foto cédula:</label>
            </div>
            <input type="submit" value="Registrarse">
            <div class="signup_link">
                ¿Ya estás registrado? <a href="login.php">Iniciar sesión</a>
            </div>
        </form>
    </div>
</body>
</html>
<?php
}
?>
