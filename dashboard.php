<?php
session_start();
?>

<div class="user-info">
<?php
require_once "db.php";

$onjconn = new Conexion();
$conn = $onjconn->getConexion();
function generarNumeroCuenta() {
    $numero_aleatorio = mt_rand(1000000000, 9999999999);  // Número de 10 dígitos
    return $numero_aleatorio;
}

function ValidarExistenciaCuenta($numero){
    global $conn;
    try{
        $sql = "SELECT COUNT(*) FROM cuenta WHERE numero = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $numero);
        $stmt->execute();
        $stmt->bind_result($result);
        $stmt->fetch();
        return $result > 0;
    }catch(Exception $e){
        echo "Error en la conexión: " . $e->getMessage();
    }
}

function AddAccountNumber() {
    global $conn;
    try {
        $numero_cuenta = generarNumeroCuenta();
        $esUnico = false;
        
        while (!$esUnico) {
            $sql = "SELECT COUNT(*) FROM cuenta WHERE numero = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("s", $numero_cuenta);
            $stmt->execute();
            $stmt->store_result();  // Guardar los resultados de la consulta

            $stmt->bind_result($existe);
            $stmt->fetch();

            if ($existe > 0) {
                $numero_cuenta = generarNumeroCuenta();
            } else {
                $esUnico = true;
            }

            $stmt->free_result();  // Liberar los resultados después de procesarlos
        }

        $cedula = $_SESSION['cedula'];
        $saldo = 0;
        
        $sql_insert = "INSERT INTO cuenta (numero, saldo, cedula_cliente) VALUES (?, ?, ?)";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param("sis", $numero_cuenta, $saldo, $cedula);
        $stmt_insert->execute();
        
        echo "Cuenta creada exitosamente!";
    } catch (Exception $e) {
        echo "Error en la conexión: " . $e->getMessage();
    }
}

function BuscarCuenta($cedula){
    global $conn;
    try{
        $sql = "SELECT COUNT(*) FROM cuenta WHERE cedula_cliente = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $cedula);
        $stmt->execute();
        $stmt->bind_result($result);
        $stmt->fetch();
        return $result > 0;
    }catch(Exception $e){
        echo "Error en la conexión: " . $e->getMessage();
    }
}

function BuscarPrestamo($cedula){
    global $conn;
    try{
        $sql = "SELECT COUNT(*) FROM prestamo WHERE cedula_cliente = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $cedula);
        $stmt->execute();
        $stmt->bind_result($result);
        $stmt->fetch();
        return $result > 0;
    }catch(Exception $e){
        echo "Error en la conexión: " . $e->getMessage();
    }
}

if(isset($_POST["abrirCuenta"])){
    AddAccountNumber();
}

if (isset($_POST["cerrarsesion"])){
    session_unset();
    session_destroy();
    header("Location: index.php?page=inicio");
    exit;
}

