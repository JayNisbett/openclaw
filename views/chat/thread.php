<?php defined('BASEPATH') or exit('No direct script access allowed'); ?>
<div class="panel_s">
    <div class="panel-body">
        <?php if (empty($active_thread)) { ?>
            <p class="text-muted">Select a thread.</p>
        <?php } else { ?>
            <h4 class="no-margin"><?php echo html_escape($active_thread['title']); ?></h4>
            <hr />
            <div id="openclaw-thread-messages">
                <?php $this->load->view('openclaw/chat/_messages', ['messages' => $messages]); ?>
            </div>
            <?php $this->load->view('openclaw/chat/_composer', ['thread' => $active_thread]); ?>
        <?php } ?>
    </div>
</div>
