<?php
/**
 * Using Session Data
 */
if (!$this->session->userdata('user_id') && $content_view != 'resend_password_v') {
    redirect("User_Management/login");
}
if (!isset($link)) {
    $link = null;
}
//Code to loop through all the menus available to this user!
//Fet the current domain
$menus = $this->session->userdata('menu_items');
$current = $this->router->class;
$counter = 0;

$ccc_stores = $this->session->userdata('ccc_store');
$actual_page = $this->uri->segment(1);
if ($this->uri->segment(2) != "") {
    $actual_page .= "/" . $this->uri->segment(2);
}
if ($this->uri->segment(3) != "") {
    $actual_page .= "/" . $this->uri->segment(3);
}
if ($this->uri->segment(4) != "") {
    $actual_page .= "/" . $this->uri->segment(4);
}
if ($this->uri->segment(5) != "") {
    $actual_page .= "/" . $this->uri->segment(5);
}
if ($this->uri->segment(6) != "") {
    $actual_page .= "/" . $this->uri->segment(6);
}
if ($this->uri->segment(7) != "") {
    $actual_page .= "/" . $this->uri->segment(7);
}


/*
 * Manage Actual Page When auto logged out
 * Check prev page session is set
 * if(present)check if actual page cookie exist and unset prev_page session
 * if cookie exists redirect to cookie
 * if cookie does not exists set cookie to current url
 * if(not present)go to current url
 * 
 */

if ($this->session->userdata("prev_page") != '') {
    $this->session->set_userdata("prev_page", "");
    if ($this->input->cookie("actual_page") != '') {
        $actual_page = $this->input->cookie("actual_page");
        redirect($actual_page);
    } else {
        $this->input->set_cookie("actual_page", $actual_page, 3600);
    }
} else {
    $this->input->set_cookie("actual_page", $actual_page, 3600);
}

$access_level = $this->session->userdata('user_indicator');
$user_is_administrator = false;
$user_is_facility_administrator = false;
$user_is_nascop = false;
$user_is_pharmacist = false;

if ($access_level == "system_administrator") {
    $user_is_administrator = true;
} else if ($access_level == "facility_administrator") {
    $user_is_facility_administrator = true;
} else {
    $user_is_pharmacist = true;
}
?>


