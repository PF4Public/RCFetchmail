<?php

/******************************************************************************
 * Fetchmail Roundcube Plugin (RC0.4 and above)
 * This software distributed under the terms of the GNU General Public License
 * as published by the Free Software Foundation
 * Further details on the GPL license can be found at 
 * http://www.gnu.org/licenses/gpl.html
 * By contributing authors release their contributed work under this license
 * For more information see README.md file 
 *****************************************************************************/

class fetchmail extends rcube_plugin {
	public $task = 'settings';
	function init() {
		$this->load_config ();
		$this->add_texts ( 'localization/', true );
		$rcmail = rcmail::get_instance ();
		$this->register_action ( 'plugin.fetchmail', array (
				$this,
				'init_html' 
		) );
		$this->register_action ( 'plugin.fetchmail.save', array (
				$this,
				'save' 
		) );
		$this->register_action ( 'plugin.fetchmail.del', array (
				$this,
				'del' 
		) );
		$this->register_action ( 'plugin.fetchmail.enable', array (
				$this,
				'enable' 
		) );
		$this->register_action ( 'plugin.fetchmail.disable', array (
				$this,
				'disable' 
		) );
		$this->api->output->add_handler ( 'fetchmail_form', array (
				$this,
				'gen_form' 
		) );
		$this->api->output->add_handler ( 'fetchmail_table', array (
				$this,
				'gen_table' 
		) );
		$this->include_script ( 'fetchmail.js' );
	}
	function load_config() {
		$rcmail = rcmail::get_instance ();
		$config = "plugins/fetchmail/config/config.inc.php";
		if (file_exists ( $config ))
			include $config;
		else if (file_exists ( $config . ".dist" ))
			include $config . ".dist";
		if (is_array ( $rcmail_config )) {
			$arr = array_merge ( $rcmail->config->all (), $rcmail_config );
			$rcmail->config->merge ( $arr );
		}
	}
	
	function init_html() {
		$rcmail = rcmail::get_instance ();
		$rcmail->output->set_pagetitle ( $this->gettext ( 'fetchmail' ) );
		$rcmail->output->send ( 'fetchmail.fetchmail' );
	}
	
	function disable() {
		$rcmail = rcmail::get_instance ();
		$id = get_input_value ( '_id', RCUBE_INPUT_GET );
		if ($id != 0 || $id != '') {
			$sql = "UPDATE fetchmail SET active = '0' WHERE id = '$id'";
			$update = $rcmail->db->query ( $sql );
		}
	}
	
	function enable() {
		$rcmail = rcmail::get_instance ();
		$id = get_input_value ( '_id', RCUBE_INPUT_GET );
		if ($id != 0 || $id != '') {
			$sql = "UPDATE fetchmail SET active = '1' WHERE id = '$id'";
			$update = $rcmail->db->query ( $sql );
		}
	}
	
	function del() {
		$rcmail = rcmail::get_instance ();
		$id = get_input_value ( '_id', RCUBE_INPUT_GET );
		if ($id != 0 || $id != '') {
			$sql = "DELETE FROM fetchmail WHERE id = '$id'";
			$delete = $rcmail->db->query ( $sql );
		}
	}
	
