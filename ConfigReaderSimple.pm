# HLstatsZ - Real-time player and clan rankings and statistics
# Originally HLstatsX Community Edition by Nicholas Hastings (2008–20XX)
# Based on ELstatsNEO by Malte Bayer, HLstatsX by Tobias Oetzel, and HLstats by Simon Garner
#
# HLstats > HLstatsX > HLstatsX:CE > HLStatsZ
# HLstatsZ continues a long lineage of open-source server stats tools for Half-Life and Source games.
# This version is released under the GNU General Public License v2 or later.
# 
# For current support and updates:
#    https://snipezilla.com
#    https://github.com/SnipeZilla
#    https://forums.alliedmods.net/forumdisplay.php?f=156


package ConfigReaderSimple;
#
# Simple interface to a configuration file
#
# Originally developed by Ben Oberin.
# Modified for HLstats by Simon Garner.
# Modified for HLstatsX by Tobias Oetzel.
#
# ObLegalStuff:
#    Copyright (c) 2000 Bek Oberin. All rights reserved. This program is
#    free software; you can redistribute it and/or modify it under the
#    same terms as Perl itself.
#

use strict;
use vars qw($VERSION @ISA @EXPORT @EXPORT_OK);

require Exporter;

@ISA = qw(Exporter);
@EXPORT = qw();
@EXPORT_OK = qw();

$VERSION = "1.0";

my $DEBUG = 0;

=head1 NAME

ConfigReader::Simple - Simple configuration file parser

=head1 SYNOPSIS

   use ConfigReader::Simple;

   $config = ConfigReader::Simple->new("configrc", [qw(Foo Bar Baz Quux)]);

   $config->parse();
   
   $config->get("Foo");
   

=head1 DESCRIPTION

   C<ConfigReader::Simple> reads and parses simple configuration files. It's
   designed to be smaller and simpler than the C<ConfigReader> module
   and is more suited to simple configuration files.

=cut

###################################################################
# Functions under here are member functions                       #
###################################################################

=head1 CONSTRUCTOR

=item new ( FILENAME, DIRECTIVES )

This is the constructor for a new ConfigReader::Simple object.

C<FILENAME> tells the instance where to look for the configuration
file.

C<DIRECTIVES> is an optional argument and is a reference to an array.  
Each member of the array should contain one valid directive. A directive
is the name of a key that must occur in the configuration file. If it
is not found, the module will die. The directive list may contain all
the keys in the configuration file, a sub set of keys or no keys at all.

=cut

sub new {
   my $prototype = shift;
   my $filename = shift;
   my $keyref = shift;

   my $class = ref($prototype) || $prototype;
   my $self  = {};

   $self->{"filename"} = $filename;
   $self->{"validkeys"} = $keyref;

   bless($self, $class);
   return $self;
}


#
# destructor
#
sub DESTROY {
   my $self = shift;

   return 1;
}

=pod
=item parse ()

This does the actual work.  No parameters needed.

=cut

sub parse {
   my $self = shift;

   open(CONFIG, $self->{"filename"}) || 
      die "Config: Can't open config file " . $self->{"filename"} . ": $!";

   my @array_buffer;
   my $ext_option = 0;
   my $parsed_line = 0;
 
   while (<CONFIG>) {
      chomp;
      next if /^\s*$/;  # blank
      next if /^\s*#/;  # comment
	  next if /^\s*.*\[[0-9]+\]\s*=\s*\(/;  # old style server config start
	  next if /^\s*.*\s*=>\s*\.*".*\",/; # old style server config option

      $parsed_line   = 0;
      my $input_text = $_;
      
      if (($ext_option == 0) && ($parsed_line == 0)) {
        my ($key, $value) = &parse_line($input_text);
        warn "Key:  '$key'   Value:  '$value'\n" if $DEBUG;
        $self->{"config_data"}{$key} = $value;
      }
   }
   close(CONFIG);

   return 1;

}

=pod
=item get ( DIRECTIVE )

Returns the parsed value for that directive.

=cut

sub get {
   my $self = shift;
   my $key = shift;

   unless (ref $self->{"config_data"}{$key}) {
     return $self->{"config_data"}{$key};
   } else {
     return %{$self->{"config_data"}{$key}};
   }  
}

# Internal methods

sub parse_line {
   my $text = shift;

   my ($key, $value);
   
   if ($text =~ /^\s*(\w+)\s+(['"]?)(.*?)\2\s*$/) {
      $key   = $1;
      $value = $3;
   } else {
      die "Config: Can't parse line: $text\n";
   }

   return ($key, $value);
}


=pod

=head1 LIMITATIONS/BUGS

Directives are case-sensitive.

If a directive is repeated, the first instance will silently be
ignored.

Always die()s on errors instead of reporting them.

C<get()> doesn't warn if used before C<parse()>.

C<get()> doesn't warn if you try to acces the value of an
unknown directive not know (ie: one that wasn't passed via C<new()>).

All these will be addressed in future releases.

=head1 CREDITS

Kim Ryan <kimaryan@ozemail.com.au> adapted the module to make declaring
keys optional.  Thanks Kim.

=head1 AUTHORS

Bek Oberin <gossamer@tertius.net.au>

=head1 COPYRIGHT

Copyright (c) 2000 Bek Oberin.  All rights reserved.

This program is free software; you can redistribute it and/or modify
it under the same terms as Perl itself.

=cut

#
# End code.
#
1;
