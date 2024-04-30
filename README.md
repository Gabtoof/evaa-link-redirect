# evaa-link-redirect
Work in progress.
Currently:
* Use XYZ PHP Code Plugin
    * create snippit - content: create-link.php
* Reference the shortlink in a web page
* Create a template file with the contents of evaa-redirect-template.php
* Create new page (/link) referencing the template

Notes:
Basic rate checking exists to curtail potential abuse. Tweak values (in seconds) as neccesary.

Currently most code is duplicated in the two files, including all the 'validation rules' and rate checking intervals. If you change values in one file, it is suggested you make a corresponding update in the other file.
