<?php
global $wpdb;
$table_name = $wpdb->prefix . "heat_trackr_abtest_experiments";

if ($_REQUEST['submit'] == "Save") {
    $values = array('exp_name'=>stripslashes($_REQUEST['exp_name']),
	'original'=>stripslashes($_REQUEST['original']),
	'variation1'=>stripslashes($_REQUEST['variation1']),
	'variation2'=>stripslashes($_REQUEST['variation2']),
	'variation3'=>stripslashes($_REQUEST['variation3']),
	'status'=>$_REQUEST['status'],
	'btwn'=>$_REQUEST['btwn']);

    if ($_GET['action'] == 'edit') {
        $wpdb->update($table_name, $values, array('id'=>$_REQUEST['id']));
    } else {
        $wpdb->insert($table_name, $values); 
        $id = $wpdb->insert_id;
        include('heat-trackr_abtest_list.php');
        exit;
    }
}

if ($_GET['action'] == 'edit') {
    $type = $wpdb->get_results( "SELECT * FROM $table_name where id=".$_GET['id'] );
}
?>

<table  cellspacing="5">
<tr><td>
<form name="form" method="post">

<input type="hidden" name="id" value="<?php echo (isset($type) ? $type[0]->id : ""); ?>" />

<table cellpadding="5" cellspacing="5" width="100%">
	<tr>
		<td>Exp Name :</td><td><input size="60" type="text" name="exp_name" value="<?php echo (isset($type) ? $type[0]->exp_name : ""); ?>" /></td>
	</tr>
	<tr>
		<td>Original :</td><td><textarea name="original" cols="70" rows="7"><?php echo (isset($type) ? $type[0]->original : ""); ?></textarea></td>
	</tr>
	<tr>
		<td>Variation 1 :</td><td><textarea name="variation1" cols="70" rows="7"><?php echo (isset($type) ? $type[0]->variation1 : ""); ?></textarea></td>
	</tr>
	<tr>
		<td>Variation 2 :</td><td><textarea name="variation2" cols="70" rows="7"><?php echo (isset($type) ? $type[0]->variation2 : ""); ?></textarea></td>
	</tr>
	<tr>
		<td>Variation 3 :</td><td><textarea name="variation3" cols="70" rows="7"><?php echo (isset($type) ? $type[0]->variation3 : ""); ?></textarea></td>
	</tr>
    <tr>
        <td>A/B Test Between :</td>
        <td><select name="btwn">
                <option value="1" <?php if (isset($type)) { echo ($type[0]->btwn == 1 ? "selected = true" : ""); } ?> >Variation 1</option>
                <option value="2" <?php if (isset($type)) { echo ($type[0]->btwn == 2 ? "selected = true" : ""); } ?> >Variation 1 and 2</option>
                <option value="3" <?php if (isset($type)) { echo ($type[0]->btwn == 3 ? "selected = true" : ""); } ?> >Variation 1 , 2 and 3</option>
            </select>
        </td>
    </tr>
	<tr>
		<td>Status</td>
		<td><select name="status">
				<option value="">-- <?php if (isset($type) && ($type[0]->status == '')) echo "Select Status"; ?> --</option>
				<option value="Active" <?php echo (isset($type) && ($type[0]->status == 'Active') ? ' selected' : ''); ?>>Active</option>
				<option value="Not Active" <?php echo (isset($type) && ($type[0]->status == 'Not Active') ? ' selected' : ''); ?>>Not Active</option>
			</select>
		</td>
	</tr>
    
	<tr><td><input type="submit" name="submit" value="Save" class="button"/></td>
    </tr>
</table>
</form>

</td>
<td valign="top" width="300px">
<h2>How to use this experiment</h2>
<br />
<?php $text = $wpdb->prepare($_GET['id']==''?'N':$_GET['id']); ?>
You need to place <b>[WPSLT id = <?php echo $text ;?>]</b> inside any post or page,<br/><br/>
Once that is done, a success or conversion is triggered when user clicks on the Call to Action button.<br/><br/>
You need to place a ping back function to let our system know this click has been performed.<br/>
<br/>
Javascript function is <b>onClick='return ping_true(this);'</b><br/><br/>
<b>Usage :</b><br/>
<pre><code> "&lt;a href='http://google.com' onClick='return ping_true(this);'&gt;This is Ping Back&lt;/a&gt;" </code></pre>
<br/>
<center><a href="<?php echo site_url();?>/wp-admin/admin.php?page=heat-trackr_abtest_admin.php" class="button"> Back </a></center>
</td>
</tr>
</table>
