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
# Copyright (c) 2002 Ryan Joseph
#
# Contributor(s):
#

use strict;
use warnings;
use File::Find;
use Fcntl qw(:mode);

my $dir_search  = $ARGV[0] || usage();
my $count       = 0;
my $mode_mask   = 07777;

sub usage {
    print "Usage: $0 [ROOT_DIR]\n\n";
    print "ROOT_DIR should be the root of your OpenRatings installation.\n";
    print "Most likely (if everything is normal), ROOT_DIR will just be '..'\n";
    exit (-1);
}

# The array of files permissions - the first element of this array (index 0)
# should always be the DEFAULT permissions for all OTHER file types that
# aren't specified here.  Otherwise, each element is a hash with a 'mode' key
# and an 'extns' key of file extensions.
my $perms_files = [
    {   'mode'  => (S_IRUSR | S_IWUSR) },
    {   'mode'  => (S_IRUSR | S_IWUSR | S_IRGRP | S_IROTH),
        'extns' => [qw( php
                        phtml
                        inc
                        gif
                        jpg
                        css
                        htaccess)]},
    {   'mode'  => (S_IRUSR | S_IWUSR | S_IXUSR),
        'extns' => [qw(pl)]}
];

# Same idea as $perms_files (0 index is default mode), but the 'extns' key has
# been changed to 'names' to hold that names of all the directories that
# should recieve the specified permissions.
my $perms_dirs = [
    {   'mode'  => (S_IRUSR | S_IWUSR | S_IXUSR |
                    S_IRGRP | S_IXGRP | S_IXOTH) },
    {   'mode'  => (S_IRUSR | S_IWUSR | S_IXUSR),
        'names' => [qw( tools
                        docs 
                        CVS)]}
];

# Start the search through directory tree.
find (\&wanted, $dir_search);

if ($count) {
    print "\nFinished with $count file/directory mode changes.\n\n";
} else {
    print "\nAll the permissions are correct!\n\n";
}

sub wanted {
    my $mode            = (((stat($_))[2]) & $mode_mask);
    my $file            = $_;
    my $fixed           = 0;

    # I realize that this if-elsif branch is somewhat redundant, but to
    # actually truly smash it into one subroutine would be a big-time
    # annoyance because there are small subilties that make dealing with files
    # different from directories.
    if (-f $file) {
        my $default_mode = $perms_files->[0]->{'mode'};

        for (my $ndx = 1; $ndx <= $#{$perms_files}; $ndx++) {
            foreach my $extn (@{$perms_files->[$ndx]->{'extns'}}) {
                my $set_mode = $perms_files->[$ndx]->{'mode'};
                
                if (($file =~ /^.*?\.$extn$/) && ($mode != $set_mode)) {
                    $fixed = 1;
                    chmod ($set_mode, $file);
                    $count++;
                    
                    printf "Changed file '%s' mode from %04o to %04o\n",
                    "$File::Find::dir/$file", $mode, $set_mode;
                } elsif ($mode == $set_mode) { 
                    $fixed = 1; 
                }
            }
        }

        if (!$fixed && ($mode != $default_mode)) {
            chmod ($default_mode, $_);
            $count++;
            
            printf "Changed file '%s' mode from %04o to %04o\n",
                    "$File::Find::dir/$file", $mode, $default_mode;
        }
    } elsif (-d $file) {
        my $default_mode = $perms_dirs->[0]->{'mode'};
        
        for (my $ndx = 1; $ndx <= $#{$perms_dirs}; $ndx++) {
            foreach my $dirnme(@{$perms_dirs->[$ndx]->{'names'}}) {
                my $set_mode = $perms_dirs->[$ndx]->{'mode'};
                
                if (($file =~ /^$dirnme$/) && ($mode != $set_mode)) {
                    $fixed = 1;
                    chmod ($set_mode, $file);
                    $count++;
                    
                    printf "Changed directory '%s' mode from %04o to %04o\n",
                     $file, $mode, $set_mode;
                } elsif ($mode == $set_mode) { 
                    $fixed = 1; 
                }
            }
        }

        if (!$fixed && ($mode != $default_mode)) {
            chmod ($default_mode, $file);
            $count++;
            
            printf "Changed directory '%s' mode from %04o to %04o\n",
             $file, $mode, $default_mode;
        }
    }
}

