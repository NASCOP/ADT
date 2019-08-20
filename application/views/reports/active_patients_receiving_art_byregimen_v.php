<script type="text/javascript">
	$(document).ready(function(){
		$(".dataTables").find("tr :first").css("width","220px");
	});
</script>
<div id="wrapperd">

	<div id="patient_enrolled_content" class="full-content">
		<?php $this->load->view("reports/reports_top_menus_v") ?>
		<h4 style="text-align: center" id='report_title'><?=$report_title;?> as at <span  id="date_of_appointment"><?= $from;?>  </span> </h4>
		<hr size="1" style="width:80%">
		<?php echo  $dyn_table;?>
	</div>
</div>	