	function save() {
		$rcmail = rcmail::get_instance ();
		$id = get_input_value ( '_id', RCUBE_INPUT_POST );
		$mailbox = $rcmail->user->data ['username'];
		$protocol = get_input_value ( '_fetchmailprotocol', RCUBE_INPUT_POST );
		$server = get_input_value ( '_fetchmailserver', RCUBE_INPUT_POST );
		$user = get_input_value ( '_fetchmailuser', RCUBE_INPUT_POST );
		$pass = base64_encode ( get_input_value ( '_fetchmailpass', RCUBE_INPUT_POST ) );
		$folder = get_input_value ( '_fetchmailfolder', RCUBE_INPUT_POST );
		$pollinterval = get_input_value ( '_fetchmailpollinterval', RCUBE_INPUT_POST );
		$keep = get_input_value ( '_fetchmailkeep', RCUBE_INPUT_POST );
		$usessl = get_input_value ( '_fetchmailusessl', RCUBE_INPUT_POST );
		$fetchall = get_input_value ( '_fetchmailfetchall', RCUBE_INPUT_POST );
		$enabled = get_input_value ( '_fetchmailenabled', RCUBE_INPUT_POST );
		$newentry = get_input_value ( '_fetchmailnewentry', RCUBE_INPUT_POST );
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
		if ($newentry or $id == '') {
			$sql = "SELECT * FROM fetchmail WHERE mailbox='" . $mailbox . "'";
			$result = $rcmail->db->query ( $sql );
			$limit = $rcmail->config->get ( 'fetchmail_limit' );
			$num_rows = $rcmail->db->num_rows ( $result );
			if ($num_rows < $limit) {
				$sql = "INSERT INTO fetchmail (mailbox, active, src_server, src_user, src_password, src_folder, poll_time, fetchall, keep, protocol, usessl, src_auth) VALUES ('$mailbox', '$enabled', '$server', '$user', '$pass', '$folder', '$pollinterval', '$fetchall', '$keep', '$protocol', '$usessl', 'password' )";
				$insert = $rcmail->db->query ( $sql );
				$rcmail->output->command ( 'display_message', $this->gettext ( 'successfullysaved' ), 'confirmation' );
			} else {
				$rcmail->output->command ( 'display_message', 'Error: ' . $this->gettext ( 'fetchmaillimitreached' ), 'error' );
			}
		} else {
			$sql = "UPDATE fetchmail SET mailbox = '$mailbox', active = '$enabled', keep = '$keep', protocol = '$protocol', src_server = '$server', src_user = '$user', src_password = '$pass', src_folder = '$folder', poll_time = '$pollinterval', fetchall = '$fetchall', usessl = '$usessl', src_auth = 'password' WHERE id = '$id'";
			$update = $rcmail->db->query ( $sql );
			$rcmail->output->command ( 'display_message', $this->gettext ( 'successfullysaved' ), 'confirmation' );
		}
		$this->init_html ();
	}
	
