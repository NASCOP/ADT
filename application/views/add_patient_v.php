<!DOCTYPE html>
<html lang="en">
<head>
	<style>
		.btn_positioning{
			float:right; position:relative; 
			bottom:50px; left:-220px;
			width: auto;
		}

		.button_size{
			width:120px; height:38px;  font-weight: bold;
		}
		.ui-multiselect-menu{
			zoom:0.8;
			display:none; 
			padding:3px; 
			z-index:10000; 
		}
		.column input[type=checkbox] {
			width: 10%;
		}

	</style>
	<script type="text/javascript">
			function setCCC(service) {
				if (service=='ART'){
					$('#patient_number').val('<?=$facility_code. $cs;?>');
					$('#patient_number').attr('maxlength','11');
				}
				if (service=='PREP'){
					$('#patient_number').val('PREP<?=$cs.$facility_code. $cs;?>');
					$('#patient_number').attr('maxlength','16');
				}
				if (service=='PEP'){
					$('#patient_number').val('PEP<?=$cs.$facility_code. $cs;?>');
					$('#patient_number').attr('maxlength','15');
				}
				if (service=='HEP'){
					$('#patient_number').val('HEP<?=$cs.$facility_code. $cs;?>');
					$('#patient_number').attr('maxlength','15');
				}

				if (service=='HEI'){
					$('#patient_number').val('<?=$facility_code. $cs.date('Y').$cs;?>');
					$('#patient_number').attr('maxlength','16');
				}			
			}
		$(document).ready(function(){

			//Function to Check Patient Number exists
			var base_url="<?php echo base_url();?>";
			$('.match_spouse').css("display","none");
			$('.status_hidden').css("display","none");
			$('.match_hidden').css("display","none");
			$("#patient_number").change(function(){
				var patient_no=$("#patient_number").val();
				// -- do regex check on patient ccc number 
				var CCC_check = new RegExp('^[0-9]{5}<?=$cs?>[0-9]{5}');
				var CCC_check_PREP = new RegExp('^PREP<?=$cs;?>[0-9]{5}<?=$cs?>[0-9]{5}');
				var CCC_check_PEP  = new RegExp('^PEP<?=$cs;?>[0-9]{5}<?=$cs?>[0-9]{5}');
				var CCC_check_HEP  = new RegExp('^HEP<?=$cs;?>[0-9]{5}<?=$cs?>[0-9]{5}');
				var CCC_check_HEI = new RegExp('^[0-9]{5}<?=$cs?><?=date('Y')?>-[0-9]{5}');

				if(!CCC_check.test(patient_no) && !CCC_check_PREP.test(patient_no) && !CCC_check_PEP.test(patient_no) && !CCC_check_HEI.test(patient_no) && !CCC_check_HEP.test(patient_no)  ){
					bootbox.alert("<h4>Wrong CCC format</h4>\n\<hr/>Please Note recommended format \
					<br /><b> ART</b> : {mfl}<?=$cs?>{ccc} e.g <b> <?=$facility_code;?><?=$cs;?>00001 </b> \
					<br /><b> PREP</b>: PREP<?=$cs?>{mfl}<?=$cs?>{ccc} e.g <b> PEP<?=$cs.$facility_code;?><?=$cs;?>00001 </b> \
					<br /><b> PEP</b>:  PEP<?=$cs?>{mfl}<?=$cs?>{ccc} e.g <b> PEP<?=$cs.$facility_code;?><?=$cs;?>00001 </b> \
					<br /><b> HEP</b>:  hEP<?=$cs?>{mfl}<?=$cs?>{ccc} e.g <b> HEP<?=$cs.$facility_code;?><?=$cs;?>00001 </b> \
					<br /><b> HEI</b>:  {mfl}<?=$cs?>{year}<?=$cs?>{ccc} e.g <b> <?=$facility_code.$cs.date('Y');?><?=$cs;?>00001 </b> \
						");
					$(".btn").attr("disabled","disabled");
				} else{
					$(".btn").attr("disabled",false);

				if(patient_no !=''){
					var link=base_url+"patient_management/checkpatient_no/"+patient_no;
					$.ajax({
						url: link,
						type: 'POST',
						success: function(data) {
							if(data==1){
								bootbox.alert("<h4>Duplicate Entry</h4>\n\<hr/><center>Patient Number Matches an existing record</center>");
								$(".btn").attr("disabled","disabled");
								$('#patient_number').focus();
								$('#patient_number').val('');

							}else{
								$(".btn").attr("disabled",false);
							
							}
						}
					});
				}
			}
			});

			$("#match_spouse").change(function(){
				var patient_no=$("#match_spouse").val();
				if(patient_no !=''){
					var link=base_url+"patient_management/checkpatient_no/"+patient_no;
					$.ajax({
						url: link,
						type: 'POST',
						success: function(data) {
							if(data==1){
								$(".btn").attr("disabled",false); 
							}else{
								bootbox.alert("<h4>CCC Number Mismatch</h4>\n\<hr/><center>Patient Number does not exist</center>");
								$(".btn").attr("disabled","disabled");
							}
						}
					});
				}
			});

			$("#match_parent").change(function(){
				var patient_no=$("#match_parent").val();
				if(patient_no !=''){
					var link=base_url+"patient_management/checkpatient_no/"+patient_no;
					$.ajax({
						url: link,
						type: 'POST',
						success: function(data) {
							if(data==1){
								$(".btn").attr("disabled",false); 
							}else{

								bootbox.alert("<h4>CCC Number Mismatch</h4>\n\<hr/><center>Patient Number does not exist</center>");
								$(".btn").attr("disabled","disabled");
							}
						}
					});
				}
			});

	        //Attach date picker for date of birth
	        $("#dob").datepicker({
	        	yearRange : "-120:+0",
	        	maxDate : "0D",
	        	dateFormat : $.datepicker.ATOM,
	        	changeMonth : true,
	        	changeYear : true,
	        });


			//Function to calculate age in years and months
			$("#dob").change(function() {
				var dob = $(this).val();
				dob = new Date(dob);
				var today = new Date();
				$('.status_hidden').css("display","none");
				$('.match_hidden').css("display","none");
				var age_in_years = Math.floor((today - dob) / (365.25 * 24 * 60 * 60 * 1000));
				$("#age_in_years").attr("value", age_in_years);
					//if age in years is less than 15 years
					if ($('#age_in_years').val()>=15){
						$('.status_hidden').css("display","block");
						$('.match_hidden').css("display","none");
					}else if($('#age_in_years').val()<15){
						$('.match_hidden').css("display","block");
					}
					var yearDiff = today.getFullYear() - dob.getFullYear();
					var y1 = today.getFullYear();
					var y2 = dob.getFullYear();
					var age_in_months = (today.getMonth() + y1 * 12) - (dob.getMonth() + y2 * 12);
					$("#age_in_months").attr("value", age_in_months);

				});

            //if female is pregnant put them on pmtct service 
            $("#pregnant").change(function(){
            	var selected_value=$(this).attr("value");
            	if(selected_value==1){
            		$("#service > option").each(function() {
            			if(this.text==="PMTCT"){
            				$(this).attr("selected","selected");    
            			}
            		});     
            	}else {
            		$('#breastfeeding').val(0)
            		$("#service").removeAttr("value");
            	}
            });

            $(".match_spouse").css("display","none");
            $('#partner_status').change(function(){
            	var selected_value= $(this).val();
            	if (selected_value == 1 || selected_value == 2) {
            		$(".match_spouse").css("display","block");
            	}else{
            		$(".match_spouse").css("display","none");	
            	}	
            });

			//Attach date picker for date of enrollment
			$("#enrolled").datepicker({
				yearRange : "-30:+0",
				maxDate : "0D",
				dateFormat : $.datepicker.ATOM,
				changeMonth : true,
				changeYear : true
			});
			
			// $("#enrolled").datepicker('setDate', new Date());
			
			
			//Attach date picker for date of start regimen 
			$("#service_started").datepicker({
				yearRange : "-30:+0",
				dateFormat : $.datepicker.ATOM,
				changeMonth : true,
				changeYear : true,
				maxDate : "0D"
			});
			
			// $("#service_started").datepicker('setDate', new Date());
			
			//Function to display transfer from list if patient source is(transfer in)
			$("#source").change(function() {
				var selected_value = $(this).val();
				
				if($('#source option:selected').html().toLowerCase().indexOf('transfer') >= 0) {
					$("#patient_source_listing").show();
		    		$("#facility-service").hide();
				} else {
					$("#patient_source_listing").hide();
		    		$("#facility-service").show();
					$('#patient_number').val('<?= $facility_code.$cs;?>');
				}
			});

			$("#transfer_source").change(function() {
				var selected_value = $(this).val();
				$('#patient_number').val(selected_value+'<?=$cs?>');
				$('#patient_number').attr('maxlength','20');
			});
			

		   //Function to display Regimens in this line
		   $("#service").change(function() {
		   	var service_line = $(this).val();
		   	var service_line_text = $("#service option[value='"+service_line+"']").text().toLowerCase();
		   	var link = base_url+"regimen_management/getRegimenLine/"+service_line;

		   	$("#drug_prophylax").css("display","block");
		   	$("#regimen option").remove();
		   	$("#service_started").val("<?php echo date('Y-m-d');?>");
		   	$("#servicestartedcontent").show();
		   	$("#prep_reason_listing").hide();
		   	$('#prep_reason').removeClass("validate[required]");					
		   	$("#prep_reason").val(0);
		   	$("#prep_test_answer").val(0)
		   	$("#prep_test_question").hide();
		   	$("#prep_test_date_view").hide();
		   	$("#prep_test_date").val('');
		   	$("#prep_test_result_view").hide();
		   	$("#prep_test_result").val(0);

		   	if(service_line_text == "pep"){
		   		$("#pep_reason_listing").show();
		   		$('#pep_reason').addClass("validate[required]");
		   		$("#who_listing").hide();
		   		$("#drug_prophylax").css("display","none");
		   	}
		   	else if(service_line_text == "oi only"){
		   		$("#service_started").val("");
		   		$("#pep_reason_listing").hide();
		   		$('#pep_reason').removeClass("validate[required]");					
		   		$("#servicestartedcontent").hide();
		   	}
		   	else if(service_line_text == "prep"){
		   		$("#prep_reason_listing").show();
		   		$('#prep_reason').addClass("validate[required]");
		   		$("#prep_test_question").show();
		   		$("#pep_reason_listing").hide();
		   		$('#pep_reason').removeClass("validate[required]");					
		   		$("#pep_reason").val(0);
		   		$("#who_listing").hide();
		   		$("#who_stage").val(0);
		   		$("#drug_prophylax").css("display","none");
		   	}
		   	else{
		   		if(service_line_text == "pmtct" && $("#age_in_years").val() < 2){
		   			var link = base_url+"regimen_management/getRegimenLine/"+service_line+"/true";
		   		}
		   		$("#pep_reason_listing").hide();
		   		$("#pep_reason").val(0);
		   		$('#pep_reason').removeClass("validate[required]");					
		   		$("#who_listing").show();
		   		$("#who_stage").val(0);
		   	}

		   	$.ajax({
		   		url: link,
		   		type: 'POST',
		   		dataType: "json",
		   		success: function(data) {	
		   			$("#regimen").append($("<option></option>").attr("value",'').text('--Select One--'));
		   			$.each(data, function(i, jsondata){
		   				$("#regimen").append($("<option></option>").attr("value",jsondata.id).text(jsondata.Regimen_Code+" | "+jsondata.Regimen_Desc));
		   			});
		   		}
		   	});
		   });

		   $("#prep_test_answer").on('change', function(){
		   	var prep_test_answer = $(this).val();

		   	$("#prep_test_date_view").hide();
		   	$("#prep_test_date").val('');
		   	$("#prep_test_result_view").hide();
		   	$("#prep_test_result").val(0);

		   	if(prep_test_answer == 1){
		   		$("#prep_test_date_view").show();
		   	}
		   });

		   $("#prep_test_date").datepicker({
		   	maxDate : "0D",
		   	dateFormat : $.datepicker.ATOM,
		   	changeMonth : true,
		   	changeYear : true
		   });

		   $("#prep_test_date").change(function(){
		   	var prep_test_date = $(this).val();
		   	$("#prep_test_result").val(0);
		   	$("#prep_test_result_view").show();
		   });

		   $("#prep_test_result").on('change', function(){
		   	var prep_test_result = $(this).val();
		   	if(prep_test_result == 1){
		   		bootbox.alert("<h4>Incorrect Regimen</h4>\n\<hr/><center>Patient Should Be started on ART Service</center>");
		   		$("#service option:contains(ART)").attr('selected', 'selected');
		   		$("#service").trigger('change')
		   	}
		   });

		   $("#tbcategory_view").hide();	
		   //Function to display tb phases
		   $(".tb").change(function() {
		   	var tb = $(this).val();
		   	if(tb == 1) {
				    //$("#tbphase_view").show();
				    $("#tbcategory_view").show();
				} 
				else {
					$("#tbphase_view").hide();
					$("#tbcategory_view").hide();
					$("#fromphase_view").hide();
					$("#tophase_view").hide();
					$("#tbphase").attr("value",'0');
					$("#fromphase").attr("value",'');
					$("#tophase").attr("value",'');
				}
			});
		   $("#tbcategory").change(function(){
		   	$("#tbphase_view").show();
		   	$("#fromphase").attr("value",'');
		   	$("#tophase").attr("value",'');

		   });
		   //Function to display tbphase dates
		   $(".tbphase").change(function() {
		   	var tbpase = $(this).val();
		   	$("#fromphase").attr("value",'');
		   	$("#tophase").attr("value",'');
		   	if(tbpase ==3) {
		   		$("#fromphase_view").hide();
		   		$("#tophase_view").show();
		   		$("#tb").val(0);
		   	} 
		   	else if(tbpase==0){
		   		$("#fromphase_view").hide();
		   		$("#tophase_view").hide();
		   	}else {
		   		$("#fromphase_view").show();
		   		$("#tophase_view").show();
		   	}
		   });
		   
		   //Function to display datepicker for tb fromphase
		   $("#fromphase").datepicker({
		   	maxDate : "0D",
		   	dateFormat : $.datepicker.ATOM,
		   	changeMonth : true,
		   	changeYear : true
		   });

			//Function to display datepicker for tb tophase
			$("#tophase").datepicker({
				dateFormat : $.datepicker.ATOM,
				changeMonth : true,
				changeYear : true
			});
                        //remove the validator class error
                        $("select,input").on('change',function(i,v){
                        	var value=$(this).val();
                        	var id=this.id;
                        	if(value !=''){ 
                        		if(id=="height"){
                        			$("#surface_area").validationEngine('hide');
                        		}
                        		$('#'+id).validationEngine('hide');
                        	}
                        });

			//Function to calculate date ranges for tb stages
			$("#fromphase").change(function(){
				var from_date=$(this).val();
				var new_date=new Date(from_date);
				var to_date=new Date();
				var category=$("#tbcategory").val();
				var tbphase=$(".tbphase").val();
				if (category==1) {
					if(tbphase==1){
					  	//Intensive
					  	var numberOfDaysToAdd=90;
					  }else if(tbphase==2){
					  	//Continuation
					  	var numberOfDaysToAdd=112;
					  } 
					}else if (category==2) {
						if(tbphase==1){
					  	//Intensive
					  	var numberOfDaysToAdd=90;
					  }else if(tbphase==2){
					  	//Continuation
					  	var numberOfDaysToAdd=150;
					  }
					}
					var start_date = new Date(new_date.getFullYear(), new_date.getMonth(), new_date.getDate());
					var start_date_timestamp = start_date.getTime();
					var end_date_timestamp = (1000 * 60 * 60 * 24 * numberOfDaysToAdd) + start_date_timestamp;
					$("#tophase").datepicker('setDate', new Date(end_date_timestamp));
				});
			
			//Function to configure multiselect in family planning,other chronic illnesses and drug allergies
			$("#family_planning").multiselect().multiselectfilter();
			$("#other_illnesses").multiselect().multiselectfilter();
			$("#drug_allergies").multiselect().multiselectfilter();
			$("#drug_prophylaxis").multiselect().multiselectfilter();
			
			//On Select Drug Prophylaxis
			$("#isoniazid_view").css("display","none");
			$("#drug_prophylaxis").on("multiselectclick", function(event, ui) { 
				$("#isoniazid_view").css("display","none");
				var array_of_checked_values = $("select#drug_prophylaxis").multiselect("getChecked").map(function(){
					return this.value;    
				}).get();
				$("select#drug_prophylaxis").multiselect("widget").find("input[value='1']").attr("disabled",false); 
				$("select#drug_prophylaxis").multiselect("widget").find("input[value='2']").attr("disabled",false); 
				//loop through values
				$.each(array_of_checked_values,function(i,v){
					if(v==1){
						//disable 2
						$("select#drug_prophylaxis").multiselect("widget").find(":checkbox[value='1']").each(function(){
							$("select#drug_prophylaxis").multiselect("widget").find("input[value='2']").attr("disabled",true);
						});
					}else if(v==2){
						//disable 1
						$("select#drug_prophylaxis").multiselect("widget").find(":checkbox[value='1']").each(function(){
							$("select#drug_prophylaxis").multiselect("widget").find("input[value='1']").attr("disabled",true); 
						});
					}
					//If Isoniazid is chosen, show isoniazid start and end dates.
					else if (v==3) {
						$("select#drug_prophylaxis").multiselect("widget").find(":checkbox[value='1']").each(function(){

							$("#isoniazid_view").css("display","block");

						});
					}


				});
			});
            //Isoniazid start and end dates
            $("#iso_start_date").datepicker({
            	maxDate : "0D",
            	dateFormat : $.datepicker.ATOM,
            	changeMonth : true,
            	changeYear : true
            });
            $("#iso_end_date").datepicker({
            	minDate : "0D",
            	dateFormat : $.datepicker.ATOM,
            	changeMonth : true,
            	changeYear : true
            });

			//To Disable Textareas
			$("textarea[name='other_chronic']").not(this).attr("disabled", "true");
			$("textarea[name='other_drugs']").not(this).attr("disabled", "true");
			$("textarea[name='other_allergies_listing']").not(this).attr("disabled", "true");
			$("textarea[name='support_group_listing']").not(this).attr("disabled", "true");

			
			
			//Function to enable textareas for other chronic illnesses
			$("#other_other").change(function() {
				var other = $(this).is(":checked");
				if(other){
					$("textarea[name='other_chronic']").not(this).removeAttr("disabled");
				}else{
					$("textarea[name='other_chronic']").not(this).attr("disabled", "true");
				}
			});
			
			//Function to enable textareas for other allergies
			$("#other_drugs_box").change(function() {
				var other = $(this).is(":checked");
				if(other){
					$("textarea[name='other_drugs']").not(this).removeAttr("disabled");
				}else{
					$("textarea[name='other_drugs']").not(this).attr("disabled", "true");
				}
			});
			
			//Function to enable textareas for other allergies
			$("#other_allergies").change(function() {
				var other = $(this).is(":checked");
				if(other){
					$("textarea[name='other_allergies_listing']").not(this).removeAttr("disabled");
				}else{
					$("textarea[name='other_allergies_listing']").not(this).attr("disabled", "true");
				}
			});
			
			$("#iso_start_date").change(function(){
				var endDate =new  Date($("#iso_start_date").val());
				var numberOfDaysToAdd = 168;
				endDate.setDate(endDate.getDate() + numberOfDaysToAdd); 
				var end_date = (endDate.getFullYear() + '-' + ("0" + (endDate.getMonth() + 1)).slice(-2) + '-' + ("0" + (endDate.getDate())).slice(-2));
				$("#iso_end_date").val(end_date);
				
			});
			
			//Function to enable textareas for support group
			$("#support_group").change(function() {
				var other = $(this).is(":checked");
				if(other){
					$("textarea[name='support_group_listing']").not(this).removeAttr("disabled");
				}else{
					$("textarea[name='support_group_listing']").not(this).attr("disabled", "true");
				}
			});
			
			$("input[name='save']").click(function(){
				var direction=$(this).attr("direction");
				$("#direction").val(direction);
			});



		});
	   //Function to calculate BSA
	   function getMSQ() {
	   	var weight = $('#weight').attr('value');
	   	var height = $('#height').attr('value');
	   	var MSQ = Math.sqrt((parseInt(weight) * parseInt(height)) / 3600);
	   	$('#surface_area').attr('value', MSQ);


	   	var BMI = (parseInt(weight) / (parseInt(height)/100 * parseInt(height)/100));
	   	$('#start_bmi').attr('value', BMI);

	   }



	    //Function to validate required fields
	    function processData(form){ 
	    	var form_selector = "#" + form;
	    	var validated = $(form_selector).validationEngine('validate');
	    	var family_planning = $("select#family_planning").multiselect("getChecked").map(function() {
	    		return this.value;
	    	}).get();
	    	var other_illnesses = $("select#other_illnesses").multiselect("getChecked").map(function() {
	    		return this.value;
	    	}).get();
	    	var drug_allergies=$("select#drug_allergies").multiselect("getChecked").map(function() {
	    		return this.value;
	    	}).get();
	    	var drug_prophylaxis=$("select#drug_prophylaxis").multiselect("getChecked").map(function() {
	    		return this.value;
	    	}).get();
	    	$("#family_planning_holder").val(family_planning);
	    	$("#other_illnesses_holder").val(other_illnesses);
	    	$("#drug_allergies_holder").val(drug_allergies);
	    	$("#drug_prophylaxis_holder").val(drug_prophylaxis);
	    	if(!validated) {
	    		return false;
	    	}else{

	    		$(".btn").attr("disabled","disabled");
	    		return true;
	    	}
	    }

		//function to check if pregnant if female and above 14(vicky)
		function showDiv(elem){
			var gender = $("#gender option:selected").text().toLowerCase();
			var dob = new Date(document.getElementById('dob').value);
			var today = new Date();
			var age_in_years = Math.floor((today - dob) / (365.25 * 24 * 60 * 60 * 1000));
			var pregnantDiv = document.getElementById('pregnant_view');
			var pregnant = $("#pregnant");
			var breastfeeding = $("#breastfeeding");

			//Ensure is 'female' and age >= 14
			if(age_in_years >= 14 && gender == 'female'){
				pregnantDiv.style.display = "inline-block";
			}else{
				pregnantDiv.style.display = "none";
				pregnant.val(0);
				breastfeeding.val(0)
			}
		}

	</script>
	<script type="text/javascript" src="<?= base_url()?>assets/scripts/jcook.js"></script>

