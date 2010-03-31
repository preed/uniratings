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
# Portions Copyright (c) 1999-2002 Forrest Lanning
#                        2001-2002 J. Paul Reed
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

my $commentid;

if ($ARGV[0] !~ /^\d+$/) {
   print "I need a valid comment ID: $ARGV[0]\n";
   exit;
}
else {
   $commentid = $ARGV[0];
}

$db = OpenRatingsLib::GetDBConnection();
die "Couldn't connect to OpenRatings database; check your config\n" if (!$db);

my $data = $db->selectall_arrayref("SELECT c.commentid,c.objectid,ct.comment 
 FROM or_comment c, or_comment_text ct WHERE c.commentid=$commentid AND 
 ct.commentid=c.commentid");

foreach my $id (@{$data}) {
   my $event = OpenRatingsLib::StartTransaction('comment-moderate-content',
    $id->[1]);

   if ($event eq undef) {
      print "Transaction FAILED to start\n";
      next;
   }

   my $sqlComment = $db->quote("$id->[2]");

   $db->do("INSERT INTO or_moderated_comment_text (commentid, comment, ctime) 
    VALUES ($id->[0], $sqlComment, NOW())");

   open(TMP, ">./modcomment.$commentid.$$") or die "open(modcomment): $!\n";
   print TMP "$id->[2]";
   close(TMP);

   system("vi ./modcomment.$commentid.$$");

   open(TMP, "<./modcomment.$commentid.$$") or die "open(modcomment): $!\n";
   my @newComment = <TMP>;
   close(TMP);


   my $sqlNewComment = $db->quote(join("\n", @newComment));

   $db->do("UPDATE or_comment_text SET comment=$sqlNewComment WHERE 
    commentid=$commentid");
   $db->do("UPDATE or_comment SET status='moderated' WHERE 
    commentid=$commentid");
   
   OpenRatingsLib::EndTransaction($event);

   unlink("./modcomment.$commentid.$$");
}

$db->disconnect();
