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

use DBI;
use strict;

use vars qw($db);

require "openratings-config.pl";
require "openratings-lib.pl";

$db = OpenRatingsLib::GetDBConnection();
die "Couldn't connect to OpenRatings database; check your config\n" if (!$db);

my $oid;

## Build the transaction types table

foreach my $type (@OpenRatingsConfig::transactionTypes) {
   print "Creating transaction type '$type->[0]'\n";
   my $sqlAction = $db->quote("$type->[0]");
   my $sqlMaintable= $db->quote("$type->[1]");

   if ($db->do("SELECT type FROM or_transaction_types WHERE action=$sqlAction") 
    == 1) {
      print "Transaction type '$type->[0]' already exists\n";
      next;
   }

   my $rv = $db->do("INSERT INTO or_transaction_types (action, maintable) 
    VALUES ($sqlAction, $sqlMaintable)");

   print "Creation of transaction type '$type->[0]' FAILED\n" if ($rv != 1);
}

## Create a campus
my $createCampusEvent = OpenRatingsLib::StartTransaction('campus-new'); 
my $campusid;

while (1) {
   my $campus = OpenRatingsLib::PromptForInfo("What is the name of your campus?");
   my $campusAbbrev = OpenRatingsLib::PromptForInfo("What is an abbreviation for your campus name?");

   print "Ready to create campus '$campus'\nwith abbreviation '$campusAbbrev'\n";
   print "Are these correct? (y/n) ";

   if (OpenRatingsLib::Prompt()) {
      ($oid) = $db->selectrow_array("SELECT objectid FROM or_transactions WHERE
       eventid=$createCampusEvent");

      my $sqlCampus = $db->quote("$campus");
      my $sqlCampusAbbrev = $db->quote("$campusAbbrev");

      my $rv = $db->do("INSERT INTO or_campus (name, abbrev, objectid) VALUES 
       ($sqlCampus, $sqlCampusAbbrev, $oid)");

      if ($rv != 1) {
         print "Create campus $campus FAILED\n";
         exit;
      }

      last;
   }
}

OpenRatingsLib::EndTransaction($createCampusEvent);

($campusid) = $db->selectrow_array("SELECT campusid FROM or_campus WHERE 
 objectid=$oid");

## Create the colleges
while (1) {
   my $college = OpenRatingsLib::PromptForInfo("What is the name of one of the colleges on campus?");

   if (OpenRatingsLib::Prompt("Ready to create college '$college'; is this correct?")) {
      my $sqlCollege = $db->quote("$college");
      my $createCollegeEvent = OpenRatingsLib::StartTransaction('college-new');

      ($oid) = $db->selectrow_array("SELECT objectid FROM or_transactions WHERE
       eventid=$createCollegeEvent");

      my $rv = $db->do("INSERT INTO or_college (name,campusid,objectid) VALUES 
       ($sqlCollege, $campusid, $oid)");

      if ($rv != 1) {
         print "Create college $college FAILED\n";
         exit;
      }

      OpenRatingsLib::EndTransaction($createCollegeEvent);
   }

   last if (!OpenRatingsLib::Prompt("Any more colleges to create?"));
}

$db->disconnect();

print "\nThe next steps are to add the college departments and the profesors;
use newdept.pl and newprof.pl to accomplish this.\n";
