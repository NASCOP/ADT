<?php
switch ($agegroup) {
	case 'below4':
	$group = 'Below 4 weeks';
	break;
	case '4weeks':
	$group = '4 weeks to < 3 years';
	break;
	case '3years':
	$group = '3 years to < 9 years';
	break;
	case '9years':
	$group = '9 years to < 15 years';
	break;
	case '15years':
	$group = '15 years to < 20 years';
	break;
	case '20years':
	$group = '20 years to < 25 years';
	break;
	case '25years':
	$group = '25 years to < 49 years';
	break;
	case 'above49':
	$group = 'above 49 years';
	break;

	default:
	break;
}		
?>
<script type="text/javascript">
	$(document).ready(function(){
		$(".dataTables").find("tr :first").css("width","220px");
	});
</script>
<div id="wrapperd">

	<div id="patient_enrolled_content" class="full-content">
		<?php $this->load->view("reports/reports_top_menus_v") ?>
		<h4 style="text-align: center" id='report_title'><?=$report_title;?> as at <span  id="date_of_appointment"><?= $from;?>  </span></h4>
		<hr size="1" style="width:80%">
		<?php echo  $dyn_table;?>
	</div>
</div>	

