<?php
require '../php/conexion.php';

if (!isset($_GET['id'])) {
    echo "Producto no encontrado";
    exit;
}

$id_producto = (int)$_GET['id'];

// 1. Información del producto
$sql_producto = "SELECT * FROM productos WHERE id_producto = $id_producto";
$res_producto = $conn->query($sql_producto);
if (!$res_producto->num_rows) {
    echo "Producto no encontrado";
    exit;
}
$producto = $res_producto->fetch_assoc();

// 2. Traer variantes (tallas, colores, precio)
$sql_variantes = "
SELECT v.id_variante, v.precio, v.stock, t.id_talla, t.nombre_talla, c.id_color, c.nombre_color
FROM variantesProducto v
JOIN tallas t ON v.id_talla = t.id_talla
JOIN colores c ON v.id_color = c.id_color
WHERE v.id_producto = $id_producto
ORDER BY v.id_variante
";
$res_variantes = $conn->query($sql_variantes);

$tallas = [];
$colores = [];
$variantes = []; // id_variante => info
while($row = $res_variantes->fetch_assoc()) {
    $tallas[$row['id_talla']] = $row['nombre_talla'];
    $colores[$row['id_color']] = [ 'nombre' => $row['nombre_color']];
    $variantes[$row['id_variante']] = $row; // guardamos cada variante
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Producto - FaDa Sports</title>
    <link rel="stylesheet" href="producto.css">
</head>
<body>

<header>
    <div class="logo-container">
        <img src="../img/logo.jpg" alt="Logo FaDa Sports" class="logo-img">
        <span class="logo-text">FaDa Sports</span>
    </div>
    <nav class="menu">
        <a href="../index.php">Inicio</a>
        <a href="../catalogo.php">Catálogo</a>
        <a href="../contacto.php">Contacto</a>
    </nav>
</header>
<div class="detalle-container">
    <div class="producto-imagen">
        <img src="../<?= htmlspecialchars($producto['imagen']) ?>" alt="<?= htmlspecialchars($producto['nombre']) ?>">
    </div>

    <div class="producto-info">
        <h2><?= htmlspecialchars($producto['nombre']) ?></h2>
        <p><?= htmlspecialchars($producto['descripcion']) ?></p>
        <p id="precio" class="precio">Precio: --</p>
        <p id="stock" class="stock">Stock: --</p>
        <p id="otras" class="otras-disponibilidades" style="display:none"></p>

        <ul class="detalle-producto">
            <li><strong>Material:</strong> <?= htmlspecialchars($producto['material']) ?></li>
        </ul>

        <!-- Formulario para enviar al carrito -->
        <form action="../php/anadir_carrito.php" method="POST">
            <input type="hidden" name="id_producto" value="<?= $producto['id_producto'] ?>">

            <!-- Selección de Talla -->
            <div class="seleccion-talla">
                <label for="talla">Elige tu talla:</label>
                <select id="talla" name="talla" onchange="updateVariant()">
                    <?php foreach ($tallas as $id_talla => $nombre_talla): ?>
                        <option value="<?= $id_talla ?>"><?= htmlspecialchars($nombre_talla) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <!-- Selección de Color -->
            <div class="seleccion-talla">
                <label for="color">Elige tu color:</label>
                <select id="color" name="color" onchange="updateVariant()">
                    <?php foreach ($colores as $id_color => $color): ?>
                        <option value="<?= $id_color ?>"><?= htmlspecialchars($color['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <input type="hidden" name="id_variante" id="id_variante" value="">
            <button type="submit" id="btnAgregar" class="btn">Agregar al carrito</button>
        </form>

    </div>
</div>


<footer>
    &copy; 2025 FaDa Sports. Todos los derechos reservados.
</footer>

<script>
// Mapa de variantes generado desde PHP
const variants = <?php echo json_encode(array_values($variantes)); ?>;
const variantMap = {};
variants.forEach(v => {
    const key = v.id_talla + '|' + v.id_color;
    variantMap[key] = v;
});

const tallaSel = document.getElementById('talla');
const colorSel = document.getElementById('color');
const precioEl = document.getElementById('precio');
const stockEl = document.getElementById('stock');
const idVarInput = document.getElementById('id_variante');
const btnAgregar = document.getElementById('btnAgregar');

function formatMoney(n){
    return Number(n).toLocaleString('es-MX', {minimumFractionDigits:2, maximumFractionDigits:2});
}

function updateVariant(){
    const talla = tallaSel.value;
    const color = colorSel.value;
    const key = talla + '|' + color;
    const v = variantMap[key];
    const otrasEl = document.getElementById('otras');
    if (v) {
        precioEl.textContent = 'Precio: $' + formatMoney(v.precio) + ' MXN';
        stockEl.textContent = 'Stock disponible: ' + v.stock;
        idVarInput.value = v.id_variante;
        otrasEl.style.display = 'none';
        stockEl.classList.remove('agotado');
        if (parseInt(v.stock) > 0) {
            btnAgregar.disabled = false;
            btnAgregar.textContent = 'Agregar al carrito';
        } else {
            // Agotado: buscar disponibilidad en otras tallas/colores
            btnAgregar.disabled = true;
            btnAgregar.textContent = 'Agotado';
            stockEl.classList.add('agotado');

            const otrasColores = new Set();
            const otrasTallas = new Set();
            variants.forEach(x => {
                if (x.id_variante == v.id_variante) return;
                if (parseInt(x.stock) > 0) {
                    if (x.id_talla == v.id_talla && x.id_color != v.id_color) otrasColores.add(x.nombre_color);
                    if (x.id_color == v.id_color && x.id_talla != v.id_talla) otrasTallas.add(x.nombre_talla);
                }
            });

            let mensajes = [];
            if (otrasColores.size > 0) mensajes.push('También disponible en otros colores: ' + Array.from(otrasColores).join(', ') + '.');
            if (otrasTallas.size > 0) mensajes.push('<br>También disponible en otras tallas: ' + Array.from(otrasTallas).join(', ') + '.');
            if (mensajes.length > 0) {
                otrasEl.innerHTML = mensajes.join(' ');
                otrasEl.style.display = 'block';
            } else {
                otrasEl.innerHTML = 'No hay disponibilidad en otras tallas o colores.';
                otrasEl.style.display = 'block';
            }
        }
    } else {
        precioEl.textContent = 'Precio: --';
        stockEl.textContent = 'No disponible para esa combinación';
        idVarInput.value = '';
        btnAgregar.disabled = true;
        btnAgregar.textContent = 'No disponible';
        otrasEl.style.display = 'none';
        stockEl.classList.remove('agotado');
    }
}

// Inicializar con la primera variante disponible
if (variants.length > 0) {
    const first = variants[0];
    if (document.querySelector('#talla option[value="' + first.id_talla + '"]')) {
        tallaSel.value = first.id_talla;
    }
    if (document.querySelector('#color option[value="' + first.id_color + '"]')) {
        colorSel.value = first.id_color;
    }
    updateVariant();
}
</script>

</body>
</html>