<!DOCTYPE html">
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title><?php echo $title; ?></title>


        <?php
        $this->load->view('sections/head');
        if ($user_is_pharmacist || $user_is_facility_administrator || $user_is_administrator) {
            //echo "<script src=\"" . base_url() . "Scripts/offline_database.js\" type=\"text/javascript\"></script>";
        }

        /**
         * Load View with Head Section
         */
        if (isset($script_urls)) {
            foreach ($script_urls as $script_url) {
                echo "<script src=\"" . $script_url . "\" type=\"text/javascript\"></script>";
            }
        }
        ?>

        <?php
        if (isset($scripts)) {
            foreach ($scripts as $script) {
                echo "<script src=\"" . base_url() . "Scripts/" . $script . "\" type=\"text/javascript\"></script>";
            }
        }

        if (isset($styles)) {
            foreach ($styles as $style) {
                echo "<link href=\"" . base_url() . "CSS/" . $style . "\" type=\"text/css\" rel=\"stylesheet\"/>";
            }
        }
        ?> 
        <script type="text/javascript" src="<?= base_url(); ?>assets/scripts/parsely.js"></script>
        <script>

            $(document).ready(function () {

                setTimeout(function () {
                    $(".message").fadeOut("2000");
                }, 5000)

                setTimeout(function () {
                    $(".message").fadeOut("2000");
                }, 5000)

<?php
$message = $this->session->flashdata('message');
$user_id = $this->session->userdata("user_id");
echo $message;
if ($message == 0) {
    ?><?php
    $message = 1;
}
if ($user_is_pharmacist || $user_is_facility_administrator) {
    ?>
                    $('#span1').load('<?php echo base_url() . 'notification_management/error_notification'; ?>');
                    $('#span2').load('<?php echo base_url() . 'notification_management/reporting_notification'; ?>');
                    $('#span3').load('<?php echo base_url() . 'notification_management/defaulter_notification'; ?>');
                    $('#span4').load('<?php echo base_url() . 'notification_management/missed_appointments_notification'; ?>');
                    $('#span5').load('<?php echo base_url() . 'notification_management/followup_notification'; ?>');
                    $('#span6').load('<?php echo base_url() . 'notification_management/prescriptions_notification_view'; ?>');
                    $('#span7').load('<?php echo base_url() . 'notification_management/update_notification'; ?>');

                    $('#span3').load('<?php echo base_url() . 'notification_management/ontime_notification'; ?>');
                    $('#span4').load('<?php echo base_url() . 'notification_management/missed_appointments_notification'; ?>');
                    $('#span5').load('<?php echo base_url() . 'notification_management/defaulter_notification'; ?>');
                    $('#span6').load('<?php echo base_url() . 'notification_management/followup_notification'; ?>');
                    $('#span7').load('<?php echo base_url() . 'notification_management/prescriptions_notification_view'; ?>');
                    $('#span8').load('<?php echo base_url() . 'notification_management/update_notification'; ?>');

    <?php
}
if ($user_is_administrator) {
    ?>
                    $('#span1').load('<?php echo base_url() . 'admin_management/inactive_users'; ?>');
                    $('#span2').load('<?php echo base_url() . 'admin_management/online_users'; ?>');
    <?php
}
?>
                /*Perform auto update when online*/
                var status = navigator.onLine;
                if (status) {
                    autoUpdate();
                }
                //Load scripts for system search
                jQuery.getScript("<?php echo base_url(); ?>assets/scripts/settings.js")
            });
        </script>
        <script>
            $(document).ready(function () {
                $(".error").css("display", "block");

                //If screen resolution is less than 1280, change css
                width = screen.width,
                        height = screen.height;
                if (height < 700) {
                    $(".welcome_msg").css("margin-top", "3.6%");
                    $("#row_container").css("padding-top", "14%");
                    $("#row_container").css("padding-bottom", "8%");
                } else {
                    $("#row_container").css("padding-top", "8.8%");
                    if (width > 1024) {
                        if (width == 1366 && height == 768) {
                            $(".welcome_msg").css("margin-top", "3.9%");
                        } else if (height == 900) {
                            $(".welcome_msg").css("margin-top", "4%")
                        } else if (height >= 1080) {
                            $(".welcome_msg").css("margin-top", "2.5%");
                        } else {
                            $(".welcome_msg").css("margin-top", "3%");
                        }
                    } else {
                        $(".welcome_msg").css("margin-top", "4%")
                    }

                }
            });

        </script>
        <?php
