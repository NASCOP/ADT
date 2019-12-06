<style type="text/css">
	.ui-multiselect-menu {
		display: none;
		margin-left: 15px;
		position: static; 
		text-align: left;
		zoom: 0.8;
	}

	.ui-multiselect-header{
		zoom:0.9;
	}

</style>

<!-- ADT UPDATE Modal-->
<div id="adt_update_modal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
	<form action="<?php echo base_url().'user_management/profile_update' ?>" method="post" id="profile_update_frm">
		<div class="modal-header">
			<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
			<h3 id="myModalLabel">ADT Update Progress</h3>
		</div>
		<div class="modal-body">

			<p>A newer version of ADT is available. <br />You are currently using <?= $this->config->item('adt_version'); ?></p>
			<?php  if (!$download_status){?>
				<div id="downloading" style="display: none;"><img src="<?=base_url()?>assets/images/loading_spin.gif" style="width: 19px;"> Downloading ADT Release.  This may take up to 3 minutes</div>

				<div id='download_status'><font  color="red">ADT Release Not Downloaded</font>. 
					<a href="javascript:;;" id="download-ADT-release"  onclick="download_ADT()">Download Now</a>
				</div>
			<?php } else {?>
				ADT Version 3.4.1--new is already downloaded. Click below button to update
				</div><?php } ?>
				<div id="update_ADT" style="display: none;">
					<a href="javascript:;;" id="download-ADT-release" class="btn btn-warning"  onclick="update_ADT()">Update</a>
				</div>
				
				<div id="updating" style="display: none;"><img src="<?=base_url()?>assets/images/loading_spin.gif" style="width: 19px;"> Updating ADT Release. May take a few minutes.</div>

				<table>
					<tr>
						<td></td>
					</tr>

				</table>

			</div>

			<div class="modal-footer">

			</div>
		</form>
	</div>

	<!-- Modal edit user profile-->
	<div id="edit_user_profile" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
		<form action="<?php echo base_url().'user_management/profile_update' ?>" method="post" id="profile_update_frm">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
				<h3 id="myModalLabel">User details</h3>
			</div>
			<div class="modal-body">
				<div id="profile_error"></div>
				<table>
					<tr>

						<td><label >Full Name</label></td><td>
							<div class="input-prepend">
								<span class="add-on"><i class="icon-user"></i></span>
								<input style='height:2.1em' type="text" class="input-xlarge" name="u_fullname" id="u_fullname" required="" value="<?php echo $this->session->userdata('full_name') ?>" />
							</div></td>
						</tr>
						<tr>
							<td><label >Username</label></td><td>
								<div class="input-prepend">
									<span class="add-on"><i class="icon-user"></i></span>
									<input style='height:2.1em' type="text" class="input-xlarge" name="u_username" id="u_username" required="" value="<?php echo $this->session->userdata('username') ?>" />
								</div></td>
							</tr>
							<tr>
								<td><label>Email Address</label></td><td>
									<div class="input-prepend">
										<span class="add-on"><i class="icon-envelope"></i></span>
										<input style='height:2.1em' type="email" class="input-xlarge" name="u_email" id="u_email" value="<?php echo $this->session->userdata('Email_Address') ?>" />
									</div></td>
								</tr>
								<tr>
									<td><label>Phone Number</label></td><td>
										<div class="input-prepend">
											<span class="add-on"><i class="icon-plus"></i>254</span>
											<input style='height:2.1em' type="tel" class="input-large" name="u_phone" id="u_phone" value="<?php echo $this->session->userdata('Phone_Number') ?>"/>
										</div></td>
									</tr>
									<tr>
										<td>
											<label>Ordering Sites</label>
										</td>
										<td>
											<span class="add-on"><i class=" icon-chevron-down icon-black"></i></span>
											<input type="hidden" id="profile_user_facilities_holder" name="profile_user_facilities_holder" />
											<select name="profile_user_facilities" id="profile_user_facilities" class="input-xlarge" multiple="multiple" required="">
											</select>
										</td>
									</tr>

									<tr>
										<td>
											<label>Store/Pharmacy</label>
										</td>
										<td>
											<span class="add-on"><i class=" icon-chevron-down icon-black"></i></span>
											<select name="user_store" id="profile_user_store" class="input-xlarge"  required="">
											</select>
										</td>
									</tr>


								</table>

							</div>

							<div class="modal-footer">
								<button class="btn" data-dismiss="modal" aria-hidden="true">Cancel</button>
								<input type="submit" class="btn btn-primary" value="Save changes" id="btn_save_profile_frm">
							</div>
						</form>
					</div>
					<!-- Modal edit user profile end-->
					<!-- Modal edit change password-->
					<div id="user_change_pass" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
						<form action="<?php echo base_url().'user_management/profile_update' ?>" method="post">
							<div class="modal-header">
								<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
								<h3 id="myModalLabel">Change password</h3>
							</div>
							<div class="modal-body">
								<input type="hidden" name="base_url" id="base_url" value="<?php echo base_url() ?>" />
								<form id="fmChangePassword" action="<?php echo base_url().'user_management/save_new_password/1'?>" method="post" class="well">
									<span class="message error" id="error_msg_change_pass"></span>
									<div id="m_loadingDiv" style="display: none"><img style="width: 30px" src="<?php echo asset_url().'images/loading_spin.gif' ?>"></div>
									<br>
									<table>
										<tr>
											<td><label >Old Password</label></td><td><input type="password" name="old_password" id="old_password" required=""></td>
										</tr>
										<tr>
											<td><label >New Password</label></td><td><input type="password" name="new_password" id="new_password" required=""><span id="result"></span></td>
										</tr>
										<tr>
											<td><label >Confirm New Password</label></td><td>
												<input type="password" name="new_password_confirm" id="new_password_confirm" required="">
											</td>
										</tr>
									</table>

								</form>

							</div>

							<div class="modal-footer">
								<button class="btn" data-dismiss="modal" aria-hidden="true">Cancel</button>
								<input type="button" class="btn btn-primary btn_submit_pass" name="btn_submit_change_pass" id="btn_submit_change_pass" value="Save changes">
							</div>
						</form>
					</div>
					<!-- Modal edit change password end-->

					<!-- Modal for synchronizing balances -->
					<div id="drug_stock_balance_synch" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">  
						<div class="modal-header">
							<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
							<h3 id="myModalLabel">Synchronization - Stock Balances</h3>
						</div>
						<div class="modal-body">  
							<span class="alert-info ">Please wait until the process is complete!</span>
							<div class="span5">
        	<!-- 
	    	<div id="div_tot_drugs" style="display: none">
	    		Number of drugs :<strong><span id="tot_drugs"></span></strong>
	    	</div> 
	    -->
	    <p>
	    	<div class="progress progress_pharmacy_dsm progress-striped active">  
	    		<div class="bar bar_dcb" style="width: 0%;">Drug Consumption</div> 
	    	</div>
	    </p>
	    <p> 
	    	<div class="progress progress_store progress-striped active">  
	    		<div class="bar bar_dsb bar_store" style="width: 0%;">Main Store - Stock balance</div> 
	    	</div>  
	    </p> 
	    <p>
	    	<div class="progress progress_pharmacy progress-striped active">  
	    		<div class="bar bar_dsb bar_pharmacy" style="width: 0%;">Pharmacy - Stock balance</div> 
	    	</div>
	    </p>
	    <p> 
	    	<div class="progress progress_store_dsm progress-striped active">  
	    		<div class="bar bar_dsm bar_store_dsm" style="width: 0%;">Main Store - Stock transactions</div> 
	    	</div>  
	    </p> 
	    <p>
	    	<div class="progress progress_pharmacy_dsm progress-striped active">  
	    		<div class="bar bar_dsm bar_pharmacy_dsm" style="width: 0%;">Pharmacy - Stock transactions</div> 
	    	</div>
	    </p>

	    <a class="sync_complete" href="#"></a>
	</div>  
	<div class="modal-footer">
		<button class="btn" data-dismiss="modal" aria-hidden="true">Done</button>
	</div>
