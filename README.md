# Clam Antivirus plugin for OJS

This plugin scans submission files using [Clam Antivirus](https://www.clamav.net/), blocking files with a known virus signature.

## Requirements

* OJS 3.1.x
* PHP 5.3 or later
* Clam Antivirus
  * To run clamscan
    * You must be able to execute `clamscan --version` with output text indicating the ClamAV version
    * Your version of `clamscan -i --no-summary <filename>` must exit with 1 on virus detection
  * To run clamd
    * The clamd socket must be accessible by Apache

## Installation

Install this as a "generic" plugin in OJS.  To install manually via the filesystem, extract the contents of this archive to a "clamav" directory under "plugins/generic" in your OJS root.  To install via Git submodule, target that same directory path: `git submodule add https://github.com/ulsdevteam/pkp-clamav plugins/generic/clamav` and `git submodule update --init --recursive plugins/generic/clamav`.  Run the upgrade script to register this plugin, e.g.: `php tools/upgrade.php upgrade`.

## Configuration

You must be the site administrator in order to enable or configure this plugin.  Enable this module and provide a path to the `clamscan` executable in the Settings.  A full path name is required. If, instead, you wish to use the clamd daemon, you must have the clam daemon installed and available to Apache. As with clamscan, a full path to the clamd.sock socket is required.

## Usage

When uploading a submission file through the Author Submission Steps, or via the Editorial interface, or via the Review process, the submission file will be scanned.  If a virus is detected, the upload will be canceled and warning notification will be displayed.

## Author / License

Written by Clinton Graham for the [University of Pittsburgh](http://www.pitt.edu).  Copyright (c) University of Pittsburgh.

Released under a license of GPL v2 or later.
