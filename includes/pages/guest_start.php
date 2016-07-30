<?php
function guest_start() {
  global $logout_url;
  redirect($logout_url);
}
?>