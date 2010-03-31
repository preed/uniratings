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

print "The following script will interactively prompt you for additional department
abbreviations. These are necessary in cases where one department teaches 
classes with a number of various abbreviations.\n
For instance, perhaps a university offers Astronomy (ASTR) classes, but 
they're taught by the Physics department. This script provides a way to add 
that abbreviation to your OpenRating site and 'attach' it to the Physics 
department in a meaningful way.\n
Press ctrl-c to end data-entry at any time.\n\n";

while (1) {
   my $newAbbrev;
   my $courseAbbrevRegex = $OpenRatingsConfig::courseAbbrevRegex;
   do {
      $newAbbrev = OpenRatingsLib::PromptForInfo("New Abbreviation (must be valid) ");
   } while ($newAbbrev !~ /$courseAbbrevRegex/o);
   
   my $abbrevDept;
   my $sqlAbbrevDept;

   do {
      $abbrevDept = OpenRatingsLib::PromptForInfo("Department (enter an existing abbreviation)?");
      $sqlAbbrevDept = $CachedDB->quote("$abbrevDept");
   } while ($CachedDB->do("SELECT deptid FROM or_dept WHERE 
    abbrev=$sqlAbbrevDept") != 1);

   my $rv = OpenRatingsLib::NewAbbreviation($newAbbrev, $abbrevDept);

   if ($rv) {
      print "Abbreviation '$newAbbrev' created.\n\n";
   }
   else {
      print "Creation of abbreviation '$newAbbrev' FAILED\n\n";
   }
}
