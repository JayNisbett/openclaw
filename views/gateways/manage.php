<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin"><?php echo _l('openclaw_gateways'); ?></h4>
                        <hr class="hr-panel-heading" />
                        <?php $this->load->view('openclaw/gateways/form', ['editing' => $editing]); ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th><?php echo _l('openclaw_name'); ?></th>
                                    <th><?php echo _l('openclaw_endpoint_url'); ?></th>
                                    <th><?php echo _l('openclaw_status'); ?></th>
                                    <th><?php echo _l('openclaw_last_heartbeat'); ?></th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($gateways as $gateway) { ?>
                                    <tr>
                                        <td><?php echo (int) $gateway['id']; ?></td>
                                        <td><?php echo html_escape($gateway['name']); ?></td>
                                        <td><?php echo html_escape($gateway['endpoint_url']); ?></td>
                                        <td><span class="label label-<?php echo $gateway['status'] === 'active' ? 'success' : 'default'; ?>"><?php echo html_escape($gateway['status']); ?></span></td>
                                        <td><?php echo html_escape((string) $gateway['last_heartbeat']); ?></td>
                                        <td class="text-right">
                                            <a href="<?php echo admin_url('openclaw/gateways/' . (int) $gateway['id']); ?>" class="btn btn-default btn-sm">Edit</a>
                                            <a href="<?php echo admin_url('openclaw/discover_gateway_agents/' . (int) $gateway['id']); ?>" class="btn btn-info btn-sm">Discover Agents</a>
                                            <a href="<?php echo admin_url('openclaw/delete_gateway/' . (int) $gateway['id']); ?>" class="btn btn-danger btn-sm _delete">Delete</a>
                                        </td>
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
