# Clam Antivirus plugin for OJS

This plugin scans submission files using [Clam Antivirus](https://www.clamav.net/), blocking files with a known virus signature.

## Requirements

* OJS 3.3 or later
* PHP7
* Clam Antivirus
  * To run clamscan or clamdscan
    * You must be able to execute `clamscan --version` with output text indicating the ClamAV version
    * Your version of `clamscan -i --no-summary <filename>` must exit with 1 on virus detection
  * To run clamd
    * The clamd socket must be accessible by your webserver process
      * For example, in RHEL7, making Apache a member of "virusgroup" and granting selinux privileges like "daemons_enable_cluster_mode" or "httpd_t antivirus_t:unix_stream_socket connectto".

## Installation

Install this as a "generic" plugin in OJS.  The preferred installation method is via the Plugin Gallery.

To install manually via the filesystem, extract the contents of this archive to a "clamav" directory under "plugins/generic" in your OJS root.  To install via Git submodule, target that same directory path: `git submodule add https://github.com/ulsdevteam/pkp-clamav plugins/generic/clamav`.  Run the installation script to register this plugin, e.g.: `php lib/pkp/tools/installPluginVersion.php plugins/generic/clamav/version.xml`.

## Configuration

You must be the site administrator in order to enable or configure this plugin.  Enable this module and provide a path to the `clamscan` or `clamdscan` executable or to a `clamd` socket in the Settings.  A full path name is required.  The `clamdscan` executable or clamd daemon will provide better performace than loading the executable for each scan, since the daemon keeps the virus defintions in memory.  If you wish to use the clamd daemon, you must have the clam daemon installed and available to Apache.

Most users will probably want to use a unix socket connection; in this case, prepend "unix://" to the path. (For example, "unix:///var/run/clamav/clamd.sock" in RHEL or "unix:///var/run/clamav/clamd.ctl" in Ubunutu). This plugin has not been extensively tested with other socket connections.

If a file is unable to be scanned (for example due to an error or timeout) you can choose whether it will be either allowed through or blocked, through an "Advanced" configuration option.

## Usage

When uploading a submission file through the Author Submission Steps, or via the Editorial interface, or via the Review process, the submission file will be scanned.  If a virus is detected, the upload will be canceled and warning notification will be displayed.

## Author / License

Written by Clinton Graham and Alex Wreschnig and Rick Hoover for the [University of Pittsburgh](http://www.pitt.edu).  Copyright (c) University of Pittsburgh.

Released under a license of GPL v2 or later.
