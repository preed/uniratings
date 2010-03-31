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

use strict;

require "openratings-config.pl";
require "openratings-lib.pl";

my $CachedDB = OpenRatingsLib::GetDBConnection();
die "Couldn't connect to OpenRatings database; check your config\n" if (!$CachedDB);

print "The following script will interactively prompt you for departmental 
information; most colleges have a *lot* of departments, so you may want to 
read the README file to learn how to import a list of your college's 
departments from a comma-separated list or some other list you might already 
have.

You can add course abbreviations for which no department exists by using 
the 'newabbrev.pl' utility; get more info on how it works by executing it.

Press ctrl-c to end data-entry at any time.\n\n";

my $colleges = $CachedDB->selectall_arrayref("SELECT collegeid, name FROM 
 or_college");

while (1) {
   my $deptName = OpenRatingsLib::PromptForInfo("Department Name?");
   my $deptAbbrev = OpenRatingsLib::PromptForInfo("Department Abbreviation?");

   foreach my $col (@{$colleges}) {
      print "$col->[0]. $col->[1]\n";
   }

   my $sqlDeptCollege;
   my $deptCollege;

   do {
      $deptCollege = OpenRatingsLib::PromptForInfo("Department College (enter a number)?");
      $sqlDeptCollege = $CachedDB->quote($deptCollege);
   } while ($CachedDB->do("SELECT name FROM or_college WHERE 
      collegeid=$sqlDeptCollege") != 1);

   my $rv = OpenRatingsLib::NewDepartment($deptName, $deptAbbrev, $deptCollege);

   if ($rv) {
      print "Department '$deptName' ($rv) created.\n\n";
   }
   else {
      print "Creation of Department '$deptName' FAILED\n\n";
   }
}
