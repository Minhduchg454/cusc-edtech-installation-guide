<?php
// Simple comment server — outputs JS snapshot on GET, JSON API on POST.
// Files will live in the same folder as index.html

// ====== Config ======
$DATA_FILE = __DIR__ . '/comments.data.json';

// Secret keys map: key => [ 'name' => 'Admin Name' ]
$SECRET_KEYS = [
  '123' => ['name' => 'HOANG NAM'],
];

// ====== Helpers ======
function read_db($file){
  if(!file_exists($file)) return ['comments'=>[]];
  $raw = file_get_contents($file);
  $data = json_decode($raw, true);
  if(!is_array($data)) $data = ['comments'=>[]];
  if(!isset($data['comments']) || !is_array($data['comments'])) $data['comments']=[];
  return $data;
}
function write_db($file, $data){
  $tmp = $file.'.tmp';
  file_put_contents($tmp, json_encode($data, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
  rename($tmp, $file);
}
function uid(){ return bin2hex(random_bytes(10)); }
function token(){ return bin2hex(random_bytes(12)); }
function nowiso(){ return gmdate('c'); }
function cors(){ header('Access-Control-Allow-Origin: *'); header('Access-Control-Allow-Headers: Content-Type'); }

// ====== Routing ======
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if($method === 'GET'){
  // Return JS snapshot
  cors();
  header('Content-Type: application/javascript; charset=utf-8');
  $db = read_db($DATA_FILE);
  // minimal exposure, no edit tokens leak
  $pub = array_map(function($c){ unset($c['edit_token']); return $c; }, $db['comments']);
  echo 'window.COMMENTS_SNAPSHOT = ' . json_encode($pub, JSON_UNESCAPED_UNICODE) . ";\n";

  // If client sends secret_key, expose admin profile (no validation response otherwise)
  $key = $_GET['secret_key'] ?? '';
  if($key && isset($GLOBALS['SECRET_KEYS'][$key])){
    $name = $GLOBALS['SECRET_KEYS'][$key]['name'] ?? 'Admin';
    echo 'window.ADMIN_PROFILE = ' . json_encode(['isAdmin'=>true,'name'=>$name], JSON_UNESCAPED_UNICODE) . ";\n";
  }
  exit;
}

// JSON API for mutations
if($method === 'POST'){
  cors();
  header('Content-Type: application/json; charset=utf-8');
  $body = json_decode(file_get_contents('php://input'), true) ?? [];
  $action = $body['action'] ?? '';
  $secret = $body['secret_key'] ?? '';
  $isAdmin = isset($SECRET_KEYS[$secret]);
  $adminName = $isAdmin ? ($SECRET_KEYS[$secret]['name'] ?? 'Admin') : null;

  $db = read_db($DATA_FILE);
  $comments = &$db['comments'];

  // whoami — check if secret key valid
  if($action === 'whoami'){
    echo json_encode(['ok'=>true,'isAdmin'=>$isAdmin,'name'=>$adminName]);
    exit;
  }

  if($action === 'add'){
    $content = trim($body['content'] ?? '');
    if($content===''){ echo json_encode(['ok'=>false,'error'=>'Empty content']); exit; }

    $id = uid();
    $editToken = token();

    $record = [
      'id' => $id,
      'author_name' => $isAdmin ? $adminName : trim($body['author_name'] ?? 'Anonymous'),
      'tag' => $body['tag'] ?? 'basic',
      'document' => $body['document'] ?? null,
      'content' => $content,
      'status' => 'pending',
      'created_at' => nowiso(),
      'updated_at' => null,
      'edit_token' => $editToken,
    ];
    $comments[] = $record;
    write_db($DATA_FILE, $db);
    $pub = $record; unset($pub['edit_token']);
    echo json_encode(['ok'=>true,'comment'=>$pub,'editToken'=>$editToken]);
    exit;
  }

  if(in_array($action, ['update','delete','status'])){
    $id = $body['id'] ?? '';
    if(!$id){ echo json_encode(['ok'=>false,'error'=>'Missing id']); exit; }

    $idx = null; $rec = null;
    foreach($comments as $i=>$c){ if(($c['id']??'') === $id){ $idx=$i; $rec=$c; break; } }
    if($idx===null){ echo json_encode(['ok'=>false,'error'=>'Not found']); exit; }

    // permission: admin OR valid edit token
    $providedToken = $body['edit_token'] ?? '';
    $isOwner = $providedToken && ($providedToken === ($rec['edit_token'] ?? ''));
    if(!$isAdmin && !$isOwner){ echo json_encode(['ok'=>false,'error'=>'Forbidden']); exit; }

    if($action==='update'){
      $content = trim($body['content'] ?? '');
      if($content===''){ echo json_encode(['ok'=>false,'error'=>'Empty content']); exit; }
      $comments[$idx]['content'] = $content;
      $comments[$idx]['updated_at'] = nowiso();
      write_db($DATA_FILE, $db);
      echo json_encode(['ok'=>true,'updated_at'=>$comments[$idx]['updated_at']]);
      exit;
    }

    if($action==='delete'){
      array_splice($comments, $idx, 1);
      write_db($DATA_FILE, $db);
      echo json_encode(['ok'=>true]);
      exit;
    }

    if($action==='status'){
      if(!$isAdmin){ echo json_encode(['ok'=>false,'error'=>'Admin only']); exit; }
      $status = $body['status'] ?? 'pending';
      if(!in_array($status, ['pending','done'])) $status='pending';
      $comments[$idx]['status'] = $status;
      $comments[$idx]['updated_at'] = nowiso();
      write_db($DATA_FILE, $db);
      echo json_encode(['ok'=>true]);
      exit;
    }
  }

  echo json_encode(['ok'=>false,'error'=>'Unknown action']);
  exit;
}

http_response_code(405);
echo json_encode(['ok'=>false,'error'=>'Method not allowed']);