//Load tableTools for datatables printing and exporting
        if (isset($report_title)) {
            ?>
            <style type="text/css" title="currentStyle">
                @import "<?php echo base_url() . 'assets/styles/datatable/demo_page.css'; ?>";
                @import "<?php echo base_url() . 'assets/styles/datatable/demo_table.css'; ?>";
                @import "<?php echo base_url() . 'assets/styles/datatable/TableTools.css' ?>";
                ";
            </style>
            <script type="text/javascript" charset="utf-8" src="<?php echo base_url() . 'assets/Scripts/datatable/ZeroClipboard.js' ?>"></script>
            <script type="text/javascript" charset="utf-8"  src="<?php echo base_url() . 'assets/Scripts/datatable/TableTools.js' ?>"></script>

            <?php
        }
        ?>      
        <style>
            .setting_table {
            font-size: 0.8em;
            }
            .input-append .btn{
            margin:0px;
            }
        </style>
    </head>

    <body>
        <div id="top-panel" class="navbar navbar-fixed-top">
            <div class="navbar-inner" style="background:white">
                <div class="container-fluid" style="padding-bottom:1.2em;">
                    <button type="button" class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                        <span class="icon-bar"></span>
                    </button>
                    <div class="top_logo" style="background: url('<?php echo base_url(); ?>assets/images/top_logo.png') no-repeat;">
                        <div id="system_title">
                            <?php
                            $this->load->view('sections/banner');
                            ?>
                            <div id="facility_name">
                                <span><?php echo $this->session->userdata('facility_name'); ?></span>
                            </div>
                            <div class="banner_text">
                                <?php echo $banner_text; ?>
                            </div>
                        </div>
                    </div>
                    <div class="nav-collapse collapse" style="float: right">
                        <?php if ($menus) { ?>
                            <ul class="nav nav_header" style="margin: 0 !important;">
                                <li><a href="<?php echo site_url('home_controller'); ?>" class="top_menu_link  first_link <?php
                                    if ($current == "home_controller") {
                                        echo " top_menu_active ";
                                    }
                                    ?>"><i class="icon-home"></i> HOME </a></li>
                                       <?php
                                       foreach ($menus as $menu) {
                                           if (strtoupper($menu['text']) == "UPDATE") {
                                               $facility = $this->session->userdata("facility");
                                               $update_link = base_url() . 'github/index/' . $facility;
                                               $update_link = str_replace("ADT", "UPDATE", $update_link);
                                               ?>
                                        <li><a href ="<?php echo $update_link; ?>" target="_blank" class="top_menu_link"><?php echo strtoupper($menu['text']); ?></a></li>
                                        <?php
                                    } else {
                                        ?>
                                        <li> <a href = "<?php echo site_url($menu['url']); ?>" class="top_menu_link <?php
                                            if ($current == $menu['url'] || $menu['url'] == $link) {
                                                echo " top_menu_active ";
                                            }
                                            ?>">
                                                <?php echo strtoupper($menu['text']); ?></a></li>
                                        <?php
                                        $counter++;
                                    }
                                }
                                ?>
                                <li class="dropdown" id="div_profile" >
                                    <a href="#" class="dropdown-toggle top_menu_link" data-toggle="dropdown"><i class="icon-user icon-black"></i> PROFILE   <span class="caret"></span></a>
                                    <ul class="dropdown-menu" id="profile_list" role="menu">
                                        <li><a href="#edit_user_profile" data-toggle="modal"><i class="icon-edit"></i> Edit Profile</a></li>
                                        <li id="change_password_link"><a href="#user_change_pass" data-toggle="modal"><i class=" icon-asterisk"></i> Change Password</a></li>
                                    </ul>
                                </li>
                            <?php } ?>
                        </ul>
                    </div><!--/.nav-collapse -->
                    <div class="welcome_msg">
                        <span><font style="color:#B8B8B8; text-transform: uppercase; font-weight: bold;" >username: </font><b style="font-weight: bold;font-size: 15px;text-transform: uppercase;"><?php echo $this->session->userdata('full_name'); ?> | </b><a id="logout_btn" href="<?php echo base_url() . 'user_management/logout/2' ?>"><i class="icon-off"></i> Logout</a></span>
                        <br>
                            <span class="date"><?php echo date('l, jS F Y') ?></span>
                            <input type="hidden" id="facility_hidden" />
                            <input type="hidden" id="base_url" value="<?php echo base_url(); ?>"/>
                    </div>
                </div>
            </div>
        </div>


        <?php
