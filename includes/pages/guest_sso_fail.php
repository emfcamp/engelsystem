<?php
function sso_fail_title() {
  return _("Login failed");
}

function guest_sso_fail() {
  return template_render('../templates/guest_sso_fail.html', array());
}
?>
