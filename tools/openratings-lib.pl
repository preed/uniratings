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
use English;

package OpenRatingsLib;

require "openratings-config.pl";

## All command line tools have a transaction class beginning with 0000
my $transactionClass = '0000';
## All DB work is done through this handle;
my $CachedDBConnection = undef;

sub GetDBConnection {
   if (($CachedDBConnection eq undef) || ($CachedDBConnection->do("SELECT 1") != 1)) {
     $CachedDBConnection = DBI->connect("DBI:mysql:$OpenRatingsConfig::dbName", 
       $OpenRatingsConfig::dbUser, $OpenRatingsConfig::dbPasswd);
   } 

   return $CachedDBConnection;
}

sub Prompt {
   my $ques = shift;
   my $prompt;
  
   for (;;) {
      print "$ques (y/n) ";
      $prompt = <STDIN>;
      return 1 if ($prompt =~ /^\s*y(es)?\s*$/i);
      return if ($prompt =~ /^\s*no?\s*$/i);
   }
}

sub PromptForInfo {
   my $ques = shift;
   my $prompt;

   while ($prompt =~ /^\s*$/) {
      print "$ques ";
      $prompt = <STDIN>;
   }

   chomp($prompt);
   return $prompt;
}

sub StartTransaction {
   my ($type, $objectid) = @_;
   my $CachedDB = GetDBConnection();

   return if ($objectid !~ /^\d+$/ && $objectid ne undef);

   if ($objectid eq undef) {
      return if ($CachedDB->do("INSERT INTO or_objects (created) VALUES (NOW())") 
       != 1);
      ($objectid) = $CachedDB->selectrow_array("SELECT LAST_INSERT_ID()");
   }

   my $sqlType = $CachedDB->quote("$type");

   my ($transType) = $CachedDB->selectrow_array("SELECT type FROM or_transaction_types
    WHERE action=$sqlType");

   return if ($transType eq undef);

   my $ipAddr = $transactionClass . sprintf("%04x", $::REAL_USER_ID);

   $CachedDB->do("INSERT INTO or_transactions (type, starttime, objectid, ipaddr)
    VALUES ($transType, NOW(), $objectid, '$ipAddr')");

   my ($eid) = $CachedDB->selectrow_array("SELECT LAST_INSERT_ID()");

   return $eid;
}

sub GetEventObjectID {
   my ($eventid) = @_;
   my $CachedDB = GetDBConnection();

   return if ($eventid !~ /^\d+$/);

   my ($oid) = $CachedDB->selectrow_array("SELECT objectid FROM or_transactions WHERE
    eventid=$eventid");

   return $oid;
}

sub EndTransaction {
   my ($eventid) = @_;
   my $CachedDB = GetDBConnection();

   return if ($eventid !~ /^\d+$/);

   my ($status) = $CachedDB->selectrow_array("SELECT state FROM or_transactions WHERE
    eventid=$eventid");

   return if ($status ne "pending");

   my $rv = $CachedDB->do("UPDATE or_transactions SET state='complete',endtime=NOW() 
    WHERE eventid=$eventid"); 

   return $rv;
}

sub NewDepartment {
   my ($dname, $dabbrev, $dcollege) = @_;
   my $courseAbbrevRegex = $OpenRatingsConfig::courseAbbrevRegex;
   my $CachedDB = GetDBConnection();

   return if ($dabbrev !~ /$courseAbbrevRegex/o);
   return if ($dname !~ /^\w[-.\s\w]{1,40}$/);
   return if ($dcollege !~ /^\d+$/);

   my $sqlDeptCollege = $CachedDB->quote("$dcollege");
   return if ($CachedDB->do("SELECT name FROM or_college WHERE 
    collegeid=$sqlDeptCollege") != 1);

   my $eventid = OpenRatingsLib::StartTransaction('department-new');
   my $oid = OpenRatingsLib::GetEventObjectID($eventid);

   my $sqlDeptName = $CachedDB->quote("$dname");

   $dabbrev =~ s/\s//g;
   my $sqlDeptAbbrev = $CachedDB->quote(uc($dabbrev));

   my $rv = $CachedDB->do("INSERT INTO or_dept (name, abbrev, collegeid, campusid, 
    objectid) VALUES ($sqlDeptName, $sqlDeptAbbrev, $sqlDeptCollege, 1, $oid)");

   return if ($rv != 1);
   my ($deptid) = $CachedDB->selectrow_array("SELECT LAST_INSERT_ID()");

   $rv = $CachedDB->do("INSERT INTO or_abbrev_map (abbrev, deptid) VALUES 
    ($sqlDeptAbbrev, $deptid)");
   return if ($rv != 1);

   OpenRatingsLib::EndTransaction($eventid);
   return $deptid;
}

sub NewProfessor {
   my ($pfname, $plname, $pdeptabbrev) = @_;
   my $courseAbbrevRegex = $OpenRatingsConfig::courseAbbrevRegex;
   my $CachedDB = GetDBConnection();

   return if ($plname !~ /^[^\s].{1,40}$/);
   return if ($pfname !~ /^[^\s].{1,30}$/);
   return if ($pdeptabbrev !~ /$courseAbbrevRegex/o);
   
   my $sqlProfDept = $CachedDB->quote("$pdeptabbrev");
   my ($deptid) = $CachedDB->selectrow_array("SELECT deptid FROM or_abbrev_map WHERE 
    abbrev=$sqlProfDept");

   return if ($deptid !~ /^\d+$/);

   my $eventid = OpenRatingsLib::StartTransaction('professor-new');
   my $oid = OpenRatingsLib::GetEventObjectID($eventid);

   my $sqlProfLName = $CachedDB->quote("$plname");
   my $sqlProfFName = $CachedDB->quote("$pfname");

   my $rv = $CachedDB->do("INSERT INTO or_professor (lname, fname, deptid, status, 
    objectid) VALUES ($sqlProfLName, $sqlProfFName, $deptid, 'active', $oid)");

   return if ($rv != 1);
   OpenRatingsLib::EndTransaction($eventid);

   my ($profid) = $CachedDB->selectrow_array("SELECT LAST_INSERT_ID()");
   return $profid;
}

sub NewAbbreviation {
   my ($abbrev, $dept) = @_;
   my $courseAbbrevRegex = $OpenRatingsConfig::courseAbbrevRegex;
   my $CachedDB = GetDBConnection();
 
   my $sqlAbbrevDept = $CachedDB->quote("$dept");
   my ($deptid) = $CachedDB->selectrow_array("SELECT deptid FROM or_dept WHERE 
    abbrev=$sqlAbbrevDept");

   return if ($deptid !~ /^\d+$/);

   ## Remove all whitespace from the abbrev
   $abbrev =~ s/\s//g;
   return if ($abbrev !~ /$courseAbbrevRegex/o);

   my $sqlNewAbbrev = $CachedDB->quote("$abbrev");

   ## Table has unique key on abbrev; will fail if it's a dup
   $rv = $CachedDB->do("INSERT INTO or_abbrev_map (abbrev, deptid) VALUES 
    ($sqlNewAbbrev, $deptid)");

   return if ($rv != 1);
   return $rv;
}

1;
