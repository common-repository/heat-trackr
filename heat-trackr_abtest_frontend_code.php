<?php
$id = $atts['id'];
global $wpdb;

$table_name = $wpdb->prefix . "heat_trackr_abtest_experiments";
$exp = $wpdb->get_results( "SELECT * FROM $table_name where id=".$id);

$table_name = $wpdb->prefix . "heat_trackr_abtest_trans";
$rec = $wpdb->get_results( "SELECT * FROM $table_name where exp_id=".$id." Order by tid desc Limit 0,1" );

if ($exp[0]->status != "Active") {echo $exp[0]->original; return;}

$next_var = -1;
$max_var = $exp[0]->btwn;

if (count($rec) != 0) {$next_var = $rec[0]->var_id;}
if ($next_var >= $max_var) {
    $next_var = 0;
} else {
    $next_var = $next_var + 1;
}

$table_name = $wpdb->prefix . "heat_trackr_abtest_trans";
$values = array('exp_id'=>$id, 'var_id'=>$next_var, 'status'=>1);
$wpdb->insert($table_name, $values); 

$tid = $wpdb->insert_id;

if ($next_var == 0) {echo $exp[0]->original;}
if ($next_var == 1) {echo $exp[0]->variation1;}
if ($next_var == 2) {echo $exp[0]->variation2;}
if ($next_var == 3) {echo $exp[0]->variation3;}
?>

<script type="text/javascript">
function ping_true(obj) {
	if (window.XMLHttpRequest) {
		// code for IE7+, Firefox, Chrome, Opera, Safari
		xmlhttp = new XMLHttpRequest();
	} else {
		// code for IE6, IE5
		xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
	}

	xmlhttp.onreadystatechange=function() {
		if (xmlhttp.readyState == 4 && xmlhttp.status == 200) {
			$href = obj.getAttribute("href");
			document.location.href = $href;
		}
	}

	xmlhttp.open("GET", "<?php echo site_url()?>/?wpslt_ping=<?php echo $tid;?>", true);
	xmlhttp.send();
	return false;
}
</script>