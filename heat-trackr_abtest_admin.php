<h2>A/B Split Test Experiments</h2>
<hr/>
<?php
if ($_GET['action']=='') {include('heat-trackr_abtest_list.php');}
if ($_GET['action']=='delete') {include('heat-trackr_abtest_list.php');}
if ($_GET['action']=='new') {include('heat-trackr_abtest_add.php');}
if ($_GET['action']=='edit') {include('heat-trackr_abtest_add.php');}
if ($_GET['action']=='report') {include('heat-trackr_abtest_visitors.php');}
?>
