<div id="wrapperd">
			
	<div id="patient_enrolled_content" class="full-content">
		<?php $this->load->view("reports/reports_top_menus_v") ?>
		<h4 style="text-align: center" id='report_title'>Patient Viral Load Results Between <span id="start_date"><?php echo date('d-M-Y',strtotime($start_date)); ?></span> to <span id="start_date"><?php echo date('d-M-Y',strtotime($end_date)); ?></span>  </h4>
		<hr size="1" style="width:80%">
		<table align='center'  width='20%' style="font-size:16px; margin-bottom: 20px">
			<tr>
				<td colspan="2"><h5 class="report_title" style="text-align:center;font-size:14px;">Number of results: <span id="total_count"><?php echo $overall_total;?></span></h5></td>
			</tr>
		</table>
		<div id="appointment_list">
		<?php echo $dyn_table;?>
		</div>
	</div>
</div>