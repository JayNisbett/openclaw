<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php init_head(); ?>
<div id="wrapper">
    <div class="content">
        <div class="row">
            <div class="col-md-12">
                <div class="panel_s">
                    <div class="panel-body">
                        <h4 class="no-margin"><?php echo _l('openclaw_event_log'); ?></h4>
                        <hr class="hr-panel-heading" />
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Event</th>
                                        <th>Source</th>
                                        <th>Context</th>
                                        <th>Gateway</th>
                                        <th>Created</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($events as $event) { ?>
                                        <tr>
                                            <td><?php echo (int) $event['id']; ?></td>
                                            <td><?php echo html_escape($event['event_type']); ?></td>
                                            <td><?php echo html_escape($event['source']); ?></td>
                                            <td><?php echo html_escape((string) $event['rel_type']); ?> #<?php echo html_escape((string) $event['rel_id']); ?></td>
                                            <td><?php echo html_escape((string) $event['gateway_id']); ?></td>
                                            <td><?php echo _dt($event['created_at']); ?></td>
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
</div>
<?php init_tail(); ?>
