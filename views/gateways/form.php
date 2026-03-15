<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<?php echo form_open(admin_url('openclaw/gateways')); ?>
<?php if (!empty($editing)) { ?>
    <input type="hidden" name="id" value="<?php echo (int) $editing['id']; ?>">
<?php } ?>
<div class="row">
    <div class="col-md-4"><?php echo render_input('name', _l('openclaw_name'), isset($editing['name']) ? $editing['name'] : ''); ?></div>
    <div class="col-md-4"><?php echo render_input('endpoint_url', _l('openclaw_endpoint_url'), isset($editing['endpoint_url']) ? $editing['endpoint_url'] : ''); ?></div>
    <div class="col-md-4">
        <?php
        echo render_select('status', [
            ['id' => 'active', 'name' => 'active'],
            ['id' => 'inactive', 'name' => 'inactive'],
        ], ['id', 'name'], _l('openclaw_status'), isset($editing['status']) ? $editing['status'] : 'active');
        ?>
    </div>
</div>
<div class="row">
    <div class="col-md-6"><?php echo render_input('api_key', _l('openclaw_api_key'), isset($editing['api_key']) ? $editing['api_key'] : app_generate_hash()); ?></div>
    <div class="col-md-6"><?php echo render_input('inbound_secret', _l('openclaw_inbound_secret'), isset($editing['inbound_secret']) ? $editing['inbound_secret'] : app_generate_hash()); ?></div>
</div>
<button type="submit" class="btn btn-primary"><?php echo _l('submit'); ?></button>
<?php echo form_close(); ?>
