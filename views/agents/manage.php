<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin"><?php echo _l('openclaw_agents'); ?></h4>
                        <hr class="hr-panel-heading" />
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Staff ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Type</th>
                                    <th>Tier</th>
                                    <th>Gateway</th>
                                    <th>API</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($agents as $agent) { ?>
                                    <tr>
                                        <td><?php echo (int) $agent['staffid']; ?></td>
                                        <td><?php echo html_escape($agent['full_name']); ?></td>
                                        <td><?php echo html_escape($agent['email']); ?></td>
                                        <td><?php echo html_escape((string) $agent['agent_type']); ?></td>
                                        <td><?php echo html_escape((string) $agent['autonomy_tier']); ?></td>
                                        <td><?php echo html_escape((string) $agent['gateway_id']); ?></td>
                                        <td><?php echo $agent['api_user_id'] ? 'Configured' : 'Missing'; ?></td>
                                        <td class="text-right"><a class="btn btn-default btn-sm" href="<?php echo admin_url('openclaw/agents/' . (int) $agent['staffid']); ?>">Details</a></td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php init_tail(); ?>
