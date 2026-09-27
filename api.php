<?php
session_set_cookie_params(['httponly'=>true,'samesite'=>'Lax','secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off']);
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
$dataDir=__DIR__.'/data'; if(!is_dir($dataDir)) mkdir($dataDir,0775,true);
$usersFile=$dataDir.'/users.json'; $stateFile=$dataDir.'/state.json';
if(!file_exists($usersFile)) file_put_contents($usersFile,json_encode([['id'=>1,'username'=>'admin','password_hash'=>password_hash('Admin@2026!',PASSWORD_DEFAULT)]],JSON_UNESCAPED_UNICODE),LOCK_EX);
function out($data,$code=200){http_response_code($code);echo json_encode($data,JSON_UNESCAPED_UNICODE);exit;}
function readJson($file,$default){if(!file_exists($file))return $default;$x=json_decode(file_get_contents($file),true);return is_array($x)?$x:$default;}
function writeJson($file,$data){$tmp=$file.'.tmp';$ok=file_put_contents($tmp,json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),LOCK_EX);if($ok===false)return false;return rename($tmp,$file);}
$action=$_GET['action']??'';
if($action==='login'){
  $body=json_decode(file_get_contents('php://input'),true)?:[];$users=readJson($GLOBALS['usersFile'],[]);$found=null;
  foreach($users as $u)if(($u['username']??'')===($body['username']??'')){$found=$u;break;}
  if(!$found || !password_verify($body['password']??'',$found['password_hash']??''))out(['message'=>'اسم المستخدم أو كلمة المرور غير صحيحة'],401);
  $_SESSION['uid']=$found['id'];$_SESSION['username']=$found['username'];out(['ok'=>true]);
}
if($action==='logout'){session_destroy();out(['ok'=>true]);}
if(empty($_SESSION['uid']))out(['message'=>'تسجيل الدخول مطلوب'],401);
if($action==='state'){
  if(!file_exists($stateFile))out(['data'=>null,'updated_at'=>null]);
  $data=readJson($stateFile,null);$mtime=filemtime($stateFile);out(['data'=>$data,'updated_at'=>$mtime?date('c',$mtime):null]);
}
if($action==='save'){
  if($_SERVER['REQUEST_METHOD']!=='POST')out(['message'=>'طريقة الطلب غير صحيحة'],405);
  $data=json_decode(file_get_contents('php://input'),true);if(!is_array($data))out(['message'=>'بيانات غير صالحة'],422);
  $allowed=['branches','departments','sections','jobs','grades','categories','salaries','salaryStructures','allowances','employees','payrolls','disbursements','experienceAdjustments'];$clean=[];
  foreach($allowed as $k)$clean[$k]=isset($data[$k])&&is_array($data[$k])?$data[$k]:[];
  if(!writeJson($stateFile,$clean))out(['message'=>'تعذر حفظ البيانات على الخادم'],500);
  out(['ok'=>true,'updated_at'=>date('c')]);
}
out(['message'=>'إجراء غير معروف'],404);
