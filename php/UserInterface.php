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

$UIRenderingMap = array(
   // index.phtml
   'index' => 'PrintIndexPage',
   'error' => 'PrintErrorPage',

   // list.phtml 
   'list' => 'PrintListPage',

   // search.phtml
   'search' => 'PrintSearchPage',
   'searchResults' => 'PrintSearchResults',
   'searchNoResults' => 'PrintNoSearchResults',

   // eval.phtml
   'evals' => 'PrintProfEvalPage',
   'evalInvalid' => 'PrintInvalidEval',
   'evalNoEvals' => 'PrintProfNoEvalsPage',

   // entereval.phtml
   'entereval' => 'PrintEnterEvaluationPage',
   'evalCollectEval' => 'PrintCollectEvaluationPage',
   'evalEvaluationSubmitted' => 'PrintEvaluationSubmittedPage',

   // comments.phtml
   'submitComment' => 'PrintSubmitCommentPage',
   'commentSubmitted' => 'PrintCommentSubmittedPage',

   // add.phtml
   'add' => 'PrintAddPage',
   'addCollectInfo' => 'PrintAddCollectInfoPage',
   'addRepeatInstructions' => 'PrintAddRepeatInstructionsPage',

   // stats.phtml
   'stats' => 'PrintStatsPage',
   'statsInvalidProf' => 'PrintInvalidStatsPage',

   // about.phtml
   'about' => 'PrintAboutPage',

   // faq.phtml
   'faq' => 'PrintFAQPage',
);

$UIIncludeMap = array(
   // index.phtml
   'index' => 'index.inc',

   // add.phtml
   'add' => 'add.inc',
   'addCollectInfo' => 'add.inc',
   'addRepeatInstructions' => 'add.inc',

   // list.phtml
   'list' => 'list.inc',

   // search.phtml
   'search' => 'search.inc',
   'searchResults' => 'search.inc',
   'searchNoResults' => 'search.inc',

   // eval.phtml
   'evals' => 'eval.inc',
   'evalInvalid' => 'eval.inc',
   'evalNoEvals' => 'eval.inc',

   // entereval.phtml
   'entereval' => 'entereval.inc',
   'evalCollectEval' => 'entereval.inc',
   'evalEvaluationSubmitted' => 'entereval.inc',

   // comments.phtml
   'submitComment' => 'comments.inc',
   'commentSubmitted' => 'comments.inc',

   // stats.phtml
   'stats' => 'stats.inc',
   'statsInvalidProf' => 'stats.inc',

   // about.phtml
   'about' => 'about.inc',

   // faq.phtml
   'faq' => 'faq.inc',
);

$GLOBALS['uiRenderingMap'] = $UIRenderingMap;
$GLOBALS['uiIncludeMap'] = $UIIncludeMap;

?>
