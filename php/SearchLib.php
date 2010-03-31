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


include_once('Constants.php');
include_once(PHP_ROOT . 'DBConnection.php');
include_once(PHP_ROOT . 'DBInterfaceLib.php');

function GetSearchFunction($searchType) {
   if (preg_match("/^\w+$/", $searchType) && 
    function_exists("SearchBy" . $searchType)) {
      $fPtr = "SearchBy" . $searchType;
         return $fPtr;
   }
   return null;
}

function isValidSearchType($type) {
   return (!is_null(GetSearchFunction($type)));
}

function isValidSortOrder($order) {
   return $order == "rating" || $order == "date" || $order == "name";
}

function isValidSearchDisplayFormat($format) {
   return $format == "short" || $format == "long" || $format == "full";
}

function SearchByProfName($criteria, $sortOrder) {
   assert(isValidSortOrder($sortOrder));
   assert(isset($criteria['terms']));

   $dbHandle = GetCachedDBConnection(); 
   $profName = $dbHandle->Quote(trim($criteria['terms']));

   if ($sortOrder == "date") {
      $profids = $dbHandle->SelectAll("SELECT DISTINCT
       or_professor.profid, max(or_objects.created) AS lastrating FROM 
       or_professor LEFT JOIN or_comment USING (profid) LEFT JOIN or_objects 
       USING (objectid) WHERE concat(or_professor.fname, ' ', 
       or_professor.lname) rlike '.*$profName.*' and or_professor.status 
       NOT IN " . PROFESSOR_INVISIBLE_STATES . " GROUP 
       BY or_professor.profid ORDER BY lastrating DESC, or_professor.lname, 
       or_professor.fname");
   }
   else if ($sortOrder == "rating") {
      $profids = $dbHandle->SelectAll("SELECT DISTINCT
       or_professor.profid, ifnull(avg(or_comment.ques3), -1) AS score FROM 
       or_professor LEFT JOIN or_comment USING (profid) WHERE 
       concat(or_professor.lname, ' ', or_professor.fname) rlike 
       '.*$profName.*' and or_professor.status NOT 
       IN " . PROFESSOR_INVISIBLE_STATES . " GROUP BY or_comment.profid 
       ORDER BY score DESC, or_professor.lname, or_professor.fname");
   }
   else {
      $profids = $dbHandle->SelectAll("SELECT DISTINCT profid FROM 
       or_professor WHERE concat(fname, ' ', lname) rlike '.*$profName.*' 
       AND status NOT IN " . PROFESSOR_INVISIBLE_STATES . " ORDER BY lname, 
       fname");
   }

   return $profids;
}

function SearchByClass($criteria, $sortOrder) {
   assert(isValidSortOrder($sortOrder));
   assert(isset($criteria['courseAbbrev']));
   assert(isset($criteria['courseNum']));

   $subject = $criteria['courseAbbrev'];
   $number = $criteria['courseNum'];

   assert(isValidCourseNumber($subject, $number));

   $dbHandle = GetCachedDBConnection();

   $courseid = GetCourseId($subject, $number);

   if (is_null($courseid))
      return null;

   if ($sortOrder == "date") {
      $profids = $dbHandle->SelectAll("SELECT DISTINCT c.profid, 
       UNIX_TIMESTAMP(max(o.created)) AS lastrating FROM or_comment c, 
       or_comment a, or_objects o, or_professor p WHERE 
       c.courseid='$courseid' AND c.profid = p.profid AND p.status NOT IN 
       " . PROFESSOR_INVISIBLE_STATES . " AND c.status NOT IN 
       " . COMMENT_INVISIBLE_STATES . " AND a.status NOT IN 
       " . COMMENT_INVISIBLE_STATES . " AND a.profid=p.profid AND 
       a.objectid=o.objectid GROUP BY a.profid ORDER BY lastrating desc, 
       p.lname, p.fname");
   }
   else if ($sortOrder == "rating") {
      $profids = $dbHandle->SelectAll("SELECT DISTINCT c.profid, 
       ifnull(round(avg(c.ques3), " . EVAL_ROUND_DIGITS . "), -1)
       as coursescore, ifnull(round(avg(a.ques3), 
       " . EVAL_ROUND_DIGITS . "), -1) as score FROM or_comment c, 
       or_professor p, or_comment a WHERE c.courseid='$courseid' and 
       c.profid=p.profid and a.profid=p.profid and p.status NOT 
       IN " . PROFESSOR_INVISIBLE_STATES . " and c.status NOT IN 
       " . COMMENT_INVISIBLE_STATES . " and a.status NOT IN 
       " . COMMENT_INVISIBLE_STATES . " GROUP BY c.profid ORDER BY 
       score desc, p.lname, p.fname");
   }
   else {
      $profids = $dbHandle->SelectAll("SELECT DISTINCT c.profid 
       FROM or_comment c, or_professor p WHERE courseid='$courseid' and 
       c.profid=p.profid AND p.status NOT IN 
       " . PROFESSOR_INVISIBLE_STATES . " AND c.status NOT IN 
       " . COMMENT_INVISIBLE_STATES . " ORDER BY p.lname, p.fname");
   }

   return $profids;
}

function SearchByDept($criteria, $sortOrder) {
   $deptid = $criteria['deptid'];
   assert(isValidSortOrder($sortOrder));
   assert(preg_match("/^\d+$/", $deptid));
   $dbHandle = GetCachedDBConnection();


   if ($sortOrder == "date") {
      $profids = $dbHandle->SelectAll("SELECT DISTINCT 
       or_professor.profid, max(or_objects.created) AS lastrating FROM 
       or_professor LEFT JOIN or_comment ON 
       or_professor.profid=or_comment.profid AND or_comment.status NOT IN
       " . COMMENT_INVISIBLE_STATES . " LEFT JOIN or_objects USING 
       (objectid) WHERE or_professor.deptid='$deptid' AND 
       or_professor.status NOT IN " . PROFESSOR_INVISIBLE_STATES . " GROUP 
       BY or_professor.profid ORDER BY lastrating DESC, or_professor.lname, 
       or_professor.fname");
   }
   else if ($sortOrder == "rating") {
      $profids = $dbHandle->SelectAll("SELECT DISTINCT or_professor.profid, 
       ifnull(avg(or_comment.ques3), -1) AS score FROM or_professor LEFT 
       JOIN or_comment ON or_professor.profid=or_comment.profid AND 
       or_comment.status NOT IN " . COMMENT_INVISIBLE_STATES . " WHERE 
       or_professor.deptid='$deptid' AND or_professor.status NOT IN 
       " . PROFESSOR_INVISIBLE_STATES . " GROUP BY or_comment.profid ORDER BY 
       score DESC, or_professor.lname, or_professor.fname");
   }
   else {
      $profids = $dbHandle->SelectAll("SELECT DISTINCT profid 
       FROM or_professor WHERE deptid='$deptid' AND status NOT IN 
       " . PROFESSOR_INVISIBLE_STATES . " ORDER BY lname, fname");
   }

   return $profids;
}

function SearchByKeyword($criteria, $sortOrder) {
   assert(isValidSortOrder($sortOrder));
   $terms = $criteria['terms'];
   $dbHandle = GetCachedDBConnection();
   $keywords = $dbHandle->Quote($terms);

   if ($sortOrder == "date") {
      $profids = $dbHandle->SelectAll("SELECT DISTINCT c.profid, 
       round(max(MATCH(ct.comment) AGAINST ('$keywords')), 
       " . NLS_ROUND_DIGITS . ") as score, max(o.created) as lastrating 
       FROM or_comment c, or_comment_text ct, or_professor p, or_objects o 
       WHERE c.commentid = ct.commentid AND MATCH(ct.comment) AGAINST 
       ('$keywords') > 0 AND c.profid = p.profid AND p.status NOT IN 
       " . PROFESSOR_INVISIBLE_STATES . " AND c.status 
       NOT IN " . COMMENT_INVISIBLE_STATES . " AND c.objectid = o.objectid 
       GROUP BY c.profid ORDER BY lastrating DESC, p.lname, p.fname");
   }
   else if ($sortOrder == "rating") {
      $profids = $dbHandle->SelectAll("SELECT DISTINCT c.profid, 
       round(max(MATCH(ct.comment) AGAINST ('$keywords')), 
       " . NLS_ROUND_DIGITS . ") as score FROM or_comment c, 
       or_comment_text ct, or_professor p WHERE c.commentid = ct.commentid 
       AND MATCH(ct.comment) AGAINST ('$keywords') > 0 AND c.profid = 
       p.profid AND p.status NOT IN " . PROFESSOR_INVISIBLE_STATES . " 
       AND c.status NOT IN " . COMMENT_INVISIBLE_STATES . " GROUP BY 
       c.profid ORDER BY score DESC, p.lname, p.fname");
   }
   else {
      $profids = $dbHandle->SelectAll("SELECT DISTINCT c.profid, 
       round(max(MATCH(ct.comment) AGAINST ('$keywords')), 
       " . NLS_ROUND_DIGITS . ") as score FROM or_comment c, or_comment_text 
       ct, or_professor p WHERE c.commentid = ct.commentid and 
       MATCH(ct.comment) AGAINST ('$keywords') > 0 and c.profid = p.profid 
       AND p.status NOT IN " . PROFESSOR_INVISIBLE_STATES . " AND c.status 
       NOT IN " . COMMENT_INVISIBLE_STATES . " GROUP BY c.profid ORDER BY 
       p.lname, p.fname");
   }

   return $profids;
}

?>
