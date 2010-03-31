<?php
/*
 * The contents of this file are subject to the Jabber Open Source License 
 * Version 1.0 (the "License").  You may not copy or use this file, in either 
 * source code or executable form, except in compliance with the License.  You 
 * may obtain a copy of the License at http://www.jabber.com/license/ or at 
 * http://www.opensource.org/.
 * 
 * Read the LICENSE file included with the source distribution for more
 * information.
 *
 * Software distributed under the License is distributed on an "AS IS" basis, 
 * WITHOUT WARRANTY OF ANY KIND, either express or implied.  See the License 
 * for the specific language governing rights and limitations under the 
 * License.
 *
 * Copyrights:
 *
 * Portions created by or assigned to Polyratings.com are Copyright (c) 
 * 1999-2002 Polyratings.com.  All Rights Reserved.  Contact information for 
 * Polyratings.com, is available at http://www.polyratings.com/.
 *
 * Portions Copyright (c) 2001-2002 J. Paul Reed
 *
 * Contributor(s):  Brian Morris (openratings@recurse.net)
 *
 */

include_once(PHP_ROOT . 'DBConnection.php');

define('PROFESSOR_INVISIBLE_STATES', "('pending','deleted','rejected')");
define('COMMENT_INVISIBLE_STATES', "('hidden','deleted','transaction')");

function isValidCourseNumber($subj, $num) {
   return preg_match(COURSE_ABBREV_REGEX, $subj) && preg_match(COURSE_NUM_REGEX, $num);
}

define('PROFS_ALL', 1);
define('PROFS_PENDING', 2);
define('PROFS_VISIBLE', 3);
define('PROFS_PENDINGONLY', 4);