</div>  
</div>
<!--  Modal for synchronizing balances end  -->



<!-- Confirmation message before synchronizing drug stock movement balance-->
<div id="confirmbox" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
		<h3 id="myModalLabel">Confirm before proceed</h3>
	</div>
	<div class="modal-body">
		<p id="confirmMessage" >
			Please make sure you synchronize the <i><strong>stock balance</strong></i> before proceeding.
		</p>
	</div>
	<div class="modal-footer">
		<button class="btn" id="confirmFalse" data-dismiss="modal" aria-hidden="true">Cancel</button>
		<button class="btn btn-primary" id="confirmTrue" data-dismiss="modal" aria-hidden="true">Proceed</button>
	</div>
</div>
<!--
/*
 * Order Modal
 */
-->


<!-- Submit confirmation for maps -->
<div id="confirmsubmission" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
		<h3 id="modalHeader">Confirm Delete</h3>
	</div>
	<div class="modal-body conf_maps_body">

	</div>
	<div class="modal-footer">
		<button class="btn order_btn" id="cFalse" data-dismiss="modal" aria-hidden="true">Cancel</button>
		<button class="btn order_btn btn-primary" id="cTrue" data-dismiss="modal" aria-hidden="true">Proceed</button>
	</div>
</div>
<!-- Submit confirmation ends  maps-->

