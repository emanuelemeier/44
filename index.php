<?php
$cfg      = require __DIR__ . '/config.php';
$dataFile = $cfg['data_file'];
if (!is_dir(dirname($dataFile))) mkdir(dirname($dataFile), 0755, true);

$responses = file_exists($dataFile)
    ? (json_decode(file_get_contents($dataFile), true) ?: [])
    : [];

$success = false; $error = ''; $myName = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'rsvp') {
    $myName   = htmlspecialchars(trim($_POST['name']     ?? ''), ENT_QUOTES);
    $coming   = $_POST['coming']   ?? '';
    $climbs   = max(0, min(11, intval($_POST['climbs']   ?? 0)));
    $adults   = max(0, min(9,  intval($_POST['adults']   ?? 0)));
    $children = max(0, min(9,  intval($_POST['children'] ?? 0)));
    $bringing = htmlspecialchars(trim($_POST['bringing'] ?? ''), ENT_QUOTES);
    $notes    = htmlspecialchars(trim($_POST['notes']    ?? ''), ENT_QUOTES);

    if ($myName === '') {
        $error = 'Inserisci il tuo nome!';
    } elseif (!in_array($coming, ['si', 'forse', 'no'])) {
        $error = 'Devi rispondere se vieni o no!';
    } else {
        foreach ($responses as $r) {
            if (mb_strtolower($r['name']) === mb_strtolower($myName)) {
                $error = 'Esiste già una risposta per "' . $myName . '". Contatta Emanuele per modificarla.';
                break;
            }
        }
        if (!$error) {
            $responses[] = [
                'name'      => $myName,
                'coming'    => $coming,
                'climbs'    => ($coming === 'si') ? $climbs : 0,
                'adults'    => $adults,
                'children'  => $children,
                'bringing'  => $bringing,
                'notes'     => $notes,
                'timestamp' => date('Y-m-d H:i:s'),
            ];
            file_put_contents($dataFile, json_encode($responses, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $success = true;
        }
    }
}

$yes   = array_values(array_filter($responses, fn($r) => $r['coming'] === 'si'));
$maybe = array_values(array_filter($responses, fn($r) => $r['coming'] === 'forse'));
$no    = array_values(array_filter($responses, fn($r) => $r['coming'] === 'no'));

$totalClimbs   = array_sum(array_column($yes, 'climbs'));
$runners       = array_filter($yes, fn($r) => $r['climbs'] > 0);
$totalAdults   = count($yes) + array_sum(array_map(fn($r) => $r['adults']   ?? 0, $yes));
$totalChildren = array_sum(array_map(fn($r) => $r['children'] ?? 0, $yes));
$totalHeads    = $totalAdults + $totalChildren;
$bringingSomething = array_filter($responses, fn($r) => !empty($r['bringing']));

// Group bringing items for round-robin display
$bringingCounts = [];
foreach ($responses as $r) {
    if (!empty($r['bringing'])) {
        $items = array_filter(array_map('trim', explode(',', $r['bringing'])));
        foreach ($items as $item) {
            $key = mb_strtolower($item);
            $bringingCounts[$key] = ($bringingCounts[$key] ?? 0) + 1;
        }
    }
}
arsort($bringingCounts);
$maxBringing = $bringingCounts ? max($bringingCounts) : 1;
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $cfg['event_name'] ?> — <?= $cfg['event_date'] ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Nunito:wght@400;600;700;800;900&display=swap" rel="stylesheet">
<style>
:root {
  --forest: #1e3d10; --green: #2d5a1b; --mid: #4a7c3f; --light: #7ab648;
  --sun: #f9b234;    --beer: #d4881e;  --fire: #e05c1a; --earth: #7d4e24;
  --cream: #fef9f0;  --card: #fffdf7;  --shadow: rgba(0,0,0,.15);
}
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: 'Nunito', sans-serif; background: var(--cream); color: #2a1a0a; overflow-x: hidden; }

/* ===== HERO ===== */
.hero {
  position: relative;
  width: 100%;
  height: 100svh;
  min-height: 560px;
  overflow: hidden;
}
.scene-svg {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
}
/* gradient overlay: sky is readable for text at top, mountains dark at bottom */
.hero-vignette {
  position: absolute;
  inset: 0;
  background: linear-gradient(
    to bottom,
    rgba(0,0,0,.35) 0%,
    rgba(0,0,0,.1)  40%,
    rgba(0,0,0,0)   65%,
    rgba(0,0,0,.45) 100%
  );
  pointer-events: none;
}
.hero-text {
  position: absolute;
  inset: 0;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 2rem 1rem 6rem;
  text-align: center;
  color: #fff;
  pointer-events: none;
}
.hero-badge {
  display: inline-block;
  background: var(--fire);
  color: #fff;
  font-family: 'Bebas Neue', sans-serif;
  font-size: 1.05rem;
  letter-spacing: .18em;
  padding: .3em 1.4em;
  border-radius: 2em;
  box-shadow: 0 2px 12px rgba(0,0,0,.4);
  animation: pop-in .7s cubic-bezier(.36,.07,.19,.97) .3s both;
}
.hero h1 {
  font-family: 'Bebas Neue', sans-serif;
  font-size: clamp(3.2rem, 11vw, 8rem);
  line-height: .95;
  letter-spacing: .04em;
  text-shadow: 4px 5px 0 rgba(0,0,0,.4), 0 0 60px rgba(0,0,0,.3);
  margin: .4rem 0 .2rem;
  animation: slide-up .7s ease-out .1s both;
}
.hero h1 em { color: var(--sun); font-style: normal; }
.hero-sub {
  font-family: 'Bebas Neue', sans-serif;
  font-size: clamp(1.2rem, 3.5vw, 2.2rem);
  letter-spacing: .12em;
  color: var(--sun);
  text-shadow: 2px 2px 6px rgba(0,0,0,.5);
  animation: slide-up .7s ease-out .25s both;
}
.hero-date {
  display: inline-block;
  background: var(--sun);
  color: var(--forest);
  font-family: 'Bebas Neue', sans-serif;
  font-size: clamp(1.4rem, 4vw, 2rem);
  letter-spacing: .14em;
  padding: .3em 1.6em;
  border-radius: 6px;
  margin: .6rem 0 1rem;
  box-shadow: 0 4px 20px rgba(0,0,0,.35);
  animation: pop-in .7s cubic-bezier(.36,.07,.19,.97) .45s both;
}
.schedule {
  display: flex;
  gap: .6rem;
  flex-wrap: wrap;
  justify-content: center;
  animation: slide-up .7s ease-out .6s both;
  pointer-events: auto;
}
.pill {
  display: flex;
  align-items: center;
  gap: .4rem;
  background: rgba(255,255,255,.15);
  backdrop-filter: blur(10px);
  -webkit-backdrop-filter: blur(10px);
  border: 1px solid rgba(255,255,255,.35);
  color: #fff;
  border-radius: 2em;
  padding: .45em 1.1em;
  font-weight: 700;
  font-size: .88rem;
  text-shadow: 0 1px 3px rgba(0,0,0,.4);
}
.scroll-hint {
  position: absolute;
  bottom: 1.5rem;
  left: 50%;
  transform: translateX(-50%);
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: .3rem;
  color: rgba(255,255,255,.7);
  font-size: .75rem;
  font-weight: 700;
  letter-spacing: .1em;
  text-transform: uppercase;
  animation: float-down 2s ease-in-out infinite;
}
.scroll-hint svg { width: 20px; height: 20px; fill: none; stroke: currentColor; stroke-width: 2; }
@keyframes float-down {
  0%,100% { transform: translateX(-50%) translateY(0); opacity: .7; }
  50%      { transform: translateX(-50%) translateY(6px); opacity: 1; }
}
@keyframes pop-in {
  0%   { transform: scale(0) rotate(-3deg); opacity: 0; }
  60%  { transform: scale(1.1); }
  100% { transform: scale(1); opacity: 1; }
}
@keyframes slide-up {
  from { transform: translateY(30px); opacity: 0; }
  to   { transform: translateY(0); opacity: 1; }
}

/* ===== BEER RULES BANNER ===== */
.beer-rules {
  background: var(--earth);
  color: var(--sun);
  padding: 1.1rem 1.5rem;
  text-align: center;
  font-family: 'Bebas Neue', sans-serif;
  font-size: clamp(.85rem, 2.5vw, 1.25rem);
  letter-spacing: .08em;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 1.5rem;
  flex-wrap: wrap;
  border-bottom: 4px solid var(--beer);
}
.beer-rules .rule { display: flex; align-items: center; gap: .4rem; }

/* ===== CONTENT ===== */
.container { max-width: 920px; margin: 0 auto; padding: 2rem 1.2rem 5rem; }

.stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
  gap: .9rem;
  margin-bottom: 2.5rem;
}
.stat-card {
  background: var(--card);
  border-radius: 16px;
  padding: 1.2rem .7rem;
  text-align: center;
  box-shadow: 0 4px 16px var(--shadow);
  border-top: 4px solid var(--mid);
  transition: transform .2s;
}
.stat-card:hover { transform: translateY(-3px); }
.stat-card .num { font-family: 'Bebas Neue', sans-serif; font-size: 2.8rem; color: var(--green); line-height: 1; }
.stat-card .lbl { font-size: .78rem; font-weight: 700; color: #777; text-transform: uppercase; letter-spacing: .05em; margin-top: .1rem; }
.stat-beer .num { color: var(--beer); }
.stat-fire .num { color: var(--fire); }
.stat-people .num { color: #2c59b8; }

.section-title {
  font-family: 'Bebas Neue', sans-serif;
  font-size: 2rem;
  letter-spacing: .08em;
  color: var(--forest);
  margin: 2rem 0 1rem;
  display: flex;
  align-items: center;
  gap: .6rem;
}
.section-title::after {
  content: '';
  flex: 1;
  height: 2px;
  background: linear-gradient(to right, var(--mid), transparent);
}

/* ===== FORM ===== */
.rsvp-box {
  background: var(--card);
  border-radius: 20px;
  padding: 2rem;
  box-shadow: 0 6px 28px var(--shadow);
  border: 2px solid #e8f0d8;
}
.form-row { margin-bottom: 1.25rem; }
.form-row-2col { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
label { display: block; font-weight: 700; margin-bottom: .4rem; font-size: .95rem; color: var(--forest); }
.optional { font-weight: 400; color: #999; font-size: .82rem; }
input[type=text], textarea, select {
  width: 100%;
  padding: .7rem 1rem;
  border: 2px solid #d4e4b8;
  border-radius: 10px;
  font-family: 'Nunito', sans-serif;
  font-size: 1rem;
  background: #f8fbf3;
  transition: border-color .2s, box-shadow .2s;
  outline: none;
}
input[type=text]:focus, textarea:focus, select:focus {
  border-color: var(--mid);
  box-shadow: 0 0 0 3px rgba(74,124,63,.15);
  background: #fff;
}
textarea { resize: vertical; min-height: 70px; }

.coming-group { display: flex; gap: .8rem; flex-wrap: wrap; }
.coming-opt input { display: none; }
.coming-opt label {
  display: flex; align-items: center; gap: .4rem;
  padding: .6em 1.4em;
  border: 2px solid #d4e4b8;
  border-radius: 2em;
  cursor: pointer; font-weight: 700;
  transition: all .2s; background: #f8fbf3; margin: 0;
}
.coming-opt input:checked + label { color: #fff; border-color: transparent; }
.coming-opt.opt-si    input:checked + label { background: var(--green); }
.coming-opt.opt-forse input:checked + label { background: var(--beer); }
.coming-opt.opt-no    input:checked + label { background: #c0392b; }
.coming-opt label:hover { transform: scale(1.04); box-shadow: 0 2px 8px rgba(0,0,0,.12); }

#climbs-row  { display: none; }
#guests-row  { display: none; }

.climbs-grid { display: flex; flex-wrap: wrap; gap: .5rem; }
.climb-opt input { display: none; }
.climb-opt label {
  display: flex; flex-direction: column; align-items: center;
  padding: .5em .7em;
  border: 2px solid #d4e4b8;
  border-radius: 10px;
  cursor: pointer; font-weight: 800; font-size: 1rem;
  min-width: 58px; background: #f8fbf3; transition: all .2s; margin: 0;
}
.climb-opt label .sub { font-size: .62rem; font-weight: 600; color: #999; text-transform: uppercase; }
.climb-opt input:checked + label { background: var(--forest); color: var(--sun); border-color: var(--forest); }
.climb-opt input:checked + label .sub { color: var(--light); }
.climb-opt label:hover { transform: scale(1.06); box-shadow: 0 2px 8px rgba(0,0,0,.12); }

.guests-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.guests-grid label { font-size: .88rem; }
.guests-grid select { padding: .55rem .8rem; }

.btn-submit {
  display: inline-flex; align-items: center; gap: .5rem;
  background: linear-gradient(135deg, var(--green), var(--mid));
  color: #fff;
  font-family: 'Bebas Neue', sans-serif;
  font-size: 1.4rem; letter-spacing: .1em;
  padding: .7em 2.5em;
  border: none; border-radius: 2em; cursor: pointer;
  box-shadow: 0 4px 18px rgba(45,90,27,.35);
  transition: transform .2s, box-shadow .2s;
  margin-top: .5rem;
}
.btn-submit:hover { transform: translateY(-2px) scale(1.03); box-shadow: 0 8px 28px rgba(45,90,27,.4); }

.alert { border-radius: 12px; padding: 1rem 1.3rem; margin-bottom: 1.2rem; font-weight: 700; }
.alert-success { background: #d4f5c8; border: 2px solid var(--light); color: var(--forest); }
.alert-error   { background: #fde8e8; border: 2px solid #e57373; color: #b71c1c; }

/* ===== GUEST LIST ===== */
.guest-list { display: flex; flex-direction: column; gap: .85rem; }
.guest-card {
  background: var(--card);
  border-radius: 14px; padding: 1rem 1.3rem;
  display: flex; align-items: flex-start; gap: 1rem;
  box-shadow: 0 3px 14px var(--shadow);
  border-left: 5px solid var(--mid);
  animation: fade-up .4s ease both;
  transition: transform .2s;
}
.guest-card:hover { transform: translateX(4px); }
.guest-card.coming-forse { border-left-color: var(--beer); }
.guest-card.coming-no    { border-left-color: #e57373; opacity: .6; }
@keyframes fade-up {
  from { opacity: 0; transform: translateY(10px); }
  to   { opacity: 1; transform: translateY(0); }
}
.guest-avatar {
  width: 46px; height: 46px; border-radius: 50%;
  background: var(--mid); color: #fff;
  display: flex; align-items: center; justify-content: center;
  font-family: 'Bebas Neue', sans-serif; font-size: 1.4rem; flex-shrink: 0;
}
.coming-forse .guest-avatar { background: var(--beer); }
.coming-no    .guest-avatar { background: #e57373; }
.guest-info { flex: 1; min-width: 0; }
.guest-name { font-weight: 800; font-size: 1.05rem; }
.guest-meta { display: flex; flex-wrap: wrap; gap: .45rem; margin-top: .35rem; }
.tag {
  display: inline-flex; align-items: center; gap: .25rem;
  font-size: .76rem; font-weight: 700;
  padding: .2em .75em; border-radius: 2em;
  background: #eef5e6; color: var(--forest);
}
.tag-beer   { background: #fef3e2; color: var(--earth); }
.tag-fire   { background: #fde8e0; color: var(--fire); }
.tag-food   { background: #e8f0fe; color: #2c59b8; }
.tag-people { background: #f0e8fe; color: #6b2fb8; }
.guest-notes { font-size: .84rem; color: #777; margin-top: .3rem; font-style: italic; }

.empty { text-align: center; padding: 3rem 1rem; color: #999; font-size: 1.1rem; }
.empty .big { font-size: 3.5rem; margin-bottom: .5rem; }

footer {
  text-align: center; padding: 2rem 1rem;
  color: #bbb; font-size: .85rem;
  border-top: 2px dashed #e0e8d0;
}
footer a { color: var(--mid); text-decoration: none; }
footer a:hover { text-decoration: underline; }
.beer-bob { display: inline-block; animation: beer-bob 1.6s ease-in-out infinite alternate; }
@keyframes beer-bob {
  from { transform: translateY(0) rotate(-5deg); }
  to   { transform: translateY(-8px) rotate(5deg); }
}

/* ===== BRINGING STATUS (form) ===== */
.bringing-status {
  display: flex; flex-wrap: wrap; gap: .4rem;
  margin-bottom: .65rem; align-items: center;
}
.bringing-status-label {
  font-size: .75rem; font-weight: 700; color: #aaa;
  text-transform: uppercase; letter-spacing: .06em;
}
.bringing-tag {
  display: inline-flex; align-items: center; gap: .2rem;
  background: #e8f5e9; color: #2d5a1b;
  border: 1.5px solid #c8e6c9; border-radius: 2em;
  padding: .2em .8em; font-size: .78rem; font-weight: 700;
  transition: transform .15s;
}
.bringing-tag:hover { transform: scale(1.06); }

/* ===== BRINGING CHART ===== */
.bringing-chart {
  background: var(--card); border-radius: 20px;
  padding: 1.5rem 1.8rem;
  box-shadow: 0 6px 28px var(--shadow);
  border: 2px solid #e8f0d8;
  display: flex; flex-direction: column; gap: .75rem;
}
.chart-row {
  display: grid; grid-template-columns: 140px 1fr;
  align-items: center; gap: 1rem;
}
.chart-label {
  font-weight: 700; font-size: .88rem; color: var(--forest);
  text-align: right; white-space: nowrap;
  overflow: hidden; text-overflow: ellipsis;
}
.chart-track {
  background: #f0f4e8; border-radius: 2em;
  height: 40px; overflow: hidden; position: relative;
}
.chart-bar {
  height: 100%; width: 0;
  border-radius: 2em;
  display: flex; align-items: center; justify-content: flex-end;
  padding-right: .9rem;
  transition: width 1.1s cubic-bezier(.25,.8,.25,1);
  position: relative; overflow: hidden;
  box-shadow: 0 2px 10px rgba(0,0,0,.18);
}
.chart-bar::before {
  content: '';
  position: absolute; inset: 0;
  background: linear-gradient(90deg, rgba(0,0,0,.12) 0%, transparent 60%);
}
.chart-bar::after {
  content: '';
  position: absolute; top: 0; left: 0; right: 0; height: 45%;
  background: rgba(255,255,255,.22); border-radius: 2em 2em 0 0;
}
.chart-count {
  font-family: 'Bebas Neue', sans-serif; font-size: 1.15rem;
  color: #fff; text-shadow: 0 1px 4px rgba(0,0,0,.35);
  position: relative; z-index: 1; letter-spacing: .06em;
}
.chart-shimmer {
  position: absolute; top: 0; left: -60%; width: 40%; height: 100%;
  background: linear-gradient(90deg, transparent, rgba(255,255,255,.3), transparent);
  animation: shimmer 2.4s infinite;
  z-index: 2;
}
@keyframes shimmer {
  0%   { left: -60%; }
  100% { left: 120%; }
}

@media (max-width: 540px) {
  .chart-row { grid-template-columns: 100px 1fr; gap: .6rem; }
  .chart-label { font-size: .78rem; }
}

@media (max-width: 540px) {
  .form-row-2col { grid-template-columns: 1fr; }
  .guests-grid { grid-template-columns: 1fr; }
  .rsvp-box { padding: 1.3rem; }
  .beer-rules { gap: .8rem; }
  .hero h1 { font-size: 3.2rem; }
}
</style>
</head>
<body>

<!-- ===== HERO ===== -->
<section class="hero">
<svg class="scene-svg" viewBox="0 0 1440 700" preserveAspectRatio="xMidYMax slice"
     xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
<defs>
  <!-- Sky gradients -->
  <linearGradient id="gSkyNight" x1="0" y1="0" x2="0" y2="1">
    <stop offset="0%"   stop-color="#050a1a"/>
    <stop offset="55%"  stop-color="#0d1b3e"/>
    <stop offset="100%" stop-color="#1a2a50"/>
  </linearGradient>
  <linearGradient id="gSkyDawn" x1="0" y1="0" x2="0" y2="1">
    <stop offset="0%"   stop-color="#0d1540"/>
    <stop offset="35%"  stop-color="#b84820"/>
    <stop offset="65%"  stop-color="#f49030"/>
    <stop offset="100%" stop-color="#ffd080"/>
  </linearGradient>
  <linearGradient id="gSkyDay" x1="0" y1="0" x2="0" y2="1">
    <stop offset="0%"   stop-color="#1156a8"/>
    <stop offset="50%"  stop-color="#2196f3"/>
    <stop offset="100%" stop-color="#90caf9"/>
  </linearGradient>
  <!-- Mountain gradients -->
  <linearGradient id="gMBack" x1="0" y1="0" x2="0" y2="1">
    <stop offset="0%"   stop-color="#253b5c"/>
    <stop offset="100%" stop-color="#182840"/>
  </linearGradient>
  <linearGradient id="gMMid" x1="0" y1="0" x2="0" y2="1">
    <stop offset="0%"   stop-color="#1b3d11"/>
    <stop offset="100%" stop-color="#0f2608"/>
  </linearGradient>
  <linearGradient id="gMMain" x1="0.35" y1="0" x2="0.65" y2="1">
    <stop offset="0%"   stop-color="#4a8030"/>
    <stop offset="45%"  stop-color="#2e5e1a"/>
    <stop offset="100%" stop-color="#1a3a0d"/>
  </linearGradient>
  <linearGradient id="gRock" x1="0" y1="0" x2="0" y2="1">
    <stop offset="0%"   stop-color="#9a8870"/>
    <stop offset="100%" stop-color="#5a4a38"/>
  </linearGradient>
  <!-- Sun glow -->
  <radialGradient id="gSunGlow" cx="50%" cy="50%" r="50%">
    <stop offset="0%"   stop-color="#fff9c4" stop-opacity="1"/>
    <stop offset="35%"  stop-color="#ffd54f" stop-opacity=".85"/>
    <stop offset="75%"  stop-color="#ff8f00" stop-opacity=".35"/>
    <stop offset="100%" stop-color="#ff8f00" stop-opacity="0"/>
  </radialGradient>
  <!-- Moon glow -->
  <radialGradient id="gMoonGlow" cx="50%" cy="50%" r="50%">
    <stop offset="0%"   stop-color="#fffde7" stop-opacity="1"/>
    <stop offset="55%"  stop-color="#fff9c4" stop-opacity=".6"/>
    <stop offset="100%" stop-color="#fff9c4" stop-opacity="0"/>
  </radialGradient>
  <!-- Ground shadow fade -->
  <linearGradient id="gGndFade" x1="0" y1="0" x2="0" y2="1">
    <stop offset="55%"  stop-color="#000" stop-opacity="0"/>
    <stop offset="100%" stop-color="#000" stop-opacity=".5"/>
  </linearGradient>
  <!-- Atmospheric mist -->
  <linearGradient id="gMist" x1="0" y1="0" x2="0" y2="1">
    <stop offset="0%"   stop-color="#fff" stop-opacity="0"/>
    <stop offset="100%" stop-color="#cce8ff" stop-opacity=".06"/>
  </linearGradient>
  <!-- Blur filter for distant mountains -->
  <filter id="fBlur"><feGaussianBlur stdDeviation="1.2"/></filter>
</defs>

<!-- SKY (night → dawn → day, one-shot 9s) -->
<rect width="1440" height="700" fill="url(#gSkyNight)">
  <animate attributeName="opacity" values="1;1;0;0" keyTimes="0;.3;.65;1" dur="9s" fill="freeze"/>
</rect>
<rect width="1440" height="700" fill="url(#gSkyDawn)" opacity="0">
  <animate attributeName="opacity" values="0;0;1;0" keyTimes="0;.28;.52;.72" dur="9s" fill="freeze"/>
</rect>
<rect width="1440" height="700" fill="url(#gSkyDay)" opacity="0">
  <animate attributeName="opacity" values="0;0;0;1" keyTimes="0;.52;.65;1" dur="9s" fill="freeze"/>
</rect>

<!-- STARS (added by JS into #g-stars) -->
<g id="g-stars">
  <animate attributeName="opacity" values="1;1;.4;0" keyTimes="0;.28;.5;.72" dur="9s" fill="freeze"/>
</g>

<!-- MOON -->
<g>
  <animate attributeName="opacity" values="1;1;.2;0" keyTimes="0;.22;.45;.6" dur="9s" fill="freeze"/>
  <circle cx="195" cy="105" r="80" fill="url(#gMoonGlow)" opacity=".4"/>
  <circle cx="195" cy="105" r="48" fill="#fffde7"/>
  <circle cx="178" cy="95"  r="8"  fill="#f5e870" opacity=".55"/>
  <circle cx="208" cy="118" r="5"  fill="#f0e060" opacity=".45"/>
  <circle cx="192" cy="116" r="3"  fill="#e8d050" opacity=".38"/>
</g>

<!-- SUN (rises from below horizon) -->
<g>
  <circle cx="1195" cy="145" r="130" fill="url(#gSunGlow)">
    <animate attributeName="cy" values="820;820;820;145" keyTimes="0;.32;.58;.9" dur="9s" fill="freeze"/>
    <animate attributeName="opacity" values="0;0;0;.7"  keyTimes="0;.32;.58;.9" dur="9s" fill="freeze"/>
  </circle>
  <circle cx="1195" cy="145" r="52" fill="#ffd740">
    <animate attributeName="cy" values="820;820;820;145" keyTimes="0;.32;.58;.9" dur="9s" fill="freeze"/>
    <animate attributeName="opacity" values="0;0;0;1"   keyTimes="0;.32;.58;.9" dur="9s" fill="freeze"/>
  </circle>
</g>

<!-- CLOUDS -->
<g opacity=".82">
  <animateTransform attributeName="transform" type="translate" from="-250 0" to="1700 0" dur="50s" repeatCount="indefinite"/>
  <ellipse cx="120" cy="175" rx="85" ry="28" fill="#fff" opacity=".9"/>
  <ellipse cx="162" cy="160" rx="58" ry="32" fill="#fff"/>
  <ellipse cx="82"  cy="168" rx="48" ry="24" fill="#fff" opacity=".92"/>
</g>
<g opacity=".65">
  <animateTransform attributeName="transform" type="translate" from="350 0" to="1900 0" dur="72s" repeatCount="indefinite"/>
  <ellipse cx="100" cy="135" rx="95" ry="26" fill="#fff" opacity=".8"/>
  <ellipse cx="145" cy="122" rx="62" ry="30" fill="#fff" opacity=".85"/>
  <ellipse cx="62"  cy="130" rx="52" ry="22" fill="#fff" opacity=".78"/>
</g>
<g opacity=".5">
  <animateTransform attributeName="transform" type="translate" from="820 0" to="2500 0" dur="95s" repeatCount="indefinite"/>
  <ellipse cx="80"  cy="195" rx="70" ry="20" fill="#fff" opacity=".7"/>
  <ellipse cx="118" cy="183" rx="46" ry="23" fill="#fff" opacity=".75"/>
</g>

<!-- DISTANT MOUNTAINS (blurred, blue-gray) -->
<path filter="url(#fBlur)" fill="url(#gMBack)" d="
  M0,700 L0,348
  C55,328  115,302  175,280
  C238,256  300,236  355,222
  C400,210  432,208  462,216
  C490,224  512,242  535,252
  C558,235  592,210  635,192
  C678,174  720,160  762,154
  C800,148  834,156  862,172
  C884,186  898,200  912,208
  C940,185  978,162  1018,148
  C1058,134  1100,128  1138,142
  C1176,156  1205,180  1232,194
  C1268,172  1310,158  1354,165
  C1394,172  1424,188  1440,200
  L1440,700 Z"/>

<!-- DISTANT SNOW (subtle) -->
<path fill="#fff" opacity=".45" filter="url(#fBlur)" d="
  M738,154 C752,142 768,132 784,124 C798,132 814,148 828,166 C812,160 795,156 778,160 C762,158 748,156 738,154 Z
  M994,148 C1008,136 1024,124 1042,116 C1058,124 1076,142 1090,160 C1072,154 1054,148 1036,152 C1018,150 1005,149 994,148 Z"/>

<!-- MID MOUNTAINS (forest green) -->
<path fill="url(#gMMid)" d="
  M0,700 L0,458
  C65,435  130,412  195,390
  C262,367  322,347  378,328
  C422,312  456,305  485,314
  C514,323  534,342  556,334
  C585,322  612,296  650,278
  C688,260  728,250  768,246
  C804,242  836,252  860,268
  C882,256  908,238  945,226
  C982,214  1018,210  1052,222
  C1084,234  1108,252  1132,260
  C1168,244  1210,234  1254,240
  C1298,246  1336,260  1374,272
  C1406,282  1428,290  1440,296
  L1440,700 Z"/>

<!-- MAIN ROUTE MOUNTAIN — rock zone near peak -->
<path fill="url(#gRock)" d="
  M948,196
  C968,170  992,144  1016,118
  C1030,100  1042,86  1054,72
  C1066,88   1080,108  1098,132
  C1114,154  1128,176  1138,198
  C1116,184  1094,168  1072,158
  C1055,150  1038,146  1022,150
  C1003,154  982,166  960,182 Z"/>

<!-- MAIN ROUTE MOUNTAIN — body (Selma → Landarenca) -->
<path fill="url(#gMMain)" d="
  M0,700 L0,602
  C72,578  144,552  218,524
  C292,496  366,466  440,436
  C514,406  588,372  662,338
  C736,304  806,268  866,236
  C912,210  950,188  982,170
  C1004,157  1024,140  1040,118
  C1046,108  1052,94  1055,82
  C1057,90  1062,106  1072,126
  C1090,156  1116,188  1148,220
  C1190,264  1238,314  1284,362
  C1330,410  1374,454  1410,486
  C1428,502  1440,514  1440,524
  L1440,700 Z"/>

<!-- PEAK SNOW -->
<path fill="#f8f9fa" opacity=".94" d="
  M1024,150
  C1034,128  1044,106  1055,82
  C1062,100  1072,122  1082,144
  C1070,137  1060,132  1052,130
  C1043,132  1033,137  1024,150 Z"/>

<!-- TREE SILHOUETTE (foreground conifers) -->
<path fill="#0a2006" opacity=".96" d="
  M0,700 L0,642
  C12,630 18,618 24,632 C30,618 37,604 44,620 C50,606 57,594 64,610
  C70,596 77,582 84,598 C91,584 98,570 106,586 C113,572 120,558 129,574
  C136,560 144,546 154,563 C162,549 171,536 181,553 C190,540 200,526 211,544
  C220,530 229,516 238,534
  L238,700 L280,700
  C290,686 298,672 306,688 C314,674 322,660 331,676 C340,662 350,648 360,665
  C369,651 379,637 390,654
  L390,700 L440,700
  C450,684 458,670 465,686 C473,672 481,658 490,674 C498,660 507,646 516,662
  C525,648 534,634 545,651
  L545,700 L594,700
  C602,686 610,672 618,688 C626,674 634,660 642,676 C651,662 660,648 670,664
  L670,700 L720,700
  C729,686 737,672 744,686 C752,672 761,658 770,674
  C779,660 788,646 798,662 C807,648 816,634 827,650
  L827,700 L876,700
  C884,686 892,672 900,688 C909,674 918,660 927,676 C936,662 945,648 955,664
  L955,700 L1010,700
  C1020,684 1030,669 1038,684 C1046,670 1054,656 1062,672 C1071,658 1080,643 1090,660
  L1090,700 L1440,700 Z"/>

<!-- Ground shadow for depth -->
<rect width="1440" height="700" fill="url(#gGndFade)"/>
<!-- Atmospheric mist -->
<rect width="1440" height="700" fill="url(#gMist)"/>

<!-- CABLE WIRE (double for realism) -->
<line x1="382" y1="595" x2="1052" y2="96" stroke="rgba(200,200,200,.75)" stroke-width="2.5"/>
<line x1="385" y1="599" x2="1055" y2="100" stroke="rgba(150,150,150,.4)"  stroke-width="1.5"/>

<!-- STATIONS -->
<rect x="356" y="582" width="52" height="28" rx="4" fill="#4a3020" stroke="#2a1810" stroke-width="1.5"/>
<rect x="362" y="574" width="10" height="10" fill="#7a5030"/>
<rect x="1040" y="90"  width="40" height="22" rx="4" fill="#4a3020" stroke="#2a1810" stroke-width="1.5"/>
<rect x="1058" y="82"  width="9"  height="10" fill="#7a5030"/>

<!-- CABLE CAR (animateMotion along wire) -->
<g>
  <animateMotion path="M382,595 L1052,96" dur="15s" repeatCount="indefinite"
    keyTimes="0;0.42;0.5;0.92;1"
    keyPoints="0;1;1;0;0"
    calcMode="spline"
    keySplines="0.42 0 0.58 1;0 0 1 1;0.42 0 0.58 1;0 0 1 1"/>
  <circle cy="-40" r="6.5" fill="#555" stroke="#333" stroke-width="2"/>
  <line x1="-15" y1="-34" x2="-11" y2="-16" stroke="#888" stroke-width="2.5" stroke-linecap="round"/>
  <line x1=" 15" y1="-34" x2=" 11" y2="-16" stroke="#888" stroke-width="2.5" stroke-linecap="round"/>
  <rect x="-19" y="-16" width="38" height="30" rx="5" fill="#c84412" stroke="#a03410" stroke-width="2"/>
  <rect x="-15" y="-12" width="12" height="10" rx="2" fill="rgba(200,235,255,.88)" stroke="#aad" stroke-width=".5"/>
  <rect x=" 3"  y="-12" width="12" height="10" rx="2" fill="rgba(200,235,255,.88)" stroke="#aad" stroke-width=".5"/>
  <rect x="-19" y="10"  width="38" height="4"  rx="0" fill="#8a2e0a" opacity=".6"/>
</g>

<!-- RUNNER (travels up the slope) -->
<g>
  <animateMotion path="M435,575 C555,522 678,465 800,402 C875,362 940,326 990,292"
    dur="9s" repeatCount="indefinite"
    keyTimes="0;0.44;0.5;0.94;1"
    keyPoints="0;1;1;0;0"
    calcMode="spline"
    keySplines="0.42 0 0.58 1;0 0 1 1;0.42 0 0.58 1;0 0 1 1"/>
  <!-- Head -->
  <circle cy="-28" r="9" fill="#f4a46a"/>
  <!-- Body -->
  <rect x="-5" y="-19" width="10" height="15" rx="3" fill="#d32f2f"/>
  <!-- Arms -->
  <g>
    <animate attributeName="transform" values="rotate(0);rotate(15);rotate(-15);rotate(0)" dur=".45s" repeatCount="indefinite"/>
    <line x1="-5" y1="-14" x2="-14" y2="-6"  stroke="#c27a50" stroke-width="3" stroke-linecap="round"/>
    <line x1=" 5" y1="-14" x2=" 14" y2="-6"  stroke="#c27a50" stroke-width="3" stroke-linecap="round"/>
  </g>
  <!-- Legs -->
  <g>
    <animate attributeName="transform" values="rotate(0);rotate(-18);rotate(18);rotate(0)" dur=".45s" repeatCount="indefinite"/>
    <line x1="-3" y1="-4" x2="-8"  y2="8" stroke="#1a237e" stroke-width="3" stroke-linecap="round"/>
    <line x1=" 3" y1="-4" x2=" 8"  y2="8" stroke="#1a237e" stroke-width="3" stroke-linecap="round"/>
  </g>
</g>

<!-- BBQ GRILL AT SUMMIT -->
<g transform="translate(1035,78)">
  <rect x="-14" y="2"  width="28" height="13" rx="3" fill="#1a1a1a" stroke="#111" stroke-width="1.5"/>
  <line x1="-14" y1="9"  x2="14" y2="9"  stroke="#2a2a2a" stroke-width="1"/>
  <line x1="-8"  y1="15" x2="-10" y2="25" stroke="#1a1a1a" stroke-width="2.5" stroke-linecap="round"/>
  <line x1=" 8"  y1="15" x2=" 10" y2="25" stroke="#1a1a1a" stroke-width="2.5" stroke-linecap="round"/>
  <!-- Flames -->
  <text x="0" y="-2" text-anchor="middle" font-size="20" style="user-select:none">&#x1F525;
    <animate attributeName="font-size" values="18;23;17;21;18" dur=".7s" repeatCount="indefinite"/>
  </text>
</g>

<!-- SMOKE from BBQ -->
<circle cx="1038" cy="65" r="4" fill="#ccc" opacity="0">
  <animate attributeName="cy"      values="65;45;25;5"   dur="3s" begin="0s"  repeatCount="indefinite"/>
  <animate attributeName="opacity" values=".5;.3;.12;0"  dur="3s" begin="0s"  repeatCount="indefinite"/>
  <animate attributeName="r"       values="3;5;7;9"      dur="3s" begin="0s"  repeatCount="indefinite"/>
  <animate attributeName="cx"      values="1038;1044;1036;1042" dur="3s" begin="0s" repeatCount="indefinite"/>
</circle>
<circle cx="1033" cy="65" r="3" fill="#bbb" opacity="0">
  <animate attributeName="cy"      values="65;44;23;3"   dur="2.6s" begin="1.1s" repeatCount="indefinite"/>
  <animate attributeName="opacity" values=".4;.25;.1;0"  dur="2.6s" begin="1.1s" repeatCount="indefinite"/>
  <animate attributeName="r"       values="2.5;4.5;6;8"  dur="2.6s" begin="1.1s" repeatCount="indefinite"/>
  <animate attributeName="cx"      values="1033;1028;1037;1032" dur="2.6s" begin="1.1s" repeatCount="indefinite"/>
</circle>

<!-- ALTITUDE LABELS -->
<g font-family="'Bebas Neue',sans-serif" font-size="12" fill="#fff">
  <rect x="320" y="608" width="118" height="20" rx="4" fill="rgba(0,0,0,.42)"/>
  <text x="379" y="622" text-anchor="middle">SELMA 800m</text>
  <rect x="1062" y="56" width="152" height="20" rx="4" fill="rgba(0,0,0,.42)"/>
  <text x="1138" y="70" text-anchor="middle">LANDARENCA 1260m</text>
</g>

</svg><!-- /scene-svg -->

<div class="hero-vignette"></div>

<div class="hero-text">
  <div class="hero-badge">&#x1F386; Compleanno di <?= $cfg['host_name'] ?></div>
  <h1>BIRTHDAY<br><em>VERTICAL</em><br>CHALLENGE</h1>
  <div class="hero-sub"><?= $cfg['distance'] ?> &bull; <?= $cfg['route'] ?></div>
  <div class="hero-date"><?= $cfg['event_date'] ?></div>
  <div class="schedule">
    <div class="pill">&#x1F319; <?= $cfg['time_start'] ?> &mdash; Partenza epica</div>
    <div class="pill">&#x1F305; Dalle <?= $cfg['time_join'] ?> &mdash; Join the climb!</div>
    <div class="pill">&#x1F525; <?= $cfg['time_bbq'] ?> &mdash; Grigliata in vetta!</div>
  </div>
</div>

<div class="scroll-hint">
  <span>RSVP</span>
  <svg viewBox="0 0 24 24"><polyline points="6 9 12 15 18 9"/></svg>
</div>
</section>

<!-- BEER RULES -->
<div class="beer-rules">
  <?php foreach ($cfg['beer_rules'] as $i => $rule): ?>
    <div class="rule"><?= $i < 2 ? '&#x1F37A;' : '&#x1F3C6;' ?> <?= $rule ?></div>
    <?php if ($i < count($cfg['beer_rules']) - 1): ?><span style="opacity:.35">|</span><?php endif; ?>
  <?php endforeach; ?>
</div>

<!-- CONTENT -->
<div class="container">

  <!-- STATS -->
  <div class="stats">
    <div class="stat-card">
      <div class="num"><?= count($yes) ?></div>
      <div class="lbl">&#x2705; Confermati</div>
    </div>
    <div class="stat-card stat-people">
      <div class="num"><?= $totalHeads ?></div>
      <div class="lbl">&#x1F465; Persone totali</div>
    </div>
    <div class="stat-card">
      <div class="num"><?= $totalChildren ?></div>
      <div class="lbl">&#x1F476; Bimbi</div>
    </div>
    <div class="stat-card">
      <div class="num"><?= count($maybe) ?></div>
      <div class="lbl">&#x1F914; Forse</div>
    </div>
    <div class="stat-card stat-fire">
      <div class="num"><?= count($runners) ?></div>
      <div class="lbl">&#x1F3C3; Runners</div>
    </div>
    <div class="stat-card stat-fire">
      <div class="num"><?= $totalClimbs ?></div>
      <div class="lbl">&#x26F0;&#xFE0F; Salite totali</div>
    </div>
    <div class="stat-card stat-beer">
      <div class="num"><?= $totalClimbs ?></div>
      <div class="lbl">&#x1F37A; Birre dovute</div>
    </div>
    <div class="stat-card">
      <div class="num"><?= count($bringingSomething) ?></div>
      <div class="lbl">&#x1F96A; Portano qualcosa</div>
    </div>
  </div>

  <!-- RSVP FORM -->
  <div class="section-title" id="rsvp">&#x1F4DD; La tua risposta</div>
  <div class="rsvp-box">
    <?php if ($success): ?>
      <div class="alert alert-success">&#x1F389; Salvato, <?= $myName ?>! A presto su a <?= $cfg['location_top'] ?>!</div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert alert-error">&#x26A0;&#xFE0F; <?= $error ?></div>
    <?php endif; ?>

    <form method="POST" action="#rsvp">
      <input type="hidden" name="action" value="rsvp">

      <div class="form-row">
        <label for="name">Il tuo nome *</label>
        <input type="text" id="name" name="name" placeholder="Come ti chiami?" required autocomplete="name"
               value="<?= $success ? '' : htmlspecialchars($_POST['name'] ?? '') ?>">
      </div>

      <div class="form-row">
        <label>Vieni? *</label>
        <div class="coming-group">
          <div class="coming-opt opt-si">
            <input type="radio" name="coming" id="c-si" value="si"
                   <?= (($_POST['coming']??'') === 'si' && !$success) ? 'checked' : '' ?>>
            <label for="c-si">&#x1F44D; Si, ci sono!</label>
          </div>
          <div class="coming-opt opt-forse">
            <input type="radio" name="coming" id="c-forse" value="forse"
                   <?= (($_POST['coming']??'') === 'forse' && !$success) ? 'checked' : '' ?>>
            <label for="c-forse">&#x1F914; Forse</label>
          </div>
          <div class="coming-opt opt-no">
            <input type="radio" name="coming" id="c-no" value="no"
                   <?= (($_POST['coming']??'') === 'no' && !$success) ? 'checked' : '' ?>>
            <label for="c-no">&#x1F614; Non riesco</label>
          </div>
        </div>
      </div>

      <!-- Extra guests (shown when "si" or "forse") -->
      <div class="form-row" id="guests-row">
        <label>Porti qualcuno? <span class="optional">(oltre a te)</span></label>
        <div class="guests-grid">
          <div>
            <label for="adults" style="font-size:.85rem; color:#555;">&#x1F9D1; Adulti extra</label>
            <select id="adults" name="adults">
              <?php for ($i = 0; $i <= 9; $i++): ?>
                <option value="<?= $i ?>" <?= (($_POST['adults']??0) == $i && !$success) ? 'selected' : '' ?>>
                  <?= $i === 0 ? '0 — solo io' : '+' . $i . ' adult' . ($i===1 ? 'o' : 'i') ?>
                </option>
              <?php endfor; ?>
            </select>
          </div>
          <div>
            <label for="children" style="font-size:.85rem; color:#555;">&#x1F476; Bimbi / neonati</label>
            <select id="children" name="children">
              <?php for ($i = 0; $i <= 9; $i++): ?>
                <option value="<?= $i ?>" <?= (($_POST['children']??0) == $i && !$success) ? 'selected' : '' ?>>
                  <?= $i === 0 ? '0' : $i . ' bimb' . ($i===1 ? 'o' : 'i') ?>
                </option>
              <?php endfor; ?>
            </select>
          </div>
        </div>
      </div>

      <!-- Climbs (shown only when "si") -->
      <div class="form-row" id="climbs-row">
        <label>Quante salite farai? <span class="optional">(0 = arrivi direttamente in vetta via funivia o cabinovia)</span></label>
        <div class="climbs-grid">
          <?php
          $climbLabels = [0=>'Solo BBQ',1=>'1 birra',2=>'2 birre',3=>'gloria!',4=>'4x',5=>'5x',6=>'6x',7=>'7x',8=>'8x',9=>'9x',10=>'10x',11=>'ALL &#x1F947;'];
          foreach (range(0, 11) as $n):
            $prev = intval($_POST['climbs'] ?? 0);
            $chk  = (!$success && $prev === $n) ? 'checked' : ($n === 0 ? 'checked' : '');
          ?>
          <div class="climb-opt">
            <input type="radio" name="climbs" id="cl-<?= $n ?>" value="<?= $n ?>" <?= $chk ?>>
            <label for="cl-<?= $n ?>">
              <?= $n ?>
              <span class="sub"><?= strip_tags($climbLabels[$n]) ?></span>
            </label>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="form-row">
        <label for="bringing">Porti qualcosa? <span class="optional">(opzionale)</span></label>
        <?php if (!empty($bringingCounts)): ?>
        <div class="bringing-status">
          <span class="bringing-status-label">Già confermato:</span>
          <?php foreach ($bringingCounts as $item => $count): ?>
            <span class="bringing-tag">🥗 <?= htmlspecialchars($item) ?> ×<?= $count ?></span>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <input type="text" id="bringing" name="bringing"
               list="bringing-suggestions"
               placeholder="es: insalata di riso, vino, dolce (separati da virgola)"
               value="<?= $success ? '' : htmlspecialchars($_POST['bringing'] ?? '') ?>">
        <datalist id="bringing-suggestions">
          <?php foreach (array_keys($bringingCounts) as $item): ?>
            <option value="<?= htmlspecialchars($item) ?>">
          <?php endforeach; ?>
        </datalist>
      </div>

      <div class="form-row">
        <label for="notes">Note <span class="optional">(opzionale)</span></label>
        <textarea id="notes" name="notes" placeholder="Hai qualcosa da dire?"><?= $success ? '' : htmlspecialchars($_POST['notes'] ?? '') ?></textarea>
      </div>

      <button type="submit" class="btn-submit">&#x26F0;&#xFE0F; Invia risposta</button>
    </form>
  </div>

  <!-- BRINGING CHART -->
  <?php if (!empty($bringingCounts)): ?>
  <div class="section-title">&#x1F96A; Cosa portano</div>
  <div class="bringing-chart" id="bringing-chart">
    <?php
    $chartColors = ['#2d5a1b','#d4881e','#2c59b8','#e05c1a','#6b2fb8','#c0392b','#1e8bc3','#4a7c3f'];
    $ci = 0;
    foreach ($bringingCounts as $item => $count):
        $pct  = round(($count / $maxBringing) * 100);
        $pct  = max($pct, 12); // min width so label is visible
        $col  = $chartColors[$ci % count($chartColors)];
        $ci++;
    ?>
    <div class="chart-row" data-pct="<?= $pct ?>">
      <div class="chart-label"><?= htmlspecialchars($item) ?></div>
      <div class="chart-track">
        <div class="chart-bar" style="background: linear-gradient(90deg, <?= $col ?>, <?= $col ?>cc);">
          <div class="chart-shimmer"></div>
          <span class="chart-count">×<?= $count ?></span>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- GUEST LIST -->
  <div class="section-title" id="lista">&#x1F465; Chi c'&egrave;</div>

  <?php if (empty($responses)): ?>
    <div class="empty">
      <div class="big">&#x1F3D4;</div>
      Nessuna risposta ancora &mdash; sii il primo!
    </div>
  <?php else: ?>
    <div class="guest-list">
      <?php foreach (array_reverse($responses) as $r):
        $ini   = mb_strtoupper(mb_substr($r['name'], 0, 1));
        $extra = ($r['adults'] ?? 0) + ($r['children'] ?? 0);
      ?>
      <div class="guest-card coming-<?= $r['coming'] ?>">
        <div class="guest-avatar"><?= $ini ?></div>
        <div class="guest-info">
          <div class="guest-name"><?= $r['name'] ?></div>
          <div class="guest-meta">
            <?php if ($r['coming'] === 'si'): ?>
              <span class="tag">&#x2705; Confermato</span>
              <?php if (($r['adults'] ?? 0) > 0): ?>
                <span class="tag tag-people">&#x1F9D1; +<?= $r['adults'] ?> adult<?= $r['adults']===1?'o':'i' ?></span>
              <?php endif; ?>
              <?php if (($r['children'] ?? 0) > 0): ?>
                <span class="tag tag-people">&#x1F476; <?= $r['children'] ?> bimb<?= $r['children']===1?'o':'i' ?></span>
              <?php endif; ?>
              <?php if ($r['climbs'] > 0): ?>
                <span class="tag tag-fire">&#x1F3C3; <?= $r['climbs'] ?> salite</span>
                <span class="tag tag-beer">&#x1F37A; <?= $r['climbs'] ?> birre</span>
              <?php else: ?>
                <span class="tag">&#x1F37D;&#xFE0F; Solo BBQ</span>
              <?php endif; ?>
            <?php elseif ($r['coming'] === 'forse'): ?>
              <span class="tag" style="background:#fef3e2;color:var(--earth)">&#x1F914; Forse</span>
              <?php if ($extra > 0): ?>
                <span class="tag tag-people">&#x1F465; +<?= $extra ?> pers.</span>
              <?php endif; ?>
            <?php else: ?>
              <span class="tag" style="background:#fde8e8;color:#c0392b">&#x1F614; Non viene</span>
            <?php endif; ?>
            <?php if (!empty($r['bringing'])): ?>
              <span class="tag tag-food">&#x1F96A; <?= $r['bringing'] ?></span>
            <?php endif; ?>
          </div>
          <?php if (!empty($r['notes'])): ?>
            <div class="guest-notes">"<?= $r['notes'] ?>"</div>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

</div>

<footer>
  <div>
    <span class="beer-bob">&#x1F37A;</span>
    &nbsp;Birra, grigliata &amp; divertimento!&nbsp;
    <span class="beer-bob" style="animation-delay:.4s">&#x1F37A;</span>
  </div>
  <div style="margin-top:.6rem"><a href="admin.php">admin</a></div>
</footer>

<script>
// Populate SVG stars
(function(){
  const g = document.getElementById('g-stars');
  if (!g) return;
  for (let i = 0; i < 80; i++) {
    const s = document.createElementNS('http://www.w3.org/2000/svg','circle');
    const r = Math.random() * 1.8 + 0.5;
    s.setAttribute('cx', Math.random() * 1440);
    s.setAttribute('cy', Math.random() * 420);
    s.setAttribute('r',  r);
    s.setAttribute('fill', '#fff');
    s.style.animation = `twinkle ${1.5 + Math.random()*2.5}s ${Math.random()*4}s infinite alternate`;
    g.appendChild(s);
  }
})();

// Show/hide form rows
const radios     = document.querySelectorAll('input[name="coming"]');
const climbsRow  = document.getElementById('climbs-row');
const guestsRow  = document.getElementById('guests-row');
function syncRows() {
  const val = document.querySelector('input[name="coming"]:checked')?.value;
  climbsRow.style.display = (val === 'si')                    ? 'block' : 'none';
  guestsRow.style.display = (val === 'si' || val === 'forse') ? 'block' : 'none';
}
radios.forEach(r => r.addEventListener('change', syncRows));
syncRows();

// Confetti burst on load
function confetti() {
  const cv = document.createElement('canvas');
  cv.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;pointer-events:none;z-index:9999';
  document.body.appendChild(cv);
  const ctx = cv.getContext('2d');
  cv.width  = window.innerWidth;
  cv.height = window.innerHeight;
  const colors = ['#f9b234','#2d5a1b','#e05c1a','#fff','#4a7c3f','#d4881e','#c0392b'];
  const pts = Array.from({length:110}, () => ({
    x:  cv.width / 2, y: cv.height * 0.32,
    vx: (Math.random()-.5) * 22,
    vy: Math.random() * -18 - 4,
    color: colors[Math.floor(Math.random()*colors.length)],
    sz: Math.random() * 9 + 3,
    rot: Math.random() * Math.PI * 2,
    rs: (Math.random()-.5) * .25,
    rect: Math.random() > .4,
  }));
  let f = 0;
  (function draw() {
    if (f > 200) { cv.remove(); return; }
    ctx.clearRect(0, 0, cv.width, cv.height);
    pts.forEach(p => {
      p.x += p.vx; p.y += p.vy; p.vy += .45; p.vx *= .97; p.rot += p.rs;
      const alpha = f > 90 ? Math.max(0, 1 - (f-90)/110) : 1;
      ctx.save(); ctx.translate(p.x, p.y); ctx.rotate(p.rot);
      ctx.globalAlpha = alpha; ctx.fillStyle = p.color;
      if (p.rect) ctx.fillRect(-p.sz/2, -p.sz/4, p.sz, p.sz/2);
      else { ctx.beginPath(); ctx.arc(0,0,p.sz/2,0,Math.PI*2); ctx.fill(); }
      ctx.restore();
    });
    f++; requestAnimationFrame(draw);
  })();
}
setTimeout(confetti, 1200);

<?php if ($error): ?>
document.getElementById('rsvp')?.scrollIntoView({behavior:'smooth', block:'start'});
<?php endif; ?>

// Animate bringing chart bars on scroll
(function() {
  const chart = document.getElementById('bringing-chart');
  if (!chart) return;
  const rows = chart.querySelectorAll('.chart-row');
  const observer = new IntersectionObserver(entries => {
    if (!entries[0].isIntersecting) return;
    rows.forEach((row, i) => {
      const bar = row.querySelector('.chart-bar');
      if (!bar) return;
      setTimeout(() => {
        bar.style.width = row.dataset.pct + '%';
      }, i * 130);
    });
    observer.disconnect();
  }, { threshold: 0.25 });
  observer.observe(chart);
})();
</script>

<style>
@keyframes twinkle {
  from { opacity:.2; r:.6; }
  to   { opacity:1;  r:1.4; }
}
</style>
</body>
</html>
