<?php
    global $feedbackMsg;
	if (!empty($_POST)) {
		if (!empty($_POST['heat_trackr_save_settings_btn'])) {
			heat_trackr_save_settings(false);
		} else if (!empty($_POST['heat_trackr_delete_clicks_btn'])) {
			if (heat_trackr_save_settings(true)) {
				if (heat_trackr_delete_clicks()) {
					heat_trackr_buildFeedbackMsg();
				}
			}
		} else if (!empty($_POST['heat_trackr_delete_scrolls_btn'])) {
			if (heat_trackr_save_settings(true)) {
				if (heat_trackr_delete_clicks()) {
					heat_trackr_buildFeedbackMsg();
				}
			}
		}
	}

	function heat_trackr_save_settings($deleting) {
        global $feedbackMsg;
		$feedbackMsg = '';
		
		if (isset($_POST['heat_trackr_collect'])) update_option('heat_trackr_collect', "1"); else update_option('heat_trackr_collect', "");
		if (isset($_POST['heat_trackr_collect_scrolls'])) update_option('heat_trackr_collect_scrolls', "1"); else update_option('heat_trackr_collect_scrolls', "");
		if (isset($_POST['heat_trackr_display'])) update_option('heat_trackr_display', "1"); else update_option('heat_trackr_display', "");
		if (isset($_POST['heat_trackr_click_summary'])) update_option('heat_trackr_click_summary', "1"); else update_option('heat_trackr_click_summary', "");

		update_option('heat_trackr_display_source', $_POST['heat_trackr_display_source']);

		if (isset($_POST['heat_trackr_all_clicks'])) update_option('heat_trackr_all_clicks', "1"); else update_option('heat_trackr_all_clicks', "");

        if ($_POST['heat_trackr_period_start'] == '' || ($startPeriodMsg = heat_trackr_checkDateTime($_POST['heat_trackr_period_start'], 'start')) == '')
			update_option('heat_trackr_period_start', $_POST['heat_trackr_period_start']);
		if ($_POST['heat_trackr_period_end'] == '' || ($endPeriodMsg = heat_trackr_checkDateTime($_POST['heat_trackr_period_end'], 'end')) == '')
			update_option('heat_trackr_period_end', $_POST['heat_trackr_period_end']);

		if (isset($_POST['heat_trackr_data_delete'])) update_option('heat_trackr_data_delete', "1"); else update_option('heat_trackr_data_delete', "");
		if (isset($_POST['heat_trackr_options_delete'])) update_option('heat_trackr_options_delete', "1"); else update_option('heat_trackr_options_delete', "");
		if (isset($_POST['heat_trackr_all_URL'])) update_option('heat_trackr_all_URL', "1"); else update_option('heat_trackr_all_URL', "");
		if (isset($_POST['heat_trackr_URL'])) update_option('heat_trackr_URL', $_POST['heat_trackr_URL']); else update_option('heat_trackr_URL', "");

		if ($startPeriodMsg != '' || $endPeriodMsg != '') {
			$feedbackMsg .= $startPeriodMsg;
			if ($startPeriodMsg != '') $feedbackMsg .= ' ';
			$feedbackMsg .= $endPeriodMsg;
			return false;
		} else {
			$feedbackMsg = 'All settings saved';
			if ($deleting) $feedbackMsg .= ' - ';
			return true;
		}
	}
	
    function heat_trackr_buildFeedbackMsg() {
        global $feedbackMsg;

		if (!empty($_POST['heat_trackr_delete_clicks_btn']))
            $feedbackMsg .= 'Clicks were deleted for ';
		else if (!empty($_POST['heat_trackr_delete_scrolls_btn']))
            $feedbackMsg .= 'Scrolls were deleted for ';

        if (get_option('heat_trackr_all_clicks') == "1")
            $feedbackMsg .= 'all times';
        else {
            if (get_option('heat_trackr_period_start') != '' && get_option('heat_trackr_period_end') != '') {
                $feedbackMsg .= 'the period > '.get_option('heat_trackr_period_start').' and';
                $feedbackMsg .= ' < '.get_option('heat_trackr_period_end');
            }
            else if (get_option('heat_trackr_period_start') != '' && get_option('heat_trackr_period_end') == '')
                $feedbackMsg .= 'the period > '.get_option('heat_trackr_period_start');
            else if (get_option('heat_trackr_period_start') == '' && get_option('heat_trackr_period_end') != '')
                $feedbackMsg .= 'the period < '.get_option('heat_trackr_period_end');
        }

		if (get_option('heat_trackr_all_URL') == "1")
            $feedbackMsg .= ' for all URLs';
		else if (get_option('heat_trackr_URL') != "")
            $feedbackMsg .= ' for the URL: '.get_option('heat_trackr_URL');
    }

    function heat_trackr_delete_clicks() {
        global $feedbackMsg;
		global $wpdb;
		$url = get_option('heat_trackr_URL');

		if (get_option('heat_trackr_all_clicks') != "1" && get_option('heat_trackr_period_start') == '' && get_option('heat_trackr_period_end') == '') {
			$feedbackMsg .= 'No time period selected for deletion, nothing was deleted';
            return false;
		}
		
		if (get_option('heat_trackr_all_URL') != "1" && $url == "") {
			$feedbackMsg .= 'No URL selected for deletion, nothing was deleted';
            return false;
		}
		
		if (!empty($_POST['heat_trackr_delete_clicks_btn'])) {
			$table_name = $wpdb->prefix . "heat_trackr_clicks";
			$table_name2 = $wpdb->prefix . "heat_trackr_data";

			if (get_option('heat_trackr_all_URL') == "1" && get_option('heat_trackr_all_clicks') == "1") {
				$wpdb->query("TRUNCATE TABLE $table_name");
				$wpdb->query("TRUNCATE TABLE $table_name2");
			} else {
				// First delete heat_trackr_data records with matching dataId
				$queryStr = heat_trackr_buildDeleteQuery($table_name, $url, 1);
				$results = $wpdb->get_results($queryStr);
				foreach ($results as $result) {
					$dataId = $result->dataId;
					$queryStr = "DELETE FROM $table_name2 WHERE id = '$dataId'";
					$wpdb->query($queryStr);
				}
				
				// Now delete heat_trackr_clicks
				$queryStr = heat_trackr_buildDeleteQuery($table_name, $url, 0);
				$wpdb->query($queryStr);
			}
		}

		if (!empty($_POST['heat_trackr_delete_scrolls_btn'])) {
			$table_name = $wpdb->prefix . "heat_trackr_scrolls";
			if (get_option('heat_trackr_all_URL') == "1" && get_option('heat_trackr_all_clicks') == "1") {
				$wpdb->query("TRUNCATE TABLE $table_name");
			} else {
				$queryStr = heat_trackr_buildDeleteQuery($table_name, $url, 0);
				$wpdb->query($queryStr);
			}
		}

        return true;
    }
	
	function heat_trackr_buildDeleteQuery($table_name, $url, $dataId) {
		if (get_option('heat_trackr_all_URL') == "1")
			$urlString = "";
		else
			$urlString = " WHERE url = '$url'";

		if ($dataId == 1)
			$queryStr = "SELECT DISTINCT $table_name.dataId FROM $table_name" . $urlString;
		else
			$queryStr = "DELETE FROM $table_name" . $urlString;

		if (get_option('heat_trackr_all_clicks') != "1") {
			$start = get_option('heat_trackr_period_start');
			if (($timestamp = strtotime($start)) === false) {
				$start = ''; // $start is bogus
			} else {
				$start = date('Y-m-d H:i', $timestamp);
			}

			$end = get_option('heat_trackr_period_end');
			if (($timestamp = strtotime($end)) === false) {
				$end = ''; // $end is bogus
			} else {
				$end = date('Y-m-d H:i', $timestamp);
			}

			if ($start != '' && $end == '') {
				if ($urlString == "")
					$queryStr .= " WHERE clickDate >= '$start'";
				else
					$queryStr .= " AND clickDate >= '$start'";
			} else if ($start == '' && $end != '') {
				if ($urlString == "")
					$queryStr .= " WHERE clickDate <= '$end'";
				else
					$queryStr .= " AND clickDate <= '$end'";
			} else {
				if ($urlString == "")
					$queryStr .= " WHERE clickDate >= '$start' AND clickDate <= '$end'";
				else
					$queryStr .= " AND clickDate >= '$start' AND clickDate <= '$end'";
			}
		}
		
		return $queryStr;
	}

	$plugin = plugin_dir_url( __FILE__ );
	echo '<script src="'.$plugin.'datetimepicker_css.js'.'"></script>';
	echo "<script language=javascript>SetImageFilesPath('".$plugin."')</script>";
	
	function heat_trackr_periodStart() {
		if (!empty($_POST))
			return $_POST['heat_trackr_period_start'];
		else
			return get_option('heat_trackr_period_start');
	}
	
	function heat_trackr_periodEnd() {
		if (!empty($_POST))
			return $_POST['heat_trackr_period_end'];
		else
			return get_option('heat_trackr_period_end');
	}

	function heat_trackr_checkDateTime($date, $which) {
		if (preg_match('/\\A(?:^((\\d{2}(([02468][048])|([13579][26]))[\\-\\/\\s]?((((0?[13578])|(1[02]))[\\-\\/\\s]?((0?[1-9])|([1-2][0-9])|(3[01])))|(((0?[469])|(11))[\\-\\/\\s]?((0?[1-9])|([1-2][0-9])|(30)))|(0?2[\\-\\/\\s]?((0?[1-9])|([1-2][0-9])))))|(\\d{2}(([02468][1235679])|([13579][01345789]))[\\-\\/\\s]?((((0?[13578])|(1[02]))[\\-\\/\\s]?((0?[1-9])|([1-2][0-9])|(3[01])))|(((0?[469])|(11))[\\-\\/\\s]?((0?[1-9])|([1-2][0-9])|(30)))|(0?2[\\-\\/\\s]?((0?[1-9])|(1[0-9])|(2[0-8]))))))(\\s(((0?[0-9])|(1[0-9])|(2[0-3]))\\:([0-5][0-9])((\\s)|(\\:([0-5][0-9])))?))?$)\\z/', $date)) {
	    	return '';
		} else {
			return ' Invalid '.$which.' time';
		}
	}	

	function heat_trackr_getURL() {
		if (!empty($_POST))
			return $_POST['heat_trackr_URL'];
		else
			return get_option('heat_trackr_URL');
	}