<!-- Login for escm -->

<script type="text/javascript">
	var sitesURL = "<?php echo base_url().'order_settings/fetch/sync_facility';?>"
	var storesURL = "<?php echo base_url().'user_management/get_stores';?>"
	var userSitesURL = "<?php echo base_url().'user_management/get_sites/'.$this->session->userdata('user_id') ;?>"
	var profileDiv = '#profile_user_facilities'
	var storesDiv = '#profile_user_store'
	var ccc_store = <?= $this->session->userdata('ccc_store_id') ?>;

	$(function(){

		
		$.get(storesURL, function(data) {
	       	//Parse json to array
	       	data = $.parseJSON(data);

			//Append results to selectbox
			$.each(data, function(i, item) {
				$(storesDiv).append($("<option></option>").attr("value", item.id).text(item.Name));
			});

			$(storesDiv).val(ccc_store);

		});


		$.get(sitesURL, function(data) {
	       	//Parse json to array
	       	data = $.parseJSON(data);

			//Append results to selectbox
			$.each(data, function(i, item) {
				$(profileDiv).append($("<option></option>").attr("value", item.id).text(item.name));
			});

			//Make multiselect
			$(profileDiv).multiselect().multiselectfilter();

			//Get user ordering sites
			$.get(userSitesURL, function(data) {
				//Parse json to array
				data = $.parseJSON(data);

				//Select user sites
				$.each(data, function(i, item) {
					$("select"+profileDiv).multiselect("widget").find(":checkbox[value='"+item+"']").each(function() {
						$(this).click();
					});
				});
			});
		});


		$("#btn_save_profile_frm").live('click',function(event){
			event.preventDefault();
			//Order sites
			var profile_user_facilities = $("select#profile_user_facilities").multiselect("getChecked").map(function() {
				return this.value;
			}).get();
			$("#profile_user_facilities_holder").val(profile_user_facilities);

			if ($.trim(profile_user_facilities) ==""){
				//Display error message
				$("#profile_error").html("<div class='alert alert-error'><button type='button' class='close' data-dismiss='alert'>&times;</button><strong>Required!</strong> Select Order Sites for user!</div>");
			}else{
				//Submit
				$("#profile_update_frm").submit();
			}
		});

	});

	function download_ADT(){
		$('#download_status').hide();
		$('#downloading').show();

		$.ajax({
			url: "<?=base_url()?>home_controller/updater/download",
			context: document.body
		}).done(function(results) {
			$('#download_status').html(results);
			$('#downloading').hide();
			$('#download_status').show();
			$('#update_ADT').show();

			// alert('Download success'+results)
			// $( this ).addClass( "done" );
		});
	}

	function update_ADT(){
		// $('#update_ADT').();
		$('#updating').show();

		$.ajax({
			url: "<?=base_url()?>home_controller/updater/update",
			context: document.body
		}).done(function(results) {
			$('#download_status').html(results);
			$('#updating').hide();
			window.location.replace('<?=base_url()?>user_management/logout/2')
			// $('#download_status').show();
			// alert('Download success'+results)
			// $( this ).addClass( "done" );
		});
	}


</script>