	function gen_form() {
		$rcmail = rcmail::get_instance ();
		$id = get_input_value ( '_id', RCUBE_INPUT_GET );
		$mailbox = $rcmail->user->data ['username'];
		
		// reasonable(?) defaults
		$pollinterval = '10';
		$usessl = 1;
		$fetchall = 0;
		$keep = 1;
		$enabled = 1;
		$protocol = 'imap';
		
		// auslesen start
		if ($id != '' || $id != 0) {
			$sql = "SELECT * FROM fetchmail WHERE mailbox='" . $mailbox . "' AND id='" . $id . "'";
			$result = $rcmail->db->query ( $sql );
			while ( $row = $rcmail->db->fetch_assoc ( $result ) ) {
				$enabled = $row ['active'];
				$keep = $row ['keep'];
				$mailget_id = $row ['id'];
				$protocol = $row ['protocol'];
				$server = $row ['src_server'];
				$user = $row ['src_user'];
				$pass = base64_decode ( $row ['src_password'] );
				$folder = $row ['src_folder'];
				$pollinterval = $row ['poll_time'];
				$fetchall = $row ['fetchall'];
				$usessl = $row ['usessl'];
			}
		}
		$newentry = 0;
		$out .= '<fieldset><legend>' . $this->gettext ( 'fetchmail_to' ) . ' ' . $mailbox . '</legend>' . "\n";
		$out .= '<br />' . "\n";
		$out .= '<table' . $attrib_str . ">\n\n";
		$hidden_id = new html_hiddenfield ( array (
				'name' => '_id',
				'value' => $mailget_id 
		) );
		$out .= $hidden_id->show ();
		
		$field_id = 'fetchmailprotocol';
		$input_fetchmailprotocol = new html_select ( array (
				'name' => '_fetchmailprotocol',
				'id' => $field_id,
				'onchange' => 'fetchmail_toggle_folder();' 
		) );
		$input_fetchmailprotocol->add ( array (
				'IMAP',
				'POP3' 
		), array (
				'imap',
				'pop3' 
		) );
		$out .= sprintf ( "<tr><td class=\"title\"><label for=\"%s\">%s</label>:</td><td>%s</td></tr>\n", $field_id, rep_specialchars_output ( $this->gettext ( 'fetchmailprotocol' ) ), $input_fetchmailprotocol->show ( $protocol ) );
		
		$field_id = 'fetchmailserver';
		$input_fetchmailserver = new html_inputfield ( array (
				'name' => '_fetchmailserver',
				'id' => $field_id,
				'maxlength' => 320,
				'size' => 40 
		) );
		$out .= sprintf ( "<tr><td class=\"title\"><label for=\"%s\">%s</label>:</td><td>%s</td></tr>\n", $field_id, rep_specialchars_output ( $this->gettext ( 'fetchmailserver' ) ), $input_fetchmailserver->show ( $server ) );
		
		$field_id = 'fetchmailuser';
		$input_fetchmailuser = new html_inputfield ( array (
				'name' => '_fetchmailuser',
				'id' => $field_id,
				'maxlength' => 320,
				'size' => 40 
		) );
		$out .= sprintf ( "<tr><td class=\"title\"><label for=\"%s\">%s</label>:</td><td>%s</td></tr>\n", $field_id, rep_specialchars_output ( $this->gettext ( 'username' ) ), $input_fetchmailuser->show ( $user ) );
		
		$field_id = 'fetchmailpass';
		$input_fetchmailpass = new html_passwordfield ( array (
				'name' => '_fetchmailpass',
				'id' => $field_id,
				'maxlength' => 320,
				'size' => 40,
				'autocomplete' => 'off' 
		) );
		$out .= sprintf ( "<tr><td class=\"title\"><label for=\"%s\">%s</label>:</td><td>%s</td></tr>\n", $field_id, rep_specialchars_output ( $this->gettext ( 'password' ) ), $input_fetchmailpass->show ( $pass ) );
		
		if ($rcmail->config->get('fetchmail_folder'))
		{
		$field_id = 'fetchmailfolder';
		$input_fetchmailfolder = new html_inputfield ( array (
				'name' => '_fetchmailfolder',
				'id' => $field_id,
				'maxlength' => 320,
				'size' => 40
		) );
		$out .= sprintf ( "<tr id=\"fetchmail_folder_display\"".(($protocol!="imap")?("style=\"display: none;\""):(""))."><td class=\"title\"><label for=\"%s\">%s</label>:</td><td>%s</td></tr>\n", $field_id, rep_specialchars_output ( $this->gettext ( 'fetchmailfolder' ) ), $input_fetchmailfolder->show ( $folder ) );
		}
		
		$field_id = 'fetchmailpollinterval';
		$input_fetchmailpollinterval = new html_select ( array (
				'name' => '_fetchmailpollinterval',
				'id' => $field_id 
		) );
		$input_fetchmailpollinterval->add ( array (
				'5',
				'10',
				'15',
				'20',
				'25',
				'30',
				'60' 
		), array (
				'5',
				'10',
				'15',
				'20',
				'25',
				'30',
				'60' 
		) );
		$out .= sprintf ( "<tr><td class=\"title\"><label for=\"%s\">%s</label>:</td><td>%s</td></tr>\n", $field_id, rep_specialchars_output ( $this->gettext ( 'fetchmailpollinterval' ) ), $input_fetchmailpollinterval->show ( "$pollinterval" ) );
		
		$field_id = 'fetchmailkeep';
		$input_fetchmailkeep = new html_checkbox ( array (
				'name' => '_fetchmailkeep',
				'id' => $field_id,
				'value' => '1' 
		) );
		$out .= sprintf ( "<tr><td class=\"title\"><label for=\"%s\">%s</label>:</td><td>%s</td></tr>\n", $field_id, rep_specialchars_output ( $this->gettext ( 'fetchmailkeep' ) ), $input_fetchmailkeep->show ( $keep ) );
		
		$field_id = 'fetchmailfetchall';
		$input_fetchmailfetchall = new html_checkbox ( array (
				'name' => '_fetchmailfetchall',
				'id' => $field_id,
				'value' => '1' 
		) );
		$out .= sprintf ( "<tr><td class=\"title\"><label for=\"%s\">%s</label>:</td><td>%s</td></tr>\n", $field_id, rep_specialchars_output ( $this->gettext ( 'fetchmailfetchall' ) ), $input_fetchmailfetchall->show ( $fetchall ) );
		
		$field_id = 'fetchmailusessl';
		$input_fetchmailusessl = new html_checkbox ( array (
				'name' => '_fetchmailusessl',
				'id' => $field_id,
				'value' => '1' 
		) );
		$out .= sprintf ( "<tr><td class=\"title\"><label for=\"%s\">%s</label>:</td><td>%s</td></tr>\n", $field_id, rep_specialchars_output ( $this->gettext ( 'fetchmailusessl' ) ), $input_fetchmailusessl->show ( $usessl ) );
		
		$field_id = 'fetchmailenabled';
		$input_fetchmailenabled = new html_checkbox ( array (
				'name' => '_fetchmailenabled',
				'id' => $field_id,
				'value' => '1' 
		) );
		$out .= sprintf ( "<tr><td class=\"title\"><label for=\"%s\">%s</label>:</td><td>%s</td></tr>\n", $field_id, rep_specialchars_output ( $this->gettext ( 'fetchmailenabled' ) ), $input_fetchmailenabled->show ( $enabled ) );
		
		if ($id != '' || $id != 0) {
			$field_id = 'fetchmailnewentry';
			$input_fetchmailnewentry = new html_checkbox ( array (
					'name' => '_fetchmailnewentry',
					'id' => $field_id,
					'value' => '1' 
			) );
			$out .= sprintf ( "<tr><td class=\"title\"><label for=\"%s\">%s</label>:</td><td>%s</td></tr>\n", $field_id, rep_specialchars_output ( $this->gettext ( 'fetchmailnewentry' ) ), $input_fetchmailnewentry->show ( $newentry ) );
		}
		
		$out .= "\n</table>";
		$out .= '<br />' . "\n";
		$out .= "</fieldset>\n";
		$rcmail->output->add_gui_object ( 'fetchmailform', 'fetchmail-form' );
		return $out;
	}
	
