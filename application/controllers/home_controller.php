<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Home_controller extends MY_Controller {

    function __construct() {
        parent::__construct();
    }

    public function index() {

        $this->platform_home();
    }

    public function platform_home() {
        //Check if the user is already logged in and if so, take him to their home page. Else, display the platform home page.
        $user_id = $this->session->userdata('user_id');
        if (strlen($user_id) > 0) {
            redirect("home_controller/home");
        }
        $data = array();
        $data['current'] = "home_controller";
        $data['title'] = "webADT | System Dashboard";
        $data['banner_text'] = "System Dashboard";
        $data['content_view'] = "platform_home_v";
        $this->load->view("template_platform", $data);
    }

    public function home() {
        $this->load->library('updater');
        $rights = User_Right::getRights($this->session->userdata('access_level'));
        $menu_data = array();
        $menus = array();
        $counter = 0;
        foreach ($rights as $right) {
            $menu_data['menus'][$right->Menu] = $right->Access_Type;
            $menus['menu_items'][$counter]['url'] = $right->Menu_Item->Menu_Url;
            $menus['menu_items'][$counter]['text'] = $right->Menu_Item->Menu_Text;
            $menus['menu_items'][$counter]['offline'] = $right->Menu_Item->Offline;
            $counter++;
        }
        $this->session->set_userdata($menu_data);
        $this->session->set_userdata($menus);

        //Check if the user is a pharmacist. If so, update his/her local envirinment with current values
        if ($this->session->userdata('user_indicator') == "pharmacist") {
            $facility_code = $this->session->userdata('facility');
            //Retrieve the Totals of the records in the master database that have clones in the clients!
            $today = date('m/d/Y');
            $timestamp = strtotime($today);
            $data['scheduled_patients'] = Patient_Appointment::getAllScheduled($timestamp);
        }


        //Get CCC Stores if they exist
        $ccc_stores = CCC_store_service_point::getAllActive();
        $this->session->set_userdata('ccc_store', $ccc_stores);

        $data['download_status']=$this->updater->check_ADTRelease_downloaded();
        $data['update_available'] = json_decode($this->updater->check_ADTrelease());
        $data['title'] = "webADT | System Home";
        $data['content_view'] = "home_v";
        $data['banner_text'] = "Home";

        $data['link'] = "home";
        $data['user'] = $this->session->userdata['full_name'];
        $this->load->view("template", $data);
    }

    public function dispensement($user, $period) {
        $results = $this->db->query("SELECT * 
                    FROM `patient_visit` 
                    WHERE `user`='$user' AND DATEDIFF(CURDATE(),dispensing_date) <= '$period' ORDER BY id DESC")->result_array();
        $dyn_table = "<table border='1' width='100%' id='menu_listing'  cellpadding='5' class='dataTables'>";
        $dyn_table .= "<thead><tr><th>Patient ID</th><th>Batch Number</th><th>Date Dispensed</th></tr></thead><tbody>";
        if ($results) {
            foreach ($results as $result) {
                $dyn_table .= "<tr><td>" . $result['patient_id'] . "</td><td>" . $result['batch_number'] . "</td><td>" . $result['dispensing_date'] . "</td></tr>";
            }
        }
        $dyn_table .= "</tbody></table>";

        $data['title'] = "webADT | Drug Dispensement";
        $data['content_view'] = "user_dispensement_log";
        $data['banner_text'] = "Home";
        $data['link'] = "home";
        $data['thetitle'] = ucfirst($user)."'s  Patient Drug Dispensment activity over last $period days";
        $data['over'] = $period;
        $data['user_'] = ucfirst($user);
        $data['content'] = $dyn_table;
        $data['user'] = $this->session->userdata['full_name'];
        $this->load->view("template", $data);
    }

    public function inventory($user, $period) {
        $results = $this->db->query("SELECT dsm.id,dru.drug, dsm.batch_number,dsm.transaction_date,dsm.source,dsm.destination FROM drug_stock_movement dsm LEFT JOIN drugcode dru ON dsm.drug = dru.id WHERE DATEDIFF(CURDATE(),dsm.transaction_date) <= '$period' AND operator='$user' ORDER BY dsm.id DESC")->result_array();
        $dyn_table = "<table border='1' width='100%' id='menu_listing'  cellpadding='5' class='dataTables'>";
        $dyn_table .= "<thead><tr><th>Drug</th><th>Batch Number</th><th>Transaction Date</th><th>From</th><th>To</th></tr></thead><tbody>";
        if ($results) {
            foreach ($results as $result) {
                $dyn_table .= "<tr><td>" . $result['drug'] . "</td><td>" . $result['batch_number'] . "</td><td>" . $result['transaction_date'] . "</td><td>" . $result['source'] . "</td><td>" . $result['destination'] . "</td></tr>";
            }
        }
        $dyn_table .= "</tbody></table>";

        $data['title'] = "webADT | Drug Movement";
        $data['content_view'] = "user_dispensement_log";
        $data['banner_text'] = "Home";
        $data['link'] = "home";
        $data['over'] = $period;
        $data['thetitle'] = ucfirst($user)."'s  Drug Stock Movement activity over last $period days";
        $data['user_'] = ucfirst($user);
        $data['content'] = $dyn_table;
        $data['user'] = $this->session->userdata['full_name'];
        $this->load->view("template", $data);
    }

    public function synchronize_patients() {
        $data['regimens'] = Regimen::getAll();
        $data['supporters'] = Supporter::getAll();
        $data['service_types'] = Regimen_Service_Type::getAll();
        $data['sources'] = Patient_Source::getAll();
        $data['drugs'] = Drugcode::getAll();
        $data['regimen_change_purpose'] = Regimen_Change_Purpose::getAll();
        $data['visit_purpose'] = Visit_Purpose::getAll();
        $data['opportunistic_infections'] = Opportunistic_Infection::getAll();
        $data['regimen_drugs'] = Regimen_Drug::getAll();
    }

    public function getNotified() {
        //Notify for patients
        // set current date
        $notice = array();
        $date = date('y-m-d');
        // parse about any English textual datetime description into a Unix timestamp
        $ts = strtotime($date);
        // find the year (ISO-8601 year number) and the current week
        $year = date('o', $ts);
        $week = date('W', $ts);
        $facility_code = $this->session->userdata('facility');
        // print week for the current date
        for ($i = 1; $i <= 6; $i++) {
            // timestamp from ISO week date format
            $ts = strtotime($year . 'W' . $week . $i);
            $string_date = date("l", $ts);
            $number_date = date("Y-m-d ", $ts);

            $appointment_query = $this->db->query("SELECT COUNT(distinct(patient)) as Total from patient_appointment where appointment='$number_date' and facility='$facility_code'");
            $visit_query = $this->db->query("SELECT COUNT(distinct(patient_id)) as Total from patient_visit where dispensing_date='$number_date' and visit_purpose='2'and facility='$facility_code'");
            $appointments_on_date = $appointment_query->result_array();
            $visits_on_date = $visit_query->result_array();
            $notice['Days'][$i - 1] = $string_date;
            $notice['Appointments'][$i - 1] = $appointments_on_date[0]['Total'];
            $notice['Visits'][$i - 1] = $visits_on_date[0]['Total'];
            $notice['Percentage'][$i - 1] = round((@$visits_on_date[0]['Total'] / @$appointments_on_date[0]['Total']) * 100, 2) . "%";
        }
        echo json_encode($notice);
    }

    public function get_faq() {
        $sql = $this->db->query("SELECT modules,questions,answers FROM faq WHERE active='1' GROUP BY modules");

        if ($sql->num_rows() > 0) {
            foreach ($sql->result()as $rows) {
                $header = $rows->questions;
            }
            // print_r ($header); die
        }

        $data['title'] = "webADT | System Home";
        $data['content_view'] = "faq_v";
        $data['banner_text'] = "Frequently Asked Questions";
        $data['hide_side_menu'] = 1;
        $data['user'] = $this->session->userdata['full_name'];
        $this->load->view("template", $data);
    }
    public function testlib(){
        $this->load->library('updater');
        $connection = ($this->updater->check_connection());

        $rs = $this->updater->check_ADTrelease();
        $rs = (json_decode($rs));

        $adt_downloaded_status = ($this->updater->check_ADTRelease_downloaded());
        var_dump($adt_downloaded_status);


        // echo  '
        // <script type="text/javascript" scr="https://code.jquery.com/jquery-3.4.1.min.js" ></script>

        // $update_ADT = ($this->updater->update_ADT());


    }
    public function updater($process = ''){
        $this->load->library('updater');
        if($process == 'download'){
            $download = ($this->updater->download_ADTRelease());
            echo 'ADT Release Download Succesful <br />';
        }

        if($process == 'update'){
            $download = ($this->updater->update_ADT());
        }

    }

}
