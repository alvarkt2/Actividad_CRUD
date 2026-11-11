
<?php
require_once "conexion.php";

$error = "";
$categorias = [
    "Computadores",
    "Monitores",
    "Accesorios",
    "Almacenamiento",
    "Audio",
    "Redes"
];

function limpiar($valor) {
    return htmlspecialchars((string)$valor, ENT_QUOTES, "UTF-8");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $accion = $_POST["accion"] ?? "";

    if ($accion === "eliminar") {

        $id = filter_var(
            $_POST["id"] ?? null,
            FILTER_VALIDATE_INT
        );

        if ($id && $id > 0) {
            $stmt = $pdo->prepare(
                "DELETE FROM productos WHERE id = ?"
            );
            $stmt->execute([$id]);

            header("Location: index.php?ok=eliminado");
            exit;
        }

        $error = "Identificador de producto inválido.";
    }

    if ($accion === "guardar") {

        $id = (int)($_POST["id"] ?? 0);
        $nombre = trim($_POST["nombre"] ?? "");
        $categoria = trim($_POST["categoria"] ?? "");
        $precio = $_POST["precio"] ?? "";
        $stock = $_POST["stock"] ?? "";

        if (
            $nombre === "" ||
            $categoria === "" ||
            $precio === "" ||
            $stock === ""
        ) {
            $error = "Todos los campos son obligatorios.";

        } elseif (mb_strlen($nombre) < 3) {
            $error = "El nombre debe tener mínimo 3 caracteres.";

        } elseif (!in_array($categoria, $categorias, true)) {
            $error = "Seleccione una categoría válida.";

        } elseif (
            !is_numeric($precio) ||
            !is_finite((float)$precio) ||
            (float)$precio < 1000
        ) {
            $error = "El precio debe ser mayor o igual a $1.000.";

        } elseif (
            !ctype_digit((string)$stock) ||
            filter_var($stock, FILTER_VALIDATE_INT) === false
        ) {
            $error = "El stock debe ser un número entero mayor o igual a 0.";

        } else {

            if ($id > 0) {

                $sql = "UPDATE productos
                        SET nombre = ?,
                            categoria = ?,
                            precio = ?,
                            stock = ?
                        WHERE id = ?";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    $nombre,
                    $categoria,
                    $precio,
                    $stock,
                    $id
                ]);

                header("Location: index.php?ok=actualizado");
                exit;

            } else {

                $sql = "INSERT INTO productos
                        (nombre, categoria, precio, stock)
                        VALUES (?, ?, ?, ?)";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    $nombre,
                    $categoria,
                    $precio,
                    $stock
                ]);

                header("Location: index.php?ok=creado");
                exit;
            }
        }
    }
}

$productoEditar = [
    "id" => 0,
    "nombre" => "",
    "categoria" => "Accesorios",
    "precio" => "",
    "stock" => 0
];

if (isset($_GET["editar"])) {

    $idEditar = (int)$_GET["editar"];

    $stmt = $pdo->prepare(
        "SELECT * FROM productos WHERE id = ?"
    );

    $stmt->execute([$idEditar]);

    $encontrado = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($encontrado) {
        $productoEditar = $encontrado;
    }
}

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    ($_POST["accion"] ?? "") === "guardar" &&
    $error !== ""
) {
    $productoEditar = [
        "id" => (int)($_POST["id"] ?? 0),
        "nombre" => $_POST["nombre"] ?? "",
        "categoria" => $_POST["categoria"] ?? "Accesorios",
        "precio" => $_POST["precio"] ?? "",
        "stock" => $_POST["stock"] ?? 0
    ];
}

$busqueda = trim($_GET["q"] ?? "");

if ($busqueda !== "") {

    $stmt = $pdo->prepare(
        "SELECT * FROM productos
         WHERE nombre LIKE ?
         OR categoria LIKE ?
         ORDER BY id DESC"
    );

    $termino = "%" . $busqueda . "%";

    $stmt->execute([$termino, $termino]);

} else {

    $stmt = $pdo->query(
        "SELECT * FROM productos ORDER BY id DESC"
    );
}

$productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totales = $pdo->query(
    "SELECT COUNT(*) AS total_productos,
            COALESCE(SUM(stock), 0) AS total_unidades,
            COALESCE(SUM(precio * stock), 0) AS valor_inventario
     FROM productos"
)->fetch(PDO::FETCH_ASSOC);

$totalProductos = (int)$totales["total_productos"];
$totalUnidades = (int)$totales["total_unidades"];
$valorInventario = (float)$totales["valor_inventario"];

$mensajes = [
    "creado" => "Producto registrado correctamente.",
    "actualizado" => "Producto actualizado correctamente.",
    "eliminado" => "Producto eliminado correctamente."
];