function GetProfessorInfo($pid, $visibility = PROFS_VISIBLE) {
   assert(preg_match("/^\d+$/", $pid));

   if (PROFS_ALL == $visibility)
      $limitation = "";
   else if (PROFS_PENDING == $visibility)
      $limitation = "AND or_professor.status NOT IN ('deleted','rejected')";
   else if (PROFS_VISIBLE == $visibility)
      $limitation = "AND or_professor.status NOT IN " .
       PROFESSOR_INVISIBLE_STATES;
   else if (PROFS_PENDINGONLY == $visibility)
      assert(false); // PROFS_PENDINGONLY not implemented for GetProfInfo
   else
      assert(false); // invalid option passed

   $dbh = GetCachedDBConnection();

   $pinfo = $dbh->SelectRow("SELECT or_professor.profid, or_professor.fname, 
    or_professor.lname, or_dept.name AS deptname, or_dept.abbrev AS 
    deptabbrev, or_dept.deptid, or_college.name AS collegename, 
    or_campus.name AS campusname, ifnull(round(avg(or_comment.ques3), 
    " . EVAL_ROUND_DIGITS . "), -1) AS rating, 
    ifnull(count(or_comment.commentid), 0) AS evalcount, 
    UNIX_TIMESTAMP(max(or_objects.created)) AS lastrating FROM 
    or_professor, or_dept, or_college, or_campus LEFT JOIN or_comment ON 
    or_comment.profid=or_professor.profid AND or_comment.status NOT IN 
    " . COMMENT_INVISIBLE_STATES . " LEFT JOIN or_objects ON 
    or_comment.objectid=or_objects.objectid WHERE or_professor.profid='$pid' 
    $limitation AND or_professor.deptid=or_dept.deptid AND 
    or_dept.collegeid=or_college.collegeid AND or_dept.campusid=
    or_campus.campusid GROUP BY or_comment.profid");

   if (is_null($pinfo['lname']))
      return null;

   return $pinfo;
}

function GetProfessorStats($pid) {
   assert(preg_match("/^\d+$/", $pid));

   $dbh = GetCachedDBConnection();

   $pstatus = $dbh->SelectRow("SELECT count(profid) AS count FROM 
    or_professor WHERE profid='$pid' AND status NOT IN 
    " . PROFESSOR_INVISIBLE_STATES);
   
   if ($pstatus['count'] != 1)
      return null;

   $pstats = $dbh->SelectRow("SELECT profid, ifnull(round(avg(ques1), 
   " . EVAL_ROUND_DIGITS . "), -1) AS q1avg, ifnull(round(avg(ques2), 
   " . EVAL_ROUND_DIGITS . "), -1) AS q2avg, ifnull(round(avg(ques3), 
   " . EVAL_ROUND_DIGITS . "), -1) AS q3avg FROM or_comment WHERE 
    profid='$pid' AND status NOT IN " . COMMENT_INVISIBLE_STATES . " GROUP 
    BY profid");

   // This should be moved to the presentation layer, but it's easier to do
   // this for now.
   if ($pstats['q1avg'] == -1)
      $pstats['q1avg'] = "N/A";
   if ($pstats['q2avg'] == -1)
      $pstats['q2avg'] = "N/A";
   if ($pstats['q3avg'] == -1)
      $pstats['q3avg'] = "N/A";

   // We need to get a separate count so we can count the evals that were
   // null in the total
   $pcount = $dbh->SelectRow("SELECT count(commentid) as evalcount FROM 
    or_comment WHERE profid='$pid' AND status NOT IN 
    " . COMMENT_INVISIBLE_STATES);

   $pstats['evalcount'] = $pcount['evalcount'];

   // Only return null if the overall $pstats call is null AND we also
   // have at least one eval in the system; if we have 0, then sure...
   // stuff will be null
   if (0 ==  $pstats['evalcount']) {
      $pstats['q1avg'] = "N/A";
      $pstats['q2avg'] = "N/A";
      $pstats['q3avg'] = "N/A";
      $pstats['profid'] = $pid;
   }
   else if (is_null($pstats['profid'])) {
      return null;
   }

   return $pstats;
}

function GetDetailedProfessorStats($pid) {
   assert(preg_match('/^\d+$/', $pid));

   $dbh = GetCachedDBConnection();

   $pstatus = $dbh->SelectRow("SELECT count(profid) AS count FROM 
    or_professor WHERE profid='$pid' AND status NOT IN 
    " . PROFESSOR_INVISIBLE_STATES);
   
   if ($pstatus['count'] != 1)
      return null;

   $roundDigits = EVAL_ROUND_DIGITS + 1;

   // Averages and standard deviations
   $pstats = $dbh->SelectRow("SELECT profid, ifnull(round(avg(ques1), 
   $roundDigits), -1) AS q1avg, ifnull(round(avg(ques2), 
   $roundDigits), -1) AS q2avg, ifnull(round(avg(ques3), 
   $roundDigits), -1) AS q3avg, ifnull(round(stddev(ques1),
   $roundDigits), -1) AS q1sd, ifnull(round(stddev(ques2),
   $roundDigits), -1) AS q2sd, ifnull(round(stddev(ques3),
   $roundDigits), -1) AS q3sd FROM or_comment WHERE 
    profid='$pid' AND status NOT IN " . COMMENT_INVISIBLE_STATES . " GROUP 
    BY profid");

   $maxQues = DEFINED_EVAL_QUESTIONS;

   // Modes
   $qModes = array();

   for ($qnum = 1; $qnum <= $maxQues; $qnum++) {
      $emode = $dbh->SelectRow("SELECT ques$qnum, count(ques$qnum) AS cnt FROM 
       or_comment WHERE profid='$pid' AND ques$qnum IS NOT NULL AND status NOT 
       IN " . COMMENT_INVISIBLE_STATES . " GROUP BY ques$qnum ORDER BY cnt 
       DESC");
      $qModes[$qnum] = $emode["ques$qnum"];
   }


   // Medians
   $qMedians = array();

   for ($qnum = 1; $qnum <= $maxQues; $qnum++) {
      $emedcnt = $dbh->SelectRow("SELECT ((count(ques$qnum) + 1) / 2) AS 
       median, (count(ques$qnum) % 2) as oddeven FROM or_comment WHERE 
       profid='$pid' AND ques$qnum IS NOT NULL AND status NOT IN " . 
       COMMENT_INVISIBLE_STATES);

      if ($emedcnt['oddeven']) {
         // odd number of evals 
         $emed = $dbh->SelectSingleColumn("SELECT ques$qnum FROM or_comment 
          WHERE profid='$pid' AND ques$qnum IS NOT NULL AND status NOT IN "
          . COMMENT_INVISIBLE_STATES . " ORDER BY ques$qnum DESC LIMIT " .
          $emedcnt['median']);

         $qMedians[$qnum] = $emed[$emedcnt['median'] - 1];
      }
      else {
         // even number of evals 
         $limit = $emedcnt['median'] + 0.5; // MySQL LIMITs must be whole #s
         $emed = $dbh->SelectSingleColumn("SELECT ques$qnum FROM or_comment 
          WHERE profid='$pid' AND ques$qnum IS NOT NULL AND status NOT IN "
          . COMMENT_INVISIBLE_STATES . " ORDER BY ques$qnum DESC LIMIT 
          $limit");

         $qMedians[$qnum] =  ($emed[$emedcnt['median'] - 1] +
          $emed[$emedcnt['median'] - 2]) / 2;
      }
   }

   // Add the modes and medians to the stats hash we pass back
   for ($qnum = 1; $qnum <= $maxQues; $qnum++) {
      $pstats['q'.$qnum.'mode'] = $qModes[$qnum];
      $pstats['q'.$qnum.'median'] = $qMedians[$qnum];
   }

   // Analysis by grade-received
   foreach ($GLOBALS['orStudentGrades'] as $grade) {
      for ($qnum = 1; $qnum <= $maxQues; $qnum++) {
         $avg = $dbh->SelectRow("SELECT round(avg(ques$qnum), $roundDigits) 
          AS avg FROM or_comment WHERE profid='$pid' AND ques$qnum IS NOT NULL 
          AND status NOT IN " .  COMMENT_INVISIBLE_STATES . " AND 
          grade='$grade' GROUP BY profid");
         $pstats['q' . $qnum . $grade . 'avg'] = $avg['avg'];
      }

      $ecnt = $dbh->SelectRow("SELECT count(grade) AS cnt FROM or_comment 
       WHERE profid='$pid' AND ques3 IS NOT NULL AND status NOT IN " .
       COMMENT_INVISIBLE_STATES . " AND grade='$grade'");

      $pstats['grade'. $grade . 'count'] = $ecnt['cnt'];
   }

   // Analysis by class-standing 
   foreach ($GLOBALS['orClassStandings'] as $year) {
      for ($qnum = 1; $qnum <= $maxQues; $qnum++) {
         $avg = $dbh->SelectRow("SELECT round(avg(ques$qnum), $roundDigits) 
          AS avg FROM or_comment WHERE profid='$pid' AND ques$qnum IS NOT NULL 
          AND status NOT IN " .  COMMENT_INVISIBLE_STATES . " AND 
          studentclass='$year' GROUP BY profid");
         $pstats['q' . $qnum . $year. 'avg'] = $avg['avg'];
      }

      $ecnt = $dbh->SelectRow("SELECT count(grade) AS cnt FROM or_comment 
       WHERE profid='$pid' AND ques3 IS NOT NULL AND status NOT IN " .
       COMMENT_INVISIBLE_STATES . " AND studentclass='$year'");

      $pstats['year'. $year . 'count'] = $ecnt['cnt'];
   }

   // Get an eval count, but only count evals where we have full numerical
   // data for the eval
   $pcount = $dbh->SelectRow("SELECT count(commentid) as evalcount FROM 
    or_comment WHERE profid='$pid' AND ques1 IS NOT NULL AND ques2 IS NOT NULL
    AND ques3 IS NOT NULL AND status NOT IN " . COMMENT_INVISIBLE_STATES);

   $pstats['evalcount'] = $pcount['evalcount'];

   // Only return null if the overall $pstats call is null AND we also
   // have at least one eval in the system; if we have 0, then sure...
   // stuff will be null
   if (is_null($pstats['profid'])) {
      return null;
   }

   return $pstats;
}

function GetProfessorEvals($pid) {
   assert(preg_match("/^\d+$/", $pid));

   $dbh = GetCachedDBConnection();

   $pstatus = $dbh->SelectRow("SELECT count(profid) AS count FROM 
    or_professor WHERE profid='$pid' AND status NOT IN 
    " . PROFESSOR_INVISIBLE_STATES);

   if ($pstatus['count'] != 1)
      return null;

   $pevals = $dbh->SelectAll("SELECT cr.abbrev, cr.number, c.commentid, 
    c.studentclass, c.coursetype, c.grade, ct.comment, c.status,
    UNIX_TIMESTAMP(o.created) AS created FROM or_course cr, or_comment c, 
    or_comment_text ct, or_objects o WHERE c.commentid = ct.commentid AND 
    c.courseid = cr.courseid AND c.objectid = o.objectid AND 
    c.profid='$pid' AND c.status NOT IN " . COMMENT_INVISIBLE_STATES . " 
    ORDER BY created DESC");

   if (0 == count($pevals))
      return null;

   return $pevals;
}

// TODO; argument list of fields to query and return
function GetProfessorList($visibility = PROFS_VISIBLE) {
   if (PROFS_ALL == $visibility)
      $limitation = "";
   else if (PROFS_PENDING == $visibility)
      $limitation = "or_professor.status NOT IN ('deleted','rejected') AND";
   else if (PROFS_VISIBLE == $visibility)
      $limitation = "or_professor.status NOT IN " . 
       PROFESSOR_INVISIBLE_STATES . " AND";
   else if (PROFS_PENDINGONLY == $visibility)
      $limitation = "or_professor.status ='pending' AND";
   else
      assert(false); // invalid option

   $dbh = GetCachedDBConnection();

   $profList = $dbh->SelectAll("SELECT or_professor.profid as profid, 
    or_professor.fname as fname, or_professor.lname as lname, or_dept.abbrev 
    as dept, count(or_comment.commentid) as evalcount FROM or_professor LEFT 
    JOIN or_comment ON or_comment.status NOT IN " . COMMENT_INVISIBLE_STATES
    . " and or_comment.profid = or_professor.profid, or_dept WHERE $limitation 
    or_professor.deptid = or_dept.deptid GROUP BY or_professor.profid ORDER 
    BY lname, fname, dept");

   return $profList;
}

define('COMMENTS_ALL', 1);          // All comments, period
define('COMMENTS_PENDING', 2);      // Pending comments
define('COMMENTS_CLEARED', 3);      // Cleared (were 'pending') comments
define('COMMENTS_MODERATED', 4);    // Moderated comments
define('COMMENTS_DELETED', 5);      // Deleted comments
define('COMMENTS_VISIBLE', 6);      // All comments visible to users
define('COMMENTS_ALL_VALID', 7);    // All *valid* comments (guaranteed to 
                                    //  have stats + a comment)

function GetCommentCount($visibility = COMMENTS_VISIBLE) {
   if (COMMENTS_ALL == $visibility)
      $limitation = "";
   else if (COMMENTS_PENDING == $visibility)
      $limitation = "WHERE status='pending'";
   else if (COMMENTS_CLEARED == $visibility)
      $limitation = "WHERE status='cleared'";
   else if (COMMENTS_MODERATED == $visibility)
      $limitation = "WHERE status='moderated'";
   else if (COMMENTS_DELETED == $visibility)
      $limitation = "WHERE status='deleted'";
   else if (COMMENTS_VISIBLE == $visibility)
      // Could also including pending, when we get comment approval going
      $limitation = "WHERE status NOT IN " . COMMENT_INVISIBLE_STATES;
   else if (COMMENTS_ALL_VALID == $visibility)
      $limitation = "WHERE status NOT IN ('transaction')";
   else
      assert(false); // Invalid arg

   $dbh = GetCachedDBConnection();

   $evalCount = $dbh->SelectSingleColumn("SELECT count(commentid) FROM 
    or_comment $limitation");

   return $evalCount[0];
}

function GetCourseID($abv, $num) {
   assert(isValidCourseNumber($abv, $num));

   $dbh = GetCachedDBConnection();

   $cid = $dbh->SelectRow("SELECT courseid FROM or_course WHERE abbrev='$abv'
    AND number='$num'");

   // if the course doesn't exist, this will be null
   // so we're still good
   return $cid['courseid']; 
}

function GetDepartmentInfo() {
   $dbh = GetCachedDBConnection();

   $deptInfo = $dbh->SelectAll("SELECT deptid, name, abbrev FROM or_dept WHERE 
    campusid='1' ORDER BY name");

   return $deptInfo;
}

function GetCourseAbbreviations() {
   $dbh = GetCachedDBConnection();

   $abbrevs = $dbh->SelectAll("SELECT a.abbrev FROM or_abbrev_map a, 
    or_dept d WHERE d.campusid = 1 and d.deptid=a.deptid ORDER BY a.abbrev");

   $retAbbrevs = array();

   foreach ($abbrevs as $a)
      array_push($retAbbrevs, $a['abbrev']);

   return $retAbbrevs;
}

function GetEvaluation($evalid) {
   assert(preg_match("/^\d+/", $evalid));

   if ($evalid <= 0)
      return null;

   $evaluation = $db->SelectRow("SELECT cr.abbrev AS courseAbbrev, 
    cr.number AS courseNumber, c.commentid, c.studentclass, c.coursetype, 
    c.grade, ct.comment, c.status, UNIX_TIMESTAMP(o.created) AS created FROM 
    or_course cr, or_comment c, or_comment_text ct, or_objects o WHERE 
    c.commentid = ct.commentid AND c.courseid = cr.courseid AND 
    c.objectid = o.objectid AND c.commentid='$evalid' AND c.status NOT IN 
    " . COMMENT_INVISIBLE_STATES . " ORDER BY created DESC");

   $eval['formattedEvalDate'] = date("g:i a, M j, Y", $eval['created']);

   return $eval;
}
?>
