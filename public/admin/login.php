<?php
declare(strict_types=1);

require __DIR__ . '/../../src/bootstrap.php';

use Dengue\{Auth, Csrf, View};

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Csrf::valido($_POST['csrf'] ?? null)) {
        http_response_code(403);
        $error = 'Solicitud no válida.';
    } else {
        $u = is_string($_POST['usuario'] ?? null) ? $_POST['usuario'] : '';
        $c = is_string($_POST['clave'] ?? null) ? $_POST['clave'] : '';
        if (Auth::login($u, $c)) {
            header('Location: index.php');
            exit;
        }
        usleep(500000);   // frena la fuerza bruta
        http_response_code(401);
        $error = 'Usuario o contraseña incorrectos.';
    }
}
View::cabecera('Administración', '../');
?>
<section class="tarjeta">
  <h1>Acceso del administrador</h1>
  <form method="post" action="login.php">
    <input type="hidden" name="csrf" value="<?= View::e(Csrf::token()) ?>">
    <?php if ($error): ?><p class="error" role="alert"><?= View::e($error) ?></p><?php endif; ?>
    <p class="campo"><label for="usuario">Usuario</label><input id="usuario" name="usuario" type="text" autocomplete="username" required></p>
    <p class="campo"><label for="clave">Contraseña</label><input id="clave" name="clave" type="password" autocomplete="current-password" required></p>
    <button class="boton" type="submit">Entrar</button>
  </form>
</section>
<?php
View::pie('../', false);