?>

<div class="wrap">
	<h2>Heat Trackr - Settings</h2>

	<form name="heat_trackr_settings_form" method="post" action="<?php echo $PHP_SELF;?>">
		<hr />
		<h3>Data Collection</h3>
		<table><tr>
		<td>Collect mouse click details</td><td>&nbsp;<input type="checkbox" name="heat_trackr_collect" <?php if (get_option('heat_trackr_collect')=="1") echo 'checked="checked"'; ?>>&nbsp;&nbsp;&nbsp;</td>
		<td>Collect user page scrolls</td><td>&nbsp;<input type="checkbox" name="heat_trackr_collect_scrolls" <?php if (get_option('heat_trackr_collect_scrolls')=="1") echo 'checked="checked"'; ?>></td>
		</tr></table>
		
		<h3>Data Period (applies to display and deletion)</h3>
		<table><tr>
		<td colspan=3>Use the <b>Start/End Times</b> below when displaying or deleting saved data, applies whether using <b>URL All</b> or a <b>Single URL</b></td>
		</tr><tr>
		<td colspan=3>Select the <b>All</b> checkbox below if you want to display or delete data for all times</td>
		</tr><tr>
		<td><b>Period to Use => All </b><input type="checkbox" name="heat_trackr_all_clicks" <?php if (get_option('heat_trackr_all_clicks')=="1") echo 'checked="checked"'; ?>></td>
		<td>
			<label for="demo1">&nbsp;&nbsp;<b>Start:</b> </label>
			<input type="Text" name="heat_trackr_period_start" value="<?php echo heat_trackr_periodStart(); ?>" id="demo1" maxlength="16" size="16"/>
			<img src="<?php echo $plugin.'images2/cal.gif';?>" style="cursor:pointer"/ onclick="javascript:NewCssCal('demo1','yyyyMMdd','arrow',true,'24')">
		</td>
		<td>
			<label for="demo2">&nbsp;&nbsp;<b>End:</b> </label>
			<input type="Text" name="heat_trackr_period_end" value="<?php echo heat_trackr_periodEnd(); ?>" id="demo2" maxlength="16" size="16"/>
			<img src="<?php echo $plugin.'images2/cal.gif';?>" style="cursor:pointer"/ onclick="javascript:NewCssCal('demo2','yyyyMMdd','arrow',true,'24')">
			<?php echo '<font color="red">'.$timeMsg.'</font>'; ?>
		</td>
		</tr></table>

		<h3>Data Display (Heatmap, Clickmap & Scrollmap)</h3>
		<table><tr>
		<td>Display overlay maps</td><td>&nbsp;<input type="checkbox" name="heat_trackr_display" <?php if (get_option('heat_trackr_display')=="1") echo 'checked="checked"'; ?>></td>
		</tr><tr>
		<td>Display summary clicks</td><td>&nbsp;<input type="checkbox" name="heat_trackr_click_summary" <?php if (get_option('heat_trackr_click_summary')=="1") echo 'checked="checked"'; ?>></td>
		<td colspan=2>Applies only to <b>Clickmaps</b></td>
		</tr><!--<tr><td>Display coordinates</td>
		 <td>&nbsp;<input type="Radio" name="heat_trackr_display_source" value="absolute" 
		 <?php if (get_option('heat_trackr_display_source')=="absolute") echo 'checked="checked"'; ?>><b> &nbsp;Absolute</b></td>
		 <td>&nbsp;<input type="Radio" name="heat_trackr_display_source" value="relative" 
		 <?php if (get_option('heat_trackr_display_source')=="relative") echo 'checked="checked"'; ?>><b> &nbsp;Relative</b></td>
		</tr>--></table>

		<h3>Data Deletion</h3>
		<table><tr>
		<td>Delete data on uninstall&nbsp;&nbsp;&nbsp;<input type="checkbox" name="heat_trackr_data_delete" <?php if (get_option('heat_trackr_data_delete')=="1") echo 'checked="checked"'; ?>>&nbsp;&nbsp;&nbsp;</td>
		<td>Delete options on uninstall&nbsp;&nbsp;&nbsp;<input type="checkbox" name="heat_trackr_options_delete" <?php if (get_option('heat_trackr_options_delete')=="1") echo 'checked="checked"'; ?>></td>
		</tr>
		<tr><td>&nbsp;</td></tr>
		<tr>
		<td colspan=3>Use the <b>URL</b> below when deleting saved data, applies whether using <b>Period All</b> or <b>Start/End Period</b></td>
		</tr><tr>
		<td colspan=3>Select the <b>All</b> checkbox below if you want to delete data for all URLs</td>
		</tr><tr>
		<td><b>URL to delete data => All </b><input type="checkbox" name="heat_trackr_all_URL" <?php if (get_option('heat_trackr_all_URL')=="1") echo 'checked="checked"'; ?>>&nbsp;&nbsp;</td>
		
		<td><select style="width:200px" size="1" name="heat_trackr_URL" id="heat_trackr_URL">
		<option value=""><?php echo esc_attr(__('Select URL')); ?></option> 
		<?php
			global $wpdb;
			$table_name = $wpdb->prefix . "heat_trackr_clicks";
			$queryStr = "SELECT DISTINCT $table_name.url FROM $table_name";
			$results = $wpdb->get_results($queryStr);
			$table_name2 = $wpdb->prefix . "heat_trackr_scrolls";
			$queryStr = "SELECT DISTINCT $table_name2.url FROM $table_name2";
			$results = $results + $wpdb->get_results($queryStr);
			foreach ($results as $result) {
				echo '<option value="'.$result->url.'"';
				if (heat_trackr_getURL() == $result->url) echo ' selected';
				echo '>'.$result->url.'</option>';
			}
		?></td>
		<td><p class="submit"><input type="submit" name="heat_trackr_view_clicks_btn" value="View Clicks" onClick="javascript: window.open(document.getElementById('heat_trackr_URL').value);"/></p></td>

		</tr><tr>
		<td><p class="submit"><input type="submit" name="heat_trackr_delete_clicks_btn" value="Delete Clicks" onClick="javascript: return confirm('Are you sure you want to delete clicks for the Data Period and URL specified?');"/></p></td>
		<td><p class="submit"><input type="submit" name="heat_trackr_delete_scrolls_btn" value="Delete Scrolls" onClick="javascript: return confirm('Are you sure you want to delete scrolls for the Data Period and URL specified?');"/></p></td>
		</tr>
		</table>
        
        <?php echo '<font color="red">'.$feedbackMsg.'</font>'; ?><hr />
        <p class="submit"><input type="submit" name="heat_trackr_save_settings_btn" value="Save Settings" /></p>  
	</form>  
</div>
