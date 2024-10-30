<br/>
<a href="<?php echo site_url();?>/wp-admin/admin.php?page=heat-trackr_abtest_admin.php&action=new" class="button"> New Experiment </a>
</br>

<?php
global $wpdb;
$table_name = $wpdb->prefix . "heat_trackr_abtest_experiments";

if($_GET['action']=="delete")
{$wpdb->get_results( "DELETE FROM $table_name where id=".$_GET['id'] );}

$type = $wpdb->get_results( "SELECT * FROM $table_name" );
?>

<br/>
<table width="70%" border="0" cellpadding="5" cellspacing="5" class="wp-list-table widefat fixed users">
<thead>
<tr>
	<th>Name</th><th>Status</th><th>Short Code</th><th>Action</th><th>Report</th>
</tr>
</thead>
<tbody>
<?php foreach($type as $sk) {?>
<tr><td><?php echo $sk->exp_name; ?></td><td><?php echo $sk->status; ?></td><td>
[WPSLT ID = <?php echo $sk->id; ?>]
</td>
<td><a href="?page=heat-trackr_abtest_admin.php&amp;action=edit&amp;id=<?php echo $sk->id; ?>">Edit</a> |
<a href="?page=heat-trackr_abtest_admin.php&amp;action=delete&amp;id=<?php echo $sk->id; ?>" onClick="javascript: return confirm('Are you sure you want to delete this?');">Delete</a></td>
 
<td><a href="?page=heat-trackr_abtest_admin.php&amp;action=report&amp;id=<?php echo $sk->id ?>">View</a></td>
</tr>
<?php } ?>
</tbody>
</table>
<br/>
<h2>How to use this experiment</h2>
You need to place <b>[WPSLT id = N]</b> inside any post or page,<br/><br/>
Once that is done, a success or conversion is triggered when user clicks on the Call to Action button.<br/><br/>
You need to place a ping back function to let our system know this click has been performed.<br/>
<br/>
Javascript function is <b>onClick='return ping_true(this);'</b><br/><br/>
<b>Usage :</b><br/>
<pre><h1><code> "&lt;a href='http://google.com' onClick='return ping_true(this);'&gt;This is Ping Back&lt;/a&gt;" </code></h1></pre>