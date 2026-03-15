<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="panel_s">
            <div class="panel-body">
                <h4>Step 2: <?php echo _l('openclaw_wizard_discover'); ?></h4>
                <p>Discover available agents from connected gateways.</p>
                <?php if (!empty($gateways)) { ?>
                    <?php foreach ($gateways as $gateway) { ?>
                        <a class="btn btn-info btn-sm mright5" href="<?php echo admin_url('openclaw/discover_gateway_agents/' . (int) $gateway['id']); ?>">
                            Discover on <?php echo html_escape($gateway['name']); ?>
                        </a>
                    <?php } ?>
                    <hr />
                    <table class="table table-striped">
                        <thead>
                            <tr><th>ID</th><th>Gateway</th><th>External Agent</th><th>Status</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($discovered_agents as $agent) { ?>
                                <tr>
                                    <td><?php echo (int) $agent['id']; ?></td>
                                    <td><?php echo (int) $agent['gateway_id']; ?></td>
                                    <td><?php echo html_escape($agent['external_agent_name']); ?> (<?php echo html_escape($agent['external_agent_id']); ?>)</td>
                                    <td><?php echo html_escape($agent['status']); ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                <?php } else { ?>
                    <p class="text-muted">No gateways configured yet.</p>
                <?php } ?>
                <a href="<?php echo admin_url('openclaw/wizard/1'); ?>" class="btn btn-default">Back</a>
                <a href="<?php echo admin_url('openclaw/wizard/3'); ?>" class="btn btn-default pull-right">Next</a>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
