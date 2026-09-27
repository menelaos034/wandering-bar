<?php
// Auto-deploy: GitHub calls this after every push. It pulls the repo in cPanel
// Git Version Control and runs the .cpanel.yml deployment (copies files live).
header('Content-Type: text/plain; charset=utf-8');

$lock = __DIR__ . '/.deploy-hook.last';
if (is_file($lock) && time() - filemtime($lock) < 15) { echo "throttled\n"; exit; }
@touch($lock);

if (!function_exists('shell_exec')) { http_response_code(500); echo "shell_exec disabled\n"; exit; }

function uapi($args) {
  $raw = shell_exec('uapi --output=json ' . $args . ' 2>&1');
  $j = json_decode((string) $raw, true);
  return [$j, $raw];
}

list($list, $raw) = uapi('VersionControl retrieve');
$root = null; $branch = 'main';
foreach (($list['result']['data'] ?? []) as $r) {
  $src = $r['source_repository']['url'] ?? '';
  if (stripos($src, 'wandering-bar') !== false || stripos($r['repository_root'] ?? '', 'wandering') !== false) {
    $root = $r['repository_root']; $branch = $r['branch'] ?: 'main'; break;
  }
}
if (!$root) { http_response_code(500); echo "repo not found\n" . $raw; exit; }

list($pull, $p) = uapi('VersionControl update repository_root=' . escapeshellarg($root) . ' branch=' . escapeshellarg($branch));
list($dep, $d)  = uapi('VersionControlDeployment create repository_root=' . escapeshellarg($root));

$ok = !empty($pull['result']['status']) && !empty($dep['result']['status']);
http_response_code($ok ? 200 : 500);
echo ($ok ? "deployed\n" : "failed\n");
echo "pull: " . (($pull['result']['status'] ?? 0) ? 'ok' : trim(implode(' ', (array) ($pull['result']['errors'] ?? [$p])))) . "\n";
echo "deploy: " . (($dep['result']['status'] ?? 0) ? 'queued' : trim(implode(' ', (array) ($dep['result']['errors'] ?? [$d])))) . "\n";