$mensaje = $mensajes[$_GET["ok"] ?? ""] ?? "";
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>IberoTech | Gestor de Inventario</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>
        body {
            background-color: #f2f5f9;
            font-family: Arial, Helvetica, sans-serif;
        }

        .navbar {
            background: linear-gradient(
                90deg,
                #0d2b4a,
                #17639c
            );
        }

        .navbar-brand {
            font-size: 1.25rem;
        }

        .titulo-seccion {
            color: #123a5c;
            font-weight: 700;
        }

        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.06);
        }

        .card-indicador {
            border-left: 5px solid #17639c;
        }

        .indicador-label {
            font-size: 0.85rem;
            color: #64748b;
        }

        .indicador-valor {
            font-size: 1.65rem;
            font-weight: 700;
            color: #123a5c;
        }

        .form-label {
            font-weight: 600;
            color: #334155;
        }

        .btn-primary {
            background-color: #17639c;
            border-color: #17639c;
        }

        .btn-primary:hover {
            background-color: #0d2b4a;
            border-color: #0d2b4a;
        }

        .table thead th {
            background-color: #123a5c;
            color: white;
            white-space: nowrap;
        }

        .table td {
            vertical-align: middle;
        }

        .tabla-productos {
            min-width: 750px;
        }

        .badge-stock {
            font-size: 0.8rem;
            padding: 7px 10px;
        }

        .footer {
            color: #64748b;
            font-size: 0.85rem;
        }

        @media (max-width: 767px) {

            .navbar-brand {
                font-size: 1rem;
            }

            .indicador-valor {
                font-size: 1.35rem;
            }

            .card-body {
                padding: 1rem;
            }

            .btn-movil {
                width: 100%;
            }

            .titulo-seccion {
                font-size: 1.15rem;
            }
        }
    </style>
</head>

<body>

<nav class="navbar navbar-dark py-3">
    <div class="container">

        <a class="navbar-brand fw-bold"
           href="index.php">
            IberoTech
            <span class="fw-normal">
                | Gestor de Inventario
            </span>
        </a>

    </div>
</nav>

