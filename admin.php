<?php
$cfg = require __DIR__ . '/config.php';
$dataFile = $cfg['data_file'];

session_start();
$error = '';

// Logout
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: admin.php');
    exit;
}

// Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    if ($_POST['password'] === $cfg['admin_password']) {
        $_SESSION['admin'] = true;
        header('Location: admin.php');
        exit;
    } else {
        $error = 'Password errata.';
    }
}

// Delete entry
if ($_SESSION['admin'] && isset($_GET['delete'])) {
    $responses = [];
    if (file_exists($dataFile)) {
        $responses = json_decode(file_get_contents($dataFile), true) ?: [];
    }
    $idx = intval($_GET['delete']);
    if (isset($responses[$idx])) {
        array_splice($responses, $idx, 1);
        file_put_contents($dataFile, json_encode($responses, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    header('Location: admin.php');
    exit;
}

// Load data
$responses = [];
if (file_exists($dataFile)) {
    $responses = json_decode(file_get_contents($dataFile), true) ?: [];
}
$yes   = array_filter($responses, fn($r) => $r['coming'] === 'si');
$maybe = array_filter($responses, fn($r) => $r['coming'] === 'forse');
$no    = array_filter($responses, fn($r) => $r['coming'] === 'no');
$totalClimbs = array_sum(array_column(array_values($yes), 'climbs'));
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin - <?= $cfg['event_name'] ?></title>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Nunito', sans-serif; background: #f0f4e8; color: #1a2a0a; min-height: 100vh; }
  .top-bar {
    background: #1e3d10;
    color: #fff;
    padding: 1rem 1.5rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
  }
  .top-bar h1 { font-family: 'Bebas Neue', sans-serif; font-size: 1.6rem; letter-spacing: .08em; }
  .top-bar a { color: #f9b234; font-weight: 700; text-decoration: none; font-size: .9rem; }
  .top-bar a:hover { text-decoration: underline; }
  .container { max-width: 960px; margin: 0 auto; padding: 2rem 1.2rem; }
  .login-box {
    max-width: 360px;
    margin: 4rem auto;
    background: #fff;
    border-radius: 16px;
    padding: 2rem;
    box-shadow: 0 4px 20px rgba(0,0,0,.12);
    text-align: center;
  }
  .login-box h2 { font-family: 'Bebas Neue'; font-size: 1.8rem; margin-bottom: 1.2rem; }
  .login-box input {
    width: 100%; padding: .7rem 1rem; border: 2px solid #d4e4b8;
    border-radius: 8px; font-size: 1rem; margin-bottom: 1rem; outline: none;
    font-family: 'Nunito', sans-serif;
  }
  .login-box input:focus { border-color: #4a7c3f; }
  .btn { display: inline-block; padding: .6em 2em; background: #2d5a1b; color: #fff;
    border: none; border-radius: 2em; font-family: 'Bebas Neue'; font-size: 1.2rem;
    letter-spacing: .08em; cursor: pointer; transition: background .2s; }
  .btn:hover { background: #4a7c3f; }
  .btn-danger { background: #c0392b; font-size: .85rem; padding: .3em 1em; font-family: 'Nunito'; font-weight: 700; letter-spacing: 0; }
  .btn-danger:hover { background: #922b21; }
  .error { background: #fde8e8; color: #b71c1c; border-radius: 8px; padding: .7rem 1rem; margin-bottom: 1rem; font-weight: 700; }
  .stats-grid { display: flex; flex-wrap: wrap; gap: 1rem; margin-bottom: 2rem; }
  .stat { background: #fff; border-radius: 12px; padding: 1rem 1.5rem; text-align: center;
    border-top: 4px solid #4a7c3f; box-shadow: 0 2px 8px rgba(0,0,0,.08); min-width: 120px; }
  .stat .n { font-family: 'Bebas Neue'; font-size: 2.5rem; color: #2d5a1b; line-height: 1; }
  .stat .l { font-size: .8rem; font-weight: 700; color: #666; text-transform: uppercase; }
  table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 12px; overflow: hidden;
    box-shadow: 0 2px 12px rgba(0,0,0,.08); }
  th { background: #2d5a1b; color: #fff; font-family: 'Bebas Neue'; font-size: 1.05rem;
    letter-spacing: .06em; padding: .7rem 1rem; text-align: left; }
  td { padding: .65rem 1rem; border-bottom: 1px solid #f0f4e8; font-size: .92rem; vertical-align: top; }
  tr:last-child td { border-bottom: none; }
  tr:hover td { background: #f8fbf3; }
  .badge { display: inline-block; padding: .2em .7em; border-radius: 2em; font-weight: 700; font-size: .8rem; }
  .badge-si    { background: #d4f5c8; color: #1e5c0e; }
  .badge-forse { background: #fef3e2; color: #7d4e24; }
  .badge-no    { background: #fde8e8; color: #b71c1c; }
  .ts { font-size: .75rem; color: #aaa; }
  .export-link { display: inline-block; margin-bottom: 1.2rem; color: #2d5a1b; font-weight: 700; text-decoration: none; }
  .export-link:hover { text-decoration: underline; }
</style>
</head>
<body>

<div class="top-bar">
  <h1>&#x1F4CB; Admin Panel &mdash; <?= $cfg['event_name'] ?></h1>
  <?php if ($_SESSION['admin'] ?? false): ?>
    <a href="admin.php?logout=1">Esci</a>
    <a href="index.php">&#x2190; Torna al sito</a>
  <?php endif; ?>
</div>

<?php if (!($_SESSION['admin'] ?? false)): ?>
  <div class="login-box">
    <h2>&#x1F512; Accesso Admin</h2>
    <?php if ($error): ?><div class="error"><?= $error ?></div><?php endif; ?>
    <form method="POST">
      <input type="password" name="password" placeholder="Password" autofocus>
      <button type="submit" class="btn">Accedi</button>
    </form>
  </div>

<?php else: ?>
  <div class="container">

    <div class="stats-grid">
      <div class="stat"><div class="n"><?= count($responses) ?></div><div class="l">Totale risposte</div></div>
      <div class="stat"><div class="n"><?= count($yes) ?></div><div class="l">&#x2705; Confermati</div></div>
      <div class="stat"><div class="n"><?= count($maybe) ?></div><div class="l">&#x1F914; Forse</div></div>
      <div class="stat"><div class="n"><?= count($no) ?></div><div class="l">&#x1F614; No</div></div>
      <div class="stat"><div class="n"><?= $totalClimbs ?></div><div class="l">&#x26F0;&#xFE0F; Salite totali</div></div>
      <div class="stat"><div class="n"><?= $totalClimbs ?></div><div class="l">&#x1F37A; Birre dovute</div></div>
    </div>

    <a class="export-link" href="export.php">&#x2B07;&#xFE0F; Esporta CSV</a>

    <?php if (empty($responses)): ?>
      <p style="text-align:center;padding:3rem;color:#888;">Nessuna risposta ancora.</p>
    <?php else: ?>
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Nome</th>
          <th>Risposta</th>
          <th>+Adulti</th>
          <th>+Bimbi</th>
          <th>Salite</th>
          <th>Porta</th>
          <th>Note</th>
          <th>Data</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($responses as $i => $r): ?>
        <tr>
          <td><?= $i + 1 ?></td>
          <td><strong><?= $r['name'] ?></strong></td>
          <td><span class="badge badge-<?= $r['coming'] ?>"><?= $r['coming'] === 'si' ? '&#x2705; Si' : ($r['coming'] === 'forse' ? '&#x1F914; Forse' : '&#x1F614; No') ?></span></td>
          <td><?= ($r['adults'] ?? 0) > 0 ? '+' . $r['adults'] : '<span style="color:#ccc">—</span>' ?></td>
          <td><?= ($r['children'] ?? 0) > 0 ? $r['children'] : '<span style="color:#ccc">—</span>' ?></td>
          <td><?= $r['coming'] === 'si' ? ($r['climbs'] > 0 ? $r['climbs'] . ' &#x1F37A;' : '-') : '-' ?></td>
          <td><?= !empty($r['bringing']) ? $r['bringing'] : '<span style="color:#ccc">—</span>' ?></td>
          <td><?= !empty($r['notes']) ? $r['notes'] : '<span style="color:#ccc">—</span>' ?></td>
          <td class="ts"><?= $r['timestamp'] ?></td>
          <td>
            <a href="admin.php?delete=<?= $i ?>" class="btn btn-danger"
               onclick="return confirm('Eliminare la risposta di <?= addslashes($r['name']) ?>?')">Elimina</a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>
<?php endif; ?>

</body>
</html>
