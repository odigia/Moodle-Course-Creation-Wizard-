h1. Overview

The Course Creation Wizard enables users to create courses through an easy workflow. 
The Wizard consists of a series of pages that breaks up the numerous aspects of course creation 
into a sequence of manageable pieces. Instructors can use the wizard to complete the initial 
setup of a course in one easy-to–follow process.

h1. Installation

1. Create \coursewizard folder and unzip content of coursewizard.zip file in this folder

2. Open \theme folder and add the following strings to the <head> section  of the layout files in the current theme:

<pre><code>
<link rel="stylesheet" href="/coursewizard/scripts/nyroModal.css" type="text/css" media="screen,projection" />
<link rel="stylesheet" type="text/css" href="/coursewizard/css/styles.css" media="all">
<!--[if lt IE 8]>
<link rel="stylesheet" type="text/css" href="/coursewizard/css/ie.css" media="all">
<![endif]-->
<!--[if IE 8]>
<link rel="stylesheet" type="text/css" href="/coursewizard/css/ie8.css" media="all">
<![endif]-->
<script type="text/javascript" language="javascript" src="/coursewizard/scripts/jquery-1.4.2.min.js"></script>
<script type="text/javascript" language="javascript" src="/coursewizard/scripts/jquery.nyroModal.js"></script>
</pre></code>

Check that these files exists in corresponding folders.
    
3. Copy coursewizard.patch.diff to the root folder of Moodle and execute the following command:

<pre><code>
patch -p 1 < coursewizard.patch.diff
</pre></code>

4. Log to Moodle as admin.

Click "Turn editing on" button.
Select "HTML" in the "Blocks" select box.
Click "Configuration" icon at the top of the new added block.
Enter any text in Block Title field (example: Course Creation Wizard)
Click "Toggle HTML source" icon in Content toolbar.

Enter the following text it Content field:
 
<pre><code>
Click <a class="nyroModal" href="coursewizard/step2.php">here</a> to create a new course<br />
</pre></code>
  	
Click "Save changes" button