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
            <img src='../blocks/dashboard/classes/img/My Certs.png' class='dashboard-img' onclick='window.location.href=`../mod/customcert/my_certificates.php?userid=".$this->userid()."`'>
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
    public function role(){
        global $USER;
        $user = $USER->id;
        global $DB;
        $assignments = $DB->get_records_sql('SELECT * FROM {role_assignments} where userid = ?', [$user]);
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
    public function courseenrolled($type){
        global $USER;
        $user = $USER->id;
        global $DB;
        $roleid = [];
        if($type === 'coach'){
            $roleid = [3, 4];
        } elseif($type === 'learner'){
            $roleid = [5];
        }
        $userEnrolments = $DB->get_records_sql('SELECT enrolid, status FROM {user_enrolments} WHERE userid = ? AND status = ?', [$user, 0]);
        $enrolTable = $DB->get_records('enrol');
        $courseids = [];
        foreach($userEnrolments as $userEnrol){
            foreach($enrolTable as $enrolTab){
                if($enrolTab->id == $userEnrol->enrolid && $userEnrol->status !== 1){
                    array_push($courseids, [$enrolTab->courseid]);
                }
            }
        }
        $temp = [];
        $contexts = $DB->get_records('context');
        $roleAssignments = $DB->get_records_sql('SELECT * FROM {role_assignments} WHERE userid = ?',[$user]);
        foreach($contexts as $context){
            foreach($roleAssignments as $roleAssign){
                if($roleAssign->contextid == $context->id && $roleAssign->roleid == $roleid[0]){
                    array_push($temp ,[$context->instanceid]);
                } else if ($roleAssign->contextid == $context->id && $roleAssign->roleid == $roleid[1] && count($roleid) == 2){
                    array_push($temp, [$context->instanceid]);
                }
            }
        }
        $temp2 = [];
        foreach($temp as $tem){
            foreach($courseids as $courseid){
                if($courseid[0] == $tem[0]){
                    array_push($temp2, $courseid);
                }
            }
        }
        return $temp2[0][0];
    }
    public function userid(){
        global $USER;
        return $USER->id;
    }
}
?>