<?php

set_include_path( ".:" . __DIR__ . "/../includes/");

include "web_functions.inc.php";
include "ldap_functions.inc.php";
include "account_lifecycle_functions.inc.php";

// Must be logged in to see this page
if (!isset($_SESSION['username'])) {
  header("Location: //{$_SERVER['HTTP_HOST']}{$SERVER_PATH}log_in\n\n");
  exit(0);
}

$USER_ID = $_SESSION['username'];

// Get account expiration information
$ldap_connection = open_ldap_connection();
$user_search = ldap_search($ldap_connection, $LDAP['user_dn'],
  "({$LDAP['account_attribute']}=" . ldap_escape($USER_ID, "", LDAP_ESCAPE_FILTER) . ")",
  array('dn'));

$expiry_date_formatted = null;
$days_expired = null;

if ($user_search) {
  $user_entry = ldap_get_entries($ldap_connection, $user_search);
  if ($user_entry['count'] > 0) {
    $user_dn = $user_entry[0]['dn'];

    // Get expiration information
    $days_remaining = null;
    $is_expired = account_lifecycle_is_expired($ldap_connection, $user_dn, $days_remaining);

    if ($is_expired && $days_remaining !== null) {
      $days_expired = abs($days_remaining);
      $expiry_date_formatted = account_lifecycle_get_expiry_date_formatted($ldap_connection, $user_dn, 'F j, Y');
    }
  }
}

ldap_close($ldap_connection);

render_header("$ORGANISATION_NAME account manager - Account expired");

?>
<div class="container">
  <div class="row justify-content-center">
    <div class="col-md-8">

      <div class="card border-danger">
        <div class="card-header bg-danger text-white text-center">
          <h4 class="card-title mb-0">
            <i class="bi bi-exclamation-triangle-fill"></i> Account expired
          </h4>
        </div>
        <div class="card-body">

          <div class="alert alert-danger">
            <h5><strong>Votre compte a expiré et ne peut plus être utilisé.</strong></h5>
          </div>

          <?php if ($expiry_date_formatted): ?>
          <div class="mb-3">
            <p><strong>Date d'expiration:</strong> <?php echo htmlspecialchars($expiry_date_formatted); ?></p>
            <?php if ($days_expired !== null): ?>
            <p><strong>Expiré:</strong> <?php echo $days_expired; ?> jours<?php echo $days_expired != 1 ? 's' : ''; ?> il y a</p>
            <?php endif; ?>
          </div>
          <?php endif; ?>

          <div class="mb-3">
            <h5>Qu'est-ce que cela signifie?h5>
            <p>Votre compte a expiré et a été désactivé. Vous ne pourrez accéder à aucun service ni ressource tant qu'un administrateur ne l'aura pas renouvelé.</p>
          </div>

          <div class="mb-3">
            <h5>Que devez-vous faire ?</h5>
            <p>Veuillez contacter votre administrateur système pour demander le renouvellement ou la prolongation de votre compte. Indiquez-lui votre nom d'utilisateur : <strong><?php echo htmlspecialchars($USER_ID); ?></strong></p>
          </div>

          <?php if (!empty($SUPPORT_EMAIL)): ?>
          <div class="alert alert-info">
            <p class="mb-0"><strong>Contact support:</strong> <a href="mailto:<?php echo htmlspecialchars($SUPPORT_EMAIL); ?>"><?php echo htmlspecialchars($SUPPORT_EMAIL); ?></a></p>
          </div>
          <?php endif; ?>

          <div class="text-center mt-4">
            <a href="<?php echo url('/log_out'); ?>" class="btn btn-secondary">
              <i class="bi bi-box-arrow-right"></i> Déconnexion
            </a>
          </div>

        </div>
      </div>

    </div>
  </div>
</div>

<?php
render_footer();
?>