	function gen_table($attrib) {
		$rcmail = rcmail::get_instance ();
		$mailbox = $rcmail->user->data ['username'];
		$sql = "SELECT * FROM fetchmail WHERE mailbox='$mailbox'";
		$result = $rcmail->db->query ( $sql );
		$num_rows = $rcmail->db->num_rows ( $result );
		$limit = $rcmail->config->get ( 'fetchmail_limit' );
		$out = '<fieldset><legend>' . $this->gettext ( 'fetchmail_entries' ) ." (<span id=\"fetchmail_items_number\">$num_rows</span>/$limit)". '</legend>' . "\n";
		$out .= '<br />' . "\n";
		$fetch_table = new html_table ( array (
				'id' => 'fetch-table',
				'class' => 'records-table',
				'cellspacing' => '0',
				'cols' => 4 
		) );
		$fetch_table->add_header ( array (
				'width' => '184px' 
		), $this->gettext ( 'fetchmailserver' ) );
		$fetch_table->add_header ( array (
				'width' => '184px' 
		), $this->gettext ( 'username' ) );
		$fetch_table->add_header ( array (
				'width' => '26px' 
		), '' );
		$fetch_table->add_header ( array (
				'width' => '26px' 
		), '' );
		
		while ( $row = $rcmail->db->fetch_assoc ( $result ) ) {
			$class = ($class == 'odd' ? 'even' : 'odd');
			if ($row ['id'] == get_input_value ( '_id', RCUBE_INPUT_GET )) {
				$class = 'selected';
			}
			$fetch_table->set_row_attribs ( array (
					'class' => $class,
					'id' => 'fetch_' . $row ['id'] 
			) );
			$this->_fetch_row ( $fetch_table, $row ['src_server'], $row ['src_user'], $row ['active'], $row ['id'], $attrib );
		}
		if ($num_rows == 0) {
			$fetch_table->add ( array (
					'colspan' => '4' 
			), rep_specialchars_output ( $this->gettext ( 'nofetch' ) ) );
			$fetch_table->set_row_attribs ( array (
					'class' => 'odd' 
			) );
			$fetch_table->add_row ();
		}
		$out .= "<div id=\"fetch-cont\">" . $fetch_table->show () . "</div>\n";
		$out .= '<br />' . "\n";
		$out .= "</fieldset>\n";
		return $out;
	}
	
	private function _fetch_row($fetch_table, $col_remoteserver, $col_remoteuser, $active, $id, $attrib) {
		$fetch_table->add ( array (
				'onclick' => 'fetchmail_edit(' . $id . ');' 
		), $col_remoteserver );
		$fetch_table->add ( array (
				'onclick' => 'fetchmail_edit(' . $id . ');' 
		), $col_remoteuser );
		$disable_button = html::img ( array (
				'src' => $attrib ['enableicon'],
				'alt' => $this->gettext ( 'enabled' ),
				'border' => 0,
				'id' => 'img_' . $id 
		) );
		$enable_button = html::img ( array (
				'src' => $attrib ['disableicon'],
				'alt' => $this->gettext ( 'disabled' ),
				'border' => 0,
				'id' => 'img_' . $id 
		) );
		$del_button = html::img ( array (
				'src' => $attrib ['deleteicon'],
				'alt' => $this->gettext ( 'delete' ),
				'border' => 0 
		) );
		if ($active == 1) {
			$status_button = $disable_button;
		} else {
			$status_button = $enable_button;
		}
		$fetch_table->add ( array (
				'id' => 'td_' . $id,
				'onclick' => 'row_edit(' . $id . ',' . $active . ');' 
		), $status_button );
		$fetch_table->add ( array (
				'id' => 'td_' . $id,
				'onclick' => 'row_del(' . $id . ');' 
		), $del_button );
		return $fetch_table;
	}
}

?>