</head>

<body>

	<div class="full-content" style="background:#80f26d">
		<div id="sub_title" >
			<a href="<?php  echo base_url().'patient_management ' ?>">Patient Listing </a> <i class=" icon-chevron-right"></i> <strong>Add Patients</strong>
			<hr size="1">
		</div>
		<h3>Patient Registration
			<div style="float:right;margin:5px 40px 0 0;width:350px;">
				(Fields Marked with <b><span class='astericks'>*</span></b> Asterisks are required)
			</div></h3>

			<form id="add_patient_form" name="add_patient_form" method="post"  action="<?php echo base_url().'patient_management/save';?>"  >
				<div class="column" id="columnOne">
					<fieldset>
						<legend>
							Patient Information &amp; Demographics
						</legend>
						<div class="max-row">
								<label><span class='astericks'>*</span>Source of Patient</label>
								<select name="source" id="source" class="validate[required]">
									<option value="">--Select--</option>
									<?php
									foreach($sources as $source){
										echo "<option value='".$source['id']."'>".$source['Name']."</option>";	
									}
									?>	
								</select>
							</div>
							<div id="patient_source_listing" class="max-row" style="display:none;">
								<label> Transfer From</label>
								<select name="transfer_source" id="transfer_source" style="width:200px;">
									<option value="">--Select--</option>
									<?php
									foreach($facilities as $facility){
										echo "<option value='".$facility['facilitycode']."'>".$facility['name']."</option>";
									}
									?>		
								</select>
							</div>
							<div class="max-row" id="facility-service">
								<a href="javascript:;;" onclick="setCCC('ART')">ART</a> | <a href="javascript:;;" onclick="setCCC('PREP')">PREP</a> | <a href="javascript:;;" onclick="setCCC('HEI')">HEI</a> | <a href="javascript:;;" onclick="setCCC('PEP')">PEP</a> | <a href="javascript:;;" onclick="setCCC('HEP')">HEP</a>
							</div>
						<div class="max-row">
							<div class="mid-row">
								<label> Medical Record No.</label>
								<input type="text" name="medical_record_number" id="medical_record_number" value="">
							</div>
							<div class="mid-row">
								<label> <span class='astericks'>*</span>Patient Number CCC </label>
								<input type="text"name="patient_number" id="patient_number" class="validate[required]" value="<?=$facility_code.$cs?>" maxlength="11">
							</div>
						</div>
						<div class="max-row">
							<label>Last Name</label>
							<input  type="text"name="last_name" id="last_name" >
						</div>
						<div class="max-row">
							<div class="mid-row">
								<label><span class='astericks'>*</span>First Name</label>
								<input type="text"name="first_name" id="first_name" class="validate[required]">
							</div>

							<div class="mid-row">
								<label>Other Name</label>
								<input type="text"name="other_name" id="other_name">
							</div>
						</div>
						<div class="max-row">
							<div class="mid-row">
								<label><span class='astericks'>*</span>Date of Birth</label>
								<input type="text"name="dob" id="dob" class="validate[required]">
							</div>
							<div class="mid-row">
								<label> Place of Birth </label>
								<select name="pob" id="pob">
									<option value=" ">--Select--</option>
									<?php
									foreach($districts as $district){
										echo "<option value='".$district['id']."'>".$district['Name']."</option>";
									}
									?>
								</select>
							</div>
						</div>
						<div class="max-row match_hidden">
							<label>Match to parent/guardian in ccc?</label>
							<input type="text" name="match_parent" id="match_parent">

						</div>
						<div class="max-row">
							<div class="mid-row">
								<label>Age(Years)</label>
								<input type="text" id="age_in_years" name="age_in_years" disabled="disabled"/>
							</div>
							<div class="mid-row">
								<label>Age(Months)</label>
								<input type="text" id="age_in_months" disabled="disabled"/>
							</div>
						</div>
						<div class="max-row">
							<label><span class='astericks'>*</span>Gender</label>				
							<select name="gender" id="gender" class="validate[required]" onchange="showDiv(this)">
								<option value="">--Select--</option>
								<?php
								foreach($genders as $gender){
									echo "<option value='".$gender['id']."'>".$gender['name']."</option>";
								}
								?>
							</select>
						</div>
						<div class="max-row" id="pregnant_view" style="display:none;">
							<div  class="mid-row" >
								<label id="pregnant_container"> Pregnant?</label>
								<select name="pregnant" id="pregnant">
									<option value="0">No</option><option value="1">Yes</option>
								</select>
							</div>
							<div class="mid-row">
								<label id="pregnant_container"> Breast Feeding?</label>
								<select name="breastfeeding" id="breastfeeding">
									<option value="0">No</option><option value="1">Yes</option>
								</select>
							</div>
						</div>
						<div class="max-row">
							<div class="mid-row">
								<label><span class='astericks'>*</span>Weight (KG)</label>
								<input type="text"name="weight" id="weight" class="validate[required]" onblur="getMSQ()">
							</div>
							<div class="mid-row">
								<label ><span class='astericks'>*</span>Height (CM)</label>
								<input  type="text"name="height" id="height" class="validate[required]" onblur="getMSQ()">
							</div>
						</div>
						<div class="max-row">
							<label><span class='astericks'>*</span> Body Surface Area (MSQ)</label>
							<input type="text" name="surface_area" id="surface_area" value="" readonly="readonly" class="validate[required]">

						</div>
						<div class="max-row">
							<label><span class='astericks'>*</span> Body Mass Index (BMI)</label>
							<input type="text" name="start_bmi" id="start_bmi" value="" readonly="readonly" class="validate[required]">

						</div>

						<div class="max-row">
							<div class="mid-row">
								<label> Patient's Phone Contact(s)</label>
								<input  type="text"  name="phone" id="phone" value="" class="phone" placeholder="e.g 0722123456">
							</div>
							<div class="mid-row">
								<label > Receive SMS Reminders</label>
								<input  type="radio"  name="sms_consent" value="1">
								Yes
								<input  type="radio"  name="sms_consent" value="0" checked="checked">
								No
							</div>

						</div>
						<div class="max-row">
							<label> Patient's Physical Contact(s)</label>
							<textarea name="physical" id="physical" value=""></textarea>
						</div>
						<div class="max-row">
							<label> Patient's Alternate Contact(s)</label>
							<textarea name="alternate" id="alternate" value=""></textarea>
						</div>
						
						<div class="max-row">
							<label>Does Patient belong to any support group?</label>
							<label>Yes
								<input type="checkbox" name="support_group" id="support_group" value="">
							</label>

							<div class="list">
								List Them
							</div>
							<textarea class="list_area" name="support_group_listing" id="support_group_listing"></textarea>
						</div>

					</div>

					<div class="column" id="colmnTwo">
						<fieldset>
							<legend>
								Patient History
							</legend>
							<div class="max-row status_hidden">
								<label  id="tstatus"><span class='astericks'>*</span> Partner Status</label>
								<select name="partner_status" id="partner_status" class="validate[required]">
									<option value="">--Select--</option>
									<option value="0" >No Partner</option>
									<option value="1" > Concordant</option>
									<option value="2" > Discordant</option>
									<option value="3" > Unknown</option>
									<option value="4" > Negative</option>
								</select>


							</div>
							<div class="max-row status_hidden">
								<div class="mid-row">
									<label id="dcs" >Disclosure</label>
									<input  type="radio"  name="disclosure" value="1">
									Yes
									<input  type="radio"  name="disclosure" value="0">
									No
								</div>
							</div>
							<div class="max-row match_spouse">
								<label>Match to spouse in this ccc?</label>
								<input type="text" name="match_spouse" id="match_spouse">

							</div>

							<div class="max-row status_hidden">
								<label>Family Planning Method</label>
								<input type="hidden" id="family_planning_holder" name="family_planning_holder" />
								<select name="family_planning" id="family_planning" multiple="multiple" style="width:400px;" >
									<?php
									foreach($family_planning as $fplan){
										echo "<option value='".$fplan['indicator']."'>"." ".$fplan['name']."</option>";
									}
									?>
								</select>

							</div>
							<div class="max-row">
								<label>Does Patient have other Chronic illnesses</label>
								<input type="hidden" id="other_illnesses_holder" name="other_illnesses_holder" />
								<select name="other_illnesses" id="other_illnesses"  multiple="multiple" style="width:400px;">
									<?php
									foreach($other_illnesses as $other_illness){
										echo "<option value='".$other_illness['indicator']."'>"." ".$other_illness['name']."</option>";
									}
									?>	
								</select>
							</div>
							<div class="max-row">
								If <b>Other Illnesses</b> 
								<br/>Click Here <input type="checkbox" name="other_other" id="other_other" value=""> 
								<br/>List Them Below (Use Commas to separate) 

								<textarea  name="other_chronic" id="other_chronic"></textarea>
							</div>
							<div class="max-row">
								<label> List Other Drugs Patient is Taking </label>
								<label>Yes
									<input type="checkbox" name="other_drugs_box" id="other_drugs_box" value="">
								</label>

								<label>List Them</label>
								<textarea name="other_drugs" id="other_drugs"></textarea>
							</div>
							<div class="max-row">
								<label>Drugs Allergies/ADR</label>
								<input type="hidden" id="drug_allergies_holder" name="drug_allergies_holder" />
								<select name="drug_allergies" id="drug_allergies"  multiple="multiple" style="width:400px;">
									<?php
									foreach($drugs as $drug){
										echo "<option value='-".$drug['id']."-'>"." ".$drug['Drug']."</option>";
									}
									?>	
								</select>
							</div>
							<br>
							<div class="max-row">
								<label>Does Patient have any other Drugs Allergies/ADR not listed?</label>
								<label>Yes
									<input type="checkbox" name="other_allergies" id="other_allergies" value="">
								</label>

								<label>List Them</label>
								<textarea class="list_area" name="other_allergies_listing" id="other_allergies_listing"></textarea>
							</div>
							<div class="max-row">
								<div class="mid-row">
									<label > Does Patient <br/>Smoke?</label>
									<select name="smoke" id="smoke">
										<option value="0" selected="selected">No</option>
										<option value="1">Yes</option>
									</select>
								</div>
								<div class="mid-row">
									<label> Does Patient Drink Alcohol?</label>
									<select name="alcohol" id="alcohol">
										<option value="0" selected="selected">No</option>
										<option value="1">Yes</option>
									</select>
								</div>
							</div>
							<div class="max-row">
								<div class="mid-row">
									<label> Has Patient been <br/>tested for TB?</label>
									<select name="tested_tb" id="tested_tb" class="tested_tb">
										<option value="0" selected="selected">No</option>
										<option value="1">Yes</option>
									</select>
								</div>
								<div class="mid-row">
									<label> Does Patient Have TB?</label>
									<select name="tb" id="tb" class="tb">
										<option value="0" selected="selected">No</option>
										<option value="1">Yes</option>
									</select>
								</div>
							</div>

							<div class="max-row">
								<div class="mid-row" id="tbcategory_view">
									<label> Select TB category</label>
									<select name="tbcategory" id="tbcategory" class="tbcategory">
										<option value="0" selected="selected">--Select One--</option>
										<option value="1">Category 1</option>
										<option value="2">Category 2</option>
									</select>
								</div>

								<div class="mid-row" id="tbphase_view" style="display:none;">
									<label id="tbstats"> TB Phase</label>
									<select name="tbphase" id="tbphase" class="tbphase">
										<option value="0" selected="selected">--Select One--</option>
										<option value="1">Intensive</option>
										<option value="2">Continuation</option>
										<option value="3">Completed</option>
									</select>
								</div>
							</div>
							<div class="max-row">
								<div class="mid-row" id="fromphase_view" style="display:none;">
									<label id="ttphase">Start of Phase</label>
									<input type="text" name="fromphase" id="fromphase" value=""/>
								</div>
								<div class="mid-row" id="tophase_view" style="display:none;">
									<label id="endp">End of Phase</label>
									<input type="text" name="tophase" id="tophase" value=""/>
								</div>
							</div>
						</fieldset>
					</div>
					<div class="column" id="columnThree">
						<fieldset>
							<legend>
								Patient Information
							</legend>
							<div class="max-row">
								<label><span class='astericks'>*</span>Date Patient Enrolled</label>
								<input type="text" name="enrolled" id="enrolled" value="" class="validate[required]">
							</div>
							<div class="max-row">
								<label><span class='astericks'>*</span>Current Status</label>
								<select name="current_status" id="current_status" class="validate[required]">
									<option value="">--Select--</option>
									<?php
									foreach($statuses as $status){
										if(strtolower($status['Name'])=="active"){
											echo "<option selected='selected' value='".$status['id']."'>".$status['Name']."</option>";											
										}else{
											echo "<option value='".$status['id']."'>".$status['Name']."</option>";
										}
									}
									?>	
								</select>
							</div>
							<div class="max-row">
								<label><span class='astericks'>*</span>Type of Service</label>
								<select name="service" id="service" class="validate[required]">
									<option value="">--Select--</option>
									<?php
									foreach($service_types as $service_type){
										echo "<option value='".$service_type['id']."'>".$service_type['Name']."</option>";
									}
									?>	
								</select> </label>
							</select>
						</div>
						
						<div class="max-row" id="pep_reason_listing" style="display:none;">
							<label><span class='astericks'>*</span>PEP Reason</label>
							<select name="pep_reason" id="pep_reason">
								<option value="">--Select--</option>
								<?php
								foreach($pep_reasons as $reason){
									echo "<option value='".$reason['id']."'>".$reason['name']."</option>";
								}
								?>	
							</select> </label>
						</select>
					</div>

					<div class="max-row" id="prep_reason_listing" style="display:none;">
						<label><span class='astericks'>*</span>PREP Reason</label>
						<select name="prep_reason" id="prep_reason">
							<option value="">--Select--</option>
							<?php
							foreach($prep_reasons as $reason){
								echo "<option value='".$reason['id']."'>".$reason['name']."</option>";
							}
							?>	
						</select> </label>
					</select>
				</div>

				<div class="max-row" id="prep_test_question" style="display:none;">
					<div class="max-row">
						<label>Have you been Tested?</label>
						<select name="prep_test_answer" id="prep_test_answer">
							<option value="0">No</option>
							<option value="1">Yes</option>
						</select>
					</div>
					<div class="mid-row" id="prep_test_date_view" style="display:none">
						<label>Test Date</label>
						<input type="text" name="prep_test_date" id="prep_test_date">
					</div>
					<div class="mid-row" id="prep_test_result_view" style="display:none">
						<label>Was the Test Positive?</label>
						<select name="prep_test_result" id="prep_test_result">
							<option value="0">No</option>
							<option value="1">Yes</option>
						</select>
					</div>
				</div>

				<div class="max-row">
					<label id="start_of_regimen"><span class='astericks'>*</span>Start Regimen </label>
					<select name="regimen" id="regimen" class="validate[required] start_regimen" >
						<option value="">--Select One--</option>

					</select>

				</div>
				<div class="max-row" id="servicestartedcontent">
					<label id="date_service_started">Start Regimen Date</label>
					<input type="text" name="service_started" id="service_started" value="">
				</div>
				<div class="max-row" id="who_listing">
					<label>WHO Stage</label>
					<select name="who_stage" id="who_stage" class="who_stage" >
						<option value="">--Select One--</option>
						<?php
						foreach($who_stages as $stages){
							echo "<option value='".$stages['id']."'>".$stages['name']."</option>";
						}
						?>					
					</select>
				</div>
				<div class="max-row" id="drug_prophylax">
					<label>Drug Prophylaxis</label>
					<input type="hidden" id="drug_prophylaxis_holder" name="drug_prophylaxis_holder" />
					<select name="drug_prophylaxis" id="drug_prophylaxis" multiple="multiple" style="width:300px;" >
						<?php
						foreach($drug_prophylaxis as $prohylaxis){
							echo "<option value='".$prohylaxis['id']."'>".$prohylaxis['name']."</option>";
						}
						?>	
					</select>
				</div>

				<div class="max-row" id="isoniazid_view">
					<div class="mid-row" id="isoniazid_start_date_view">
						<label>Isoniazid Start Date</label>
						<input type="text" name="iso_start_date" id="iso_start_date"  style="color:red"/>
					</div>
					<div class="mid-row" id="isoniazid_end_date_view">
						<label> Isoniazid End Date</label>
						<input  type="text"name="iso_end_date" id="iso_end_date" style="color:red">
					</div>								
				</div>

			</fieldset>
		</div>
		<div class="button-bar btn_positioning" >
			<div class="btn-group " style="float:right;" >
				<input type="hidden" name="direction" id="direction" />
				<input form="add_patient_form"  class="btn actual button_size" direction="0"  value="Save" name="save"  />
				<input form="add_patient_form"  class="btn actual button_size" direction="1" value="Dispense" name="save"/>
				<input type="reset"  class="btn btn-danger button_size" value="Reset" />
			</div>

		</div>


	</form>
</div>
</body>
</html>