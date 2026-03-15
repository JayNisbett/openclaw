<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-3">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin"><?php echo count($gateways); ?></h4>
                        <p class="text-muted"><?php echo _l('openclaw_gateways'); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin"><?php echo count($agents); ?></h4>
                        <p class="text-muted"><?php echo _l('openclaw_agents'); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin"><?php echo count($threads); ?></h4>
                        <p class="text-muted"><?php echo _l('openclaw_threads'); ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin"><?php echo count($events); ?></h4>
                        <p class="text-muted"><?php echo _l('openclaw_event_log'); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4><?php echo _l('openclaw_chat'); ?></h4>
                        <a href="<?php echo admin_url('openclaw/chat'); ?>" class="btn btn-info"><?php echo _l('openclaw_start_chat'); ?></a>
                        <a href="<?php echo admin_url('openclaw/wizard'); ?>" class="btn btn-default"><?php echo _l('openclaw_onboarding_wizard'); ?></a>
                        <a href="<?php echo admin_url('openclaw/settings'); ?>" class="btn btn-default pull-right"><?php echo _l('openclaw_settings'); ?></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
