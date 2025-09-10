<?php
session_start();
// TODO: validar rol admin si aplica
header('Content-Type: application/json; charset=utf-8');

include("../conexion.php");

function ok($d=[]) { echo json_encode(['ok'=>true]+$d, JSON_UNESCAPED_UNICODE); exit; }
function err($m,$c=400){ http_response_code($c); echo json_encode(['error'=>$m],JSON_UNESCAPED_UNICODE); exit; }
function vtime($t){ return preg_match('/^\d{2}:\d{2}$/',$t); }

$action = strtolower($_GET['action'] ?? $_POST['action'] ?? '');

if ($action==='list') {
  $rows=[]; 
  $q=$conn->query("SELECT id, etiqueta, DATE_FORMAT(inicio,'%H:%i') inicio, DATE_FORMAT(fin,'%H:%i') fin, activo, orden FROM horarios ORDER BY orden, inicio");
  while($q && $r=$q->fetch_assoc()) $rows[]=$r;
  ok(['items'=>$rows]);
}

if ($action==='create') {
  // CSRF opcional si lo usas
  $inicio=trim($_POST['inicio']??''); $fin=trim($_POST['fin']??''); $etiqueta=trim($_POST['etiqueta']??'');
  $activo=isset($_POST['activo'])?(int)!!$_POST['activo']:1;
  if(!vtime($inicio)||!vtime($fin)) err('Formato de hora inválido (HH:MM)');
  if(!$etiqueta) $etiqueta="$inicio-$fin";
  $st=$conn->prepare("SELECT COUNT(*) FROM horarios WHERE etiqueta=? OR (inicio=? AND fin=?)");
  $st->bind_param("sss",$etiqueta,$inicio,$fin); $st->execute(); $st->bind_result($c); $st->fetch(); $st->close();
  if($c>0) err('Ya existe un bloque con esa etiqueta o intervalo');
  $max=0; if($r=$conn->query("SELECT COALESCE(MAX(orden),0) FROM horarios")){ [$max]=$r->fetch_row(); }
  $st=$conn->prepare("INSERT INTO horarios(etiqueta,inicio,fin,activo,orden) VALUES(?,?,?,?,?)");
  $o=$max+1; $st->bind_param("sssii",$etiqueta,$inicio,$fin,$activo,$o);
  if(!$st->execute()) err('No se pudo crear'); $id=$st->insert_id; $st->close(); ok(['id'=>$id]);
}

if ($action==='update') {
  $id=(int)($_POST['id']??0); $inicio=trim($_POST['inicio']??''); $fin=trim($_POST['fin']??''); $etiqueta=trim($_POST['etiqueta']??''); $activo=isset($_POST['activo'])?(int)!!$_POST['activo']:null;
  if($id<=0) err('ID inválido'); if(!vtime($inicio)||!vtime($fin)) err('Formato de hora inválido (HH:MM)');
  if(!$etiqueta) $etiqueta="$inicio-$fin";
  $st=$conn->prepare("SELECT COUNT(*) FROM horarios WHERE (etiqueta=? OR (inicio=? AND fin=?)) AND id<>?");
  $st->bind_param("sssi",$etiqueta,$inicio,$fin,$id); $st->execute(); $st->bind_result($c); $st->fetch(); $st->close();
  if($c>0) err('Ya existe otro bloque con esa etiqueta o intervalo');
  if($activo===null){ $st=$conn->prepare("UPDATE horarios SET etiqueta=?,inicio=?,fin=? WHERE id=?"); $st->bind_param("sssi",$etiqueta,$inicio,$fin,$id); }
  else { $st=$conn->prepare("UPDATE horarios SET etiqueta=?,inicio=?,fin=?,activo=? WHERE id=?"); $st->bind_param("sssii",$etiqueta,$inicio,$fin,$activo,$id); }
  if(!$st->execute()) err('No se pudo actualizar'); $st->close(); ok();
}

if ($action==='delete') {
  $id=(int)($_POST['id']??0); if($id<=0) err('ID inválido');
  $st=$conn->prepare("DELETE FROM horarios WHERE id=?"); $st->bind_param("i",$id);
  if(!$st->execute()) err('No se pudo eliminar'); $st->close(); ok();
}

if ($action==='reorder') {
  $ids=$_POST['ids']??[]; if(!is_array($ids)||!$ids) err('Lista de ids vacía');
  $st=$conn->prepare("UPDATE horarios SET orden=? WHERE id=?"); $o=1;
  foreach($ids as $id){ $id=(int)$id; if($id<=0) continue; $st->bind_param("ii",$o,$id); $st->execute(); $o++; }
  $st->close(); ok();
}

err('Acción no soportada',404);
