<?php

/*******************************************************************************
 * Fetchmail Roundcube Plugin (Roundcube version 1.0-beta and above)
 * This software distributed under the terms of the GNU General Public License
 * as published by the Free Software Foundation
 * Further details on the GPL license can be found at
 * http://www.gnu.org/licenses/gpl.html
 * By contributing authors release their contributed work under this license
 * For more information see README.md file
 ******************************************************************************/

 class fetchmail extends rcube_plugin
 {
     public $task = 'settings';
     private $rc;
     private $show_folder;
     public function init()
     {
         $this->rc = rcube::get_instance();
         $this->load_config('config.inc.php.dist');
         $this->load_config();
         $this->add_texts('localization/', true);
         $this->show_folder = $this->rc->config->get('fetchmail_folder');
        
         $this->add_hook('settings_actions', array(
                $this,
                'settings_actions'
        ));
        
         $this->register_action('plugin.fetchmail', array(
                $this,
                'init_html'
        ));
         $this->register_action('plugin.fetchmail.save', array(
                $this,
                'save'
        ));
         $this->register_action('plugin.fetchmail.del', array(
                $this,
                'del'
        ));
         $this->register_action('plugin.fetchmail.enable', array(
                $this,
                'enable'
        ));
         $this->register_action('plugin.fetchmail.disable', array(
                $this,
                'disable'
        ));
         if (strpos($this->rc->action, 'plugin.fetchmail') === 0) {
             $this->include_script('fetchmail.js');
         }
     }
     public function init_html()
     {
         $this->register_handler('plugin.fetchmail_form', array(
                $this,
                'gen_form'
        ));
         $this->register_handler('plugin.fetchmail_table', array(
                $this,
                'gen_table'
        ));
         $this->rc->output->set_pagetitle($this->gettext('fetchmail'));
         $this->rc->output->send('fetchmail.fetchmail');
     }
     public function disable()
     {
         $id = rcube_utils::get_input_value('_id', rcube_utils::INPUT_GET);
         if ($id != 0 || $id != '') {
             $sql = "UPDATE fetchmail SET active = '0' WHERE id = '$id'";
             $update = $this->rc->db->query($sql);
         }
     }
     public function enable()
     {
         $id = rcube_utils::get_input_value('_id', rcube_utils::INPUT_GET);
         if ($id != 0 || $id != '') {
             $sql = "UPDATE fetchmail SET active = '1' WHERE id = '$id'";
             $update = $this->rc->db->query($sql);
         }
     }
     public function del()
     {
         $id = rcube_utils::get_input_value('_id', rcube_utils::INPUT_GET);
         if ($id != 0 || $id != '') {
             $sql = "DELETE FROM fetchmail WHERE id = '$id'";
             $delete = $this->rc->db->query($sql);
         }
     }
     public function save()
     {
         $id = rcube_utils::get_input_value('_id', rcube_utils::INPUT_POST);
         $mailbox = $this->rc->user->data ['username'];
         $explode_mailbox = explode('@', $mailbox);
         $mailbox_user = $explode_mailbox[0];
         $mailbox_domain = $explode_mailbox[1];
         $protocol = rcube_utils::get_input_value('_fetchmailprotocol', rcube_utils::INPUT_POST);
         $protocol = strtoupper($protocol); // TODO: temporary
         $server = rcube_utils::get_input_value('_fetchmailserver', rcube_utils::INPUT_POST);
         $user = rcube_utils::get_input_value('_fetchmailuser', rcube_utils::INPUT_POST);
         $pass = base64_encode(rcube_utils::get_input_value('_fetchmailpass', rcube_utils::INPUT_POST));
         $folder = rcube_utils::get_input_value('_fetchmailfolder', rcube_utils::INPUT_POST);
         $pollinterval = rcube_utils::get_input_value('_fetchmailpollinterval', rcube_utils::INPUT_POST);
         $keep = rcube_utils::get_input_value('_fetchmailkeep', rcube_utils::INPUT_POST);
         $usessl = rcube_utils::get_input_value('_fetchmailusessl', rcube_utils::INPUT_POST);
         $fetchall = rcube_utils::get_input_value('_fetchmailfetchall', rcube_utils::INPUT_POST);
         $enabled = rcube_utils::get_input_value('_fetchmailenabled', rcube_utils::INPUT_POST);
         $newentry = rcube_utils::get_input_value('_fetchmailnewentry', rcube_utils::INPUT_POST);
         if (! $keep) {
             $keep = 0;
         } else {
             $keep = 1;
         }
         if (! $enabled) {
             $enabled = 0;
         } else {
             $enabled = 1;
         }
         if (! $usessl) {
             $usessl = 0;
         } else {
             $usessl = 1;
         }
         if (! $fetchall) {
             $fetchall = 0;
         } else {
             $fetchall = 1;
         }
         $mda = $this->rc->config->get('fetchmail_mda');
         if ($newentry or $id == '') {
             $sql = "SELECT * FROM fetchmail WHERE mailbox='" . $mailbox . "'";
             $result = $this->rc->db->query($sql);
             $limit = $this->rc->config->get('fetchmail_limit');
             $num_rows = $this->rc->db->num_rows($result);
             if ($num_rows < $limit) {
                 $sql = "INSERT INTO fetchmail (mailbox, domain, active, src_server, src_user, src_password, src_folder, poll_time, fetchall, keep, protocol, usessl, src_auth, mda) VALUES ('$mailbox', '$mailbox_domain', '$enabled', '$server', '$user', '$pass', '$folder', '$pollinterval', '$fetchall', '$keep', '$protocol', '$usessl', 'password', '$mda' )";
                 $insert = $this->rc->db->query($sql);
                 $this->rc->output->command('display_message', $this->gettext('successfullysaved'), 'confirmation');
             } else {
                 $this->rc->output->command('display_message', 'Error: ' . $this->gettext('fetchmaillimitreached'), 'error');
             }
         } else {
             $sql = "UPDATE fetchmail SET mailbox = '$mailbox', domain = '$mailbox_domain', active = '$enabled', keep = '$keep', protocol = '$protocol', src_server = '$server', src_user = '$user', src_password = '$pass', src_folder = '$folder', poll_time = '$pollinterval', fetchall = '$fetchall', usessl = '$usessl', src_auth = 'password', mda = '$mda' WHERE id = '$id'";
             $update = $this->rc->db->query($sql);
             $this->rc->output->command('display_message', $this->gettext('successfullysaved'), 'confirmation');
         }
         //$this->init_html ();
         rcmail::get_instance()->output->command('plugin.fetchmailcallback', array('message' => 'done.'));
     }
     public function gen_form()
     {
         $id = rcube_utils::get_input_value('_id', rcube_utils::INPUT_GET);
         $mailbox = $this->rc->user->data ['username'];
        
         // reasonable(?) defaults
         $pollinterval = '10';
         $usessl = 1;
         $fetchall = 0;
         $keep = 1;
         $enabled = 1;
         $protocol = 'IMAP';
        
         // auslesen start
         if ($id != '' || $id != 0) {
             $sql = "SELECT * FROM fetchmail WHERE mailbox='" . $mailbox . "' AND id='" . $id . "'";
             $result = $this->rc->db->query($sql);
             while ($row = $this->rc->db->fetch_assoc($result)) {
                 $enabled = $row ['active'];
                 $keep = $row ['keep'];
                 $mailget_id = $row ['id'];
                 $protocol = $row ['protocol'];
                 $server = $row ['src_server'];
                 $user = $row ['src_user'];
                 $pass = base64_decode($row ['src_password']);
                 $folder = $row ['src_folder'];
                 $pollinterval = $row ['poll_time'];
                 $fetchall = $row ['fetchall'];
                 $usessl = $row ['usessl'];
             }
         }
         $newentry = 0;
         $out .= '<fieldset><legend>' . $this->gettext('fetchmail_to') . ' ' . $mailbox . '</legend>' . "\n";
         $out .= '<br />' . "\n";
         $out .= '<table' . $attrib_str . ">\n\n";
         $hidden_id = new html_hiddenfield(array(
                'name' => '_id',
                'value' => $mailget_id
        ));
         $out .= $hidden_id->show();
        
         $field_id = 'fetchmailprotocol';
         $input_fetchmailprotocol = new html_select(array(
                'name' => '_fetchmailprotocol',
                'id' => $field_id,
                'onchange' => 'fetchmail_toggle_folder();'
        ));
         $input_fetchmailprotocol->add(array(
                'IMAP',
                'POP3'
        ), array(
                'IMAP',
                'POP3'
        ));
         $out .= sprintf("<tr><td class=\"title\"><label for=\"%s\">%s</label>:</td><td>%s</td></tr>\n", $field_id, rcube_utils::rep_specialchars_output($this->gettext('fetchmailprotocol')), $input_fetchmailprotocol->show($protocol));
        
         $field_id = 'fetchmailserver';
         $input_fetchmailserver = new html_inputfield(array(
                'name' => '_fetchmailserver',
                'id' => $field_id,
                'maxlength' => 320,
                'size' => 40
        ));
         $out .= sprintf("<tr><td class=\"title\"><label for=\"%s\">%s</label>:</td><td>%s</td></tr>\n", $field_id, rcube_utils::rep_specialchars_output($this->gettext('fetchmailserver')), $input_fetchmailserver->show($server));
        
         $field_id = 'fetchmailuser';
         $input_fetchmailuser = new html_inputfield(array(
                'name' => '_fetchmailuser',
                'id' => $field_id,
                'maxlength' => 320,
                'size' => 40
        ));
         $out .= sprintf("<tr><td class=\"title\"><label for=\"%s\">%s</label>:</td><td>%s</td></tr>\n", $field_id, rcube_utils::rep_specialchars_output($this->gettext('username')), $input_fetchmailuser->show($user));
        
         $field_id = 'fetchmailpass';
         $input_fetchmailpass = new html_passwordfield(array(
                'name' => '_fetchmailpass',
                'id' => $field_id,
                'maxlength' => 320,
                'size' => 40,
                'autocomplete' => 'off'
        ));
         $out .= sprintf("<tr><td class=\"title\"><label for=\"%s\">%s</label>:</td><td>%s</td></tr>\n", $field_id, rcube_utils::rep_specialchars_output($this->gettext('password')), $input_fetchmailpass->show($pass));
        
         if ($this->show_folder) {
             $field_id = 'fetchmailfolder';
             $input_fetchmailfolder = new html_inputfield(array(
                    'name' => '_fetchmailfolder',
                    'id' => $field_id,
                    'maxlength' => 320,
                    'size' => 40
            ));
             $out .= sprintf("<tr id=\"fetchmail_folder_display\"" . (($protocol != "IMAP") ? ("style=\"display: none;\"") : ("")) . "><td class=\"title\"><label for=\"%s\">%s</label>:</td><td>%s</td></tr>\n", $field_id, rcube_utils::rep_specialchars_output($this->gettext('fetchmailfolder')), $input_fetchmailfolder->show($folder));
         }
        
         $field_id = 'fetchmailpollinterval';
         $input_fetchmailpollinterval = new html_select(array(
                'name' => '_fetchmailpollinterval',
                'id' => $field_id
        ));
         $input_fetchmailpollinterval->add(array(
                '5',
                '10',
                '15',
                '20',
                '25',
                '30',
                '60'
        ), array(
                '5',
                '10',
                '15',
                '20',
                '25',
                '30',
                '60'
        ));
         $out .= sprintf("<tr><td class=\"title\"><label for=\"%s\">%s</label>:</td><td>%s</td></tr>\n", $field_id, rcube_utils::rep_specialchars_output($this->gettext('fetchmailpollinterval')), $input_fetchmailpollinterval->show("$pollinterval"));
        
         $field_id = 'fetchmailkeep';
         $input_fetchmailkeep = new html_checkbox(array(
                'name' => '_fetchmailkeep',
                'id' => $field_id,
                'value' => '1'
        ));
         $out .= sprintf("<tr><td class=\"title\"><label for=\"%s\">%s</label>:</td><td>%s</td></tr>\n", $field_id, rcube_utils::rep_specialchars_output($this->gettext('fetchmailkeep')), $input_fetchmailkeep->show($keep));
        
         $field_id = 'fetchmailfetchall';
         $input_fetchmailfetchall = new html_checkbox(array(
                'name' => '_fetchmailfetchall',
                'id' => $field_id,
                'value' => '1'
        ));
         $out .= sprintf("<tr><td class=\"title\"><label for=\"%s\">%s</label>:</td><td>%s</td></tr>\n", $field_id, rcube_utils::rep_specialchars_output($this->gettext('fetchmailfetchall')), $input_fetchmailfetchall->show($fetchall));
        
         $field_id = 'fetchmailusessl';
         $input_fetchmailusessl = new html_checkbox(array(
                'name' => '_fetchmailusessl',
                'id' => $field_id,
                'value' => '1'
        ));
         $out .= sprintf("<tr><td class=\"title\"><label for=\"%s\">%s</label>:</td><td>%s</td></tr>\n", $field_id, rcube_utils::rep_specialchars_output($this->gettext('fetchmailusessl')), $input_fetchmailusessl->show($usessl));
        
         $field_id = 'fetchmailenabled';
         $input_fetchmailenabled = new html_checkbox(array(
                'name' => '_fetchmailenabled',
                'id' => $field_id,
                'value' => '1'
        ));
         $out .= sprintf("<tr><td class=\"title\"><label for=\"%s\">%s</label>:</td><td>%s</td></tr>\n", $field_id, rcube_utils::rep_specialchars_output($this->gettext('fetchmailenabled')), $input_fetchmailenabled->show($enabled));
        
         if ($id != '' || $id != 0) {
             $field_id = 'fetchmailnewentry';
             $input_fetchmailnewentry = new html_checkbox(array(
                    'name' => '_fetchmailnewentry',
                    'id' => $field_id,
                    'value' => '1'
            ));
             $out .= sprintf("<tr><td class=\"title\"><label for=\"%s\">%s</label>:</td><td>%s</td></tr>\n", $field_id, rcube_utils::rep_specialchars_output($this->gettext('fetchmailnewentry')), $input_fetchmailnewentry->show($newentry));
         }
        
         $out .= "\n</table>";
         $out .= '<br />' . "\n";
         $out .= "</fieldset>\n";
         $this->rc->output->add_gui_object('fetchmailform', 'fetchmail-form');
         return $out;
     }
     public function gen_table($attrib)
     {
         $mailbox = $this->rc->user->data ['username'];
         $sql = "SELECT * FROM fetchmail WHERE mailbox='$mailbox'";
         $result = $this->rc->db->query($sql);
         $num_rows = $this->rc->db->num_rows($result);
         $limit = $this->rc->config->get('fetchmail_limit');
         $out = '<fieldset><legend>' . $this->gettext('fetchmail_entries') . " (<span id=\"fetchmail_items_number\">$num_rows</span>/$limit)" . '</legend>' . "\n";
         $out .= '<br />' . "\n";
         $fetch_table = new html_table(array(
                'id' => 'fetch-table',
                'class' => 'records-table listing',
                'cellspacing' => '0',
                'cols' => ($this->show_folder) ? 5 : 4
        ));
         $fetch_table->add_header(array(
                'width' => ($this->show_folder) ? ('100px') : ('184px')
        ), $this->gettext('fetchmailserver'));
         $fetch_table->add_header(array(
                'width' => ($this->show_folder) ? ('100px') : ('184px')
        ), $this->gettext('username'));
         if ($this->show_folder) {
             $fetch_table->add_header(array(
                    'width' => '100px'
            ), $this->gettext('fetchmailfolder'));
         }
         $fetch_table->add_header(array(
                'width' => '26px'
        ), '');
         $fetch_table->add_header(array(
                'width' => '26px'
        ), '');
        
         while ($row = $this->rc->db->fetch_assoc($result)) {
             $class = ($class == 'odd' ? 'even' : 'odd');
             if ($row ['id'] == rcube_utils::get_input_value('_id', rcube_utils::INPUT_GET)) {
                 $class = 'selected';
             }
             $fetch_table->set_row_attribs(array(
                    'class' => $class,
                    'id' => 'fetch_' . $row ['id']
            ));
             $this->_fetch_row($fetch_table, $row ['src_server'], $row ['src_user'], $row ['src_folder'], $row ['active'], $row ['id'], $attrib);
         }
         if ($num_rows == 0) {
             $fetch_table->add(array(
                    'colspan' => '4'
            ), rcube_utils::rep_specialchars_output($this->gettext('nofetch')));
             $fetch_table->set_row_attribs(array(
                    'class' => 'odd'
            ));
             $fetch_table->add_row();
         }
         $out .= "<div id=\"fetch-cont\">" . $fetch_table->show() . "</div>\n";
         $out .= '<br />' . "\n";
         $out .= "</fieldset>\n";
         return $out;
     }
     private function _fetch_row($fetch_table, $col_remoteserver, $col_remoteuser, $col_folder, $active, $id, $attrib)
     {
         $fetch_table->add(array(
                'onclick' => 'fetchmail_edit(' . $id . ');'
        ), $col_remoteserver);
         $fetch_table->add(array(
                'onclick' => 'fetchmail_edit(' . $id . ');'
        ), $col_remoteuser);
         if ($this->show_folder) {
             $fetch_table->add(array(
                    'onclick' => 'fetchmail_edit(' . $id . ');'
            ), $col_folder);
         }
         $disable_button = html::img(array(
                'src' => $attrib ['enableicon'],
                'alt' => $this->gettext('enabled'),
                'border' => 0,
                'id' => 'img_' . $id
        ));
         $enable_button = html::img(array(
                'src' => $attrib ['disableicon'],
                'alt' => $this->gettext('disabled'),
                'border' => 0,
                'id' => 'img_' . $id
        ));
         $del_button = html::img(array(
                'src' => $attrib ['deleteicon'],
                'alt' => $this->gettext('delete'),
                'border' => 0
        ));
         if ($active == 1) {
             $status_button = $disable_button;
         } else {
             $status_button = $enable_button;
         }
         $fetch_table->add(array(
                'id' => 'td_' . $id,
                'onclick' => 'row_edit(' . $id . ',' . $active . ');'
        ), $status_button);
         $fetch_table->add(array(
                'id' => 'td_' . $id,
                'onclick' => 'row_del(' . $id . ');'
        ), $del_button);
         return $fetch_table;
     }
     public function settings_actions($args)
     {
         $args ['actions'] [] = array(
                'action' => 'plugin.fetchmail',
                'class'  => 'fetchmail',
                'label' => 'fetchmail.fetchmail',
                'title' => 'fetchmail.fetchmail'
        );
         return $args;
     }
 }
