#!/usr/bin/perl
#
# The contents of this file are subject to the Jabber Open Source License 
# Version 1.0 (the "License").  You may not copy or use this file, in either 
# source code or executable form, except in compliance with the License.  You 
# may obtain a copy of the License at http://www.jabber.com/license/ or at 
# http://www.opensource.org/.
# 
# Read the LICENSE file included with the source distribution for more
# information.
#
# Software distributed under the License is distributed on an "AS IS" basis, 
# WITHOUT WARRANTY OF ANY KIND, either express or implied.  See the License 
# for the specific language governing rights and limitations under the 
# License.
#
# Copyrights:
#
# Portions created by or assigned to Polyratings.com are Copyright (c) 
# 1999-2002 Polyratings.com.  All Rights Reserved.  Contact information for 
# Polyratings.com, is available at http://www.polyratings.com/.
#
# Portions Copyright (c) 2002 J. Paul Reed
#
# Contributor(s):
#

package OpenRatingsConfig;

$dbName = "openratings";
$dbUser = "orweb";
$dbPasswd = "password";

$courseAbbrevRegex = '^\w{2,5}$';
$courseNumRegex    = '^\d{3}\$';

@transactionTypes = (   ['campus-new','campus'],
                        ['college-new','college'],
                        ['department-new','dept'],
                        ['professor-new','professor'],
                        ['course-new','course'],
                        ['evaluation-new','comment'],
                        ['comment-delete-content','comment'],
                        ['comment-delete-invalid','comment'],
                        ['comment-moderate-content','comment'],
                        ['professor-delete','professor'],
                        ['comment-delete-unauthposter','comment'],
                        ['professor-new-approve','professor'],
                        ['professor-new-modinfo','professor'],
                        ['professor-new-deny-invalid','professor'],
                        ['professor-new-deny-dangling','professor'],
                        ['professor-new-start-approval','professor']
                    );

1;
