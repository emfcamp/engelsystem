<?php

// Testet ob ein User eingeloggt ist und lädt die entsprechenden Privilegien
function load_auth() {
  global $user, $privileges;

  $user = null;
  if (isset($_SESSION['uid'])) {
    $user = sql_select("SELECT * FROM `User` WHERE `UID`='" . sql_escape($_SESSION['uid']) . "' LIMIT 1");
    if (count($user) > 0) {
      // User ist eingeloggt, Datensatz zur Verfügung stellen und Timestamp updaten
      list ($user) = $user;
      sql_query("UPDATE `User` SET " . "`lastLogIn` = '" . time() . "'" . " WHERE `UID` = '" . sql_escape($_SESSION['uid']) . "' LIMIT 1;");
    } else
      unset($_SESSION['uid']);
  }

  $privileges = isset($user) ? privileges_for_user($user['UID']) : privileges_for_group(- 1);
}

// generate a salt (random string) of arbitrary length suitable for the use with crypt()
function generate_salt($length = 16) {
  $alphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/";
  $salt = "";
  for($i = 0; $i < $length; $i ++) {
    $salt .= $alphabet[rand(0, strlen($alphabet) - 1)];
  }
  return $salt;
}

// set the password of a user
function set_password($uid, $password) {
  return sql_query("UPDATE `User` SET `Passwort` = '" . sql_escape(crypt($password, CRYPT_ALG . '$' . generate_salt(16) . '$')) . "', `password_recovery_token`=NULL WHERE `UID` = " . intval($uid) . " LIMIT 1");
}

// verify a password given a precomputed salt.
// if $uid is given and $salt is an old-style salt (plain md5), we convert it automatically
function verify_password($password, $salt, $uid = false) {
  $correct = false;
  if (substr($salt, 0, 1) == '$') // new-style crypt()
    $correct = crypt($password, $salt) == $salt;
  elseif (substr($salt, 0, 7) == '{crypt}') // old-style crypt() with DES and static salt - not used anymore
    $correct = crypt($password, '77') == $salt;
  elseif (strlen($salt) == 32) // old-style md5 without salt - not used anymore
    $correct = md5($password) == $salt;

  if ($correct && substr($salt, 0, strlen(CRYPT_ALG)) != CRYPT_ALG && $uid) {
    // this password is stored in another format than we want it to be.
    // let's update it!
    // we duplicate the query from the above set_password() function to have the extra safety of checking the old hash
    sql_query("UPDATE `User` SET `Passwort` = '" . sql_escape(crypt($password, CRYPT_ALG . '$' . generate_salt() . '$')) . "' WHERE `UID` = " . intval($uid) . " AND `Passwort` = '" . sql_escape($salt) . "' LIMIT 1");
  }
  return $correct;
}

function privileges_for_user($user_id) {
  $privileges = array ();
  $user_privs = sql_select("SELECT `Privileges`.`name` FROM `User` JOIN `UserGroups` ON (`User`.`UID` = `UserGroups`.`uid`) JOIN `GroupPrivileges` ON (`UserGroups`.`group_id` = `GroupPrivileges`.`group_id`) JOIN `Privileges` ON (`GroupPrivileges`.`privilege_id` = `Privileges`.`id`) WHERE `User`.`UID`='" . sql_escape($user_id) . "'");
  foreach ($user_privs as $user_priv)
    $privileges[] = $user_priv['name'];
  return $privileges;
}

function privileges_for_group($group_id) {
  $privileges = array ();
  $groups_privs = sql_select("SELECT * FROM `GroupPrivileges` JOIN `Privileges` ON (`GroupPrivileges`.`privilege_id` = `Privileges`.`id`) WHERE `group_id`='" . sql_escape($group_id) . "'");
  foreach ($groups_privs as $guest_priv)
    $privileges[] = $guest_priv['name'];
  return $privileges;
}


function base64url_encode($data) {
  return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64url_decode($data) {
  return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
}

function hash_compare($a, $b) {
  if (!is_string($a) || !is_string($b)) {
    return false;
  }

  $len = strlen($a);
  if ($len !== strlen($b)) {
    return false;
  }

  $status = 0;
  for ($i = 0; $i < $len; $i++) {
    $status |= ord($a[$i]) ^ ord($b[$i]);
  }
  return $status === 0;
}

function generate_sso_code($key, $timestamp, $uid) {
  $msg = sprintf("%s-%s", intval($timestamp), $uid);
  $mac = hash_hmac('sha256', 'sso-' . $msg, $key, TRUE);
  # Truncate the digest to 20 base64 bytes
  return $msg . "-" . substr(base64url_encode($mac), 0, 20);
}

function verify_sso_code($key, $current_timestamp, $code) {
  list($timestamp, $uid) = explode('-', $code, 3);
  if (!is_string($uid)) return null;
  $expected_code = generate_sso_code($key, $timestamp, $uid);
  if (hash_compare($expected_code, $code)) {
    $age = $current_timestamp - intval($timestamp);
    // Allow 10 minutes for connection failure and clock drift
    if ($age > 600) return false;
    return intval($uid);
  }
  return null;
}

?>

