<?php
// Wandering Bar: enquiry form handler. Emails the booking form to Andrew.
header('Content-Type: application/json; charset=utf-8');

$TO   = 'info@wanderingbarcy.com';
$FROM = 'info@wanderingbarcy.com';

function out($code, $data) { http_response_code($code); echo json_encode($data); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') out(405, ['ok' => false, 'error' => 'method']);
if (!empty($_POST['website'])) out(200, ['ok' => true]); // spam bot filled the hidden field

function f($k, $max = 500) {
  $v = isset($_POST[$k]) ? trim((string) $_POST[$k]) : '';
  $v = str_replace(["\r", "\0"], '', $v);
  return mb_substr($v, 0, $max);
}
$type  = f('type', 80);   $guests = f('guests', 10); $pkg = f('pkg', 80);
$date  = f('date', 20);   $area   = f('area', 60);   $venue = f('venue', 200);
$name  = f('name', 100);  $phone  = f('phone', 40);  $email = f('email', 150);
$notes = f('notes', 3000);

if ($type === '' || $date === '' || $area === '' || $venue === '' || $name === '' || $phone === '') {
  out(422, ['ok' => false, 'error' => 'missing']);
}
$name1 = preg_replace('/[\n]+/', ' ', $name);

$lines = [
  'New enquiry from the Wandering Bar website',
  '',
  'Event:     ' . $type,
  'Guests:    about ' . $guests,
  'Package:   ' . $pkg,
  'Date:      ' . $date,
  'Location:  ' . $venue . ', ' . $area,
  '',
  'Name:      ' . $name1,
  'Phone:     ' . $phone,
  'Email:     ' . ($email !== '' ? $email : '-'),
];
if ($notes !== '') { $lines[] = ''; $lines[] = 'Notes:'; $lines[] = $notes; }
$body = implode("\n", $lines) . "\n";

$subject = 'Event enquiry: ' . $type . ' - ' . $name1 . ' (' . $date . ')';
$subject = '=?UTF-8?B?' . base64_encode(preg_replace('/[\n]+/', ' ', $subject)) . '?=';

$headers  = 'From: Wandering Bar Website <' . $FROM . ">\r\n";
if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
  $headers .= 'Reply-To: ' . $email . "\r\n";
}
$headers .= "MIME-Version: 1.0\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n";

$sent = @mail($TO, $subject, $body, $headers, '-f' . $FROM);
out($sent ? 200 : 500, ['ok' => (bool) $sent]);
