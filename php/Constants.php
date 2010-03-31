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
 * Contributor(s):  Ryan Joseph (ryan@ryanjoseph.com)
 *                  Brian Morris (openratings@recurse.net)
 *
 */

// System-wide Settings 

// Set the correct timezone so php/mysql show the dates correctly
putenv("TZ=America/Los_Angeles");

// Database stuff
define('PR2_DBNAME', 'uniratings');
define('PR2_DBUSER', 'urweb');
define('PR2_DBPASSWD', 'password');
define('PR2_DBHOST', 'localhost:/var/lib/mysql/mysql.sock');

// define()s that must change before any of this will work
define('COMMENTS_EMAIL', 'you@localhost, admin@localhost, root@localhost');
define('ERROR_EMAIL', 'you@localhost');
define('UNIVERSITYDIR_URL', 'http://www.example.edu/directory.html'); 
define('SITE_URL', 'http://my.uniratingssite.example.com');
define('SITE_NAME', 'MyUniRatingsSite');
define('SITE_ROOT', '/var/www/MyUniRatingsSite/htdocs/');
define('SITEUI_URL', SITE_URL . 'uniratings-ui/');
define('UI_ROOT', SITE_ROOT . 'uniratings-ui/');

// Installation-specific Settings

// Abbreviation configuration. Modify COURSE_ABBREV_REGEX an acceptable 
// abbreviation range. Modify COURSE_NUM_REGEX for a standard course
// number length. Please, note that REGEX *do not* override set DB parameters.

define ('COURSE_ABBREV_REGEX', '/^[A-Z]{2,5}$/');
define ('COURSE_NUM_REGEX', '/^\d{3}$/');
define ('COURSE_NUM_LENGTH', 3);
   
// The number of minutes that a user must wait if they're posting to the
// same prof from the same IP before it will go through; see bug 28
define('EVALS_DUPLICATE_BLACKOUT', 5); 

// Define some default search params in case they're not specified by the
// query
define('SEARCH_DEFAULT_FORMAT', 'long');
define('SEARCH_DEFAULT_SORT', 'name');

// If there's only a single result to a given search, should we give the user 
// the result directly (true), or display the search results page with the
// lone result (false)
define('EVALS_AUTODISPLAY_SINGLE_RESULT', true);

// The minimum number of evaluations a prof must have before the 
// statistical analysis page will calculate the results 
define('STATS_MIN_EVALS', 5);


/**** DON'T CHANGE BELOW THIS LINE UNLESS YOU KNOW WHAT YOU'RE DOING ****/
/************************************************************************/ 
/************************************************************************/ 

// What version is this?!
define('PR2_VERSION', '2.0.0alaph');
// define('PR2_RELEASE_TAG', 'OPENRATINGS-1_3_1-RELEASE');

// Host filesystem locations
define('PHP_ROOT', SITE_ROOT . 'php/'); 

// URL locations
define('INDEX_URL', SITE_URL . 'index.phtml');
define('EVAL_URL', SITE_URL . 'eval.phtml');
define('ADDPROF_URL', SITE_URL . 'add.phtml');
define('SEARCH_URL', SITE_URL . 'search.phtml');
define('LIST_URL', SITE_URL . 'list.phtml');
define('ABOUT_URL', SITE_URL . 'about.phtml');
define('COMMENTS_URL', SITE_URL . 'comments.phtml');
define('FAQ_URL', SITE_URL . 'faq.phtml');
define('EVALID_URL', EVAL_URL . '?profid=');
define('ENTEREVAL_URL', SITE_URL . 'entereval.phtml');
define('ENTEREVALID_URL', ENTEREVAL_URL . '?profid=');
define('PROFSTATS_URL', SITE_URL . 'stats.phtml');
define('PROFSTATSID_URL', PROFSTATS_URL . '?profid=');

define('UNIRATINGS_URL', 'http://github.com/preed/uniratings/');

// Other constants
define('MAX_FNAME_LEN', 30);
define('MAX_LNAME_LEN', 40);
define('EVAL_ROUND_DIGITS', 2);
define('NLS_ROUND_DIGITS', 4);
define('DEFINED_EVAL_QUESTIONS', 3);

// Can't just change these; must also change the DB schema!
$GLOBALS['orStudentGrades'] = array('a','b','c','d','f','w','cr','nc','na'); 
$GLOBALS['orClassStandings'] = array('frosh','soph','junior','senior',
 'spr-senior','grad');

// Make asserts cause the app to quit
assert_options(ASSERT_BAIL, true);
assert_options(ASSERT_WARNING, false);
assert_options(ASSERT_QUIET_EVAL, true);
assert_options(ASSERT_CALLBACK, 'PrintFailedAssertion');

// ignore 'undefined variable' warnings (generated by the way we create
// the criteria array on the search page, since all the terms aren't
// always defined
//
// For debug/development:
// error_reporting(E_ALL);
//
// For production:
error_reporting(E_ERROR | E_PARSE | E_CORE_ERROR | E_CORE_WARNING);

?>
