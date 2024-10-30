<?php 
$id = $_GET['id'];

global $wpdb;
$table_name = $wpdb->prefix . "heat_trackr_abtest_trans";

$org = $wpdb->get_results("SELECT count(*) total, sum(case status when 2 then 1 else 0 end) pass FROM `$table_name` where exp_id = $id and var_id = 0");
$var1 = $wpdb->get_results("SELECT count(*) total, sum(case status when 2 then 1 else 0 end) pass FROM `$table_name` where exp_id = $id and var_id = 1");
$var2 = $wpdb->get_results("SELECT count(*) total, sum(case status when 2 then 1 else 0 end) pass FROM `$table_name` where exp_id = $id and var_id = 2");
$var3 = $wpdb->get_results("SELECT count(*) total, sum(case status when 2 then 1 else 0 end) pass FROM `$table_name` where exp_id = $id and var_id = 3");

$visitors = $wpdb->get_results("SELECT count(*) total FROM `$table_name` where exp_id = $id ");
?>

<h4>Visitor : <?php echo $visitors[0]->total; ?></h4>
<table width="80%" class="wp-list-table widefat fixed users">
	<thead>
	<tr align="left">
		<th>State</th><th>Original</th><th>Variation 1</th><th>Variation 2</th><th>Variation 3</th>
	</tr>
	</thead>
    <tbody>
	<tr align="left">
		<td>Visitors</td>
        <td><?php echo $org[0]->total; ?></td><td><?php echo $var1[0]->total; ?></td><td><?php echo $var2[0]->total; ?></td><td><?php echo $var3[0]->total; ?></td>
	</tr>
	<tr align="left">
		<td>Conversions</td>
        <td><?php echo $org[0]->pass==''?0:$org[0]->pass; ?></td><td><?php echo $var1[0]->pass==''?0:$var1[0]->pass; ?></td>
		<td><?php echo $var2[0]->pass==''?0:$var2[0]->pass; ?></td><td><?php echo $var3[0]->pass==''?0:$var3[0]->pass; ?></td>
	</tr>
    </tbody>
    <thead>
	<tr align="left">
		<th>Conversion Rate</th>
        <th style="color: blue;"><?php echo $org[0]->total==0?0:number_format(($org[0]->pass * 100)/$org[0]->total, 2) ?>%</th>
        <th style="color: blue;"><?php echo $var1[0]->total==0?0:number_format(($var1[0]->pass * 100)/$var1[0]->total, 2) ?>%</th>
        <th style="color: blue;"><?php echo $var2[0]->total==0?0:number_format(($var2[0]->pass * 100)/$var2[0]->total, 2) ?>%</th>
        <th style="color: blue;"><?php echo $var3[0]->total==0?0:number_format(($var3[0]->pass * 100)/$var3[0]->total, 2) ?>%</th>
	</tr>
	</thead>
</table>
<br/><br/>
<a href="<?php echo site_url();?>/wp-admin/admin.php?page=heat-trackr_abtest_admin.php" class="button"> Back </a>