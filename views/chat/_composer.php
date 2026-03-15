<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<form id="openclaw-chat-send-form">
    <input type="hidden" name="thread_id" value="<?php echo isset($thread['id']) ? (int) $thread['id'] : 0; ?>">
    <div class="input-group">
        <input type="text" class="form-control" name="message" placeholder="<?php echo _l('openclaw_send_message'); ?>">
        <span class="input-group-btn">
            <button class="btn btn-primary" type="submit"><?php echo _l('openclaw_send_message'); ?></button>
        </span>
    </div>
</form>
