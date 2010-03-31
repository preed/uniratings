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
 * Contributor(s):
 *
 */

define('EVAL_WELLFORMED', 0);
define('EVAL_INVAL_COURSE_ABBREV', 1);
define('EVAL_INVAL_COURSE_NUM', 2);
define('EVAL_INVAL_COURSE_TYPE', 3);
define('EVAL_INVAL_STUDENT_COURSE_TYPE', 4);
define('EVAL_INVAL_STUDENT_TYPE', 5);
define('EVAL_INVAL_STUDENT_GRADE', 6);
define('EVAL_INVAL_QUES1', 7);
define('EVAL_INVAL_QUES2', 8);
define('EVAL_INVAL_QUES3', 9);
define('EVAL_INVAL_COMMENT', 10);

$evalVerificationErrors = array(
   EVAL_WELLFORMED => 'Evaluation was well formed',
   EVAL_INVAL_COURSE_ABBREV => 'Invalid Course Abbreviation',
   EVAL_INVAL_COURSE_NUM => 'Course numbers must be three digits',
   EVAL_INVAL_COURSE_TYPE => 'Select a course type',
   EVAL_INVAL_STUDENT_COURSE_TYPE => 'Select why you took the course',
   EVAL_INVAL_STUDENT_TYPE => 'Select your class standing',
   EVAL_INVAL_STUDENT_GRADE => 'Invalid student grade',
   EVAL_INVAL_QUES1 => 'You must rate this professor\'s ability to present the course material',
   EVAL_INVAL_QUES2 => 'You must rate this professor\'s ability to recognize student difficulties',
   EVAL_INVAL_QUES3 => 'You must give this professor an overall rating',
   EVAL_INVAL_COMMENT => 'Give us a comment about this professor!' 
);

function VerifyEvaluationInput($input) {
   assert(!is_null($input));

   if (! preg_match(COURSE_ABBREV_REGEX, $input['course']))
      return EVAL_INVAL_COURSE_ABBREV;
   else if (! preg_match(COURSE_NUM_REGEX, $input['number']))
      return EVAL_INVAL_COURSE_NUM;
   else if (! preg_match("/^lec|lab|rec|exp$/", $input['coursetype']))
      return EVAL_INVAL_COURSE_TYPE;
   else if (! preg_match("/^elective|ge|major|support$/", $input['studentcoursetype']))
      return EVAL_INVAL_STUDENT_COURSE_TYPE;
   else if (! preg_match("/^frosh|soph|junior|senior|spr-senior|grad$/", 
    $input['studentclass']))
      return EVAL_INVAL_STUDENT_TYPE;
   else if (! preg_match("/^[a-d]|f|cr|nc|w|na$/", $input['grade']))
      return EVAL_INVAL_STUDENT_GRADE;
   else if (! preg_match("/^[0-4]$/", $input['ques1']))
      return EVAL_INVAL_QUES1;
   else if (! preg_match("/^[0-4]$/", $input['ques2']))
      return EVAL_INVAL_QUES2;
   else if (! preg_match("/^[0-4]$/", $input['ques3']))
      return EVAL_INVAL_QUES3;
   else if (preg_match('/^\s*$/', $input['comments']))
      return EVAL_INVAL_COMMENT;

   return EVAL_WELLFORMED;
}

function CreateCourse($abbrev, $number, $type, $title) {
   assert(isValidCourseNumber($abbrev, $number));

   $dbh = GetCachedDBConnection();

   $transactionID = $dbh->BeginTransaction('course-new');
   assert(!is_null($transactionID));

   $deptid = $dbh->SelectRow("SELECT deptid FROM or_abbrev_map WHERE 
    abbrev='$abbrev'");

   assert(!is_null($deptid['deptid']) && $deptid['deptid'] > 0);  

   $did = $deptid['deptid'];
   $oid = $dbh->GetObjectID();

   $sqlCourseTitle = $dbh->Quote($title);

   $rows = $dbh->DoOp("INSERT INTO or_course (deptid, abbrev, number, type, 
    title, objectid) VALUES ('$did', '$abbrev', '$number', '$type', 
    '$sqlCourseTitle', '$oid')");

   assert($rows == 1);

   $courseid = $dbh->SelectRow("SELECT courseid FROM or_course WHERE 
    objectid='$oid'");

   $transactionEnd = $dbh->EndTransaction();
   assert($transactionEnd);

   return $courseid['courseid'];
}

define('RECORDEVAL_SUCCESS', 0);
define('RECORDEVAL_BLOCKED_DUPBLACKOUT', 1);
   
define('SECONDS_PER_MINUTE', 60); // NO MAGIC NUMBERS!

function RecordEvaluation($input) {
   assert(EVAL_WELLFORMED == VerifyEvaluationInput($input));

   $dbh = GetCachedDBConnection();

   $packedIP = PackIPAddress(getenv('REMOTE_ADDR'));
   $pid = $input['profid'];
   $blackout = EVALS_DUPLICATE_BLACKOUT * SECONDS_PER_MINUTE; 

   $oldPosts = $dbh->SelectAll("SELECT t.eventid FROM or_transactions t,
    or_comment c WHERE c.profid='$pid' AND t.ipaddr='$packedIP' AND
    UNIX_TIMESTAMP(t.endtime) > (UNIX_TIMESTAMP(NOW()) - $blackout) AND
    c.objectid=t.objectid");

   if (count($oldPosts) != 0)
      return RECORDEVAL_BLOCKED_DUPBLACKOUT;

   $transactionID = $dbh->BeginTransaction('evaluation-new');
   assert(!is_null($transactionID)); 

   $oid = $dbh->GetObjectID();

   $rows = $dbh->DoOp("INSERT INTO or_comment (profid, courseid, 
    studentclass, coursetype, grade, ques1, ques2, ques3, objectid) 
    VALUES ('" . $input['profid'] . "', '"
              . $input['courseid'] . "', '"
              . $input['studentclass'] . "', '"
              . $input['studentcoursetype'] . "', '"
              . $input['grade'] . "', '"
              . $input['ques1'] . "', '"
              . $input['ques2'] . "', '"
              . $input['ques3'] . "', '$oid')");

   assert($rows == 1);

   $commentid = $dbh->LastInsertID();

   $quotedComment = $dbh->Quote($input['comments']);

   $rows = $dbh->DoOp("INSERT INTO or_comment_text (commentid, comment)
    VALUES ($commentid, '$quotedComment')"); 
   assert($rows == 1);

   $rows = $dbh->DoOp("UPDATE or_comment SET status='pending' WHERE 
    objectid=$oid");
   assert($rows == 1);

   $transactionStatus = $dbh->EndTransaction(); 
   assert($transactionStatus);
      
   return RECORDEVAL_SUCCESS;
}

?>
