<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin"><?php echo _l('openclaw_settings'); ?></h4>
                        <hr class="hr-panel-heading" />
                        <?php echo form_open(admin_url('openclaw/settings')); ?>
                        <div class="row">
                            <div class="col-md-6"><?php echo render_input('openclaw_cpanel_host', 'cPanel Host', get_option('openclaw_cpanel_host')); ?></div>
                            <div class="col-md-6"><?php echo render_input('openclaw_cpanel_domain', 'cPanel Domain', get_option('openclaw_cpanel_domain')); ?></div>
                        </div>
                        <div class="row">
                            <div class="col-md-6"><?php echo render_input('openclaw_cpanel_username', 'cPanel Username', get_option('openclaw_cpanel_username')); ?></div>
                            <div class="col-md-6"><?php echo render_input('openclaw_cpanel_token', 'cPanel API Token', get_option('openclaw_cpanel_token')); ?></div>
                        </div>
                        <div class="row">
                            <div class="col-md-4"><?php echo render_input('openclaw_chat_poll_interval', 'Chat Poll Interval (ms)', get_option('openclaw_chat_poll_interval')); ?></div>
                        </div>
                        <button class="btn btn-primary" type="submit"><?php echo _l('submit'); ?></button>
                        <?php echo form_close(); ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
