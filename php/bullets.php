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

$infoBullets = array(
"<b>Congratulations!</b> You've successfully installed UniRatings",

"Check out <a class=\"nav\" href=\"" . UNIRATINGS_URL . "\">" . 
UNIRATINGS_URL . "</a> for more information",
);

$finePrintDisclaimers = array(
'Put important information that won\'t change here; examples follow',
SITE_NAME . ' is a student-run web site; we are not affiliated with the Administration or student government',

'Faculty and staff of Example University are hereby denied access to post material to ' . SITE_NAME . '.',

'Views expressed here are not necessarily those of ' . SITE_NAME . '.',

'Questions or comments? E-mail us via the <a class="link" href="' . COMMENTS_URL . '"><b>comments page</b></a>');

$emailBoxAddrs = array(
'<a class="link" href="mailto:me@localhost"><b>Cool person 1</b></a><br><i>Maintainer</i><br><a class="link" href="mailto:me@localhost">me@localhost</a>',

'<a class="link" href="mailto:you@localhost"><b>Cool person 2</b></a><br><i>Co-Maintainer</i><br><a class="link" href="mailto:you@localhost">you@localhost</a>',
);

// Make sure the arrays are accessible outside of this scope 
$GLOBALS['infoBullets'] = $infoBullets;
$GLOBALS['emailBoxAddrs'] = $emailBoxAddrs;
$GLOBALS['finePrintDisclaimers'] = $finePrintDisclaimers;

?>