//Load validation settings for reports
        if (isset($reports) || isset($report_title)) {
            ?>
            <style type="text/css">
                .full-content{
                font-size:1.2em;
                }
                select{
                font-size:0.9em;
                }
                table.dataTable {
                zoom:1;
                letter-spacing: 2px;
                font-weight:bold;
                }
            </style>
            <script type="text/javascript">
            $(document).ready(function () {
                $("select,input").css("font-weight", "bold");
                $("select").css("width", "auto");
                $("select").css("height", "30px");
                $("input").css("height", "30px");

                window.addEventListener("load", function () {

                    // does the actual opening
                    function openWindow(event) {
                        event = event || window.event;

                        // find the url and title to set
                        var href = this.getAttribute("href");
                        var newTitle = this.getAttribute("data-title");
                        // or if you work the title out some other way...
                        // var newTitle = "Some constant string";

                        // open the window
                        var newWin = window.open(href, "_blank");

                        // add a load listener to the window so that the title gets changed on page load
                        newWin.addEventListener("load", function () {
                            newWin.document.title = newTitle;
                        });

                        // stop the default `a` link or you will get 2 new windows!
                        event.returnValue = false;
                    }

                    // find all a tags opening in a new window
                    var links = document.querySelectorAll("a[target=_blank][data-title]");
                    // or this if you don't want to store custom titles with each link
                    //var links = document.querySelectorAll("a[target=_blank]");

                    // add a click event for each so we can do our own thing
                    for (var i = 0; i < links.length; i++) {
                        links[i].addEventListener("click", openWindow.bind(links[i]));
                    }

                });
            });
            </script>
            <?php
        }
        ?>


        <?php
        if ($this->session->userdata("message_user_update_success")) {
            ?>
            <script type="text/javascript">
                setTimeout(function () {
                    $("#msg_user_update").fadeOut("2000");
                }, 6000)

            </script>
            <div id="msg_user_update"><?php echo $this->session->userdata("message_user_update_success"); ?></div>
            <?php
            $this->session->unset_userdata('message_user_update_success');
        }
        if (!isset($hide_side_menu)) {
            ?>
            <div class="container-fluid">
                <div class="row-fluid" id="row_container" style="padding-top:10.5%">
                    <div class="span3">
                        <div class="left-content">

                            <h3>Quick Links</h3>
                            <ul class="nav nav-list well">
                                <?php
                                if ($user_is_pharmacist || $user_is_facility_administrator) {
                                    ?>
                                    <li><a href="<?php echo base_url() . 'patient_management/addpatient_show' ?>"><i class="icon-user"></i>Add Patients</a></li>

                                    <?php
                                    $count_ccc = count($ccc_stores);
                                    if ($count_ccc > 0) {
                                        ?>
                                        <li class="dropdown-submenu">
                                            <a tabindex="-1" href="#"><i class="icon-plus"></i>Stock Transactions</a>
                                            <ul class="dropdown-menu">
                                                <?php
                                                foreach ($ccc_stores as $ccc_store) {
                                                    ?>
                                                    <li><a href="<?php echo base_url() . 'inventory_management/stock_transaction/' . $ccc_store['id']; ?>"><i class="icon-inbox"></i>Receive/Issue - <?php echo $ccc_store['Name']; ?></a></li>
                                                    <?php
                                                }
                                                ?>
                                            </ul>
                                        </li>
                                        <li><a href="<?php echo base_url() . 'inventory_management/pqmp/' ?>"><i class="icon-flag"></i>Pharmacovigilance</a></li>

                                        <li>
                                            <a href="<?php echo base_url() . 'home_controller/get_faq'; ?>" target="_blank"><i class="icon-question-sign"></i>FAQ</a>
                                        </li>
                                        <?php
                                    } else {//If no Extra pharmacies, load main store and Main Pharmacy Only
                                        ?>
                                        <li><a href="<?php echo base_url() . 'inventory_management/stock_transaction/1' ?>"><i class="icon-inbox"></i>Receive/Issue - Main Store</a></li>
                                        <li><a href="<?php echo base_url() . 'inventory_management/stock_transaction/2' ?>"><i class="icon-inbox"></i>Receive/Issue - Pharmacy</a></li>
                                        <?php
                                    }
                                    ?>

                                    <li class="divider"></li>
                                    <li><a href="<?php echo base_url() . 'facilitydashboard_management/getPatientMasterList'  ?>"  id="ReportGenerator"> <i class="icon-book"></i>Patient Master list</a></li>           
                                    <li><a href="<?php echo base_url() . 'assets/manuals/user_manual.pdf' ?>" target="_blank"><i class="icon-book"></i>User Manual</a></li> 


                                    <?php
                                }if ($user_is_administrator) {
                                    ?>
                                    <li>
                                        <a  id="addCounty" class="admin_link"><i class="icon-eye-open icon-black"></i>View Counties</a>
                                    </li>
                                    <li>
                                        <a  id="addSatellite" class="admin_link"><i class="icon-eye-open icon-black"></i>View Satellites</a>
                                    </li>
                                    <li>
                                        <a  id="addFacility" class="admin_link"><i class="icon-eye-open icon-black"></i>View Facilities</a>
                                    </li>
                                    <li>
                                        <a  id="addDistrict" class="admin_link"><i class="icon-eye-open icon-black"></i>View Districts</a>
                                    </li>
                                    <li>
                                        <a  id="addMenu" class="admin_link"><i class="icon-eye-open icon-black"></i>View Menus</a>
                                    </li>
                                    <li>
                                        <a id="addFAQ" class="admin_link"><i class="icon-eye-open icon-black"></i>View FAQ</a>
                                    </li>
                                    <li>
                                        <a  id="addAccessLevel" class="admin_link"><i class="icon-eye-open icon-black"></i>View Access Levels</a>
                                    </li> 
                                    <li>
                                        <a  id="addUsers" class="admin_link"><i class="icon-user"></i>View Users</a>
                                    </li> 
                                    <li class="divider"></li>
                                    <li>
                                        <a  id="assignRights" class="admin_link"><i class="icon-cog"></i>Assign User Rights</a>
                                    </li>
                                    <li>
                                        <a  id="getAccessLogs" class="admin_link"><i class="icon-book"></i>Access Logs</a>
                                    </li>
                                    <li>
                                        <a  id="getDeniedLogs" class="admin_link"><i class="icon-book"></i>Denied Logs</a>
                                    </li>
                                    <li>
                                        <a href="<?php echo base_url() . 'assets/manuals/user_manual.pdf' ?>"><i class="icon-book"></i>User Manual</a>
                                    </li>   
                                    <?php
                                }
                                ?>


                            </ul>
                            <h3>Notifications</h3>
                            <ul id="notification1" class="nav nav-list well">
                                <?php
                                if ($user_is_administrator) {
                                    ?>
                                    <li><a id='online' class='admin_link'><i class='icon-signal'></i>Online Users <div id="span2" class='badge badge-important'></div></a></li>
                                    <li><a id='inactive' class='admin_link'><i class='icon-th'></i>Deactivated Users <div id="span1" class='badge badge-important'></div></a></li>
                                    <li id="span3"></li>
                                    <?php
                                } else {
                                    ?>
                                    <li id="span1"></li>                                    
                                    <li id="span2"></li>
                                    <li id="span3"></li>
                                    <li id="span4"></li>
                                    <li id="span5"></li>
                                    <li id="span6"></li>
                                    <li id="span7"></li>
                                    <li id="span8"></li>

                                    <?php
                                }
                                ?>
                            </ul>   
                        </div>
                    </div>
                    <div class="span9">
                        <?php
                    }
                    $this->load->view($content_view);
