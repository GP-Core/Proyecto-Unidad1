<?php
session_start();
if (!isset($_SESSION['id_usuario'])) {
    header("Location: php/login.php");
    exit();
}

$id_usuario = $_SESSION['id_usuario'];

require_once "php/conexion.php";

/* ==== 1. Obtener datos del usuario ==== */
$sql_user = "SELECT nombre, email, telefono FROM usuarios WHERE id_usuario = ?";
$stmt = $conn->prepare($sql_user);
$stmt->bind_param("i", $id_usuario);
$stmt->execute();
$result_user = $stmt->get_result()->fetch_assoc();

/* ==== 2. Obtener historial de compras (CORREGIDO) ==== */
$sql_compras = "
    SELECT
        c.id_compra,
        c.fecha_compra,
        c.total_compra,
        c.estado,

        GROUP_CONCAT(
            CONCAT(
                p.nombre,
                ' - ', col.nombre_color,
                ' - ', t.nombre_talla,
                ' (x', d.cantidad, ') — $', d.subtotal
            )
            SEPARATOR '<br>'
        ) AS productos

    FROM compras c
    JOIN detalleCompra d ON c.id_compra = d.id_compra
    JOIN variantesProducto vp ON d.id_variante = vp.id_variante
    JOIN productos p ON vp.id_producto = p.id_producto
    JOIN colores col ON vp.id_color = col.id_color
    JOIN tallas t ON vp.id_talla = t.id_talla

    WHERE c.id_usuario = ?

    GROUP BY c.id_compra
    ORDER BY c.fecha_compra DESC
";
$stmt2 = $conn->prepare($sql_compras);
$stmt2->bind_param("i", $id_usuario);
$stmt2->execute();
$compras = $stmt2->get_result();

/* ==== 3. Obtener direcciones ==== */
$sql_dir = "SELECT * FROM direccionesUsuario WHERE id_usuario = ?";
$stmt3 = $conn->prepare($sql_dir);
$stmt3->bind_param("i", $id_usuario);
$stmt3->execute();
$direcciones = $stmt3->get_result();

/* ==== 4. Obtener tarjetas del usuario ==== */
/* ==== 4. Obtener tarjetas del usuario (incluye marca) ==== */
$sql_tarjetas = "SELECT 
                    id_tarjeta, 
                    titular, 
                    numero_tarjeta, 
                    mes_expiracion, 
                    anio_expiracion, 
                    es_predeterminada,
                    marca
                 FROM tarjetasUsuario 
                 WHERE id_usuario = ?";

$stmt4 = $conn->prepare($sql_tarjetas);
$stmt4->bind_param("i", $id_usuario);
$stmt4->execute();
$tarjetas = $stmt4->get_result();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Perfil de Usuario - FaDa Sports</title>
    <link rel="icon" type="img/logo.jpg" href="img/logo.jpg">
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<header>
    <div class="logo">FaDa Sports</div>
    <nav class="menu">
        <a style="padding-top: 10px" href="index.php">Inicio</a>
        <a style="padding-top: 10px" href="catalogo.php">Catálogo</a>
        <a style="padding-top: 10px" href="carrito/carrito.php">Mi Carrito</a>
        <a style="padding-top: 10px" href="contacto.php">Contacto</a>
        <a href="php/logout.php" class="cerrar-sesion">Cerrar Sesión</a>
    </nav>
</header>

<section class="perfil section">

<h1>Mi Perfil</h1>

<!-- DATOS DEL USUARIO -->
<div class="perfil-card">
    <h2>Datos del Usuario</h2>

    <?php if (isset($_GET['error']) && $_GET['error'] == 'email_duplicado'): ?>
        <p style="color:red; padding-bottom: 10px;">El correo ya está registrado por otro usuario.</p>
    <?php endif; ?>

    <?php if (isset($_GET['msg']) && $_GET['msg'] == 'ok'): ?>
        <p style="color:green; padding-bottom: 10px;">Datos actualizados correctamente.</p>
    <?php endif; ?>


    <form action="update_usuario.php" method="POST" class="perfil-form">

        <p><strong>Nombre:</strong> <?= $result_user['nombre'] ?></p>

        <div class="field-row">
            <label>Email:</label>
            <input type="email" name="email" value="<?= $result_user['email'] ?>" required>
        </div>

        <div class="field-row">
            <label>Teléfono:</label>
            <input maxlength="10" type="text" name="telefono" value="<?= $result_user['telefono'] ?>">
        </div>

        <button class="btn">Actualizar Información</button>

    </form>
</div>