function updateSaldoByCuenta($cedula, $monto) {
    global $conn;
    try {
        $sql = "UPDATE cuenta SET saldo = saldo + ? WHERE cedula_cliente = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $monto, $cedula);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            echo "Saldo actualizado correctamente.";
        } else {
            echo "No se pudo actualizar el saldo.";
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
}

if (isset($_POST["solicitarPrestamo"])) {
    // Obtener datos del formulario
    $cedula = $_POST['cedula'];
    $monto_prestamo = $_POST['amountprestamo'];

    solicitarPrestamo($monto_prestamo, $cedula);

}


function transferirSaldo($cedula_origen, $cedula_destino, $monto) {
    global $conn;
    try {
        $conn->begin_transaction();
        
        // Verificar saldo en cuenta de origen
        $sql = "SELECT saldo FROM cuenta WHERE cedula_cliente = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $cedula_origen);
        $stmt->execute();
        $stmt->bind_result($saldo_origen);
        $stmt->fetch();
        $stmt->close();

        if ($saldo_origen < $monto) {
            throw new Exception("Saldo insuficiente.");
        }

        // Verificar existencia de cuenta destino
        $sql = "SELECT saldo FROM cuenta WHERE cedula_cliente = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $cedula_destino);
        $stmt->execute();
        $stmt->bind_result($saldo_destino);
        $stmt->fetch();

        if ($stmt->affected_rows == 0) {
            throw new Exception("La cuenta de destino no existe.");
        }

        // Descontar monto de cuenta origen
        $sql = "UPDATE cuenta SET saldo = saldo - ? WHERE cedula_cliente = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $monto, $cedula_origen);
        $stmt->execute();

        // Sumar monto a cuenta destino
        $sql = "UPDATE cuenta SET saldo = saldo + ? WHERE cedula_cliente = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $monto, $cedula_destino);
        $stmt->execute();

        $conn->commit();
        echo "Transferencia realizada con éxito.";

    } catch (Exception $e) {
        $conn->rollback();
        echo "Error al realizar la transferencia: " . $e->getMessage();
    }
}

if (isset($_POST["transferirSaldo"])) {
    $cedula_origen = $_POST['cedula'];
    $cedula_destino = $_POST['cedulaDestino'];
    $monto = $_POST['amount'];
    transferirSaldo($cedula_origen, $cedula_destino, $monto);
}


function ObtenerDatosPrestamo($cedula){
    global $conn;
    // Establecer la conexión a la base de datos con mysqli
    $link = $conn;

    // Verificar si la conexión es exitosa
    if ($link->connect_error) {
        die("Error de conexión: " . $link->connect_error);
    }

    // Prepara la consulta para evitar inyección SQL
    $sql = "SELECT deuda FROM prestamo WHERE cedula_cliente = ?";
    $stmt = $link->prepare($sql);
    $stmt->bind_param("s", $cedula); // Vincula el parámetro cedula

    // Ejecuta la consulta
    $stmt->execute();

    // Vincula el resultado a una variable
    $stmt->bind_result($deuda);
    
    // Verifica si se obtuvo un resultado
    if ($stmt->fetch()) {
        // Si se encontró, retorna un arreglo con la deuda
        return ['deuda' => $deuda];
    } else {
        // Si no se encontró el registro, retorna false
        return false;
    }

    // Cerrar los recursos
    $stmt->close();
}


function pagarPrestamo($cedula, $montoPago) {
    global $conn;
    try {
        $sql_cuenta = "SELECT saldo FROM cuenta WHERE cedula_cliente = ?";
        $stmt = $conn->prepare($sql_cuenta);
        $stmt->bind_param("s", $cedula);
        $stmt->execute();
        $stmt->bind_result($saldo);
        $stmt->fetch();
        $stmt->close();

        if ($montoPago > $saldo) {
            echo "Saldo insuficiente!";
            return;
        }

        $sql = "SELECT codigo, deuda FROM prestamo WHERE cedula_cliente = ? AND pagado = 0";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $cedula);
        $stmt->execute();
        $stmt->bind_result($codigo, $deuda);
        $stmt->fetch();

        if (!$codigo) {
            echo "No tienes un préstamo activo o ya está pagado.";
            return;
        }
        $stmt->close();
        $nuevoMonto = $deuda - $montoPago;
        
        if ($nuevoMonto < 0) {
            echo "No puedes pagar más que el monto total del préstamo.";
            return;
        }

        $sql_update_cuenta = "UPDATE cuenta SET saldo = saldo - ? WHERE cedula_cliente = ?";
        $stmt = $conn->prepare($sql_update_cuenta);
        $stmt->bind_param("is", $montoPago, $cedula);
        $stmt->execute();

        if ($nuevoMonto == 0) {
            $sql_update = "UPDATE prestamo SET deuda = 0, pagado = 1 WHERE codigo = ?";
            $stmt = $conn->prepare($sql_update);
            $stmt->bind_param("i", $codigo);
            $stmt->execute();
            eliminarUsuarioPrestamo($cedula);
            echo "Préstamo pagado completamente. Gracias.";
        } else {
            $sql_update = "UPDATE prestamo SET deuda = ? WHERE codigo = ?";
            $stmt = $conn->prepare($sql_update);
            $stmt->bind_param("ii", $nuevoMonto, $codigo);
            $stmt->execute();
            echo "Pago recibido. Monto restante a pagar: {$nuevoMonto}.";
        }

    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
}
function solicitarPrestamo($monto_solicitado, $cedula) {

    global $conn;  // Usamos la conexión global $conn
    
    // Verificar si la conexión es exitosa
    if ($conn->connect_error) {
        die("Error de conexión: " . $conn->connect_error);
    }

    // Definir el límite máximo para el préstamo
    $limite_maximo = 2147483647;

    if ($monto_solicitado <= 0) {
        echo "Error: El monto solicitado no es válido!";
        return false;
    }

    // Verificar si el monto solicitado supera el límite
    if ($monto_solicitado > $limite_maximo) {
        echo "Error: El monto solicitado excede el límite permitido.";
        return false;
    }

    // Verificar si ya existe un préstamo pendiente
    $sql = "SELECT COUNT(*) FROM prestamo WHERE cedula_cliente = ? AND pagado = 0";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        echo "Error en la preparación de la consulta: " . $conn->error;
        return;
    }
    
    $stmt->bind_param("i", $cedula); // Cambié "s" a "i" para que sea entero
    $stmt->execute();
    $stmt->bind_result($existePrestamo);
    $stmt->fetch();

    if ($existePrestamo > 0) {
        echo "Ya tienes un préstamo activo. Por favor, paga el préstamo antes de solicitar uno nuevo.";
        $stmt->close();
        return;  // Terminar la función aquí si ya hay un préstamo pendiente
    }
    $stmt->close();

    // Verificar si el cliente con la cédula existe
    $sql_cliente = "SELECT COUNT(*) FROM cliente WHERE cedula = ?";
    $stmt_cliente = $conn->prepare($sql_cliente);
    if ($stmt_cliente === false) {
        echo "Error en la preparación de la consulta: " . $conn->error;
        return;
    }
    
    $stmt_cliente->bind_param("i", $cedula); // Cambié "s" a "i" para que sea entero
    $stmt_cliente->execute();
    $stmt_cliente->bind_result($cliente_existe);
    $stmt_cliente->fetch();

    if ($cliente_existe == 0) {
        echo "Error: El cliente con cédula $cedula no existe.";
        $stmt_cliente->close();
        return false;
    }
    $stmt_cliente->close();

    // Insertar el nuevo préstamo en la base de datos
    $sql_insert = "INSERT INTO prestamo (deuda, cedula_cliente) VALUES (?, ?)";
    $stmt_insert = $conn->prepare($sql_insert);
    if ($stmt_insert === false) {
        echo "Error en la preparación de la consulta de inserción: " . $conn->error;
        return;
    }

    $stmt_insert->bind_param("ii", $monto_solicitado, $cedula); // Vincular los parámetros (deuda y cedula_cliente)
    $stmt_insert->execute();

    // Verificar si se insertó correctamente
    if ($stmt_insert->affected_rows > 0) {
        echo "Préstamo solicitado con éxito.";
    } else {
        echo "Error al solicitar el préstamo. Verifica los datos ingresados o el estado de la base de datos.";
        echo " Error MySQL: " . $conn->error;
    }

    $stmt_insert->close();
}




