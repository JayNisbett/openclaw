<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="panel_s">
            <div class="panel-body">
                <h4>Step 1: <?php echo _l('openclaw_wizard_connect'); ?></h4>
                <p>Connect one or more OpenClaw gateways first.</p>
                <a href="<?php echo admin_url('openclaw/gateways'); ?>" class="btn btn-info">Manage Gateways</a>
                <a href="<?php echo admin_url('openclaw/wizard/2'); ?>" class="btn btn-default pull-right">Next</a>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