<main class="container py-4">

    <?php if ($mensaje !== ""): ?>

        <div class="alert alert-success alert-dismissible fade show"
             role="alert">

            <?= limpiar($mensaje) ?>

            <button type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"
                    aria-label="Cerrar">
            </button>

        </div>

    <?php endif; ?>

    <?php if ($error !== ""): ?>

        <div class="alert alert-danger"
             role="alert">

            <?= limpiar($error) ?>

        </div>

    <?php endif; ?>

    <section class="row g-3 mb-4">

        <div class="col-12 col-md-4">

            <div class="card card-indicador h-100">
                <div class="card-body">

                    <div class="indicador-label">
                        Productos registrados
                    </div>

                    <div class="indicador-valor">
                        <?= $totalProductos ?>
                    </div>

                </div>
            </div>

        </div>

        <div class="col-12 col-md-4">

            <div class="card card-indicador h-100">
                <div class="card-body">

                    <div class="indicador-label">
                        Unidades en inventario
                    </div>

                    <div class="indicador-valor">
                        <?= $totalUnidades ?>
                    </div>

                </div>
            </div>

        </div>

        <div class="col-12 col-md-4">

            <div class="card card-indicador h-100">
                <div class="card-body">

                    <div class="indicador-label">
                        Valor del inventario
                    </div>

                    <div class="indicador-valor">
                        $<?= number_format(
                            $valorInventario,
                            0,
                            ",",
                            "."
                        ) ?>
                    </div>

                </div>
            </div>

        </div>

    </section>

    <section class="row g-4">

        <div class="col-12 col-lg-4">

            <div class="card h-100">

                <div class="card-body">

                    <h4 class="titulo-seccion mb-4">

                        <?= $productoEditar["id"] > 0
                            ? "Editar producto"
                            : "Nuevo producto" ?>

                    </h4>

                    <form method="POST"
                          action="index.php">

                        <input type="hidden"
                               name="accion"
                               value="guardar">

                        <input type="hidden"
                               name="id"
                               value="<?= (int)$productoEditar["id"] ?>">

                        <div class="mb-3">

                            <label class="form-label">
                                Nombre del producto
                            </label>

                            <input
                                type="text"
                                class="form-control"
                                name="nombre"
                                minlength="3"
                                maxlength="100"
                                required
                                value="<?= limpiar(
                                    $productoEditar["nombre"]
                                ) ?>"
                            >

                        </div>

                        <div class="mb-3">

                            <label class="form-label">
                                Categoría
                            </label>

                            <select
                                name="categoria"
                                class="form-select"
                                required
                            >

                                <?php foreach ($categorias as $cat): ?>

                                    <option
                                        value="<?= limpiar($cat) ?>"
                                        <?= $productoEditar["categoria"] === $cat
                                            ? "selected"
                                            : "" ?>
                                    >
                                        <?= limpiar($cat) ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>

                        <div class="mb-3">

                            <label class="form-label">
                                Precio ($ COP)
                            </label>

                            <input
                                type="number"
                                class="form-control"
                                name="precio"
                                min="1000"
                                step="0.01"
                                required
                                value="<?= limpiar(
                                    $productoEditar["precio"]
                                ) ?>"
                            >

                        </div>

                        <div class="mb-3">

                            <label class="form-label">
                                Stock disponible
                            </label>

                            <input
                                type="number"
                                class="form-control"
                                name="stock"
                                min="0"
                                step="1"
                                required
                                value="<?= limpiar(
                                    $productoEditar["stock"]
                                ) ?>"
                            >

                        </div>

                        <div class="d-grid gap-2">

                            <button
                                type="submit"
                                class="btn btn-primary"
                            >

                                <?= $productoEditar["id"] > 0
                                    ? "Guardar cambios"
                                    : "Agregar producto" ?>

                            </button>

                            <?php if ($productoEditar["id"] > 0): ?>

                                <a
                                    href="index.php"
                                    class="btn btn-outline-secondary"
                                >
                                    Cancelar edición
                                </a>

                            <?php endif; ?>

                        </div>

                    </form>

                </div>

            </div>

        </div>

        <div class="col-12 col-lg-8">

            <div class="card">

                <div class="card-body">

                    <div class="d-flex flex-column flex-md-row
                                justify-content-between
                                align-items-md-center
                                gap-3 mb-4">

                        <h4 class="titulo-seccion mb-0">
                            Inventario de productos
                        </h4>

                        <span class="badge bg-primary">
                            <?= count($productos) ?> resultados
                        </span>

                    </div>

                    <form
                        method="GET"
                        action="index.php"
                        class="row g-2 mb-4"
                    >

                        <div class="col-12 col-sm-7">

                            <input
                                type="text"
                                name="q"
                                class="form-control"
                                placeholder="Buscar nombre o categoría..."
                                value="<?= limpiar($busqueda) ?>"
                            >

                        </div>

                        <div class="col-6 col-sm-3">

                            <button
                                type="submit"
                                class="btn btn-primary w-100"
                            >
                                Buscar
                            </button>

                        </div>

                        <div class="col-6 col-sm-2">

                            <a
                                href="index.php"
                                class="btn btn-outline-secondary w-100"
                            >
                                Limpiar
                            </a>

                        </div>

                    </form>

                    <div class="table-responsive">

                        <table class="table table-hover table-bordered
                                      align-middle tabla-productos">

                            <thead>

                                <tr>
                                    <th>ID</th>
                                    <th>Producto</th>
                                    <th>Categoría</th>
                                    <th>Precio</th>
                                    <th>Stock</th>
                                    <th>Acciones</th>
                                </tr>

                            </thead>

                            <tbody>

                                <?php if (count($productos) === 0): ?>

                                    <tr>
                                        <td colspan="6"
                                            class="text-center py-4 text-muted">

                                            No se encontraron productos.

                                        </td>
                                    </tr>

                                <?php else: ?>

                                    <?php foreach ($productos as $p): ?>

                                        <tr>

                                            <td>
                                                <?= (int)$p["id"] ?>
                                            </td>

                                            <td class="fw-semibold">
                                                <?= limpiar($p["nombre"]) ?>
                                            </td>

                                            <td>
                                                <?= limpiar($p["categoria"]) ?>
                                            </td>

                                            <td class="text-nowrap">
                                                $<?= number_format(
                                                    $p["precio"],
                                                    0,
                                                    ",",
                                                    "."
                                                ) ?>
                                            </td>

                                            <td>

                                                <?php if ($p["stock"] > 0): ?>

                                                    <span class="badge bg-success badge-stock">
                                                        <?= (int)$p["stock"] ?>
                                                        disponibles
                                                    </span>

                                                <?php else: ?>

                                                    <span class="badge bg-danger badge-stock">
                                                        Agotado
                                                    </span>

                                                <?php endif; ?>

                                            </td>

                                            <td>

                                                <div class="d-flex gap-2">

                                                    <a
                                                        href="index.php?editar=<?= (int)$p["id"] ?>"
                                                        class="btn btn-sm btn-outline-primary"
                                                    >
                                                        Editar
                                                    </a>

                                                    <form
                                                        method="POST"
                                                        action="index.php"
                                                        onsubmit="return confirm('¿Está seguro de eliminar este producto?');"
                                                    >

                                                        <input
                                                            type="hidden"
                                                            name="accion"
                                                            value="eliminar"
                                                        >

                                                        <input
                                                            type="hidden"
                                                            name="id"
                                                            value="<?= (int)$p["id"] ?>"
                                                        >

                                                        <button
                                                            type="submit"
                                                            class="btn btn-sm btn-outline-danger"
                                                        >
                                                            Eliminar
                                                        </button>

                                                    </form>

                                                </div>

                                            </td>

                                        </tr>

                                    <?php endforeach; ?>

                                <?php endif; ?>

                            </tbody>

                        </table>

                    </div>

                </div>

            </div>

        </div>

    </section>

</main>

<footer class="footer text-center py-4">

    <div class="container">
        IberoTech | Sistema de Gestión de Inventario
        <br>
        Desarrollado con PHP, MySQL y Bootstrap 5
    </div>

</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