function eliminarUsuarioPrestamo($cedula) {
    global $conn;
    try {
        $sql = "DELETE FROM prestamo WHERE cedula_cliente = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $cedula);
        $stmt->execute();
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
}

if (isset($_POST["PagarPrestamo"])) {
    $cedula = $_POST["cedula"];
    $valorAPagar = $_POST["amountprestamopago"];
    pagarPrestamo($cedula, $valorAPagar);
}

if(isset($_POST["addsaldo"])){
    $cedula = $_POST["cedula"];
    $amount = $_POST["amount"];

    updateSaldoByCuenta($cedula, $amount);


}

$sql = "SELECT numero, saldo, cedula_cliente FROM cuenta WHERE numero = ?";
$numero_cuenta = $_SESSION['numero_cuenta'];  // Aquí iría el número de cuenta que quieres buscar
$deuda = ObtenerDatosPrestamo($_SESSION['cedula']);
// Preparar y ejecutar la consulta
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $numero_cuenta);  // Enlace del número de cuenta
$stmt->execute();

// Vincular el resultado a variables PHP
$stmt->bind_result($user, $saldo, $cedula_cliente);

// Obtener los resultados
if ($stmt->fetch()) {
    // Mostramos los resultados en los campos deseados
    echo  "<br>" ."User: " . $user . "<br>";
    echo "Saldo: " . $saldo . "<br>";
    echo "Prestamo: " . (isset($deuda["deuda"]) ? $deuda["deuda"] : 0);
      // Aquí deberías agregar la lógica si tienes un campo para préstamo
} else {
    echo $_SESSION['numero_cuenta'] . "No se encontraron resultados para el número de cuenta proporcionado.";
}
$stmt->close();
?>

