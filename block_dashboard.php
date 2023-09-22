<?php 
class block_dashboard extends block_base{
    public function init(){
        $this->title = 'Dashboard';
        $role = $this->role();
        $context = context_system::instance();
        if($role == 'admin' || has_capability('block/dashboard:admin', $context)){
            $this->title = get_string('admin_d', 'block_dashboard');
        } elseif ($role == 'coach'){
            $courseid = $this->courseenrolled($role);
            $context = context_course::instance($courseid);
            require_capability('block/dashboard:coach', $context);
            $this->title = get_string('coach_d', 'block_dashboard');
        } elseif ($role == 'learner'){
            $courseid = $this->courseenrolled($role);
            $context = context_course::instance($courseid);
            require_capability('block/dashboard:learner', $context);
            $this->title = get_string('learner_d', 'block_dashboard');
        }
    }
    public function get_content(){
        $this->content = new stdClass();
        $role = $this->role();
        $this->content->text = "            
            <div class='text-center' id='dashboarddiv'>
            <img src='../blocks/dashboard/classes/img/Calendar.png' class='dashboard-img' onclick='window.location.href=`../calendar/view.php?view=month`'>
            <img src='../blocks/dashboard/classes/img/My Files.png' class='dashboard-img' onclick='window.location.href=`../user/files.php`'>
            <img src='../blocks/dashboard/classes/img/My Evidence.png' class='dashboard-img' onclick='window.location.href=`../admin/tool/lp/user_evidence_list.php`'>
            <img src='../blocks/dashboard/classes/img/My Badges.png' class='dashboard-img' onclick='window.location.href=`../badges/mybadges.php`'>
            <img src='../blocks/dashboard/classes/img/My Certs.png' class='dashboard-img' onclick='window.location.href=`../mod/customcert/my_certificates.php?userid=".$this->get_userid()."`'>
        ";
        $context = context_system::instance();
        if($role === 'admin' || has_capability('block/dashboard:admin', $context)){
            //admin
            $context = context_system::instance();
            require_capability('block/dashboard:admin', $context);
            $this->content->text .= "
            <img src='../blocks/dashboard/classes/img/All Courses.png' class='dashboard-img' onclick='window.location.href=`../course/index.php`'>
            <img src='../blocks/dashboard/classes/img/Latest Announcements.png' class='dashboard-img' onclick='window.location.href=`../mod/forum/view.php?id=3`'>
            <img src='../blocks/dashboard/classes/img/All Users.png' class='dashboard-img' onclick='window.location.href=`../admin/user.php`'>
            <img src='../blocks/dashboard/classes/img/Admin Reports.png' class='dashboard-img' onclick='window.location.href=`../local/lessonanalytics/manage.php`'>
            ";

        } elseif($role === 'coach'){
            //coach
            $courseid = $this->courseenrolled($role);
            $context = context_course::instance($courseid);
            require_capability('block/dashboard:coach', $context);
            $this->content->text .= "
            <img src='../blocks/dashboard/classes/img/Latest Announcements.png' class='dashboard-img' onclick='window.location.href=`../mod/forum/view.php?id=3`'>
            <img src='../blocks/dashboard/classes/img/My Courses.png' class='dashboard-img' onclick='courseoverview()'>
            ";
        } elseif($role === 'learner'){
            //learner
            $courseid = $this->courseenrolled($role);
            $context = context_course::instance($courseid);
            require_capability('block/dashboard:learner', $context);
            $this->content->text .= "
            <img src='../blocks/dashboard/classes/img/Latest Announcements.png' class='dashboard-img' onclick='window.location.href=`../mod/forum/view.php?id=3`'>
            <img src='../blocks/dashboard/classes/img/My Courses.png' class='dashboard-img' onclick='courseoverview()'>
            ";
        }
        $this->content->text .= "            
            <img src='../blocks/dashboard/classes/img/Events.png' class='dashboard-img' onclick='window.location.href=`../calendar/view.php`'>
            <img src='../blocks/dashboard/classes/img/Site News.png' class='dashboard-img' onclick='window.location.href=`https://www.northerntrainingacademy.com/news`'>
            <img src='../blocks/dashboard/classes/img/My Learning.png' class='dashboard-img' onclick='window.location.href=`../admin/tool/lp/plans.php`'>
            <img src='../blocks/dashboard/classes/img/Help.png' class='dashboard-img' onclick='window.location.href=`../local/manuals/manual.php`'>
            <img src='../blocks/dashboard/classes/img/Safeguarding.png' class='dashboard-img' onclick='window.location.href=`../blocks/dashboard/classes/pdf/Safeguarding.pdf`'>
            </div>
            <style>
                .dashboard-img{
                    width: 120px;
                    height: 120px;
                    cursor: pointer;
                }
            </style>
            <script>
                function courseoverview(){
                    document.querySelector('.block_myoverview').scrollIntoView();
                }
            </script>
        ";
    }

    private function get_userid(){
        global $USER;
        return $USER->id;
    }

    private function role(){
        $user = $this->get_userid();
        global $DB;
        $assignments = $DB->get_records_sql('SELECT id, roleid FROM {role_assignments} where userid = ?', [$user]);
        //admin, coach, learner
        $role = [false, false, false];
        foreach($assignments as $assignment){
            if($assignment->roleid == 5){
                $role[2] = true;
            } elseif($assignment->roleid == 4 || $assignment->roleid == 3){
                $role[1] = true;
            } elseif($assignment->roleid == 1){
                $role[0] = true;
            }
        }
        if($role[0] === true){
            return 'admin';
        } elseif($role[1] === true){
            return 'coach';
        } elseif($role[2] === true){
            return 'learner';
        }
    }

    private function courseenrolled($type){
        $user = $this->get_userid();
        global $DB;
        $records = null;
        if($type === 'coach'){
            $records = $DB->get_records_sql('SELECT DISTINCT {enrol}.courseid as courseid FROM {enrol}
                INNER JOIN {user_enrolments} ON {user_enrolments}.enrolid = {enrol}.id
                INNER JOIN {context} ON {context}.instanceid = {enrol}.courseid
                INNER JOIN {role_assignments} ON {role_assignments}.contextid = {context}.id
                INNER JOIN {course} ON {course}.id = {enrol}.courseid
                WHERE {user_enrolments}.userid = {role_assignments}.userid AND {role_assignments}.roleid IN (3,4) AND {user_enrolments}.status = 0 AND {role_assignments}.userid = ?',
            [$user]);
        } elseif($type === 'learner'){
            $records = $DB->get_records_sql('SELECT DISTINCT {enrol}.courseid as courseid FROM {enrol}
                INNER JOIN {user_enrolments} ON {user_enrolments}.enrolid = {enrol}.id
                INNER JOIN {context} ON {context}.instanceid = {enrol}.courseid
                INNER JOIN {role_assignments} ON {role_assignments}.contextid = {context}.id
                INNER JOIN {course} ON {course}.id = {enrol}.courseid
                WHERE {user_enrolments}.userid = {role_assignments}.userid AND {role_assignments}.roleid = 5 AND {user_enrolments}.status = 0 AND {role_assignments}.userid = ?',
            [$user]);
        }
        $array = [];
        foreach($records as $record){
            array_push($array, $record->courseid);
        }
        return $array[0];
    }
}
?>