<!-- HISTORIAL DE COMPRAS -->
<div class="perfil-card">
    <h2>Historial de Compras</h2>

    <div class="tabla-container">
        <table class="tabla-compras">
            <thead>
                <tr>
                    <th>Productos</th>
                    <th>Fecha</th>
                    <th>Monto</th>
                    <th>Estado</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $compras->fetch_assoc()) { ?>
                <tr>
                    <td><?= $row['productos'] ?></td>
                    <td><?= $row['fecha_compra'] ?></td>
                    <td>$<?= $row['total_compra'] ?></td>
                    <td><?= $row['estado'] ?></td>

                    <td>
                        <?php if ($row['estado'] === "Pendiente") { ?>
                            <a href="cancelar_compra.php?id=<?= $row['id_compra'] ?>" class="btn btn-danger">Cancelar</a>
                        <?php } ?>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</div>

<!-- DIRECCIONES -->
<div class="perfil-card">
    <h2>Direcciones de Envío</h2>

    <ul class="lista-direcciones">
    <?php while ($d = $direcciones->fetch_assoc()) { ?>
        <li class="direccion-item"
            data-direccion="<?= strtolower(trim($d['direccion'])) ?>"
            data-ciudad="<?= strtolower(trim($d['ciudad'])) ?>"
            data-cp="<?= strtolower(trim($d['codigo_postal'])) ?>">
            
            <span>
                <strong><?= $d['ciudad'] ?>:</strong>
                <?= $d['direccion'] ?>, CP <?= $d['codigo_postal'] ?>
            </span>


            <a href="eliminar_direccion.php?id=<?= $d['id_direccion'] ?>"
               class="btn-eliminar"
               onclick="return confirm('¿Eliminar esta dirección?');">
               Eliminar
            </a>
        </li>
    <?php } ?>
</ul>

    <button class="btn" onclick="document.getElementById('form-dir').style.display='block'">
        Agregar Nueva Dirección
    </button>

    <div id="mensajeDireccion" 
        style="display:none; 
                background:#ffdddd; 
                color:#a40000; 
                padding:10px; 
                border-left:4px solid #d00000; 
                margin-bottom:10px; 
                border-radius:5px;
                margin-top:15px;">
    </div>

    <div id="form-dir" style="display:none; margin-top:15px;">
        <form class="resumen-final" id="formAgregarDireccion" action="agregar_direccion.php" method="POST">
            <input type="text" name="direccion" placeholder="Dirección completa" required>
            <input type="text" name="ciudad" placeholder="Ciudad" required>
            <input type="text" name="codigo_postal" placeholder="Código Postal" required>
            <button class="btn">Guardar Dirección</button>
        </form>
    </div>
</div>

<!-- TARJETAS GUARDADAS -->
<div class="perfil-card">
    <h2>Métodos de Pago (Tarjetas)</h2>

    <div class="tabla-container">
        <table class="tabla-compras">
            <thead>
                <tr>
                    <th>Titular</th>
                    <th>Número</th>
                    <th>Expiración</th>
                    <th>Banca</th>
                    <th>Acción</th>
                </tr>
            </thead>
            <tbody>

            <?php while ($t = $tarjetas->fetch_assoc()) { ?>
            <tr>
                <td><?= $t['titular'] ?></td>

                <td><?= $t['numero_tarjeta'] ?></td>

                <td><?= $t['mes_expiracion'] ?>/<?= $t['anio_expiracion'] ?></td>

                <td style="text-align:center;">
                    <?php if (!empty($t['marca'])) { ?>
                        <img src="img/<?= $t['marca'] ?>.png" 
                            alt="<?= $t['marca'] ?>" 
                            style="height:32px; margin-bottom:4px; display:block; margin-left:auto; margin-right:auto;">
                    <?php } ?>
                </td>

                <td>
                    <a href="php/eliminar_tarjeta.php?id=<?= $t['id_tarjeta'] ?>" 
                    class="btn-eliminar"
                    onclick="return confirm('¿Eliminar esta tarjeta?')">
                    Eliminar
                    </a>
                </td>
            </tr>
            <?php } ?>



            </tbody>
        </table>
    </div>

    <!-- Botón para mostrar formulario -->
    <button class="btn"
        onclick="document.getElementById('form-tarjeta').style.display='block'">
        Agregar Nueva Tarjeta
    </button>

    <!-- Formulario oculto -->
    <div id="form-tarjeta" style="display:none; margin-top:15px;">
        
        <form id="formAgregarTarjeta" action="php/guardar_tarjeta.php" method="POST" class="resumen-final">

            <input id="titular" type="text" name="titular" placeholder="Titular de la tarjeta" required>

            <div id="mensajeTarjeta" style="margin-top:3px; font-size:14px;"></div>

            <div style="position: relative; display: inline-block; width: 100%;">
                <input 
                    type="text" 
                    id="numero_tarjeta" 
                    name="numero_tarjeta" 
                    maxlength="19" 
                    placeholder="Número de tarjeta"
                    style="padding-right: 45px;"
                >

                <!-- Icono dentro del input -->
                <img 
                    id="icono-tarjeta" 
                    src="" 
                    alt=""
                    style="
                        position: absolute;
                        right: 10px;
                        top: 50%;
                        transform: translateY(-50%);
                        height: 28px;
                        display: none;
                    "
                >
            </div>


            <div style="display:flex; gap:10px;">
                <input type="text" id="expiracion" name="expiracion" placeholder="MM/AA" maxlength="5">
                <p id="error-exp"></p>
            </div>

            <button class="btn">Guardar Tarjeta</button>
        </form>
    </div>
</div>


</section>

<footer>
    <p>&copy; 2025 FaDa Sports. Todos los derechos reservados.</p>
</footer>

<script src="js/validar_direccion.js"></script>
<script src="js/validar_tarjeta.js"></script>
<script src="js/validar_telefono.js"></script>
<script>
    document.getElementById('numero_tarjeta').addEventListener('input', function(e) {
        // Quita todos los espacios
        let valor = e.target.value.replace(/\s+/g, '');

        // Permite solo números
        valor = valor.replace(/\D/g, '');

        // Agrupa cada 4 dígitos
        let formateado = valor.match(/.{1,4}/g)?.join(' ') || '';

        e.target.value = formateado;
    });
</script>

<script>
    window.tarjetasRegistradas = [
        <?php
        mysqli_data_seek($tarjetas, 0); // Reinicia el puntero
        while ($t = $tarjetas->fetch_assoc()) {
            $ultimos4 = substr($t['numero_tarjeta'], -4);
            echo "'$ultimos4',";
        }
        ?>
    ];
</script>

</body>
</html>
