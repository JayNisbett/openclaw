<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="panel_s">
            <div class="panel-body">
                <h4>Step 4: <?php echo _l('openclaw_wizard_staff'); ?></h4>
                <p>Create staff profiles for discovered agents and generate API keys on their behalf.</p>
                <?php if (empty($discovered_agents)) { ?>
                    <p class="text-muted">No discovered agents. Run discovery in step 2 first.</p>
                <?php } else { ?>
                    <?php foreach ($discovered_agents as $agent) { ?>
                        <div class="well">
                            <h5><?php echo html_escape($agent['external_agent_name']); ?> (<?php echo html_escape($agent['external_agent_id']); ?>)</h5>
                            <?php if (!empty($agent['staff_id'])) { ?>
                                <p>Already onboarded as staff #<?php echo (int) $agent['staff_id']; ?></p>
                                <a href="<?php echo admin_url('openclaw/agents/' . (int) $agent['staff_id']); ?>" class="btn btn-default btn-sm">View Agent</a>
                            <?php } else { ?>
                                <form method="post" action="<?php echo admin_url('openclaw/wizard_create_staff'); ?>">
                                    <?php echo form_hidden($this->security->get_csrf_token_name(), $this->security->get_csrf_hash()); ?>
                                    <input type="hidden" name="gateway_agent_id" value="<?php echo (int) $agent['id']; ?>">
                                    <input type="hidden" name="gateway_id" value="<?php echo (int) $agent['gateway_id']; ?>">
                                    <input type="hidden" name="external_agent_id" value="<?php echo html_escape($agent['external_agent_id']); ?>">
                                    <div class="row">
                                        <div class="col-md-3"><?php echo render_input('firstname', 'First Name', $agent['external_agent_name']); ?></div>
                                        <div class="col-md-3"><?php echo render_input('lastname', 'Last Name', 'Agent'); ?></div>
                                        <div class="col-md-3"><?php echo render_input('email', 'Email', strtolower(str_replace(' ', '.', $agent['external_agent_name'])) . '@' . get_option('openclaw_cpanel_domain')); ?></div>
                                        <div class="col-md-3"><?php echo render_input('agent_type', 'Agent Type', 'worker'); ?></div>
                                    </div>
                                    <button class="btn btn-primary btn-sm">Create Staff + API Key</button>
                                </form>
                            <?php } ?>
                        </div>
                    <?php } ?>
                <?php } ?>
                <a href="<?php echo admin_url('openclaw/wizard/3'); ?>" class="btn btn-default">Back</a>
                <a href="<?php echo admin_url('openclaw/wizard/5'); ?>" class="btn btn-default pull-right">Next</a>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