//Load modals view
                    $this->load->view('sections/modals_v');
//Load modals view end
                    ?>
                </div>
            </div>
        </div>

        <!-- This sets css for display length in reports -->
        <script type="text/javascript">
            $(document).ready(function () {
                $("div.dataTables_length select").css("width", "13%");



            })
        </script>   
        <div class="row-fluid">
            <footer id="bottom_ribbon">
                <script>

                </script>
                <div class="container-fluid">
                    <div class="row-fluid">
                        <div id="footer_text2" class="span12" style="text-align:center">
                            Government of Kenya &copy; <?php echo date('Y'); ?>.
                            All Rights Reserved . <strong>Web-ADT version <?= $this->config->item('adt_version'); ?></strong>
<?php  if (str_replace('.', '', $update_available->release) + 0  > str_replace('.', '', $this->config->item('adt_version')) + 0  ){?>
                            <a class="badge badge-warning" href="#adt_update_modal" data-toggle="modal">New Update available</div>
                            <?php } ?>
                        </div>  
                    </div>
                </div>
            </footer>
        </div>

        <!-- Search Modal -->
        <div id="searchModal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
            <form method="post" action="" id="fmSearchSystem" name="fmSearchSystem">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                    <h3 id="myModalLabel">Web ADT Search </h3>
                </div>
                <div class="modal-body">
                    <div class="control-group">
                        <div class="controls">
                            <select id="search_criteria" name="search_criteria" required  class="span3">
                                <option data-cat='patient' value="0" data-dest="patient_management/load_view/details/">Search Patients</option>
                                <?php
                                foreach ($ccc_stores as $ccc_store) {
                                    echo "<option data-cat='drugcode' value='" . $ccc_store['id'] . "' data-id ='" . $ccc_store['id'] . "' data-dest='inventory_management/getDrugBinCard/'>Search drugs (" . $ccc_store['Name'] . ")</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <p></p>
                        <input type="text" id="search_option" name="search_option" class="form-control span6 validate"  style="width:90%; margin-top:2%" placeholder="Enter your search text" required="" />

                    </div>
                </div>
                <div class="modal-footer">
                    <input type="submit" class="btn btn-primary" value="Get details">
                </div>
                <?php echo form_close(); ?>
        </div>
        <!--end Search modal-->
    </body>
</html>