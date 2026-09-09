<?php
/**
 *
 * Forgot Password for Administrator
 * Copyright (C) 2020 Eddy Subratha (eddy.subratha@gmail.com)
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 */

use SLiMS\Url;
use SLiMS\Captcha\Factory as Captcha;

// be sure that this file not accessed directly
if (!defined('INDEX_AUTH')) {
    die("can not access this file directly");
} elseif (INDEX_AUTH != 1) { 
    die("can not access this file directly");
}

/*
if (defined('LIGHTWEIGHT_MODE')) {
    header('Location: index.php');
}
*/

// required file
require SIMBIO.'simbio_DB/simbio_dbop.inc.php';

// https connection (if enabled)
if ($sysconf['https_enable']) {
    simbio_security::doCheckHttps($sysconf['https_port']);
}

// Captcha initialize
$captcha = Captcha::section('forgot');

// start the output buffering for main content
ob_start();

if (isset($_POST['resetPass'])) {
    $email = $dbs->escape_string($_POST['currentmail']);
    if (!$email) {
        echo '<script type="text/javascript">alert(\''.__('Please supply valid username and password').'\');</script>';
    } else {
        # <!-- Captcha form processing - start -->
        try {
            if ($captcha->isSectionActive() && $captcha->isValid() === false) throw new Exception(__('Captcha incorrect.'));

            // Validate current email
            $_q = $dbs->query("SELECT user_id, realname FROM user WHERE email='{$email}'");

            if ($_q->num_rows === 0) throw new Exception(__('Current email not found. Please try again.'));

            // Name
            $file_d = $_q->fetch_assoc();
            $name = $file_d['realname'];
            /// Generate a token for forgot password
            $salt = password_hash($email, PASSWORD_DEFAULT);
            $_sql_update_salt = sprintf("UPDATE user SET forgot = '{$salt}', last_update = CURDATE() WHERE email = '%s'", $email);
            // write log
            writeLog('staff', $name, 'Forgot Password', $name.' has been requested a new password.', 'Password', 'Request');
            $_update_q = $dbs->query($_sql_update_salt);

            // build the reset link pointing to this site's own newpass page
            $resetLink = Url::getSlimsBaseUri() . 'index.php?p=newpass&email=' . urlencode($email) . '&salt=' . urlencode($salt);

            // send the reset email using our own SMTP configuration (System > Mail Setting)
            $sent = \SLiMS\Mail::to($email, $name)
                ->subject(__('Reset Password').' - '.$sysconf['library_name'])
                ->message(
                    __('Hi').' '.$name.",\n\n".
                    __('We received a request to reset your password. Click the link below to set a new password').":\n\n".
                    $resetLink."\n\n".
                    __('This link can only be used once. If you did not request this, please ignore this email.')
                )
                ->send();

            if (!$sent) {
                $error = isDev() ? ' ' . __('Error') . ' : ' . \SLiMS\Mail::getInstance()->ErrorInfo : '';
                throw new Exception(__('Cannot send the email. Please try again.') . $error);
            }

            flash('resetSuccess', __('<strong>Congratulations! </strong>An instruction has been sent to your email. Please check your inbox.'));
        } catch (Exception $e) {
            flash('resetFailed', $e->getMessage());
        }
    }
}
?>
<div id="loginForm">
    <noscript>
        <div style="font-weight: bold; color: #FF0000;"><?php echo __('Your browser does not support Javascript or Javascript is disabled. Application won\'t run without Javascript!'); ?><div>
    </noscript>
    <div class="mb-3">
        <?php
        if (flash()->isEmpty()) {
            // if there is login action
            echo __('If you need help resetting your password, we can help by sending you a link to reset it.');
        } else if ($key = flash()->includes('resetFailed','resetSuccess')) {
            flash()->show($key);
        }
        ?>
        &nbsp;<a href="javascript:void(0)" id="forgotHelpLink"><strong><?php echo __('How does this work?'); ?></strong></a>
    </div>
    <form action="index.php?p=forgot" method="post" novalidation>
        <div class="heading1"><?php echo __('Your email address'); ?></div>
        <div style="font-size: 12px; color: #666; margin-bottom: 4px;"><?php echo __('Use the email address registered to your account (not a phone number). We will send a reset link to that email inbox.'); ?></div>
        <div class="login_input"><input type="email" name="currentmail" id="currentmail" class="login_input" required /></div>
        <?php
        if ($captcha->isSectionActive()) { ?>
            <div class="captchaAdmin">
                <?= $captcha->getCaptcha() ?>
            </div>
            <?php
        }
        ?>
        <div class="marginTop">
        <input type="submit" name="resetPass" value="<?php echo __('Reset my password'); ?>" class="loginButton" />
        <a class="forgotButton" href="index.php?p=login"><?php echo __('Cancel') ?></a>
        </div>
    </form>
</div>

<div id="forgotHelpOverlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:9998;">
    <div style="background:#fff; max-width:420px; margin:8% auto; padding:20px 24px; border-radius:6px; position:relative; box-shadow:0 2px 12px rgba(0,0,0,0.3);">
        <a href="javascript:void(0)" id="forgotHelpClose" style="position:absolute; top:10px; right:14px; font-size:18px; font-weight:bold; text-decoration:none; color:#888;">&times;</a>
        <h4 style="margin-top:0;"><?php echo __('How to reset your password'); ?></h4>
        <ol style="padding-left:18px; line-height:1.7;">
            <li><?php echo __('Type in the <strong>email address</strong> that is registered to your account (ask the system administrator if you are not sure which email is registered). This is <strong>not</strong> your phone number.'); ?></li>
            <li><?php echo __('Click the <strong>"Reset my password"</strong> button.'); ?></li>
            <li><?php echo __('Open your <strong>email inbox</strong> (check the Spam/Junk folder too) on your phone or computer. Look for a message from the library.'); ?></li>
            <li><?php echo __('Click the link inside that email to set a new password. The link can only be used once.'); ?></li>
        </ol>
        <div style="font-size:12px; color:#888; margin-top:10px;"><?php echo __('Didn\'t receive the email after a few minutes? Make sure the email address you typed is correct and matches what is registered on your account.'); ?></div>
    </div>
</div>
<script type="text/javascript">
jQuery('#currentmail').focus();
jQuery('#forgotHelpLink').on('click', function() { jQuery('#forgotHelpOverlay').fadeIn(150); });
jQuery('#forgotHelpClose, #forgotHelpOverlay').on('click', function(e) {
    if (e.target === this) jQuery('#forgotHelpOverlay').fadeOut(150);
});
</script>

<?php
// main content
$main_content = ob_get_clean();

// page title
$page_title = __('Forgot My Password').' | '.$sysconf['library_name'];

if ($sysconf['template']['base'] == 'html') {
    // create the template object
    $template = new simbio_template_parser($sysconf['template']['dir'].'/'.$sysconf['template']['theme'].'/login_template.html');
    // assign content to markers
    $template->assign('<!--PAGE_TITLE-->', $page_title);
    $template->assign('<!--CSS-->', $sysconf['template']['css']);
    $template->assign('<!--MAIN_CONTENT-->', $main_content);
    // print out the template
    $template->printOut();
} else if ($sysconf['template']['base'] == 'php') {
    require_once $sysconf['template']['dir'].'/'.$sysconf['template']['theme'].'/login_template.inc.php';
}
exit();
