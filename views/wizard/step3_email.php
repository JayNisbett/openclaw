<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="panel_s">
            <div class="panel-body">
                <h4>Step 3: <?php echo _l('openclaw_wizard_email'); ?></h4>
                <p>Configure cPanel details in settings to auto-create inboxes, or provide existing emails in Step 4.</p>
                <a href="<?php echo admin_url('openclaw/settings'); ?>" class="btn btn-info">Open Settings</a>
                <hr />
                <h5>Create inbox in cPanel</h5>
                <form method="post" action="<?php echo admin_url('openclaw/wizard_create_email'); ?>">
                    <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
                    <div class="row">
                        <div class="col-md-4"><?php echo render_input('local_part', 'Local Part', 'agent-' . time()); ?></div>
                        <div class="col-md-4"><?php echo render_input('password', 'Password', app_generate_hash()); ?></div>
                        <div class="col-md-4 mtop25"><button class="btn btn-default">Create Inbox</button></div>
                    </div>
                </form>
                <a href="<?php echo admin_url('openclaw/wizard/2'); ?>" class="btn btn-default">Back</a>
                <a href="<?php echo admin_url('openclaw/wizard/4'); ?>" class="btn btn-default pull-right">Next</a>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
