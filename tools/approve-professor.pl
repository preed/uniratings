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

## Little hack so the functions in openratings-config can use the db; this is
## sloppy but... so what.
use vars qw($db);

require "openratings-config.pl";
require "openratings-lib.pl";

$db = OpenRatingsLib::GetDBConnection();

die "Couldn't connect to OpenRatings database; check your config\n" if (!$db);

print "Gathering completed pending professors...\n";

my $data = $db->selectall_arrayref("SELECT p.profid, p.fname, p.lname, d.name, 
 p.deptid, ct.comment, p.objectid FROM or_professor p, or_dept d, or_comment c, 
 or_comment_text ct WHERE p.status='pending' and p.deptid=d.deptid and 
 c.profid = p.profid and ct.commentid=c.commentid;");

if ($data->[0] eq undef) {
   print "No new professors found!";
}

foreach my $info (@{$data}) {
   ## 12 is newprof; god, I love mysql's subselects...
   my $approveEvent= OpenRatingsLib::StartTransaction(
    'professor-new-approve', $info->[6]);

   print "---\n\n";
   print "Professor Last Name: $info->[2]\n";
   print "Professor First Name: $info->[1]\n";
   print "Department: $info->[3]\n";
   print "Initial comment:\n$info->[5]\n";

   print "--\n";

   my $sqlLName = $db->quote("$info->[2]");
   my $sqlFName = $db->quote("$info->[1]");
   my $sqlDeptId = $db->quote("$info->[4]");

   if (!OpenRatingsLib::Prompt("Is this information correct?")) {
      ## No answer

      if (OpenRatingsLib::Prompt("Is the entry completely invalid (should we throw it out?)")) { 
         my $denyEvent = OpenRatingsLib::StartTransaction(
          'professor-new-deny-invalid', $info->[6]);

         $db->do("UPDATE or_professor SET status='rejected' WHERE 
          profid=$info->[0]");

         OpenRatingsLib::EndTransaction($denyEvent);
         OpenRatingsLib::EndTransaction($approveEvent);
         next;
      }

      my $modInfoEvent = OpenRatingsLib::StartTransaction(
       'professor-new-modinfo', $info->[6]);

      print "Enter corrected professor last name [$info->[2]]: ";
      my $answer = <STDIN>;
      chomp($answer);

      if ($answer !~ /^\s*$/) {
         $sqlLName = $db->quote("$answer");
      }

      print "Enter corrected professor first name [$info->[1]]: ";
      $answer = <STDIN>;
      chomp($answer);

      if ($answer !~ /^\s*$/) {
         $sqlFName = $db->quote("$answer");
      }

      print "Enter corrected professor department ID [$info->[4]]: ";
      $answer = <STDIN>;
      chomp($answer);

      if ($answer !~ /^\d+$/ && $answer !~ /^\s*$/) {
         print "You entered an invalid department ID\n";
         exit;
      }
      elsif ($answer =~ /^\d$/) {
         ## We should also check here to make sure the deptid actually
         ## exists...
         $sqlDeptId = $db->quote("$answer");
      }

      OpenRatingsLib::EndTransaction($modInfoEvent);
   }

   my $rv = $db->do("UPDATE or_professor SET lname=$sqlLName, fname=$sqlFName,
    deptid=$sqlDeptId, status='active' WHERE profid=$info->[0]");

   OpenRatingsLib::EndTransaction($approveEvent);

   print "Professor $info->[0]: status changed to 'active'\n";
}

print "\n---\nGathering dangling new professors...\n";

my $data = $db->selectall_arrayref("SELECT or_professor.fname, 
 or_professor.lname, or_professor.profid, or_professor.objectid FROM 
 or_professor LEFT JOIN or_comment USING (profid) WHERE 
 or_professor.status='pending' GROUP BY profid HAVING 
 count(or_comment.commentid) = 0");
  
my %pendingEvents;

if ($data->[0] ne undef) {

   print "The following professors additions are incomplete; they have no comments:\n";

   foreach my $entry (@{$data}) {
      my $invalidProfEvent = OpenRatingsLib::StartTransaction(
       'professor-new-deny-dangling', $entry->[3]);
      
      $pendingEvents{$entry->[3]} = $invalidProfEvent;
      print "ProfId $entry->[2]: $entry->[1], $entry->[0]\n";
   }


   print "Answer yes to delete these professor additions; they are incomplete and,\nthus,useless. Answer no if you would like to keep them; note, though, that if\nyou keep them, you will have to edit the database manually to get these\nentries to show up because they contain incomplete information.\n\n";
  
   if (OpenRatingsLib::Prompt("Clear dangling professors now?")) {
      foreach my $entry (@{$data}) {
         print "Deleting professor $entry->[1], id: $entry->[2]\n";
         $db->do("UPDATE or_professor SET status='rejected' WHERE 
          profid='$entry->[2]'");
         OpenRatingsLib::EndTransaction($pendingEvents{$entry->[3]});
      }
   }
}
else {
   print "No dangling professors found!\n";
}

$db->disconnect();
