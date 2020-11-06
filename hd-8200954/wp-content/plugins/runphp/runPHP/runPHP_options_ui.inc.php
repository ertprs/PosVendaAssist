<?php
   global $wp_roles;

   // process the update
   if (isset($_POST['info_update'])) {
      $rolePerms = $_POST['can_runPHP'];
      $roleNames = $wp_roles->get_names();

      foreach ($roleNames as $role => $roleName) {
         $roleObj = $wp_roles->get_role($role);

         if (is_array($rolePerms) && in_array($role, $rolePerms)) {
            /* Bug in PHP code: if I use $wp_roles->add_cap() instead
               of calling add_cap() on a Role object the object does
               not get updated - the updates below which ask the Role
               objects for their state are thus out of sync until the
               next page reload.

            bad: $wp_roles->add_cap($role, 'can_runPHP');
            */
            $roleObj->add_cap('can_runPHP', true);
         }
         else {
            $roleObj->add_cap('can_runPHP', false);
         }
      }

      // I'd prefer to use this wp_redirect, but WP has already sent headers...
      /*
      $to = $_SERVER['REQUEST_URI'] . '&amp;updated=true';
      wp_redirect($to);
      */

      echo '<div class="updated"><p><strong>';
      _e('Update completed successfully.', 'runPHP');
      echo '</strong></p></div>';
   }
?>

   <div class=wrap>
   <form method="post">
      <h2><?php _e('RunPHP Options', 'runPHP'); ?></h2>
      <fieldset name="runPHP Post Options">
      <legend><?php _e('Roles allowed to use runPHP:', 'runPHP'); ?></legend>
      <ul style="list-style-type: none;">
      <?php
      $roles = $wp_roles->get_names();

      foreach ($roles as $role => $roleName) {
         $roleObj = $wp_roles->get_role($role);

         if ($roleObj->has_cap('can_runPHP'))
            $checked = ' checked="checked"';
         else
            $checked = '';
         
         echo "\n  <li><label for=\"$role\">" .
          '<input name="can_runPHP[]" type="checkbox" id="' . $role . '"' .
          ' value="' . $role . '"' . $checked . '/> ';

         echo $roleName . '</label></li>';
      }
      ?>

      </ul>
      </fieldset>

      <div class="submit">
         <input type="submit" name="info_update" value="<?php
         _e('Update options', 'runPHP'); ?> &raquo;" />
      </div>
   </form>
 </div>
<?php
?>
