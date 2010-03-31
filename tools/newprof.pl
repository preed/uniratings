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

print "The following script will interactively prompt you for professor 
information; most colleges have a *lot* of professors, so you may want to 
read the README file to learn how to import a list of your college's professor 
from a comma-separated list or some other list you might already have.\n\nPress ctrl-c to end data-entry at any time.\n\n";

while (1) {
   ## Feel free to switch these if that's more convenient for you.
   my $profFName = OpenRatingsLib::PromptForInfo("Professor First Name?");
   my $profLName = OpenRatingsLib::PromptForInfo("Professor Last Name?");

   my $profDept;
   my $sqlProfDept;

   do {
      $profDept = OpenRatingsLib::PromptForInfo("Professor Department (enter an abbreviation)?");
      $sqlProfDept = $CachedDB->quote($profDept);
   } while ($CachedDB->do("SELECT deptid FROM or_dept WHERE 
    abbrev=$sqlProfDept") != 1);

   my $rv = OpenRatingsLib::NewProfessor($profFName, $profLName, $profDept);

   if ($rv) {
      print "Professor $profLName ($rv) created.\n\n";
   }
   else {
      print "Creation of Professor '$profLName' FAILED\n\n";
   }
}