</div>
<?php 
if(isset($_SESSION['login'])){
	if($_SESSION['login']=='ok'){
?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Dashboard</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

            <style>
                body {
                    font-family: Arial, sans-serif;
                    background-color: #f4f4f4;
                    margin: 0;
                    padding: 0;
                }

                .user-info {
                    background-color: #ffffff;
                    border-radius: 8px;
                    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
                    padding: 20px;
                    max-width: 600px;
                    margin: 20px auto;
                    border: 1px solid #ddd;
                }

                .user-info h1 {
                    font-size: 24px;
                    margin-bottom: 15px;
                    color: #333;
                    text-align: center;
                }

                .user-info p {
                    font-size: 18px;
                    margin-bottom: 10px;
                    color: #555;
                    text-align: center;
                }

                .user-info p:last-of-type {
                    margin-bottom: 0;
                }

                .user-info .highlight {
                    font-weight: bold;
                    color: #2c3e50; /* Color de texto destacado */
                }
                .user-info .button-container {
                text-align: center; /* Centra los botones horizontalmente */
                margin-top: 20px;
                }

                .container-fluid {
                background-color: cyan;
                border: 2px solid black;
                display: flex;
                justify-content: center;	
                }
                
                
                .col {
                    background-color: pink;
                    border: 2px solid black;
                
                }

                .btn-logout {
                    position: absolute;
                    top: 10px;
                    right: 10px;
                    font-size: 16px;
                }
            </style>
        </head>
        <body>
                <!-- Button trigger modal -->
                <div class="button-container text-center">
                    <?php if(BuscarCuenta($_SESSION["cedula"])): ?>
                        <form action="" method="POST"></form>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addsaldo">
                        Añadir saldo
                        </button>
                        </form>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#transferir"> Transferir
                        </button>
                    <?php endif; ?>
                    <?php if(!BuscarCuenta($_SESSION["cedula"])): ?>
                        <form action="" method="POST">
                            <button type="submit" class="btn btn-primary" name="abrirCuenta">
                            Abrir cuenta
                            </button>
                        </form>

                    <?php endif; ?>
                    <?php if(!BuscarPrestamo($_SESSION["cedula"]) && BuscarCuenta($_SESSION["cedula"])): ?>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#prestamos">
                        Pedir prestamo
                        </button>
                    <?php endif; ?>
                    <?php if(BuscarPrestamo($_SESSION["cedula"])): ?>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#pagarprestamo">
                        Pagar prestamo
                        </button>
                    <?php endif; ?>
                </div>

            <!-- Modal -->
            <div class="modal fade" id="addsaldo" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="exampleModalLabel">Añadir saldo</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="" method="POST">
                        <input type="hidden" id="cedula" name="cedula" required value="<?php echo $_SESSION["cedula"];?>">

                        <label for="amount">Monto a añadir:</label>
                        <input type="number" id="amount" name="amount" step="0.01" required><br><br>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        <button class="btn btn-primary" name="addsaldo">Añadir</button>
                    </form>
                </div>
                </div>
            </div>
            </div>


            <!-- Modal -->
            <div class="modal fade" id="transferir" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="exampleModalLabel">Transferencia</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="" method="POST">
                        <input type="hidden" id="cedula" name="cedula" required value="<?php echo $_SESSION["cedula"];?>">
                        
                        Cedula: <input type="text" name="cedulaDestino"><br>
                        Valor a transferir: <input type="number" id="amount" name="amount" step="0.01" required><br><br>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        <button class="btn btn-primary" name="transferirSaldo">Enviar</button>
                    </form>
                </div>
                </div>
            </div>
            </div>


            <!-- Modal -->
            <div class="modal fade" id="prestamos" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="exampleModalLabel">Prestamos</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="" method="POST">
                        <input type="hidden" id="cedula" name="cedula" required value="<?php echo $_SESSION["cedula"];?>">
                        Valor a solicitar: <input type="number" id="amountprestamo" name="amountprestamo" step="0.01" required><br><br>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        <button class="btn btn-primary" name="solicitarPrestamo">Enviar</button>
                    </form>
                </div>
                </div>
            </div>
            </div>


            <!-- Modal -->
            <div class="modal fade" id="pagarprestamo" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="exampleModalLabel">Pagar prestamo</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="" method="POST">
                        <input type="hidden" id="cedula" name="cedula" required value="<?php echo $_SESSION["cedula"];?>">
                        Valor a pagar: <input type="number" id="amountprestamopago" name="amountprestamopago" step="0.01" required><br><br>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                        <button class="btn btn-primary" name="PagarPrestamo">Pagar</button>
                    </form>
                </div>
                </div>
            </div>
            </div>

            <form action="" method="post">
                <button class="btn btn-danger btn-logout" name="cerrarsesion">Cerrar sesión</button>
            </form>
            
        <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js" integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous"></script>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js" integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy" crossorigin="anonymous"></script>
<?php
	}else{
		header("Location: login.php");
	}

}else{
	header("Location: login.php");
}		
?>
    
</body>
</html>